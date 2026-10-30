<?php
/**
 * AuthMiddleware - Middleware de Autenticación
 * 
 * Funciones:
 * - Verificar sesión activa
 * - Control de inactividad (30 minutos)
 * - Redirección automática a login
 * - Compatible con AJAX
 */
class AuthMiddleware
{
    // [FIX-P3] Eliminada constante INACTIVITY_LIMIT (1800 s) — era distinta al valor real de config.php
    // (SESSION_TIMEOUT_SECONDS = 1500 s). La fuente de verdad del timeout es config.php.
    // Session::start() → Session::checkInactivity() ya gestiona el timeout y actualiza last_activity.

    /**
     * Verificar si el usuario tiene sesión activa
     * Si no tiene sesión o está inactivo, redirige al login.
     *
     * La verificación de inactividad ya fue ejecutada por Session::start() → checkInactivity().
     * Este método solo valida que user_id exista tras ese check.
     *
     * @param bool $isAjax Si la petición es AJAX
     * @return void
     */
    public static function verificarSesion($isAjax = false)
    {
        // [FIX-P3] Session::start() ya invoca checkInactivity() internamente con SESSION_TIMEOUT_SECONDS
        Session::start();

        // Verificar si existe sesión de usuario (si expiró, checkInactivity ya destruyó la sesión y redirigió)
        if (!Session::get('user_id')) {
            self::redirectToLogin($isAjax, 'Debe iniciar sesión');
            return;
        }
        // [FIX-P3] Eliminados isInactive() y updateActivity() — lógica duplicada y con timeout incorrecto.
        // Session::checkInactivity() ya actualizó $_SESSION['last_activity'] en esta misma request.
    }
    
