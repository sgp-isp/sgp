<?php
class Session
{
    public static function start($checkInactivity = true)
    {
        if (session_status() === PHP_SESSION_NONE) {
            // [FIX-P2] Forzar flags de seguridad en la cookie PHPSESSID antes de session_start()
            session_set_cookie_params([
                'lifetime' => 0,                              // Cookie de sesión (expira al cerrar el navegador)
                'path'     => '/',
                'domain'   => $_SERVER['HTTP_HOST'] ?? '',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // Solo HTTPS si está disponible
                'httponly' => true,                           // Bloquea acceso desde JavaScript (anti-XSS)
                'samesite' => 'Lax'                           // Protección CSRF básica
            ]);

            $urlActual = $_SERVER['REQUEST_URI'] ?? '';

            // Si la ruta NO contiene '/public/kiosco', iniciamos sesión normalmente
            if (strpos($urlActual, '/public/kiosco') === false) {
                session_start();
            }
        }
        
        if ($checkInactivity) {
            self::checkInactivity(); 
        }
    }

    public static function destroy()
    {
        self::start(false); // Romper bucle de recursión: no re-chequear inactividad
        session_destroy();
    }

    public static function regenerate()
    {
        session_regenerate_id(true);
    }

    // Set a session variable
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    // Get a session variable
    public static function get($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // Check if user is logged in
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    // Flash message helper
    // Usage: Session::flash('register_success', 'You are now registered');
    // Display: echo Session::flash('register_success');
    public static function flash($name = '', $message = '', $class = 'alert alert-success')
    {
        if (!empty($name)) {
            if (!empty($message) && empty($_SESSION[$name])) {
                if (!empty($_SESSION[$name . '_class'])) {
                    unset($_SESSION[$name . '_class']);
                }
                $_SESSION[$name] = $message;
                $_SESSION[$name . '_class'] = $class;
            } elseif (empty($message) && !empty($_SESSION[$name])) {
                $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
                echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
                unset($_SESSION[$name]);
                unset($_SESSION[$name . '_class']);
            }
        }
    }
    
    /**
     * Establecer un mensaje flash (simplificado)
     * 
     * @param string $type Tipo de mensaje: 'success', 'error', 'warning', 'info'
     * @param string $message Mensaje a mostrar
     * @return void
     */
    public static function setFlash($type, $message)
    {
        $_SESSION['flash_' . $type] = $message;
    }
    
    /**
     * Obtener y eliminar un mensaje flash
     * 
     * @param string $type Tipo de mensaje
     * @return string|null
     */
    public static function getFlash($type)
    {
        if (isset($_SESSION['flash_' . $type])) {
            $message = $_SESSION['flash_' . $type];
            unset($_SESSION['flash_' . $type]);
            return $message;
        }
        return null;
    }
    
    /**
     * Verificar si existe un mensaje flash sin consumirlo
     * 
     * @param string $type Tipo de mensaje
     * @return bool
     */
    public static function hasFlash($type)
    {
        return isset($_SESSION['flash_' . $type]);
    }

    /**
     * Generar un token CSRF seguro
     * 
     * @return string
     */
    public static function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validar el token CSRF recibido
     * 
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken($token)
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Verifica inactividad de sesión y la destruye si supera el timeout.
     * Devuelve JSON 401 para peticiones AJAX/PJAX; redirect para el resto.
     * SGP-FIX-v2 [2] aplicado
     */
    public static function checkInactivity(): void
    {
        $timeout = defined('SESSION_TIMEOUT_SECONDS') ? SESSION_TIMEOUT_SECONDS : 600;

        // Solo verificar si hay sesión activa con usuario autenticado
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        if (isset($_SESSION['last_activity'])) {
            $inactivo = time() - $_SESSION['last_activity'];
            if ($inactivo > $timeout) {
                self::destroy();
                $esPjax = !empty($_SERVER['HTTP_X_PJAX'])
                       || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
                if ($esPjax) {
                    http_response_code(401);
                    header('Content-Type: application/json');
                    $loginUrl = (defined('URLROOT') ? URLROOT : '') . '/auth/login';
                    echo json_encode([
                        'success'  => false,
                        'reason'   => 'session_expired',
                        'redirect' => $loginUrl
                    ]);
                    exit;
                }
                // Iniciar nueva sesión limpia para dejar el flash de inactividad
                // antes de redirigir, de modo que login.php pueda mostrarlo.
                session_start();
                $_SESSION['flash_inactividad'] = 'Tu sesión ha concluido por inactividad.';
                session_write_close();

                $loginUrl = (defined('URLROOT') ? URLROOT : '') . '/auth/login';
                header('Location: ' . $loginUrl);
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }
}
