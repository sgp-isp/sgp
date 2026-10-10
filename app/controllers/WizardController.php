<?php
/**
 * WizardController - Wizard de Seguridad / Onboarding para Nuevos Usuarios
 *
 * FLUJO DE SEGURIDAD ("LA JAULA") — 4 PASOS:
 *   Paso 1: Cambio de Contraseña
 *   Paso 2: Preguntas de Seguridad
 *   Paso 3: Datos Personales (nombres, apellidos, teléfono, fecha_nac, género)
 *   Paso 4: Perfil Profesional (PIN + Institución para pasantes / Cargo + Dpto para admin/tutor)
 *
 * ESQUEMA HÍBRIDO DEFINITIVO:
 *   usuarios         → id, cedula (UNIQUE), correo, password, pin_asistencia, rol_id, estado
 *   datos_personales → usuario_id, nombres, apellidos, telefono, genero, fecha_nacimiento
 *   datos_pasante    → lógica académica (pasantes)
 *
 * @version 5.0 - Wizard de 4 Pasos con Datos Personales obligatorios
 */
class WizardController extends Controller
{
    private $userModel;
    private $db;

    public function __construct()
    {
        Session::start();

        if (!Session::get('user_id')) {
            header('Location: ' . URLROOT . '/auth/login');
            exit;
        }

        $this->userModel = $this->model('User');
        $config = require '../app/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
    }

    /**
     * Vista: Mostrar Wizard de 4 Pasos
     */
    public function index()
    {
        $userId = Session::get('user_id');

        // Datos básicos del usuario + nombres/apellidos ya existentes (para la tarjeta de identidad)
        $this->db->query("
            SELECT u.id, u.cedula, u.correo, u.rol_id, u.requiere_cambio_clave,
                   u.pin_asistencia, u.departamento_id,
                   r.nombre AS rol_nombre,
                   dp.nombres, dp.apellidos, dp.telefono, dp.genero, dp.fecha_nacimiento, dp.cargo,
                   dpa.institucion_procedencia
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            WHERE u.id = :uid
        ");
        $this->db->bind(':uid', $userId);
        $user = $this->db->single();

        // Detectar si es un restablecimiento de contraseña de usuario con perfil ya completo.
        // Criterio definitivo: el usuario YA tiene preguntas de seguridad guardadas.
        //   - Usuario NUEVO (creado por admin, nunca usó el wizard): 0 preguntas → wizard completo (4 pasos).
        //   - Usuario EXISTENTE (password reseteado): tiene 3 preguntas → solo pasos 1 y 2.
        // Esta lógica es robusta independientemente de si cargo/departamento/teléfono están rellenos.
        $rolId = (int)($user->rol_id ?? 0);
        $soloPasswordReset = false;
        if ($user && $user->requiere_cambio_clave == 1) {
            $this->db->query("SELECT COUNT(id) AS cnt FROM usuarios_respuestas WHERE usuario_id = :uid");
            $this->db->bind(':uid', $userId);
            $respRow      = $this->db->single();
            $tienePreguntas = ($respRow && (int)$respRow->cnt >= 3);

            if ($tienePreguntas) {
                if ($rolId === 3) {
                    // Pasante: además debe tener PIN; si no lo tiene necesita el wizard completo
                    $soloPasswordReset = !empty($user->pin_asistencia);
                } else {
                    // Admin / Tutor / SuperAdmin
                    $soloPasswordReset = true;
                }
            }
        }

        // Preguntas de seguridad
        $questions = $this->userModel->getSecurityQuestions();

        // Paso 4: Catálogos según rol
        $instituciones = [];
        $departamentos = [];

        try {
            $this->db->query("SELECT id, nombre, direccion FROM instituciones ORDER BY nombre ASC");
            $instituciones = $this->db->resultSet();
        } catch (Exception $e) {
            error_log('[SGP-WIZARD] [WARN] Error cargando instituciones: ' . $e->getMessage());
        }

        try {
            $this->db->query("SELECT id, nombre, descripcion FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
            $departamentos = $this->db->resultSet();
        } catch (Exception $e) {
            error_log('[SGP-WIZARD] [WARN] Error cargando departamentos: ' . $e->getMessage());
        }

        $data = [
            'user'               => $user,
            'questions'          => $questions,
            'instituciones'      => $instituciones,
            'departamentos'      => $departamentos,
            'title'              => 'Configuración de Seguridad',
            'saltar_seguridad'   => (Session::get('requiere_cambio_clave') == 0),
            'solo_password_reset' => $soloPasswordReset,
        ];

        $this->view('wizard/index', $data, false);
    }

    /**
     * Procesar Wizard — Guardar Configuración
     *
     * ACID: Todo se guarda dentro de una única transacción.
     */
    public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/wizard/index');
            return;
        }

        // [FIX-CS-1] Verificación CSRF — este flujo forzoso ("La Jaula") cambia la
        // contraseña, PIN y datos personales; usa redirect+flash (no JSON) porque
        // wizard/index.php es un formulario clásico, no AJAX.
        $csrfToken = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Token de seguridad inválido o expirado. Vuelve a intentarlo.');
            $this->redirect('/wizard/index');
            return;
        }

