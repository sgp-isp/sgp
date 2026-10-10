<?php
class AuthController extends Controller
{
    public function index() {
        $this->login();
    }

    /**
     * [SGP-LOCK-PERM] Centraliza el mensaje de bloqueo tras 5 intentos fallidos.
     *
     * Reemplaza el bloqueo temporal de 15 minutos por uno persistente: el par
     * (IP + correo) queda bloqueado en `intentos_acceso` sin fecha de expiración
     * hasta que el usuario complete el flujo de recuperación de contraseña
     * (ver recovery() paso 3, que borra el candado para ese correo en todas las IP).
     *
     * Nota de seguridad: el candado sigue aplicándose por IP+correo (no se toca
     * usuarios.estado), igual que antes de este cambio — por eso NO reintroduce
     * el vector de sabotaje que motivó el paso a bloqueo temporal ([FIX-AS-1]):
     * alguien que solo conozca el correo de la víctima y falle 5 veces desde OTRA
     * IP no bloquea el acceso real de la víctima desde su propia IP de oficina.
     *
     * @param bool $html Si true, incluye el botón/enlace en HTML (para el banner
     *                    AJAX que sí interpreta innerHTML). Si false, devuelve
     *                    texto plano (para el toast de fallback, que usa textContent).
     */
    private static function mensajeBloqueoPermanente(bool $html = true): string
    {
        $texto = 'Tu usuario ha sido bloqueado por medida de seguridad tras 5 intentos fallidos. '
               . 'Dirígete al módulo de recuperación de contraseña para restablecer tu acceso.';

        if (!$html) {
            return $texto;
        }

        return $texto . '<br><br><a href="' . URLROOT . '/auth/recovery" '
             . 'style="display:inline-block;padding:8px 18px;background:#ffffff;'
             . 'color:#b91c1c;font-weight:700;border-radius:6px;text-decoration:none;">'
             . '➜ Recuperar contraseña ahora</a>';
    }

