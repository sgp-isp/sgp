<?php
/**
 * PerfilController - Gestión de Perfiles de Usuario
 * 
 * PROPÓSITO EDUCATIVO:
 * Este controlador maneja el flujo completo de configuración de perfil para nuevos usuarios.
 * Implementa el patrón MVC separando la lógica de negocio (Controller) de la presentación (View)
 * y el acceso a datos (Model).
 * 
 * FLUJO PRINCIPAL:
 * 1. Usuario inicia sesión con contraseña temporal (requiere_cambio_clave = 1)
 * 2. Sistema lo redirige al wizard de 3 pasos (completar_wizard.php)
 * 3. Usuario completa: Contraseña → Preguntas Seguridad → Datos Personales
 * 4. Sistema guarda todo en una transacción atómica (todo o nada)
 * 5. Usuario es redirigido al dashboard con perfil completo
 * 
 * @author Sistema SGP
 * @version 2.0 - Refactorización Educativa
 */

class PerfilController extends Controller
{
    private $userModel;
    private $db;
    private $notificationModel;

    /**
     * Constructor del Controlador
     * 
     * FLUJO:
     * 1. Inicia sesión PHP (necesaria para verificar autenticación)
     * 2. Verifica que el usuario esté autenticado (tiene user_id en sesión)
     * 3. Carga el modelo User para acceder a datos de usuarios
     * 4. Carga la conexión a BD para queries complejas
     * 5. Carga el modelo de Notificaciones
     * 
     * SEGURIDAD:
     * Si no hay user_id en sesión, redirige al login (previene acceso no autorizado)
     */
    public function __construct()
    {
        Session::start();
        
        // Verificar autenticación: Si no hay user_id, el usuario no ha iniciado sesión
        if (!Session::get('user_id')) {
            header('Location: ' . URLROOT . '/auth/login');
            exit;
        }
        
        // 🔒 SEGURIDAD: Verificar estado del usuario (Sistema "La Jaula")
        // Fuerza a usuarios con requiere_cambio_clave=1 a completar el wizard
        // Previene bypass por manipulación de URL (OWASP Top 10 #1: Broken Access Control)
        AuthMiddleware::verificarEstado();
        
        // Cargar dependencias
        $this->userModel = $this->model('User');
        $config = require '../app/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        
        require_once '../app/models/NotificationModel.php';
        $this->notificationModel = new NotificationModel($this->db);
    }

    /**
     * Vista Principal del Perfil - Redirige a ver()
     */
    public function index()
    {
        $this->ver();
    }