    /**
     * Redirigir al login (compatible con AJAX)
     * 
     * @param bool $isAjax
     * @param string $message
     * @return void
     */
    private static function redirectToLogin($isAjax, $message)
    {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'redirect' => URLROOT . '/auth/login',
                'session_expired' => true
            ]);
            exit;
        }
        
        // Redirección normal
        Session::setFlash('error', $message);
        header('Location: ' . URLROOT . '/auth/login');
        exit;
    }
    
    /**
     * Verificar si la petición es AJAX
     * 
     * @return bool
     */
    public static function isAjaxRequest()
    {
        // Check X-Requested-With (jQuery/axios style)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        // Check Accept header (fetch() style)
        if (isset($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        return false;
    }
    
    /**
     * Verificar sesión automáticamente detectando AJAX
     * 
     * @return void
     */
    public static function require()
    {
        // Detectar si es una petición fetch() con nuestros headers personalizados de Grid.js
        $isAjax = self::isAjaxRequest();
        self::verificarSesion($isAjax);
    }
    
    /**
     * Verificar estado del usuario - Sistema "La Jaula"
     * Fuerza cambio de contraseña y verifica completitud del perfil
     * 
     * CAMBIO ARQUITECTÓNICO (v3.0 - 2026-02-02):
     * - Antes: Redirigía a /perfil/completar (PerfilController)
     * - Ahora: Redirige a /wizard/index (WizardController dedicado)
     * 
     * RAZÓN: Separación de responsabilidades (SRP - SOLID)
     * - WizardController: SOLO seguridad inicial
     * - PerfilController: SOLO gestión de perfil
     * 
     * @return void
     */
    public static function verificarEstado(): void
    {
        Session::start();

        // Si no hay sesión, no hacer nada
        if (!Session::get('user_id')) {
            return;
        }

        // NIVEL 0: Verificar que la cuenta siga activa en BD (cache 60 s para no golpear la BD en cada request)
        $now = time();
        if (!Session::get('_estado_check_ts') || ($now - (int)Session::get('_estado_check_ts')) > 60) {
            try {
                $db = Database::getInstance();
                $db->query("SELECT estado FROM usuarios WHERE id = :uid LIMIT 1");
                $db->bind(':uid', Session::get('user_id'));
                $row = $db->single();
                Session::set('_estado_check_ts', $now);

                if ($row && $row->estado === 'inactivo') {
                    // Cuenta desactivada mientras el usuario tenía sesión abierta
                    $uid    = Session::get('user_id');
                    $roleId = (int)Session::get('role_id');
                    Session::destroy();
                    Session::start();

                    $msg = 'Tu sesión fue cerrada porque tu cuenta fue desactivada.';
                    if ($roleId === 3) {
                        // Consultar si fue por fin de período o retiro
                        try {
                            $db->query("
                                SELECT estado_pasantia FROM datos_pasante
                                WHERE usuario_id = :uid
                                ORDER BY FIELD(estado_pasantia, 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, id DESC
                                LIMIT 1
                            ");
                            $db->bind(':uid', $uid);
                            $dp = $db->single();
                            if ($dp && $dp->estado_pasantia === 'Finalizado') {
                                $msg = 'Tu período de pasantía ha finalizado. Tu sesión fue cerrada automáticamente. '
                                     . 'Comunícate con el administrador para más información.';
                            } elseif ($dp && $dp->estado_pasantia === 'Retirado') {
                                $msg = 'Tu condición de pasantía ha cambiado a Retirado. Tu sesión fue cerrada automáticamente.';
                            }
                        } catch (\Throwable $e) {}
                    }

                    if (self::isAjaxRequest()) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success'          => false,
                            'message'          => $msg,
                            'redirect'         => URLROOT . '/auth/login',
                            'session_expired'  => true,
                        ]);
                        exit;
                    }

                    Session::setFlash('login_error', $msg);
                    header('Location: ' . URLROOT . '/auth/login');
                    exit;
                }
            } catch (\Throwable $e) {
                // Si la BD no responde, no bloqueamos al usuario
            }
        }

        // NIVEL 1: LA JAULA - Cambio de Clave Obligatorio O Perfil Incompleto
        if (Session::get('requiere_cambio_clave') == 1 || Session::get('perfil_completado') === false) {
            /**
             * WHITELIST: Rutas permitidas durante "La Jaula"
             * 
             * ACTUALIZADO v3.0: Cambiadas rutas de /perfil/* a /wizard/*
             */
            $allowedPaths = [
                '/auth/logout',           // Permitir cerrar sesión
                '/wizard/index',          // Vista del wizard (NUEVO)
                '/wizard/procesar'        // Procesamiento del wizard (NUEVO)
            ];
            
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $currentPath = str_replace('/proyecto_sgp/sgp/public', '', $currentPath);
            
            // Allow access to logout and wizard completion
            $isAllowed = false;
            foreach ($allowedPaths as $path) {
                if (strpos($currentPath, $path) !== false) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                /**
                 * REDIRECCIÓN FORZOSA (La Jaula)
                 * 
                 * CAMBIO v3.0: /perfil/completar → /wizard/index
                 * Ahora redirige al WizardController dedicado
                 * 
                 * Si es AJAX/JSON (Grid.js, fetch, etc.) devolver JSON en vez de HTML
                 */
                if (self::isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Debe completar el wizard de configuración',
                        'redirect' => URLROOT . '/wizard/index'
                    ]);
                    exit;
                }
                header('Location: ' . URLROOT . '/wizard/index');
                exit;
            }
            // Si está en ruta permitida, dejar continuar
            return;
        }
        
        // NIVEL 2: Verificar que admin/tutor tenga cargo registrado.
        // Se ejecuta una sola vez por sesión (controlado por 'cargo_verificado').
        // Captura usuarios que completaron el wizard antes de que se exigiera el cargo.
        if (!Session::get('cargo_verificado')) {
            $rolId = (int)Session::get('role_id');
            if ($rolId !== 3 && $rolId > 0) { // solo admin (1) y tutor (2)
                try {
                    $db = Database::getInstance();
                    $db->query("SELECT cargo FROM datos_personales WHERE usuario_id = :uid LIMIT 1");
                    $db->bind(':uid', Session::get('user_id'));
                    $dp = $db->single();

                    if (!$dp || empty(trim($dp->cargo ?? ''))) {
                        Session::set('perfil_completado', false);

                        $allowedPaths = ['/auth/logout', '/wizard/index', '/wizard/procesar'];
                        $currentPath  = str_replace('/proyecto_sgp/sgp/public', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
                        $isAllowed    = false;
                        foreach ($allowedPaths as $path) {
                            if (strpos($currentPath, $path) !== false) { $isAllowed = true; break; }
                        }

                        if (!$isAllowed) {
                            if (self::isAjaxRequest()) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => 'Complete su perfil profesional', 'redirect' => URLROOT . '/wizard/index']);
                                exit;
                            }
                            header('Location: ' . URLROOT . '/wizard/index');
                            exit;
                        }
                        return;
                    }
                } catch (Exception $e) {
                    // Si falla la consulta no bloqueamos al usuario
                }
            }

            Session::set('cargo_verificado', true);
        }
    }
}