    /**
     * Iniciar Sesión de Usuario
     * 
     * LÓGICA DE CONTROL (Dualidad de Acceso):
     * 1. Autenticación: Verifica credenciales contra hashed password en BD.
     * 2. Auditoría: Valida que la cuenta esté 'activa'.
     * 3. "La Jaula" (Fuerza Wizard):
     *    - Si requiere_cambio_clave = 1: Redirige al Wizard (Paso 1). Típico en usuarios creados por Admin.
     *    - Si perfil_completado = false: Redirige al Wizard (Paso 3). Típico en auto-registros.
     * 
     * @return void
     */
    public function login(): void
    {
        Session::start();
        if (Session::get('user_id')) {
            $this->redirectByRole(Session::get('role_id'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('auth/login', [], false);
            return;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        try {

        // SGP-FIX-v2 [T8] Rate limiting simplificado por email
        // Ya no penalizamos con 15 minutos de inactividad por IP.
        // Si hay intentos, solo sirven para hacer trigger a la validación de estado.
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $email = trim($_POST['email'] ?? '');
        $db    = Database::getInstance();

        $email = Validator::email('email');
        $password = Validator::post('password');
        $captcha = Validator::post('captcha');

        // Validar CAPTCHA primero
        require_once APPROOT . '/helpers/CaptchaHelper.php';
        if (!CaptchaHelper::validate($captcha)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                // [FIX-UX] 'field' le indica al frontend dónde mostrar el error: inline
                // junto al campo de captcha en vez del toast genérico usado para el resto.
                echo json_encode(['success' => false, 'message' => 'Código CAPTCHA incorrecto', 'field' => 'captcha']);
                exit;
            }
            $this->view('auth/login', ['error' => 'Código CAPTCHA incorrecto'], false);
            return;
        }

        // [SGP-LOCK-PERM] Bloqueo PERSISTENTE por IP+correo — se verifica ANTES de
        // tocar la contraseña. Ya no expira solo: se libera al completar recovery().
        $db->query('SELECT bloqueado_hasta FROM intentos_acceso WHERE direccion_ip = :ip AND correo = :email LIMIT 1');
        $db->bind(':ip', $ip);
        $db->bind(':email', $email);
        $filaBloqueo = $db->single();
        if ($filaBloqueo && !empty($filaBloqueo->bloqueado_hasta) && $filaBloqueo->bloqueado_hasta > date('Y-m-d H:i:s')) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => self::mensajeBloqueoPermanente(true), 'blocked' => true]);
                exit;
            }
            Session::setFlash('login_error', self::mensajeBloqueoPermanente(false));
            $this->view('auth/login', [], false);
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findByEmail($email);

        // ============================================
        // VALIDACIÓN ESPECÍFICA (UX MEJORADA)
        // ============================================
        // Separamos la validación para dar feedback específico al usuario

        // 1. Verificar si el correo existe
        if (!$user) {
            // SGP-FIX-v2 [1.4] — Mensaje genérico para evitar enumeración de usuarios (OWASP)
            $errorMsg = 'Credenciales incorrectas. Verifica tu correo y contraseña.';

            // SGP-FIX-v2 [T8] Registrar intento fallido
            $db->query(
                'INSERT INTO intentos_acceso (direccion_ip, correo, intentos, ultimo_intento)
                 VALUES (:ip, :email, 1, NOW())
                 ON DUPLICATE KEY UPDATE
                     intentos       = intentos + 1,
                     ultimo_intento = NOW()'
            );
            $db->bind(':ip',    $ip);
            $db->bind(':email', $email);
            $db->execute();

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            Session::setFlash('login_error', $errorMsg);
            $this->view('auth/login', [], false);
            return;
        }
        
        // 2. Verificar si la contraseña es correcta
        // Guard: hash NULL significa que el usuario fue creado sin contraseña (seed manual)
        if (empty($user['password'])) {
            error_log("[SGP-AUTH] Usuario {$email} tiene password NULL — fue insertado sin hash válido.");
            $errorMsg = 'Esta cuenta no tiene contraseña configurada. Contacte al administrador del sistema.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            Session::setFlash('login_error', $errorMsg);
            $this->view('auth/login', [], false);
            return;
        }

        if (!password_verify($password, $user['password'])) {
            // SGP-FIX-v2 [1.4] — Mismo mensaje genérico (no revelar si correo existe)
            $errorMsg = 'Credenciales incorrectas. Verifica tu correo y contraseña.';

            // SGP-FIX-v2 [T8] Registrar intento fallido
            $db->query(
                'INSERT INTO intentos_acceso (direccion_ip, correo, intentos, ultimo_intento)
                 VALUES (:ip, :email, 1, NOW())
                 ON DUPLICATE KEY UPDATE
                     intentos       = intentos + 1,
                     ultimo_intento = NOW()'
            );
            $db->bind(':ip',    $ip);
            $db->bind(':email', $email);
            $db->execute();

            // [SGP-LOCK-PERM] Bloqueo PERSISTENTE tras 5 intentos — antes se liberaba
            // solo a los 15 minutos ([FIX-AS-1]). Ahora el candado (IP+correo, NO la
            // cuenta real) queda activo sin expiración hasta que el usuario complete
            // el flujo de recuperación de contraseña (recovery() paso 3 lo libera).
            $db->query(
                'SELECT intentos FROM intentos_acceso
                 WHERE direccion_ip = :ip AND correo = :email
                 LIMIT 1'
            );
            $db->bind(':ip',    $ip);
            $db->bind(':email', $email);
            $fila = $db->single();

            $bloqueado = false;
            if ($fila && (int)$fila->intentos >= 5) {
                $db->query(
                    "UPDATE intentos_acceso SET bloqueado_hasta = '9999-12-31 23:59:59'
                     WHERE direccion_ip = :ip AND correo = :email"
                );
                $db->bind(':ip',    $ip);
                $db->bind(':email', $email);
                $db->execute();
                $bloqueado = true;
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $bloqueado ? self::mensajeBloqueoPermanente(true) : $errorMsg,
                    'blocked' => $bloqueado,
                ]);
                exit;
            }
            Session::setFlash('login_error', $bloqueado ? self::mensajeBloqueoPermanente(false) : $errorMsg);
            $this->view('auth/login', [], false);
            return;
        }

        // 3. Verificar si la cuenta está activa (Estado Auditoría Nivel Crítico)
        if (($user['estado'] ?? 'activo') === 'bloqueado') {
            $errorMsg = 'Tu cuenta ha sido bloqueada por seguridad tras múltiples intentos fallidos.<br><br><a href="' . URLROOT . '/auth/recovery" style="color:#ffffff; font-weight:bold; text-decoration:underline;">➜ Recuperar mi cuenta ahora</a>';
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            
            // Pasamos raw_html = true por si el sistema lo renderiza
            Session::setFlash('login_error', $errorMsg);
            $this->view('auth/login', [], false);
            return;
        }
        
        if (($user['estado'] ?? 'activo') === 'inactivo') {
            // Diferenciar: ¿pasante cuyo período finalizó, o cuenta desactivada manualmente?
            $errorMsg = 'Esta cuenta ha sido desactivada. Contacte al administrador.';
            if ((int)($user['role_id'] ?? $user['rol_id'] ?? 99) === 3) {
                try {
                    $db->query("
                        SELECT estado_pasantia FROM datos_pasante
                        WHERE usuario_id = :uid
                        ORDER BY FIELD(estado_pasantia, 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, id DESC
                        LIMIT 1
                    ");
                    $db->bind(':uid', $user['id']);
                    $dpRow = $db->single();
                    if ($dpRow && $dpRow->estado_pasantia === 'Finalizado') {
                        $errorMsg = 'Tu período de pasantía ha finalizado y tu acceso fue cerrado automáticamente. '
                                  . 'Si tienes alguna consulta, comunícate con el administrador.';
                    }
                } catch (\Throwable $e) { /* no interrumpir el flujo */ }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }

            Session::setFlash('login_error', $errorMsg);
            $this->view('auth/login', [], false);
            return;
        }

        // ✨ LOGIN EXITOSO: Reset contador de intentos
        // SGP-FIX-v2 [T8] Limpiar intentos fallidos tras login exitoso
        $db->query(
            'DELETE FROM intentos_acceso
             WHERE direccion_ip = :ip AND correo = :email'
        );
        $db->bind(':ip',    $ip);
        $db->bind(':email', $email);
        $db->execute();

        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('role_id', $user['role_id']);
        
        // Tarea: Establecer nombre real desde datos_personales
        $fullName = trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''));
        if (empty($fullName)) $fullName = 'Usuario';
        Session::set('user_name', $fullName);

        Session::set('requiere_cambio_clave', $user['requiere_cambio_clave'] ?? 0);
        Session::set('departamento_id', $user['departamento_id'] ?? null);
        Session::set('user_avatar', $user['avatar'] ?? 'default.png');
        Session::set('last_activity', time());

        // Cargar permisos en sesión (Data-Driven UI)
        // SuperAdmin (rol 0) retorna array vacío; hasPermission() le permite todo directamente.
        $permisosModel = $this->model('Permisos');
        $permisos = $permisosModel->getPermisosUsuario((int)$user['id'], (int)$user['role_id']);
        Session::set('permisos', $permisos);
        Session::set('permisos_ts', time());

        // Verificar si el perfil está completo
        $perfilCompletado = $userModel->verificarPerfilCompleto((int)$user['id']);
        Session::set('perfil_completado', $perfilCompletado);

        // Registrar login en bitácora
        AuditModel::log('LOGIN');

        // ── Notificación automática si hoy es feriado (solo roles 0, 1, 2) ──
        try {
            $db->query("
                SELECT id, nombre, es_laborable FROM dias_feriados
                WHERE fecha = CURDATE()
                LIMIT 1
            ");
            $feriadoHoy = $db->single();
            if ($feriadoHoy && in_array((int)$user['rol_id'], [0, 1, 2])) {
                require_once APPROOT . '/models/NotificationModel.php';
                $notifModel = new NotificationModel($db);
                // Solo insertar si no existe ya una para hoy
                if (!$notifModel->existeHoy((int)$user['id'], 'feriado_hoy', (int)$feriadoHoy->id)) {
                    $esLaborable = (int)$feriadoHoy->es_laborable === 1;
                    if ($esLaborable) {
                        $titulo  = '📅 Hoy es día feriado (laborable)';
                        $cuerpo  = 'Hoy (' . date('d/m/Y') . ') es "' . $feriadoHoy->nombre . '". '
                                 . 'Es un feriado laborable — las asistencias se registran con normalidad.';
                    } else {
                        $titulo  = '📅 Hoy es día feriado';
                        $cuerpo  = 'Hoy (' . date('d/m/Y') . ') es "' . $feriadoHoy->nombre . '". '
                                 . 'No se requiere asistencia. El día no cuenta en el cómputo de horas de pasantía.';
                    }
                    $notifModel->createWithRef(
                        (int)$user['id'],
                        'feriado_hoy',
                        $titulo,
                        $cuerpo,
                        URLROOT . '/configuracion#feriados',
                        (int)$feriadoHoy->id
                    );
                }
            }
        } catch (\Throwable $e) {
            // No bloquear el login si la verificación falla
            error_log('[SGP-AUTH] Error verificando feriado al login: ' . $e->getMessage());
        }
        // ──────────────────────────────────────────────────────────────────

        // Redirección al Wizard si requiere cambio de clave O perfil incompleto
        if ($user['requiere_cambio_clave'] == 1 || !$perfilCompletado) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'   => true,
                    'redirect'  => URLROOT . '/wizard/index',
                    'user_name' => $fullName
                ]);
                exit;
            }
            $this->redirect('/wizard/index');
            return;
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'redirect' => URLROOT . '/dashboard',
                'user_name' => $fullName
            ]);
            exit;
        }

        // ✨ Toast de bienvenida en el Dashboard
        Session::setFlash('success', '¡Bienvenido, ' . $fullName . '!');

        $this->redirectByRole($user['role_id']);

        } catch (\Throwable $e) {
            error_log('[SGP-AUTH] login() DB error: ' . $e->getMessage());
            $genericMsg = 'Error cargando el sistema. Intente de nuevo.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $genericMsg]);
                exit;
            }
            $this->view('auth/login', ['error' => $genericMsg], false);
            return;
        }
    }

    /**
     * Registro Público de Pasantes
     * 
     * FLUJO TÉCNICO:
     * 1. Validación de Entrada: Email y Cédula únicos.
     * 2. Transacción ACID: Crea cuenta y perfil simultáneamente.
     * 3. Configuración Inicial:
     *    - requiere_cambio_clave = 0: El usuario mismo elije su clave.
     *    - perfil_completado = false: Disparad por falta de campos obligatorios.
     * 4. Seguridad: Inserta 3 respuestas de seguridad hasheadas.
     * 
     * @return void
     */
    public function register(): void
    {
        Session::start();
        
        // ============================================
        // CARGAR PREGUNTAS SIEMPRE (GET y POST)
        // ============================================
        $preguntas = $this->model('User')->getSecurityQuestions();

        // Fallback: Datos de prueba si la BD está vacía
        if (empty($preguntas)) {
            error_log('[SGP-AUTH] [WARN] No se encontraron preguntas en la BD. Usando datos de prueba.');
            $preguntas = [
                ['id' => 1, 'pregunta' => '🔧 PRUEBA: ¿Nombre de tu primera mascota?'],
                ['id' => 2, 'pregunta' => '🔧 PRUEBA: ¿Ciudad donde naciste?'],
                ['id' => 3, 'pregunta' => '🔧 PRUEBA: ¿Tu comida favorita?'],
                ['id' => 4, 'pregunta' => '🔧 PRUEBA: ¿Nombre de tu mejor amigo de infancia?'],
                ['id' => 5, 'pregunta' => '🔧 PRUEBA: ¿Marca de tu primer carro?']
            ];
        }

        // ============================================
        // GET REQUEST: Mostrar formulario
        // ============================================
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: text/html; charset=UTF-8');
            $this->view('auth/register', ['questions' => $preguntas], false);
            return;
        }
        
        // ============================================
        // POST REQUEST: Procesar registro
        // ============================================
        
        // Capturar datos del formulario
        $fullName = Validator::post('name');
        $cedula = Validator::post('cedula');
        $email = Validator::email('email');
        $password = Validator::post('password');
        $roleId = 3; // Pasante por defecto

        // [FIX-V-1][FIX-V-7] Validación de formato server-side — antes solo
        // existía en JS (el cliente se puede saltar con una petición directa).
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/register', ['questions' => $preguntas, 'error' => 'Formato de correo inválido.'], false);
            return;
        }
        if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
            $this->view('auth/register', ['questions' => $preguntas, 'error' => 'La cédula debe tener entre 7 y 8 dígitos numéricos.'], false);
            return;
        }
        if (strlen($password) < 8) {
            $this->view('auth/register', ['questions' => $preguntas, 'error' => 'La contraseña debe tener al menos 8 caracteres.'], false);
            return;
        }

        $userModel = $this->model('User');
        
        // ============================================
        // TRANSACCIÓN ACID: TODO O NADA
        // ============================================
        try {
            // Iniciar transacción
            $userModel->beginTransaction();
            
            // Validación 1: Email duplicado
            if ($userModel->findByEmail($email)) {
                throw new Exception('El correo ya existe');
            }

            // Validación 2: Cédula duplicada
            if ($userModel->findByCedula($cedula)) {
                throw new Exception('La cédula ya está registrada');
            }

            // Parsear nombre completo
            $nameParts = explode(' ', trim($fullName), 2);
            $nombres = $nameParts[0] ?? 'Usuario';
            $apellidos = $nameParts[1] ?? 'Nuevo';

            // Crear usuario
            $userData = [
                'email' => $email,
                'password' => $password,
                'cedula' => $cedula, // FIX: PERSISTENCIA EN TABLA USUARIOS
                'role_id' => $roleId,
                'departamento_id' => null,
                'requiere_cambio_clave' => 0
            ];

            if (!$userModel->create($userData)) {
                throw new Exception('Error al crear el usuario');
            }

            // Obtener el usuario recién creado
            $newUser = $userModel->findByEmail($email);
            
            if (!$newUser) {
                throw new Exception('Error al obtener el usuario creado');
            }

            // ============================================
            // GUARDAR RESPUESTAS DE SEGURIDAD (3 PREGUNTAS)
            // ============================================
            $pregunta1 = Validator::post('pregunta_id_1');
            $respuesta1 = Validator::post('respuesta_1');
            
            $pregunta2 = Validator::post('pregunta_id_2');
            $respuesta2 = Validator::post('respuesta_2');
            
            $pregunta3 = Validator::post('pregunta_id_3');
            $respuesta3 = Validator::post('respuesta_3');

            // Validar que las 3 preguntas sean diferentes
            if ($pregunta1 == $pregunta2 || $pregunta1 == $pregunta3 || $pregunta2 == $pregunta3) {
                throw new Exception('Debes seleccionar 3 preguntas diferentes');
            }

            // ✨ NUEVO: Validar que las 3 respuestas sean diferentes (case-insensitive)
            $r1_lower = strtolower(trim($respuesta1));
            $r2_lower = strtolower(trim($respuesta2));
            $r3_lower = strtolower(trim($respuesta3));

            if ($r1_lower === $r2_lower || $r1_lower === $r3_lower || $r2_lower === $r3_lower) {
                throw new Exception('Las respuestas de seguridad deben ser diferentes');
            }

            // ✨ NUEVO: Validar longitud mínima de respuestas (seguridad)
            if (strlen($respuesta1) < 3 || strlen($respuesta2) < 3 || strlen($respuesta3) < 3) {
                throw new Exception('Las respuestas deben tener al menos 3 caracteres');
            }

            // Guardar las 3 respuestas (si falla alguna, se hace rollback)
            if (!$userModel->saveSecurityAnswer((int)$newUser['id'], (int)$pregunta1, $respuesta1)) {
                throw new Exception('Error al guardar la respuesta de seguridad 1');
            }
            
            if (!$userModel->saveSecurityAnswer((int)$newUser['id'], (int)$pregunta2, $respuesta2)) {
                throw new Exception('Error al guardar la respuesta de seguridad 2');
            }
            
            if (!$userModel->saveSecurityAnswer((int)$newUser['id'], (int)$pregunta3, $respuesta3)) {
                throw new Exception('Error al guardar la respuesta de seguridad 3');
            }

            // ============================================
            // GUARDAR DATOS PERSONALES
            // ============================================
            $perfilData = [
                'usuario_id' => $newUser['id'],
                'cedula' => $cedula,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => null,
                'genero' => null,
                'fecha_nacimiento' => null
            ];
            
            if (!$userModel->registrarPerfil($perfilData)) {
                throw new Exception('Error al crear el perfil del usuario');
            }

            // ============================================
            // CONFIRMAR TRANSACCIÓN (TODO EXITOSO)
            // ============================================
            $userModel->commit();
            
            // PATRÓN PRG (Post-Redirect-Get): Redirigir al login
            // Esto evita el error ERR_CACHE_MISS al dar "Atrás" en el navegador
            header('Location: ' . URLROOT . '/auth/login?status=success');
            exit();
            
        } catch (\Throwable $e) {
            // ============================================
            // ROLLBACK: REVERTIR TODO (ACID)
            // ============================================
            $userModel->rollBack();
            error_log('[SGP-AUTH] register() error: ' . $e->getMessage());

            // Mensajes explícitos de validación se muestran al usuario.
            // Errores internos (PDOException, etc.) muestran mensaje genérico.
            $mensajesValidacion = [
                'El correo ya existe',
                'La cédula ya está registrada',
                'Debes seleccionar 3 preguntas diferentes',
                'Las respuestas de seguridad deben ser diferentes',
                'Las respuestas deben tener al menos 3 caracteres',
            ];
            $errorMsg = in_array($e->getMessage(), $mensajesValidacion)
                ? $e->getMessage()
                : 'Error al procesar el registro. Intente de nuevo.';

            $this->view('auth/register', [
                'questions' => $preguntas,
                'error' => $errorMsg
            ], false);
        }
    }

    public function recovery(): void
    {
        Session::start();

        // ============================================================
        // 🛡️ CAPA 1 — GUARDIA: No mezclar recovery con sesión activa
        // Si el usuario ya tiene sesión iniciada en otra pestaña,
        // redirigirlo a su dashboard en lugar de continuar el flujo.
        // ============================================================
        if (Session::get('user_id')) {
            $this->redirectByRole(Session::get('role_id'));
            return;
        }

        // ============================================================
        // 🛡️ CAPA 2 — HEADERS NO-CACHÉ
        // Impide que el navegador restaure estas páginas desde caché
        // al presionar el botón "Atrás", evitando estados obsoletos.
        // ============================================================
        CacheControl::noCache();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Limpiar cualquier sesión de recovery previa al iniciar
            $this->clearRecoverySession();
            $this->view('auth/recovery', [], false);
            return;
        }

        $step = $_POST['step'] ?? '1';

        // ============================================================
        // PASO 1: Validar correo y mostrar preguntas de seguridad
        // ============================================================
        if ($step == '1') {
            $email = Validator::email('email');
            $user = $this->model('User')->findByEmail($email);

            if (!$user) {
                $this->view('auth/recovery', ['error' => 'Correo no encontrado'], false);
                return;
            }

            $questions = $this->model('User')->getUserAnswers($user['id']);
            if (empty($questions)) {
                $this->view('auth/recovery', ['error' => 'Sin preguntas de seguridad configuradas'], false);
                return;
            }

            // 🛡️ CAPA 3 — TOKEN DE FLUJO CON EXPIRACIÓN (15 minutos)
            // Cada Recovery genera un token único. Si el usuario retrocede
            // más allá del tiempo límite, el flujo se invalida automáticamente.
            Session::set('rec_uid',            $user['id']);
            Session::set('rec_email',          $email);  // Requerido por solicitud de ayuda
            Session::set('rec_token',          bin2hex(random_bytes(16)));
            Session::set('rec_token_expires',  time() + 900); // 15 minutos
            Session::set('rec_verified',       false);
            // [FIX-AS-2] Contador de intentos de respuestas de seguridad dentro de esta ventana
            Session::set('rec_intentos',       0);

            $this->view('auth/recovery_questions', ['questions' => $questions], false);
        }

        // ============================================================
        // PASO 2: Verificar respuestas de seguridad
        // ============================================================
        elseif ($step == '2') {
            $uid     = Session::get('rec_uid');
            $expires = Session::get('rec_token_expires');

            // Validar que el flujo sea vigente (sesión + token no expirado)
            if (!$uid || !$expires || time() > $expires) {
                $this->clearRecoverySession();
                $this->redirect('/auth/recovery');
                return;
            }

            // [FIX-AS-2] Límite de intentos — antes solo dependía de la expiración
            // de 15 min del token, sin tope de cuántas veces se podía probar
            // combinaciones dentro de esa ventana (las respuestas suelen tener
            // baja entropía: mascota, ciudad natal).
            $intentos = (int)(Session::get('rec_intentos') ?? 0);
            if ($intentos >= 5) {
                $this->clearRecoverySession();
                Session::setFlash('error', 'Demasiados intentos fallidos. Inicia el proceso de recuperación nuevamente.');
                $this->redirect('/auth/recovery');
                return;
            }

            $inputAnswers = $_POST['answers'] ?? [];
            $userAnswers  = $this->model('User')->getUserAnswers($uid);

            $allCorrect = true;
            foreach ($userAnswers as $ua) {
                $qid = $ua['question_id'];
                if (!isset($inputAnswers[$qid]) || !password_verify(strtolower(trim($inputAnswers[$qid])), $ua['answer_hash'])) {
                    $allCorrect = false;
                    break;
                }
            }

            if ($allCorrect) {
                // Marcar verificación exitosa y renovar expiración
                Session::set('rec_verified',      true);
                Session::set('rec_token_expires', time() + 900); // 15 min para cambiar contraseña
                $userForView = $this->model('User')->findById($uid);
                $this->view('auth/reset_password', ['cedula' => $userForView['cedula'] ?? ''], false);
            } else {
                Session::set('rec_intentos', $intentos + 1);
                $intentosRestantes = 5 - ($intentos + 1);
                $msg = $intentosRestantes > 0
                    ? "Las respuestas de seguridad no coinciden. Te quedan {$intentosRestantes} intento(s)."
                    : 'Las respuestas de seguridad no coinciden.';
                $this->view('auth/recovery_questions', ['questions' => $userAnswers, 'error' => $msg], false);
            }
        }

        // ============================================================
        // PASO 3: Actualizar contraseña
        // ============================================================
        elseif ($step == '3') {
            $uid     = Session::get('rec_uid');
            $expires = Session::get('rec_token_expires');

            // Doble verificación: token vigente + respuestas validadas
            if (!Session::get('rec_verified') || !$uid || !$expires || time() > $expires) {
                $this->clearRecoverySession();
                $this->redirect('/auth/recovery');
                return;
            }

            $pass        = Validator::post('password');
            $passConfirm = Validator::post('password_confirm');

            // [FIX-V-2] La confirmación se recibía pero nunca se comparaba.
            if ($pass !== $passConfirm) {
                $userForView = $this->model('User')->findById($uid);
                $this->view('auth/reset_password', [
                    'cedula' => $userForView['cedula'] ?? '',
                    'error'  => 'Las contraseñas no coinciden.'
                ], false);
                return;
            }

            // [FIX-V-1] Validación de longitud — antes solo existía en JS.
            if (strlen($pass) < 8) {
                $userForView = $this->model('User')->findById($uid);
                $this->view('auth/reset_password', [
                    'cedula' => $userForView['cedula'] ?? '',
                    'error'  => 'La contraseña debe tener al menos 8 caracteres.'
                ], false);
                return;
            }

            // 🔒 Bloquear contraseña temporal como contraseña permanente
            $userRec = $this->model('User')->findById($uid);
            $cedula  = $userRec['cedula'] ?? '';
            if (!empty($cedula) && $pass === 'Sgp.' . $cedula) {
                $this->view('auth/reset_password', [
                    'cedula' => $cedula,
                    'error' => 'No puedes usar tu contraseña temporal como nueva contraseña. Elige una contraseña personalizada.'
                ], false);
                return;
            }

            $this->model('User')->updatePassword($uid, $pass);

            // [SGP-LOCK-PERM] Liberar el bloqueo de login (todas las IP asociadas a
            // este correo) — el usuario acaba de demostrar su identidad respondiendo
            // las preguntas de seguridad y ya tiene contraseña nueva.
            if (!empty($userRec['correo'])) {
                $dbUnlock = Database::getInstance();
                $dbUnlock->query('DELETE FROM intentos_acceso WHERE correo = :email');
                $dbUnlock->bind(':email', $userRec['correo']);
                $dbUnlock->execute();
            }

            // Registrar en bitácora (sin sesión activa aún, se registrará como sistema/anónimo pero con el email en detalles)
            AuditModel::log('RECUPERACION_PASSWORD_EXITOSA', 'usuarios', $uid, [
                'email' => $userRec['correo'] ?? 'desconocido'
            ]);

            // Limpiar SOLO las variables de recovery, no destruir sesión entera
            $this->clearRecoverySession();

            Session::setFlash('success', 'Contraseña actualizada correctamente. Por favor inicia sesión.');
            $this->redirect('/auth/login');
        }
    }

    /**
     * Limpia todas las variables de sesión del flujo de recovery.
     * Se llama al completar el flujo, al cancelar, o al detectar token expirado.
     */
    private function clearRecoverySession(): void
    {
        $keys = ['rec_uid', 'rec_email', 'rec_token', 'rec_token_expires', 'rec_verified', 'recovery_email'];
        foreach ($keys as $key) {
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }
    }


    public function cambiarPassword(): void
    {
        Session::start();
        
        // Verify user is logged in
        if (!Session::get('user_id')) {
            $this->redirect('/auth/login');
            return;
        }
        
        // Show form if GET request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $userModel = $this->model('User');
            $user = $userModel->findById(Session::get('user_id'));
            $this->view('auth/cambiar_password', ['cedula' => $user['cedula'] ?? ''], false);
            return;
        }
        
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        $newPassword = Validator::post('new_password');
        $confirmPassword = Validator::post('confirm_password');

        $userId = Session::get('user_id');
        $userModel = $this->model('User');
        $user = $userModel->findById($userId);

        // Validate new password is different from current (compare against stored hash)
        if (password_verify($newPassword, $user['password'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe ser diferente a la temporal']);
                exit;
            }
            $this->view('auth/cambiar_password', ['error' => 'La nueva contraseña debe ser diferente'], false);
            return;
        }
        
        // Validate passwords match
        if ($newPassword !== $confirmPassword) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
                exit;
            }
            $this->view('auth/cambiar_password', ['error' => 'Las contraseñas no coinciden'], false);
            return;
        }

        // [FIX-V-1] Validación de longitud — antes solo existía en JS.
        if (strlen($newPassword) < 8) {
            $errorMsg = 'La contraseña debe tener al menos 8 caracteres.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $this->view('auth/cambiar_password', ['error' => $errorMsg], false);
            return;
        }

        // 🔒 Bloquear contraseña temporal como contraseña permanente
        $cedula = $user['cedula'] ?? '';
        if (!empty($cedula) && $newPassword === 'Sgp.' . $cedula) {
            $errorMsg = 'No puedes usar tu contraseña temporal como contraseña permanente. Elige una contraseña personalizada.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $this->view('auth/cambiar_password', ['error' => $errorMsg], false);
            return;
        }

        // Update password and clear flag
        if ($userModel->updatePassword($userId, $newPassword)) {
            Session::set('requiere_cambio_clave', 0);
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => URLROOT . '/dashboard']);
                exit;
            }
            
            $this->redirect('/dashboard');
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña']);
                exit;
            }
            $this->view('auth/cambiar_password', ['error' => 'Error al actualizar'], false);
        }
    }

    /**
     * API endpoint for real-time email validation (AJAX)
     * Returns JSON: { exists: true/false }
     */
    public function apiCheckEmail(): void
    {
        header('Content-Type: application/json');
        
        // Get JSON input
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['email']) && !empty($data['email'])) {
            $email = trim($data['email']);
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['exists' => false, 'valid' => false]);
                return;
            }
            
            // Check if email exists in database
            $userModel = $this->model('User');
            $user = $userModel->findByEmail($email);
            
            echo json_encode([
                'exists' => $user ? true : false,
                'valid' => true
            ]);
        } else {
            echo json_encode(['exists' => false, 'valid' => false]);
        }
    }

    /**
     * Verifica disponibilidad de correo/cédula en el formulario público de registro.
     * Equivalente sin sesión a UsersController::checkUnique (esa requiere login y
     * por eso fallaba silenciosamente desde auth/register, mostrando "Formato válido"
     * aunque el correo/cédula ya existiera).
     */
    public function checkAvailability(): void
    {
        header('Content-Type: application/json');

        $campo = $_GET['campo'] ?? '';
        $valor = trim($_GET['valor'] ?? '');

        if (!in_array($campo, ['cedula', 'correo']) || $valor === '') {
            echo json_encode(['available' => true]);
            return;
        }

        $userModel = $this->model('User');

        if ($campo === 'cedula') {
            if (!preg_match('/^[0-9]{7,8}$/', $valor)) {
                echo json_encode(['available' => true, 'skip' => true]);
                return;
            }
            $exists = $userModel->findByCedula($valor);
        } else {
            if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['available' => true, 'skip' => true]);
                return;
            }
            $exists = $userModel->findByEmail($valor);
        }

        echo json_encode(['available' => !$exists]);
    }

    public function logout(): void
    {
        // Aplicar cache control para prevenir botón atrás
        CacheControl::noCache();
        
        $razon = $_GET['razon'] ?? '';
        
        Session::start();
        
        // Registrar logout en bitácora
        // Usamos el ID de la sesión actual antes de destruirla
        AuditModel::log('LOGOUT');

        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión si existe
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destruir la sesión
        Session::destroy();
        
        // Redirigir al login con mensaje
        Session::start(); // Reiniciar para el flash message
        
        if ($razon === 'inactividad') {
            Session::setFlash('inactividad', 'Tu sesión ha concluido automáticamente debido un tiempo prolongado de inactividad.');
            session_write_close(); // Garantizar que el flash quede escrito antes del redirect
            $this->redirect('/auth/login?razon=inactividad');
        } else {
            Session::setFlash('success', 'Sesión cerrada correctamente');
            $this->redirect('/auth/login');
        }
    }

    /**
     * Solicitar Desbloqueo de Cuenta (Recuperación Asistida)
     * 
     * PROPÓSITO:
     * Permitir que usuarios bloqueados (que olvidaron respuestas de seguridad)
     * soliciten ayuda al administrador mediante una notificación interna.
     * 
     * FLUJO:
     * 1. Verificar sesión de recuperación válida
     * 2. Obtener datos del usuario
     * 3. Verificar que no existe solicitud pendiente (prevenir abuso)
     * 4. Crear notificación para el administrador
     * 5. Limpiar sesión y redirigir con mensaje de confirmación
     * 
     * SEGURIDAD:
     * - Solo 1 solicitud cada 24 horas por usuario
     * - Requiere sesión de recuperación activa
     * - Limpia sesión después de solicitar
     */
    public function solicitarDesbloqueo()
    {
        $this->verifyCsrf(); // SGP-FIX-v2 [1.1] aplicado

        // 1. Verificar sesión de recuperación
        if (!isset($_SESSION['recovery_user_id'])) {
            Session::setFlash('error', 'Sesión expirada. Inicia el proceso de recuperación nuevamente.');
            $this->redirect('/auth/recovery');
            return;
        }
        
        $userId = $_SESSION['recovery_user_id'];
        
        // 2. Obtener datos del usuario
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Session::setFlash('error', 'Usuario no encontrado.');
            $this->redirect('/auth/login');
            return;
        }
        
        // 3. Verificar si ya existe una solicitud pendiente (últimas 24 horas)
        // NOTA: Esto previene que usuarios hagan spam de solicitudes
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM notificaciones 
            WHERE tipo = 'unlock_request' 
            AND metadata LIKE :user_pattern
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $this->db->bind(':user_pattern', '%"user_id":' . $userId . '%');
        $result = $this->db->single();
        
        if ($result && $result['count'] > 0) {
            Session::setFlash('warning', 'Ya tienes una solicitud pendiente. El administrador la revisará pronto.');
            $this->redirect('/auth/login');
            return;
        }
        
        // 4. Crear notificación para el administrador
        $adminId = 1; // ID del administrador principal
        
        $mensaje = "El usuario {$user['nombre']} {$user['apellido']} ({$user['email']}) ha solicitado un reseteo de cuenta por olvido de respuestas de seguridad.";
        
        $metadata = json_encode([
            'user_id' => $userId,
            'user_email' => $user['email'],
            'user_name' => $user['nombre'] . ' ' . $user['apellido'],
            'request_type' => 'unlock_account'
        ]);
        
        $this->db->query("
            INSERT INTO notificaciones (user_id, tipo, mensaje, metadata, leida, created_at) 
            VALUES (:user_id, 'unlock_request', :mensaje, :metadata, 0, NOW())
        ");
        $this->db->bind(':user_id', $adminId);
        $this->db->bind(':mensaje', $mensaje);
        $this->db->bind(':metadata', $metadata);
        
        if ($this->db->execute()) {
            // 5. Limpiar sesión de recuperación
            unset($_SESSION['recovery_user_id']);
            unset($_SESSION['recovery_questions']);
            
            // 6. Redirigir con mensaje de éxito
            Session::setFlash('success', 'Solicitud enviada. El administrador revisará tu caso y te contactará pronto.');
            $this->redirect('/auth/login');
        } else {
            Session::setFlash('error', 'Error al enviar la solicitud. Intenta nuevamente.');
            $this->redirect('/auth/recovery');
        }
    }

    /**
     * Solicitar ayuda al administrador cuando el usuario olvida sus respuestas de seguridad (AJAX).
     *
     * FLUJO:
     * 1. Validar que es una petición AJAX con email válido en sesión de recovery.
     * 2. Rate-limit: 1 solicitud cada 24 h por usuario para evitar spam.
     * 3. Obtener nombre + cédula del usuario desde datos_personales (JOIN).
     * 4. Consultar TODOS los admins activos (role_id = 1).
     * 5. Crear notificación tipo 'warning' para cada admin vía NotificationModel.
     * 6. Devolver JSON de éxito/error al frontend.
     */
    public function requestHelp(): void
    {
        Session::start();

        // ── Validar petición AJAX ─────────────────────────────────────────────
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                  && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!$isAjax) {
            $this->redirect('/auth/login');
            return;
        }

        header('Content-Type: application/json');

        try {
            // ── Leer y validar el email del body JSON ─────────────────────────
            $raw   = file_get_contents('php://input');
            $data  = json_decode($raw, true);
            $email = trim($data['email'] ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Correo inválido o no proporcionado.']);
                exit;
            }

            $db = Database::getInstance();

            // ── Obtener usuario con nombre y cédula (cedula está en `usuarios`, no en datos_personales) ──
            $db->query("
                SELECT u.id,
                       u.correo,
                       u.cedula,
                       dp.nombres,
                       dp.apellidos
                FROM   usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE  u.correo = :email
                LIMIT 1
            ");
            $db->bind(':email', $email);
            $usuario = $db->single();

            if (!$usuario) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado en el sistema.']);
                exit;
            }

            $userId    = (int) $usuario->id;
            $nombreC   = trim(($usuario->nombres ?? 'Usuario') . ' ' . ($usuario->apellidos ?? ''));
            $cedulaC   = $usuario->cedula ?? 'N/D';

            // ── Rate-limit: máximo 1 solicitud por usuario en las últimas 24h ─
            $db->query("
                SELECT COUNT(*) AS total
                FROM   notificaciones
                WHERE  tipo    = 'warning'
                  AND  titulo  = 'Asistencia de Seguridad Requerida'
                  AND  mensaje LIKE :patron
                  AND  created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $db->bind(':patron', '%' . $cedulaC . '%');
            $check = $db->single();

            if ($check && (int)$check->total > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ya tienes una solicitud pendiente. El administrador la revisará pronto.'
                ]);
                exit;
            }

            // ── Obtener TODOS los administradores activos (rol_id = 1) ────────
            $db->query("
                SELECT id
                FROM   usuarios
                WHERE  rol_id = 1
                  AND  estado  = 'activo'
            ");
            $admins = $db->resultSet();

            if (empty($admins)) {
                // Sin admins activos: registrar en log pero responder éxito al usuario
                error_log('[SGP-AUTH][requestHelp] No se encontraron administradores activos para notificar.');
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud registrada. Un administrador te contactará pronto.'
                ]);
                exit;
            }

            // ── Instanciar NotificationModel y disparar evento por cada admin ─
            require_once APPROOT . '/models/NotificationModel.php';
            $notifModel = new NotificationModel($db);

            $titulo  = 'Asistencia de Seguridad Requerida';
            $mensaje = "El usuario {$nombreC} (Cédula: {$cedulaC}) ha olvidado sus respuestas "
                     . "de seguridad y solicita asistencia para restablecer su acceso. "
                     . "Correo: {$email}.";
            $url     = URLROOT . '/users';

            foreach ($admins as $admin) {
                $notifModel->create((int) $admin->id, 'warning', $titulo, $mensaje, $url);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Solicitud enviada correctamente.'
            ]);

        } catch (\Throwable $e) {
            // [FIX-ED-1] Este endpoint es alcanzable SIN sesión (flujo "olvidé mis
            // respuestas" del login) — antes devolvía $e->getMessage() crudo a
            // cualquier visitante anónimo, exponiendo potencialmente fragmentos de
            // SQL o rutas del servidor. Detalle solo en log, mensaje genérico al cliente.
            error_log('[SGP-AUTH][requestHelp] Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo procesar la solicitud. Intenta de nuevo más tarde.'
            ]);
        }

        exit;
    }

    protected function redirectByRole($roleId) {
        $routes = [
            0 => '/superadmin', // SuperAdministrador
            1 => '/admin',      // Administrador
            2 => '/tutor',      // Tutor
            3 => '/pasante'     // Pasante
        ];
        $this->redirect($routes[$roleId] ?? '/auth/login');
    }

    /**
     * Renueva la sesión activa desde JS (keep-alive de inactividad).
     * SGP-FIX-v2 [sesión_inactividad paso_4] aplicado
     */
    public function keepalive(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false]);
            exit;
        }
        $_SESSION['last_activity'] = time();
        echo json_encode(['success' => true]);
        exit;
    }
}