        $userId = Session::get('user_id');
        $user   = $this->userModel->findById($userId);
        $rolId  = (int)($user['rol_id'] ?? Session::get('role_id') ?? 0);

        $saltarSeguridad  = ($user['requiere_cambio_clave'] == 0);
        // Restablecimiento de contraseña de usuario que ya tiene perfil completo → solo pasos 1 y 2
        $soloPasswordReset = (!$saltarSeguridad && (int)($_POST['solo_password_reset'] ?? 0) === 1);

        // ────────────────────────────────────────────────────
        // PASO 1: VALIDAR CONTRASEÑA (Solo si requiere_cambio_clave = 1)
        // ────────────────────────────────────────────────────
        $newPassword = null;
        if (!$saltarSeguridad) {
            $newPassword     = trim($_POST['new_password']     ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if ($newPassword !== $confirmPassword) {
                Session::setFlash('error', 'Las contraseñas nuevas no coinciden.');
                $this->redirect('/wizard/index');
                return;
            }

            if (strlen($newPassword) < 8) {
                Session::setFlash('error', 'La contraseña debe tener al menos 8 caracteres.');
                $this->redirect('/wizard/index');
                return;
            }

            // 🔒 Bloquear contraseña temporal como contraseña permanente
            $cedula = $user['cedula'] ?? '';
            if (!empty($cedula) && $newPassword === 'Sgp.' . $cedula) {
                Session::setFlash('error', 'No puedes usar tu contraseña temporal como contraseña permanente. Elige una contraseña personalizada.');
                $this->redirect('/wizard/index');
                return;
            }
        }

        // ────────────────────────────────────────────────────
        // PASO 2: VALIDAR PREGUNTAS DE SEGURIDAD (Solo si requiere_cambio_clave = 1)
        // ────────────────────────────────────────────────────
        $securityQuestions = [];
        if (!$saltarSeguridad) {
            for ($i = 1; $i <= 3; $i++) {
                $questionId = $_POST["question_$i"] ?? null;
                $answer     = trim($_POST["answer_$i"] ?? '');

                if (!$questionId || !$answer) {
                    Session::setFlash('error', 'Debes completar todas las preguntas de seguridad.');
                    $this->redirect('/wizard/index');
                    return;
                }

                $securityQuestions[] = ['question_id' => $questionId, 'answer' => $answer];
            }

            $questionIds = array_column($securityQuestions, 'question_id');
            if (count($questionIds) !== count(array_unique($questionIds))) {
                Session::setFlash('error', 'No puedes seleccionar la misma pregunta más de una vez.');
                $this->redirect('/wizard/index');
                return;
            }
        }

        // ────────────────────────────────────────────────────
        // PASO 3 y 4: SOLO si no es restablecimiento de contraseña de usuario existente
        // ────────────────────────────────────────────────────
        $telefono        = null;
        $fechaNacimiento = null;
        $genero          = null;
        $pinAsistencia   = null;
        $institucionId   = null;
        $cargo           = null;
        $departamentoId  = null;
        $nombres_reales  = '';

        // Siempre recuperar el nombre real para actualizar la sesión
        $this->db->query("SELECT nombres, apellidos FROM datos_personales WHERE usuario_id = :uid");
        $this->db->bind(':uid', $userId);
        $dpRow = $this->db->single();
        $nombres_reales = $dpRow ? trim(($dpRow->nombres ?? '') . ' ' . ($dpRow->apellidos ?? '')) : '';

        if (!$soloPasswordReset) {
            // PASO 3: DATOS PERSONALES
            $telefono        = trim($_POST['telefono']         ?? '');
            $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
            $genero          = trim($_POST['genero']           ?? '');

            if (!$telefono) {
                Session::setFlash('error', 'El campo Teléfono es obligatorio.');
                $this->redirect('/wizard/index');
                return;
            }
            // [FIX-V-8] Antes solo se verificaba que no viniera vacío; el frontend
            // formatea a dígitos+guion pero nada impedía enviar cualquier string vía POST directo.
            if (!preg_match('/^[0-9\-]{7,20}$/', $telefono)) {
                Session::setFlash('error', 'El Teléfono debe contener solo números (y guiones), entre 7 y 20 caracteres.');
                $this->redirect('/wizard/index');
                return;
            }
            if (!$fechaNacimiento) {
                Session::setFlash('error', 'La Fecha de Nacimiento es obligatoria.');
                $this->redirect('/wizard/index');
                return;
            }
            // [FIX-V-8] Validar que sea una fecha real (no solo no-vacía) y en un rango razonable,
            // igual al min/max que ya usa perfil/ver.php (hasta 100 años atrás, no en el futuro).
            $fnacObj = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
            $hoy     = new DateTime('today');
            $minFnac = (new DateTime('today'))->modify('-100 years');
            if (!$fnacObj || $fnacObj->format('Y-m-d') !== $fechaNacimiento || $fnacObj > $hoy || $fnacObj < $minFnac) {
                Session::setFlash('error', 'La Fecha de Nacimiento no es válida.');
                $this->redirect('/wizard/index');
                return;
            }
            // [FIX-V-8] Whitelist del select real (wizard/index.php: M, F, Otro).
            if (!in_array($genero, ['M', 'F', 'Otro'], true)) {
                Session::setFlash('error', 'Selecciona tu género.');
                $this->redirect('/wizard/index');
                return;
            }

            // PASO 4: PERFIL PROFESIONAL
            if ($rolId === 3) {
                $pinAsistencia    = trim($_POST['pin_asistencia']     ?? '');
                $pinTieneExistente= (int)($_POST['pin_tiene_existente'] ?? 0) === 1;
                $institucionId    = (int)($_POST['institucion_id']    ?? 0);

                // PIN obligatorio para usuarios nuevos; opcional si ya tiene uno configurado
                if (!$pinTieneExistente && !preg_match('/^[0-9]{4}$/', $pinAsistencia)) {
                    Session::setFlash('error', 'El PIN debe tener exactamente 4 dígitos numéricos.');
                    $this->redirect('/wizard/index');
                    return;
                }
                // Si entró algo pero no son 4 dígitos exactos → error igualmente
                if ($pinAsistencia && !preg_match('/^[0-9]{4}$/', $pinAsistencia)) {
                    Session::setFlash('error', 'El PIN debe tener exactamente 4 dígitos numéricos.');
                    $this->redirect('/wizard/index');
                    return;
                }
                if ($institucionId <= 0) {
                    Session::setFlash('error', 'Debes seleccionar tu institución de procedencia.');
                    $this->redirect('/wizard/index');
                    return;
                }
            } else {
                $cargo          = trim($_POST['cargo'] ?? '');
                $departamentoId = (int)($_POST['departamento_id'] ?? 0);

                if (!$cargo) {
                    Session::setFlash('error', 'El campo Cargo / Puesto es obligatorio.');
                    $this->redirect('/wizard/index');
                    return;
                }
                // Si no viene departamento del POST, usar el que ya tenía asignado en usuarios
                if ($departamentoId <= 0) {
                    $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
                    $this->db->bind(':uid', $userId);
                    $row = $this->db->single();
                    $departamentoId = (int)($row->departamento_id ?? 0);
                }
                if ($departamentoId <= 0) {
                    Session::setFlash('error', 'Debes seleccionar un departamento.');
                    $this->redirect('/wizard/index');
                    return;
                }
            }
        }

        // ────────────────────────────────────────────────────
        // TRANSACCIÓN ATÓMICA (ACID)
        // Esquema híbrido definitivo:
        //   usuarios         → credenciales + cedula (identificador único)
        //   datos_personales → nombres, apellidos, teléfono, fecha_nac, género
        //   datos_pasante    → lógica académica
        // ────────────────────────────────────────────────────
        try {
            $this->db->beginTransaction();

            // SÓLO SI NO ES AUTO-REGISTRADO (Paso 1 y 2)
            if (!$saltarSeguridad) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                // Actualizar contraseña
                $this->db->query("UPDATE usuarios SET password = :password WHERE id = :user_id");
                $this->db->bind(':password', $hashedPassword);
                $this->db->bind(':user_id',  $userId);
                $this->db->execute();

                // Guardar preguntas de seguridad
                $this->db->query("DELETE FROM usuarios_respuestas WHERE usuario_id = :user_id");
                $this->db->bind(':user_id', $userId);
                $this->db->execute();

                foreach ($securityQuestions as $sq) {
                    $normalizedAnswer = strtolower(trim($sq['answer']));
                    $hashedAnswer     = password_hash($normalizedAnswer, PASSWORD_BCRYPT);

                    $this->db->query("
                        INSERT INTO usuarios_respuestas (usuario_id, pregunta_id, respuesta_hash)
                        VALUES (:user_id, :question_id, :answer_hash)
                    ");
                    $this->db->bind(':user_id',     $userId);
                    $this->db->bind(':question_id', $sq['question_id']);
                    $this->db->bind(':answer_hash', $hashedAnswer);
                    $this->db->execute();
                }
            }

            if ($soloPasswordReset) {
                // ── RESTABLECIMIENTO DE CONTRASEÑA: solo limpiar el flag ──
                $this->db->query("UPDATE usuarios SET requiere_cambio_clave = 0 WHERE id = :user_id");
                $this->db->bind(':user_id', $userId);
                if (!$this->db->execute()) {
                    throw new Exception('Error al actualizar el estado de la cuenta.');
                }

            } elseif ($rolId === 3) {
                // ── PASANTE ──────────────────────────────────────────────
                // A. usuarios: actualizar PIN solo si se ingresó uno nuevo
                if ($pinAsistencia) {
                    $pinHasheado = password_hash($pinAsistencia, PASSWORD_BCRYPT);
                    $this->db->query("
                        UPDATE usuarios
                        SET pin_asistencia        = :pin,
                            requiere_cambio_clave = 0
                        WHERE id = :user_id
                    ");
                    $this->db->bind(':pin',     $pinHasheado);
                    $this->db->bind(':user_id', $userId);
                } else {
                    // PIN existente conservado — solo limpiar el flag
                    $this->db->query("UPDATE usuarios SET requiere_cambio_clave = 0 WHERE id = :user_id");
                    $this->db->bind(':user_id', $userId);
                }

                if (!$this->db->execute()) {
                    throw new Exception('Error al actualizar registro del pasante.');
                }

                // B. datos_personales: biografía (UPSERT)
                $this->db->query("
                    INSERT INTO datos_personales
                        (usuario_id, telefono, fecha_nacimiento, genero)
                    VALUES
                        (:uid, :telefono, :fnac, :genero)
                    ON DUPLICATE KEY UPDATE
                        telefono        = VALUES(telefono),
                        fecha_nacimiento = VALUES(fecha_nacimiento),
                        genero          = VALUES(genero)
                ");
                $this->db->bind(':uid',       $userId);
                $this->db->bind(':telefono',  $telefono);
                $this->db->bind(':fnac',      $fechaNacimiento);
                $this->db->bind(':genero',    $genero);

                if (!$this->db->execute()) {
                    throw new Exception('Error al guardar datos personales del pasante.');
                }

                // C. datos_pasante: lógica académica (UPSERT)
                // Calcular horas_meta desde el período activo
                $this->db->query("
                    SELECT id, fecha_inicio, fecha_fin
                    FROM periodos_academicos
                    WHERE estado = 'Activo'
                    ORDER BY fecha_inicio DESC
                    LIMIT 1
                ");
                $periodoActivo = $this->db->single();
                if ($periodoActivo && $periodoActivo->fecha_inicio && $periodoActivo->fecha_fin) {
                    // Contar días L-V reales menos feriados no laborables
                    $cur = new DateTime($periodoActivo->fecha_inicio);
                    $end = new DateTime($periodoActivo->fecha_fin);
                    $diasHabiles = 0;
                    while ($cur <= $end) {
                        if ((int)$cur->format('N') < 6) $diasHabiles++;
                        $cur->modify('+1 day');
                    }
                    $this->db->query("
                        SELECT COUNT(*) AS total FROM dias_feriados
                        WHERE fecha BETWEEN :ini AND :fin
                          AND es_laborable = 0 AND DAYOFWEEK(fecha) BETWEEN 2 AND 6
                    ");
                    $this->db->bind(':ini', $periodoActivo->fecha_inicio);
                    $this->db->bind(':fin', $periodoActivo->fecha_fin);
                    $diasHabiles -= (int)(($this->db->single()->total) ?? 0);
                    $horasMeta = max(160, $diasHabiles * 8);
                    $periodoId = (int) $periodoActivo->id;
                } else {
                    $horasMeta = 1440;
                    $periodoId = null;
                }

                $this->db->query("
                    INSERT INTO datos_pasante (usuario_id, tipo_pasantia, estado_pasantia, horas_meta, horas_acumuladas, periodo_id, institucion_procedencia)
                    VALUES (:uid, 'Regular', 'Sin Asignar', :horas_meta, 0, :periodo_id, :inst_txt)
                    ON DUPLICATE KEY UPDATE
                        tipo_pasantia           = 'Regular',
                        institucion_procedencia = VALUES(institucion_procedencia)
                ");
                $this->db->bind(':uid',       $userId);
                $this->db->bind(':horas_meta', $horasMeta);
                $this->db->bind(':periodo_id', $periodoId);
                $this->db->bind(':inst_txt',  $institucionId);

                if (!$this->db->execute()) {
                    throw new Exception('Error al crear registro de pasantía.');
                }

            } else {
                // ── ADMIN / TUTOR ─────────────────────────────────────────
                // A. usuarios: credenciales + departamento
                $this->db->query("
                    UPDATE usuarios
                    SET password              = :password,
                        departamento_id       = :dept_id,
                        requiere_cambio_clave = 0
                    WHERE id = :user_id
                ");
                $this->db->bind(':password', $hashedPassword);
                $this->db->bind(':dept_id',  $departamentoId);
                $this->db->bind(':user_id',  $userId);

                if (!$this->db->execute()) {
                    throw new Exception('Error al actualizar credenciales.');
                }

                // B. datos_personales: cargo, etc. (UPSERT)
                $this->db->query("
                    INSERT INTO datos_personales
                        (usuario_id, telefono, fecha_nacimiento, genero, cargo)
                    VALUES
                        (:uid, :telefono, :fnac, :genero, :cargo)
                    ON DUPLICATE KEY UPDATE
                        telefono         = VALUES(telefono),
                        fecha_nacimiento = VALUES(fecha_nacimiento),
                        genero           = VALUES(genero),
                        cargo            = VALUES(cargo)
                ");
                $this->db->bind(':uid',       $userId);
                $this->db->bind(':telefono',  $telefono);
                $this->db->bind(':fnac',      $fechaNacimiento);
                $this->db->bind(':genero',    $genero);
                $this->db->bind(':cargo',     $cargo);

                if (!$this->db->execute()) {
                    throw new Exception('Error al guardar datos personales.');
                }
            }

            // ── Preguntas de seguridad ────────────────────────────────
            $this->db->query("DELETE FROM usuarios_respuestas WHERE usuario_id = :user_id");
            $this->db->bind(':user_id', $userId);
            $this->db->execute();

            foreach ($securityQuestions as $sq) {
                $normalizedAnswer = strtolower(trim($sq['answer']));
                $hashedAnswer     = password_hash($normalizedAnswer, PASSWORD_BCRYPT);

                $this->db->query("
                    INSERT INTO usuarios_respuestas (usuario_id, pregunta_id, respuesta_hash)
                    VALUES (:user_id, :question_id, :answer_hash)
                ");
                $this->db->bind(':user_id',     $userId);
                $this->db->bind(':question_id', $sq['question_id']);
                $this->db->bind(':answer_hash', $hashedAnswer);

                if (!$this->db->execute()) {
                    throw new Exception('Error al guardar las preguntas de seguridad.');
                }
            }

            $this->db->commit();

            Session::set('requiere_cambio_clave', 0);
            Session::set('perfil_completado', true);
            Session::set('cargo_verificado', true); // evitar re-check de cargo en esta sesión

            // Actualizar sesión con el nombre real del usuario (Tarea 1)
            if ($nombres_reales) {
                Session::set('user_name', $nombres_reales);
            }

            error_log("[SGP-WIZARD] [OK] Usuario ID $userId (rol $rolId) completó el onboarding de 4 pasos.");

            Session::setFlash('success', '¡Configuración completada! Bienvenido al sistema.');
            $this->redirect('/dashboard');

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('[SGP-WIZARD] [ERROR] ' . $e->getMessage());
            // [FIX-C2] Mensaje genérico en flash — detalle solo en log
            Session::setFlash('error', 'Error al procesar la configuración. Intente de nuevo.');
            $this->redirect('/wizard/index');
        }
    }

    /**
     * Endpoint AJAX: Verifica si el teléfono ya está en uso por OTRO usuario.
     * Requerido para la validación asíncrona del Paso 3 del Wizard.
     */
    public function checkPhone()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }

        header('Content-Type: application/json');

        $userId = Session::get('user_id');
        if (!$userId) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $telefono = trim($_POST['telefono'] ?? '');
        if (empty($telefono)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        try {
            // Verificar exclusión de sí mismo: id != :uid
            $this->db->query("SELECT COUNT(id) as total FROM datos_personales WHERE telefono = :telefono AND usuario_id != :uid");
            $this->db->bind(':telefono', $telefono);
            $this->db->bind(':uid', $userId);
            $result = $this->db->single();
            
            $exists = ($result && $result->total > 0);
            echo json_encode(['exists' => $exists]);
            
        } catch (Exception $e) {
            error_log('[SGP-WIZARD] Error en checkPhone AJAX: ' . $e->getMessage());
            echo json_encode(['exists' => false, 'error' => 'Error BD']);
        }
        exit;
    }
}
