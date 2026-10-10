<?php
/**
 * =====================================================
 * SNIPPET 1: Registro Seguro de Respuestas de Seguridad
 * =====================================================
 * 
 * Descripción:
 * Este snippet procesa y almacena de forma segura las respuestas
 * de seguridad durante el registro de usuario.
 * 
 * Características:
 * - Normalización de respuestas (lowercase + trim)
 * - Encriptación con bcrypt (password_hash)
 * - Almacenamiento en tabla usuarios_respuestas
 * 
 * Uso:
 * Incluir este código en el controlador de registro (RegisterController.php)
 */

/**
 * Guarda la respuesta de seguridad de forma encriptada
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param int $pregunta_id ID de la pregunta de seguridad
 * @param string $respuesta Respuesta en texto plano
 * @return bool True si se guardó correctamente, False en caso de error
 */
function guardarRespuestaSeguridad($pdo, $usuario_id, $pregunta_id, $respuesta) {
    try {
        // PASO 1: Normalizar la respuesta
        // - Convertir a minúsculas para evitar problemas de case-sensitivity
        // - Eliminar espacios en blanco al inicio y final
        $respuesta_normalizada = strtolower(trim($respuesta));
        
        // Validar que la respuesta no esté vacía después de normalizar
        if (empty($respuesta_normalizada)) {
            throw new Exception("La respuesta de seguridad no puede estar vacía");
        }
        
        // PASO 2: Encriptar la respuesta con bcrypt
        // Usamos password_hash() que genera un hash seguro con salt automático
        $respuesta_hash = password_hash($respuesta_normalizada, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // PASO 3: Guardar en la base de datos
        $sql = "INSERT INTO usuarios_respuestas (usuario_id, pregunta_id, respuesta_hash) 
                VALUES (:usuario_id, :pregunta_id, :respuesta_hash)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':pregunta_id' => $pregunta_id,
            ':respuesta_hash' => $respuesta_hash
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        // Log del error (en producción usar un sistema de logs apropiado)
        error_log("Error al guardar respuesta de seguridad: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error de validación: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica una respuesta de seguridad
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param int $pregunta_id ID de la pregunta de seguridad
 * @param string $respuesta Respuesta proporcionada por el usuario
 * @return bool True si la respuesta es correcta, False en caso contrario
 */
function verificarRespuestaSeguridad($pdo, $usuario_id, $pregunta_id, $respuesta) {
    try {
        // Normalizar la respuesta de la misma forma que al guardar
        $respuesta_normalizada = strtolower(trim($respuesta));
        
        // Obtener el hash almacenado
        $sql = "SELECT respuesta_hash FROM usuarios_respuestas 
                WHERE usuario_id = :usuario_id AND pregunta_id = :pregunta_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':pregunta_id' => $pregunta_id
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            return false; // No se encontró la respuesta
        }
        
        // Verificar el hash usando password_verify
        return password_verify($respuesta_normalizada, $resultado['respuesta_hash']);
        
    } catch (PDOException $e) {
        error_log("Error al verificar respuesta de seguridad: " . $e->getMessage());
        return false;
    }
}

/**
 * =====================================================
 * EJEMPLO DE USO EN CONTROLADOR DE REGISTRO
 * =====================================================
 */

/*
// En RegisterController.php - Método register()

public function register() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Validar y sanitizar datos
        $nombre_completo = trim($_POST['nombre_completo']);
        $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $pregunta_id = (int)$_POST['pregunta_seguridad'];
        $respuesta_seguridad = $_POST['respuesta_seguridad'];
        
        // Validaciones básicas
        if (empty($nombre_completo) || empty($correo) || empty($password)) {
            // Manejar error
            return;
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // 1. Insertar usuario
            $sql = "INSERT INTO usuarios (nombre_completo, correo, password, rol_id, perfil_completado) 
                    VALUES (:nombre, :correo, :password, 3, 0)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre_completo,
                ':correo' => $correo,
                ':password' => $password_hash
            ]);
            
            $usuario_id = $this->db->lastInsertId();
            
            // 2. Guardar respuesta de seguridad (USANDO LA FUNCIÓN)
            if (!guardarRespuestaSeguridad($this->db, $usuario_id, $pregunta_id, $respuesta_seguridad)) {
                throw new Exception("Error al guardar respuesta de seguridad");
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            // Redirigir al login
            header('Location: /login?registro=exitoso');
            exit;
            
        } catch (Exception $e) {
            // Revertir cambios
            $this->db->rollBack();
            error_log("Error en registro: " . $e->getMessage());
            // Mostrar error al usuario
        }
    }
}
*/

/**
 * =====================================================
 * SNIPPET 2: Sistema de Login "Semáforo"
 * =====================================================
 * 
 * Descripción:
 * Middleware de redirección condicional basado en el estado del usuario
 * 
 * Flujo de Decisión:
 * 1. Verificar credenciales
 * 2. Verificar rol del usuario
 * 3. Si es Pasante:
 *    - ¿Perfil completado? NO → completar_datos.php
 *    - ¿Departamento asignado? NO → espera_asignacion.php
 *    - Todo OK → dashboard.php
 * 4. Si es Tutor/Administrador → dashboard.php
 */

/**
 * Verifica el acceso del usuario y retorna la ruta de redirección apropiada
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $usuario_id ID del usuario autenticado
 * @return array ['puede_acceder' => bool, 'ruta' => string, 'mensaje' => string]
 */
function verificarAccesoUsuario($pdo, $usuario_id) {
    try {
        // Obtener información del usuario con su rol
        $sql = "SELECT u.id, u.rol_id, u.perfil_completado, u.departamento_id, r.nombre as rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.id = :usuario_id AND u.activo = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return [
                'puede_acceder' => false,
                'ruta' => '/login',
                'mensaje' => 'Usuario no encontrado o inactivo'
            ];
        }
        
        // LÓGICA DEL SEMÁFORO
        
        // Caso 1: Usuario es Pasante (rol_id = 3)
        if ($usuario['rol_id'] == 3) {
            
            // Verificar si completó el perfil
            if ($usuario['perfil_completado'] == 0) {
                return [
                    'puede_acceder' => false,
                    'ruta' => '/completar-datos',
                    'mensaje' => 'Debes completar tu perfil antes de continuar'
                ];
            }
            
            // Verificar si tiene departamento asignado
            if ($usuario['departamento_id'] === null) {
                return [
                    'puede_acceder' => false,
                    'ruta' => '/espera-asignacion',
                    'mensaje' => 'Tu tutor debe asignarte un departamento'
                ];
            }
            
            // Todo está completo, puede acceder
            return [
                'puede_acceder' => true,
                'ruta' => '/dashboard',
                'mensaje' => 'Acceso permitido'
            ];
        }
        
        // Caso 2: Usuario es Tutor (rol_id = 2) o Administrador (rol_id = 1)
        // Acceso directo al dashboard
        return [
            'puede_acceder' => true,
            'ruta' => '/dashboard',
            'mensaje' => 'Acceso permitido'
        ];
        
    } catch (PDOException $e) {
        error_log("Error al verificar acceso: " . $e->getMessage());
        return [
            'puede_acceder' => false,
            'ruta' => '/login',
            'mensaje' => 'Error del sistema'
        ];
    }
}

/**
 * =====================================================
 * EJEMPLO DE USO EN CONTROLADOR DE LOGIN
 * =====================================================
 */

/*
// En LoginController.php - Método login()

public function login() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        // Buscar usuario por correo
        $sql = "SELECT id, password FROM usuarios WHERE correo = :correo AND activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar credenciales
        if ($usuario && password_verify($password, $usuario['password'])) {
            
            // Guardar en sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['autenticado'] = true;
            
            // APLICAR SISTEMA SEMÁFORO
            $acceso = verificarAccesoUsuario($this->db, $usuario['id']);
            
            if ($acceso['puede_acceder']) {
                // Redirigir al dashboard
                header('Location: ' . $acceso['ruta']);
                exit;
            } else {
                // Redirigir a la página correspondiente según el estado
                $_SESSION['mensaje_info'] = $acceso['mensaje'];
                header('Location: ' . $acceso['ruta']);
                exit;
            }
            
        } else {
            // Credenciales incorrectas
            $_SESSION['error'] = 'Correo o contraseña incorrectos';
            header('Location: /login');
            exit;
        }
    }
}
*/

/**
 * =====================================================
 * MIDDLEWARE PARA PROTEGER RUTAS
 * =====================================================
 * 
 * Usar este middleware en todas las páginas protegidas
 * para verificar que el usuario tenga acceso
 */

/*
// En el archivo de middleware o al inicio de páginas protegidas

session_start();

// Verificar si está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login');
    exit;
}

// Verificar acceso con el sistema semáforo
require_once 'path/to/this/file.php';
$db = Database::getInstance()->getConnection();
$acceso = verificarAccesoUsuario($db, $_SESSION['usuario_id']);

// Si la ruta actual no es la que debería estar, redirigir
$ruta_actual = $_SERVER['REQUEST_URI'];
if (!$acceso['puede_acceder'] && $ruta_actual !== $acceso['ruta']) {
    $_SESSION['mensaje_info'] = $acceso['mensaje'];
    header('Location: ' . $acceso['ruta']);
    exit;
}
*/