    /**
     * Mostrar Wizard de Completar Perfil
     * 
     * PROPÓSITO:
     * Renderiza el formulario de 3 pasos para que el usuario configure su cuenta.
     * 
     * LÓGICA DINÁMICA:
     * - Si el usuario tiene datos personales (cedula, nombres, apellidos) → Campos READONLY
     * - Si NO tiene datos personales → Campos EDITABLES
     * 
     * RAZÓN TÉCNICA:
     * Usamos LEFT JOIN porque usuarios nuevos pueden NO tener registro en datos_personales todavía.
     * Un INNER JOIN excluiría estos usuarios y causaría error.
     * 
     * FLUJO DE DATOS:
     * Input: user_id desde Session
     * Proceso: Query a BD con LEFT JOIN
     * Output: Array $data con información del usuario y preguntas de seguridad
     * Destino: Vista completar_wizard.php
     */
    public function completar()
    {
        Session::start();
        $userId = Session::get('user_id');
        
        /**
         * Query con LEFT JOIN para obtener datos del usuario
         * 
         * LEFT JOIN permite que usuarios SIN datos_personales también aparezcan.
         * Si no existe registro en datos_personales, los campos (cedula, nombres, apellidos) serán NULL.
         * 
         * COLUMNAS:
         * - u.id, u.correo, u.rol_id: Datos básicos del usuario
         * - dp.cedula, dp.nombres, dp.apellidos: Datos personales (pueden ser NULL)
         * - d.nombre as departamento_nombre: Nombre del departamento (puede ser NULL)
         */
        $this->db->query("
            SELECT
                u.id,
                u.correo,
                u.cedula,
                u.rol_id,
                u.departamento_id,
                dp.nombres,
                dp.apellidos,
                d.nombre as departamento_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
            LEFT JOIN departamentos d ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            WHERE u.id = :uid
        ");
        $this->db->bind(':uid', $userId);
        $user = $this->db->single();
        
        // Obtener preguntas de seguridad aleatorias desde la BD
        $questions = $this->userModel->getSecurityQuestions();
        
        // Preparar datos para la vista
        $data = [
            'user' => $user,
            'questions' => $questions
        ];
        
        // Renderizar vista del wizard
        $this->view('perfil/completar_wizard', $data);
    }

    /**
     * Guardar Datos del Wizard (3 Pasos) - TRANSACCIÓN ATÓMICA
     * 
     * PROPÓSITO EDUCATIVO:
     * Este método implementa el concepto de "transacción atómica" en bases de datos.
     * Una transacción atómica garantiza que TODAS las operaciones se ejecuten correctamente,
     * o NINGUNA se ejecute (principio "todo o nada").
     * 
     * FLUJO:
     * 1. Validar contraseña actual (verificar que el usuario conoce su clave temporal)
     * 2. Validar nueva contraseña (longitud, coincidencia)
     * 3. Validar preguntas de seguridad (3 preguntas con respuestas)
     * 4. Validar datos personales (todos los campos requeridos)
     * 5. BEGIN TRANSACTION → Iniciar transacción
     * 6. Actualizar contraseña en tabla usuarios
     * 7. Guardar 3 respuestas de seguridad en usuarios_respuestas
     * 8. Guardar/Actualizar datos personales en datos_personales
     * 9. COMMIT → Confirmar todos los cambios
     * 10. Si hay error en cualquier paso → ROLLBACK (deshacer todo)
     * 
     * SEGURIDAD:
     * - Usamos password_verify() para comparar contraseñas (NO comparación directa)
     * - Usamos password_hash() con BCRYPT para almacenar contraseñas de forma segura
     * - Las respuestas de seguridad también se hashean (previene lectura directa en BD)
     * 
     * PREVENCIÓN DE ERRORES:
     * - Si falla la contraseña pero se guardan las preguntas → INCONSISTENCIA
     * - Si fallan las preguntas pero se guardan los datos → INCONSISTENCIA
     * - Con transacciones: O se guarda TODO correctamente, o NO se guarda NADA
     */
    public function guardarWizard()
    {
        // Solo aceptar peticiones POST (formularios)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil/completar');
            return;
        }

        Session::start();
        $userId = Session::get('user_id');
        
        // Obtener datos actuales del usuario desde BD
        $user = $this->userModel->findById($userId);
        
        // ============================================
        // PASO 1: VALIDAR CONTRASEÑA
        // ============================================
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        /**
         * Verificar contraseña actual con password_verify()
         * 
         * RAZÓN TÉCNICA:
         * NO podemos comparar directamente ($currentPassword == $user['password'])
         * porque la contraseña en BD está hasheada con BCRYPT.
         * 
         * password_verify() compara el texto plano con el hash de forma segura.
         */
        if (!password_verify($currentPassword, $user['password'])) {
            Session::setFlash('error', 'La contraseña actual es incorrecta');
            $this->redirect('/perfil/completar');
            return;
        }
        
        // Validar que las contraseñas nuevas coincidan
        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'Las contraseñas no coinciden');
            $this->redirect('/perfil/completar');
            return;
        }
        
        // Validar longitud mínima (seguridad básica)
        if (strlen($newPassword) < 8) {
            Session::setFlash('error', 'La contraseña debe tener al menos 8 caracteres');
            $this->redirect('/perfil/completar');
            return;
        }
        
        // ============================================
        // PASO 2: VALIDAR PREGUNTAS DE SEGURIDAD
        // ============================================
        
        $securityQuestions = [];
        for ($i = 1; $i <= 3; $i++) {
            $questionId = $_POST["question_$i"] ?? null;
            $answer = $_POST["answer_$i"] ?? '';
            
            if (!$questionId || !$answer) {
                Session::setFlash('error', 'Debe completar todas las preguntas de seguridad');
                $this->redirect('/perfil/completar');
                return;
            }
            
            // Almacenar temporalmente (aún no guardamos en BD)
            $securityQuestions[] = [
                'question_id' => $questionId,
                'answer' => $answer
            ];
        }
        
        // ============================================
        // PASO 3: VALIDAR DATOS PERSONALES
        // ============================================
        
        $datosPersonales = [
            'usuario_id' => $userId,
            'cedula' => trim($_POST['cedula']),
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'cargo' => trim($_POST['cargo'] ?? ''),
            'telefono' => trim($_POST['telefono']),
            'genero' => $_POST['genero'],
            'fecha_nacimiento' => $_POST['fecha_nacimiento']
        ];
        
        // Validar que ningún campo esté vacío (excepto cargo que es opcional)
        foreach ($datosPersonales as $key => $value) {
            if ($key !== 'usuario_id' && $key !== 'cargo' && empty($value)) {
                Session::setFlash('error', 'Todos los campos son obligatorios');
                $this->redirect('/perfil/completar');
                return;
            }
        }

        // [FIX-V-12] Esta ruta legado (/perfil/guardarWizard) solo comprobaba que los
        // campos no vinieran vacíos, igual que el bug corregido en WizardController (V-8).
        if (!preg_match('/^[0-9\-]{7,20}$/', $datosPersonales['telefono'])) {
            Session::setFlash('error', 'El Teléfono debe contener solo números (y guiones), entre 7 y 20 caracteres.');
            $this->redirect('/perfil/completar');
            return;
        }
        $fnacObj = DateTime::createFromFormat('Y-m-d', $datosPersonales['fecha_nacimiento']);
        $hoy     = new DateTime('today');
        $minFnac = (new DateTime('today'))->modify('-100 years');
        if (!$fnacObj || $fnacObj->format('Y-m-d') !== $datosPersonales['fecha_nacimiento'] || $fnacObj > $hoy || $fnacObj < $minFnac) {
            Session::setFlash('error', 'La Fecha de Nacimiento no es válida.');
            $this->redirect('/perfil/completar');
            return;
        }
        if (!in_array($datosPersonales['genero'], ['M', 'F', 'Otro'], true)) {
            Session::setFlash('error', 'Selecciona un género válido.');
            $this->redirect('/perfil/completar');
            return;
        }
        if (!preg_match('/^[0-9]{7,8}$/', $datosPersonales['cedula'])) {
            Session::setFlash('error', 'La cédula debe tener entre 7 y 8 dígitos numéricos.');
            $this->redirect('/perfil/completar');
            return;
        }
        
        // ============================================
        // TRANSACCIÓN ATÓMICA: TODO O NADA
        // ============================================
        
        try {
            /**
             * BEGIN TRANSACTION
             * 
             * A partir de aquí, todos los cambios en la BD son TEMPORALES.
             * Solo se harán permanentes si ejecutamos COMMIT.
             * Si hay un error, ejecutamos ROLLBACK y TODO se deshace.
             */
            $this->db->beginTransaction();
            
            // ----------------------------------------
            // 1. Actualizar contraseña y quitar flag
            // ----------------------------------------
            
            /**
             * Hashear la nueva contraseña con BCRYPT
             * 
             * RAZÓN TÉCNICA:
             * NUNCA guardamos contraseñas en texto plano en la BD.
             * password_hash() usa BCRYPT, un algoritmo diseñado para ser LENTO
             * (dificulta ataques de fuerza bruta).
             * 
             * El hash generado incluye el "salt" automáticamente, por lo que
             * no necesitamos generarlo manualmente.
             */
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            /**
             * Query con prepared statement
             * 
             * RAZÓN TÉCNICA:
             * Usamos prepare() + bind() en lugar de concatenar variables directamente
             * para prevenir SQL Injection.
             * 
             * MAL:  "UPDATE usuarios SET password = '$hashedPassword' WHERE id = $userId"
             * BIEN: prepare() + bind() (lo que hacemos aquí)
             */
            $this->db->query("UPDATE usuarios SET password = :password, requiere_cambio_clave = 0 WHERE id = :id");
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':id', $userId);
            
            if (!$this->db->execute()) {
                throw new Exception('Error al actualizar contraseña');
            }
            
            // ----------------------------------------
            // 2. Guardar preguntas de seguridad
            // ----------------------------------------
            
            /**
             * Iterar sobre las 3 preguntas y guardar cada respuesta
             * 
             * IMPORTANTE:
             * Las respuestas también se hashean con password_hash() para seguridad.
             * Usamos strtolower() y trim() para normalizar (evitar errores por mayúsculas/espacios).
             */
            foreach ($securityQuestions as $sq) {
                $this->db->query("
                    INSERT INTO usuarios_respuestas (usuario_id, pregunta_id, respuesta_hash)
                    VALUES (:usuario_id, :pregunta_id, :respuesta_hash)
                ");
                $this->db->bind(':usuario_id', $userId);
                $this->db->bind(':pregunta_id', $sq['question_id']);
                
                // Hashear respuesta (normalizada a minúsculas y sin espacios extra)
                $this->db->bind(':respuesta_hash', password_hash(strtolower(trim($sq['answer'])), PASSWORD_DEFAULT));
                
                if (!$this->db->execute()) {
                    throw new Exception('Error al guardar preguntas de seguridad');
                }
            }
            
            // ----------------------------------------
            // 3. Guardar o actualizar datos personales
            // ----------------------------------------
            
            /**
             * Verificar si el usuario ya tiene registro en datos_personales
             * 
             * RAZÓN:
             * - Si existe → UPDATE (actualizar datos)
             * - Si NO existe → INSERT (crear nuevo registro)
             */
            $this->db->query("SELECT id FROM datos_personales WHERE usuario_id = :uid");
            $this->db->bind(':uid', $userId);
            $exists = $this->db->single();
            
            if ($exists) {
                $this->db->query("
                    UPDATE datos_personales 
                    SET nombres = :nombres,
                        apellidos = :apellidos,
                        cargo = :cargo,
                        telefono = :telefono,
                        genero = :genero,
                        fecha_nacimiento = :fecha_nacimiento
                    WHERE usuario_id = :usuario_id
                ");
            } else {
                $this->db->query("
                    INSERT INTO datos_personales 
                    (usuario_id, nombres, apellidos, cargo, telefono, genero, fecha_nacimiento) 
                    VALUES (:usuario_id, :nombres, :apellidos, :cargo, :telefono, :genero, :fecha_nacimiento)
                ");
            }
            
            // Vincular parámetros (funciona tanto para UPDATE como INSERT)
            $datosPersonalesSinCedula = array_filter($datosPersonales, fn($k) => $k !== 'cedula', ARRAY_FILTER_USE_KEY);
            foreach ($datosPersonalesSinCedula as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }
            
            if (!$this->db->execute()) {
                throw new Exception('Error al guardar datos personales');
            }
            
            /**
             * COMMIT TRANSACTION
             * 
             * Si llegamos aquí, TODOS los pasos anteriores se ejecutaron correctamente.
             * Ahora hacemos los cambios permanentes en la BD.
             */
            $this->db->commit();
            
            // ----------------------------------------
            // 4. Actualizar sesión del usuario
            // ----------------------------------------
            
            /**
             * Actualizar variables de sesión
             * 
             * RAZÓN:
             * La sesión almacena datos del usuario para evitar consultar la BD en cada página.
             * Actualizamos requiere_cambio_clave y user_name para reflejar el nuevo estado.
             */
            Session::set('requiere_cambio_clave', 0);
            Session::set('user_name', trim($_POST['nombres']) . ' ' . trim($_POST['apellidos']));
            
            // Mensaje de éxito y redirección
            Session::setFlash('success', '¡Bienvenido a SGP! Tu perfil ha sido completado exitosamente');
            $this->redirect('/dashboard');
            
        } catch (Exception $e) {
            /**
             * ROLLBACK TRANSACTION
             * 
             * Si hubo un error en CUALQUIER paso, deshacemos TODOS los cambios.
             * Esto garantiza que la BD no quede en un estado inconsistente.
             * 
             * EJEMPLO DE INCONSISTENCIA SIN ROLLBACK:
             * - Se guardó la contraseña ✓
             * - Se guardaron las preguntas ✓
             * - Falló guardar datos personales ✗
             * → Usuario puede iniciar sesión pero no tiene perfil completo (ERROR)
             * 
             * CON ROLLBACK:
             * - Falla cualquier paso → Se deshace TODO
             * → Usuario mantiene su estado original y puede intentar de nuevo
             */
            $this->db->rollback();
            Session::setFlash('error', 'Error al guardar: ' . $e->getMessage());
            $this->redirect('/perfil/completar');
        }
    }

    /**
     * Ver Perfil del Usuario Autenticado
     * 
     * PROPÓSITO:
     * Mostrar los datos completos del perfil del usuario actual.
     * 
     * FLUJO DE DATOS:
     * Input: user_id desde Session
     * Proceso: Query con LEFT JOINs para obtener todos los datos
     * Output: Vista perfil/ver.php con array de datos del usuario
     */
    public function ver()
    {
        $userId = Session::get('user_id');
        $roleId = Session::get('role_id');
        
        // Query base con datos comunes
        $query = "
            SELECT 
                u.id,
                u.correo,
                u.cedula,
                u.rol_id,
                u.estado,
                u.created_at,
                CASE WHEN u.rol_id = 0 THEN 'Super Administrador' ELSE r.nombre END as rol_nombre,
                dp.nombres,
                dp.apellidos,
                dp.cargo,
                dp.telefono,
                dp.genero,
                dp.fecha_nacimiento";
        
        // Agregar campo específico para pasantes
        if ($roleId == 3) {
            $query .= ",
                u.avatar,
                dpa.institucion_procedencia,
                i.nombre                   as institucion_nombre,
                i.representante_nombre     as inst_rep_nombre,
                i.representante_cargo      as inst_rep_cargo,
                i.representante_correo     as inst_rep_correo,
                i.representante_telefono   as inst_rep_telefono,
                d_pas.nombre               as departamento_nombre";
        } else {
            $query .= ", u.avatar";
        }

        $query .= "
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id";

        // Agregar JOIN específico para pasantes e instituciones
        if ($roleId == 3) {
            $query .= " LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
                        LEFT JOIN instituciones i ON dpa.institucion_procedencia = i.id
                        LEFT JOIN departamentos d_pas ON d_pas.id = dpa.departamento_asignado_id";
        }
        
        $query .= " WHERE u.id = :uid";
        
        $this->db->query($query);
        $this->db->bind(':uid', $userId);
        $userData = $this->db->single();
        
        if (!$userData) {
            $this->redirect('/dashboard');
            return;
        }

        // Tarea 1: Asegurar que los nombres vienen de datos_personales
        $dataArray = (array) $userData;

        // Corregir visualización de Super Administrador (rol_id = 0)
        if ($dataArray['rol_id'] == 0) {
            $dataArray['rol_nombre'] = 'Super Administrador';
        }
        
        // Obtener preguntas de seguridad (para el nuevo modal)
        $preguntas = $this->userModel->getSecurityQuestions();

        // Obtener todas las instituciones para el modal si es pasante
        $instituciones = [];
        if ($roleId == 3) {
            $this->db->query("SELECT id, nombre FROM instituciones ORDER BY nombre ASC");
            $instituciones = $this->db->resultSet();
        }
        
        // Obtener respuestas actuales del usuario
        $this->db->query("
            SELECT ur.pregunta_id, ps.pregunta
            FROM usuarios_respuestas ur
            INNER JOIN preguntas_seguridad ps ON ur.pregunta_id = ps.id
            WHERE ur.usuario_id = :usuario_id
            ORDER BY ur.id ASC
        ");
        $this->db->bind(':usuario_id', $userId);
        $respuestas = $this->db->resultSet();
        
        $this->view('perfil/ver', [
            'title' => 'Mi Perfil',
            'user' => $dataArray,
            'preguntas' => $preguntas ?: [],
            'respuestas' => $respuestas ?: [],
            'instituciones' => $instituciones
        ]);
    }
    
    

    /**
     * Actualizar Datos del Perfil (AJAX)
     * 
     * PROPÓSITO:
     * Procesar la actualización de información personal del usuario.
     * Retorna JSON para manejo con Notyf.
     * 
     * @return void (JSON response)
     */
    public function actualizar()
    {
        // CRÍTICO: Forzar HTTP 200 para que JavaScript pueda leer la respuesta
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        // Errores van a error_log, nunca al output (protege integridad del JSON)
        ini_set('display_errors', '0');
        
        try {
            Session::start();

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // [FIX-A3] Verificar token CSRF — previene ataques Cross-Site Request Forgery
            CsrfHelper::verify();

            $userId = Session::get('user_id');

            if (!$userId) {
                throw new Exception('Usuario no autenticado');
            }
            
            $data = [
                'nombres' => trim(Validator::post('nombres')),
                'apellidos' => trim(Validator::post('apellidos')),
                'telefono' => trim(Validator::post('telefono')),
                'genero' => Validator::post('genero'),
                'fecha_nacimiento' => Validator::post('fecha_nacimiento'),
                'cargo' => trim(Validator::post('cargo'))
            ];
            
            // Validar campos obligatorios
        if (empty($data['nombres']) || empty($data['apellidos']) || empty($data['telefono'])) {
            throw new Exception('Los campos Nombres, Apellidos y Teléfono son obligatorios');
        }
        
        // ============================================
        // VALIDACIONES DE TIPO Y FORMATO
        // ============================================
        
        // Validar formato de teléfono (11 dígitos, formato venezolano)
        // Acepta formatos: 0414-1234567 o 04141234567
        if (!preg_match('/^0\d{3}-?\d{7}$/', $data['telefono'])) {
            throw new Exception('El teléfono debe tener formato válido (ej: 0414-1234567)');
        }
        
        // Validar que nombres solo contengan letras, espacios y acentos
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['nombres'])) {
            throw new Exception('Los nombres solo pueden contener letras');
        }
        
        // Validar que apellidos solo contengan letras, espacios y acentos
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['apellidos'])) {
            throw new Exception('Los apellidos solo pueden contener letras');
        }
        
        // Validar fecha de nacimiento (no futuro, no más de 100 años)
        if (!empty($data['fecha_nacimiento'])) {
            $fechaNac = strtotime($data['fecha_nacimiento']);
            $hoy = time();
            $hace100 = strtotime('-100 years');
            
            if ($fechaNac > $hoy) {
                throw new Exception('La fecha de nacimiento no puede ser futura');
            }
            
            if ($fechaNac < $hace100) {
                throw new Exception('La fecha de nacimiento no puede ser mayor a 100 años');
            }
        }
        
        // ============================================
        // VALIDACIONES DE LONGITUD MÁXIMA
        // ============================================
        
        if (strlen($data['nombres']) > 100) {
            throw new Exception('Los nombres no pueden exceder 100 caracteres');
        }
        
        if (strlen($data['apellidos']) > 100) {
            throw new Exception('Los apellidos no pueden exceder 100 caracteres');
        }
        

            
        // Logging mínimo (sin estructura interna de datos)
            error_log("[SGP-PERFIL] Actualización de perfil — user_id: $userId");
            
            $this->db->query("
                UPDATE datos_personales
                SET 
                    nombres = :nombres,
                    apellidos = :apellidos,
                    telefono = :telefono,
                    genero = :genero,
                    fecha_nacimiento = :fecha_nacimiento,
                    cargo = :cargo
                WHERE usuario_id = :usuario_id
            ");
            
            $this->db->bind(':nombres', $data['nombres']);
            $this->db->bind(':apellidos', $data['apellidos']);
            $this->db->bind(':telefono', $data['telefono']);
            $this->db->bind(':genero', $data['genero'] ?: null);
            $this->db->bind(':fecha_nacimiento', $data['fecha_nacimiento'] ?: null);
            $this->db->bind(':cargo', $data['cargo'] ?: null);
            $this->db->bind(':usuario_id', $userId);
            
            $resultado = $this->db->execute();
            
            if (!$resultado) {
                throw new Exception('Error al ejecutar la consulta SQL. Verifica los logs del servidor.');
            }
            
            // Si es pasante, actualizar institución (UPSERT: UPDATE o INSERT)
            if (Session::get('role_id') == 3) {
                $institucion = trim(Validator::post('institucion_procedencia'));
                if (!empty($institucion)) {
                    try {
                        // Intentar UPDATE primero
                        $this->db->query("
                            UPDATE datos_pasante
                            SET institucion_procedencia = :institucion
                            WHERE usuario_id = :usuario_id
                        ");
                        $this->db->bind(':institucion', $institucion);
                        $this->db->bind(':usuario_id', $userId);
                        $this->db->execute();
                        
                        // Si no se actualizó ninguna fila, el registro no existe → INSERT
                        if ($this->db->rowCount() == 0) {
                            error_log("=== REGISTRO NO EXISTE EN datos_pasante, CREANDO... ===");
                            $this->db->query("
                                INSERT INTO datos_pasante (usuario_id, institucion_procedencia)
                                VALUES (:usuario_id, :institucion)
                            ");
                            $this->db->bind(':usuario_id', $userId);
                            $this->db->bind(':institucion', $institucion);
                            $this->db->execute();
                            error_log("=== REGISTRO CREADO EXITOSAMENTE ===");
                        } else {
                            error_log("=== INSTITUCIÓN ACTUALIZADA: $institucion ===");
                        }
                    } catch (Exception $e) {
                        error_log("Error actualizando institución: " . $e->getMessage());
                        // No lanzar excepción, el perfil principal ya se guardó
                    }
                }
            }
            
            // Actualizar nombre en sesión
            Session::set('user_name', $data['nombres'] . ' ' . $data['apellidos']);
            
            // Registrar en bitácora
            try {
                require_once '../app/models/AuditModel.php';
                AuditModel::log('UPDATE_PROFILE', 'datos_personales', $userId, [
                    'campos_actualizados' => array_keys($data)
                ]);
            } catch (Exception $e) {
                error_log("Error en auditoría: " . $e->getMessage());
                // No lanzar excepción, el perfil ya se guardó
            }
            
            error_log("=== PERFIL ACTUALIZADO EXITOSAMENTE ===");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Perfil actualizado exitosamente'
            ]);
            
        } catch (PDOException $e) {
            // [FIX-C2] Detalle interno solo en log — nunca exponer mensaje de BD al cliente
            error_log('[SGP-PERFIL] PDOException en actualizarPerfil() | code=' . $e->getCode() . ' | ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar el perfil. Intente de nuevo.'
            ]);
        } catch (Throwable $e) {
            // [FIX-C2] Mensaje genérico al cliente — detalles completos solo en log del servidor
            error_log('[SGP-PERFIL] Throwable en actualizarPerfil() | type=' . get_class($e) . ' | ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor. Intente de nuevo.'
            ]);
        }
        
        exit;
    }

    /**
     * Cambiar Contraseña (AJAX)
     * 
     * PROPÓSITO:
     * Permitir al usuario cambiar su contraseña validando la actual.
     * Retorna JSON para manejo con Notyf.
     * 
     * SEGURIDAD:
     * - Verifica contraseña actual con password_verify()
     * - Valida requisitos de la nueva contraseña
     * - Hashea la nueva contraseña con BCRYPT
     * 
     * @return void (JSON response)
     */
    public function cambiar_password()
    {
        Session::start();

        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-A3] Verificar token CSRF — previene ataques Cross-Site Request Forgery
        CsrfHelper::verify();

        $userId = Session::get('user_id');
        
        // Capturar datos
        $passwordActual = Validator::post('password_actual');
        $passwordNueva = Validator::post('password_nueva');
        $passwordConfirmar = Validator::post('password_confirmar');
        
        // Validar campos
        if (empty($passwordActual) || empty($passwordNueva) || empty($passwordConfirmar)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Todos los campos son obligatorios'
            ]);
            exit;
        }
        
        // Validar que coincidan
        if ($passwordNueva !== $passwordConfirmar) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Las contraseñas nuevas no coinciden'
            ]);
            exit;
        }
        
        // Validar longitud mínima
        if (strlen($passwordNueva) < 8) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ]);
            exit;
        }
        
        // Obtener usuario actual
        $user = $this->userModel->findById($userId);
        
        // Verificar contraseña actual
        if (!password_verify($passwordActual, $user['password'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'La contraseña actual es incorrecta'
            ]);
            exit;
        }
        
        // Validar que la nueva contraseña sea diferente a la actual
        if ($passwordActual === $passwordNueva) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'La nueva contraseña debe ser diferente a la actual'
            ]);
            exit;
        }
        
        // Validar requisitos de seguridad de la nueva contraseña
        if (strlen($passwordNueva) < 8 || 
            !preg_match('/[A-Z]/', $passwordNueva) || 
            !preg_match('/[a-z]/', $passwordNueva) || 
            !preg_match('/[0-9]/', $passwordNueva) ||
            !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $passwordNueva)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales'
            ]);
            exit;
        }
        
        // Actualizar contraseña
        if ($this->userModel->updatePassword($userId, $passwordNueva)) {
            // Registrar en bitácora
            require_once '../app/models/AuditModel.php';
            AuditModel::log('CHANGE_PASSWORD', 'usuarios', $userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Contraseña cambiada exitosamente'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Error al cambiar la contraseña'
            ]);
        }
        
        exit;
    }

    /**
     * Vista: Gestionar Preguntas de Seguridad
     * 
     * PROPÓSITO:
     * Mostrar formulario para que el usuario actualice sus preguntas de seguridad.
     */
    public function gestionar_preguntas()
    {
        Session::start();
        $userId = Session::get('user_id');
        
        // Obtener preguntas disponibles
        $preguntas = $this->userModel->getSecurityQuestions();
        
        // CORRECCIÓN PHP: Validar que preguntas no sea false
        if ($preguntas === false || !is_array($preguntas)) {
            $preguntas = [];
        }
        
        // Obtener respuestas actuales del usuario
        $this->db->query("
            SELECT ur.id, ur.pregunta_id, ps.pregunta
            FROM usuarios_respuestas ur
            INNER JOIN preguntas_seguridad ps ON ur.pregunta_id = ps.id
            WHERE ur.usuario_id = :usuario_id
            ORDER BY ur.id ASC
        ");
        $this->db->bind(':usuario_id', $userId);
        $respuestas = $this->db->resultSet();
        
        // CORRECCIÓN PHP: Validar que respuestas no sea false
        if ($respuestas === false || !is_array($respuestas)) {
            $respuestas = [];
        }
        
        $this->view('perfil/gestionar_preguntas', [
            'title' => 'Gestionar Preguntas de Seguridad',
            'preguntas' => $preguntas,
            'respuestas' => $respuestas
        ]);
    }

    /**
     * Actualizar Preguntas de Seguridad (AJAX)
     * 
     * PROPÓSITO:
     * Procesar la actualización de preguntas de seguridad del usuario.
     * 
     * SEGURIDAD:
     * - Hashea las respuestas con password_hash()
     * - Valida que no se repitan preguntas
     * - Usa transacción para garantizar atomicidad
     */
    public function actualizar_preguntas()
    {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-A3] Verificar token CSRF — previene ataques Cross-Site Request Forgery
        CsrfHelper::verify();

        $userId = Session::get('user_id');
        
        // Capturar datos
        $pregunta1 = Validator::post('pregunta_1');
        $respuesta1 = Validator::post('respuesta_1');
        $pregunta2 = Validator::post('pregunta_2');
        $respuesta2 = Validator::post('respuesta_2');
        $pregunta3 = Validator::post('pregunta_3');
        $respuesta3 = Validator::post('respuesta_3');
        
        // Validar que no se repitan preguntas
        if ($pregunta1 == $pregunta2 || $pregunta1 == $pregunta3 || $pregunta2 == $pregunta3) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No puedes seleccionar la misma pregunta dos veces'
            ]);
            exit;
        }
        
        // Validar que todos los campos estén completos
        if (empty($pregunta1) || empty($respuesta1) || empty($pregunta2) || empty($respuesta2) || empty($pregunta3) || empty($respuesta3)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ]);
            exit;
        }

        // [FIX-V-11] AuthController::register() ya exige respuestas distintas entre sí
        // y con longitud mínima; esta ruta (edición desde Perfil) no lo hacía.
        $r1_lower = strtolower(trim($respuesta1));
        $r2_lower = strtolower(trim($respuesta2));
        $r3_lower = strtolower(trim($respuesta3));
        if ($r1_lower === $r2_lower || $r1_lower === $r3_lower || $r2_lower === $r3_lower) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Las respuestas de seguridad deben ser diferentes'
            ]);
            exit;
        }
        if (strlen($respuesta1) < 3 || strlen($respuesta2) < 3 || strlen($respuesta3) < 3) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Las respuestas deben tener al menos 3 caracteres'
            ]);
            exit;
        }

        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Eliminar respuestas anteriores
            $this->db->query("DELETE FROM usuarios_respuestas WHERE usuario_id = :usuario_id");
            $this->db->bind(':usuario_id', $userId);
            $this->db->execute();
            
            // Insertar nuevas respuestas (hasheadas)
            $preguntas = [
                ['pregunta_id' => $pregunta1, 'respuesta' => $respuesta1],
                ['pregunta_id' => $pregunta2, 'respuesta' => $respuesta2],
                ['pregunta_id' => $pregunta3, 'respuesta' => $respuesta3]
            ];
            
            foreach ($preguntas as $pregunta) {
                $this->db->query("
                    INSERT INTO usuarios_respuestas (usuario_id, pregunta_id, respuesta_hash)
                    VALUES (:usuario_id, :pregunta_id, :respuesta_hash)
                ");
                $this->db->bind(':usuario_id', $userId);
                $this->db->bind(':pregunta_id', $pregunta['pregunta_id']);
                $this->db->bind(':respuesta_hash', password_hash(strtolower(trim($pregunta['respuesta'])), PASSWORD_DEFAULT));
                $this->db->execute();
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            // Registrar en bitácora
            require_once '../app/models/AuditModel.php';
            AuditModel::log('UPDATE_SECURITY_QUESTIONS', 'usuarios_respuestas', $userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Preguntas de seguridad actualizadas exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
            error_log('[SGP-PERFIL] actualizar_preguntas error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar las preguntas de seguridad.'
            ]);
        }

        exit;
    }

    /**
     * POST /perfil/subirFoto
     * Sube o cambia la foto de perfil del usuario autenticado.
     * Acepta JPG, PNG, WEBP — max 2 MB.
     * Guarda en /sgp/public/img/avatars/{user_id}.{ext}
     * Actualiza columna `avatar` en tabla `usuarios`.
     */
    public function subirFoto(): void
    {
        header('Content-Type: application/json');
        $userId = (int)Session::get('user_id');
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Sin sesión']);
            exit;
        }

        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
            exit;
        }

        $file    = $_FILES['foto'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'La imagen no puede superar 2 MB']);
            exit;
        }

        // Validar tipo real con finfo (no confiar en extension)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mime     = $finfo->file($file['tmp_name']);
        $allowed  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!array_key_exists($mime, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato no permitido. Usa JPG, PNG o WEBP']);
            exit;
        }

        $ext     = $allowed[$mime];
        $dirPath = APPROOT . '/../public/img/avatars';

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        // Eliminar foto anterior si existe
        foreach (['jpg','png','webp'] as $e) {
            $old = $dirPath . '/' . $userId . '.' . $e;
            if (file_exists($old)) unlink($old);
        }

        $destFile = $dirPath . '/' . $userId . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $destFile)) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']);
            exit;
        }

        $avatarVal = $userId . '.' . $ext;

        try {
            $this->db->query("UPDATE usuarios SET avatar = :avatar WHERE id = :id");
            $this->db->bind(':avatar', $avatarVal);
            $this->db->bind(':id',     $userId);
            $this->db->execute();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
            exit;
        }

        // Actualizar sesión para que el header muestre la foto de inmediato
        Session::set('user_avatar', $avatarVal);

        echo json_encode([
            'success' => true,
            'avatar'  => URLROOT . '/img/avatars/' . $avatarVal . '?v=' . time(),
            'message' => 'Foto actualizada correctamente'
        ]);
        exit;
    }
}
