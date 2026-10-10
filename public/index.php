<?php
// Configuración global de Zona Horaria para Venezuela (-04:00)
date_default_timezone_set('America/Caracas');

// [FIX-P1] Silenciar display_errors para evitar que Notices/Warnings corrompan respuestas JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL); // Mantener E_ALL para que set_error_handler los capture en log

// NOTE: Content-Type is NOT set globally here — each controller/endpoint sets its own headers.
//       Previously, the `header('Content-Type: text/html')` here was overriding application/json endpoints.
require_once '../app/config/config.php';

// Define App Root
define('APPROOT', dirname(dirname(__FILE__)) . '/app');

// Auto-detect URLROOT for mobile access support
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('URLROOT', $protocol . $host . $scriptDir);


require_once '../app/core/Database.php';
require_once '../app/core/Session.php';
require_once '../app/helpers/CsrfHelper.php'; // SGP-FIX-v2 [4] aplicado
require_once '../app/core/Validator.php';
require_once '../app/core/Controller.php';
require_once '../app/core/AuthMiddleware.php';
require_once '../app/core/RoleMiddleware.php';
require_once '../app/helpers/CacheControl.php';
require_once '../app/libraries/UrlSecurity.php';
require_once '../app/models/NotificationModel.php'; // Modelo de notificaciones
require_once '../app/models/AuditModel.php'; // Modelo de auditoría (Global)
require_once '../app/models/FeriadoModel.php'; // Modelo de días feriados
require_once '../app/core/App.php';

// ============================================
// SGP — Manejador global de errores no capturados
// ============================================
set_exception_handler(function (\Throwable $e) {
    error_log('[SGP-FATAL] ' . date('Y-m-d H:i:s') . ' | ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    $view500 = defined('APPROOT')
        ? APPROOT . '/views/errors/500.php'
        : dirname(__DIR__) . '/app/views/errors/500.php';

    if (file_exists($view500)) {
        require $view500;
    } else {
        $fallback = defined('URLROOT') ? URLROOT : '';
        echo '<meta http-equiv="refresh" content="0;url=' . $fallback . '/auth/login?error=sistema">';
    }
    exit;
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) return false;
    error_log('[SGP-ERROR] ' . date('Y-m-d H:i:s') . " | [{$errno}] {$errstr} en {$errfile}:{$errline}");
    return true; // no ejecutar el handler interno de PHP
});

// ============================================
// ROUTER INTELIGENTE - Redirección Automática
// ============================================
Session::start();

// ── Refresh de permisos en tiempo real ──────────────────────────────────────
// Si el SuperAdmin cambió los permisos de este usuario mientras tenía sesión
// activa, se recarga el cache de $_SESSION['permisos'] automáticamente.
(function () {
    $__uid    = (int)Session::get('user_id');
    $__roleId = (int)Session::get('role_id');
    if (!$__uid || $__roleId === 0) return;  // SuperAdmin no necesita cache

    $__flag = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sgp_perm_' . $__uid . '.flag';
    if (!file_exists($__flag)) return;

    $__flagTs   = (int)@file_get_contents($__flag);
    $__sessionTs = (int)Session::get('permisos_ts');
    if ($__flagTs <= $__sessionTs) { @unlink($__flag); return; }

    // Recargar permisos desde BD
    require_once '../app/models/PermisosModel.php';
    $__pm = new PermisosModel();
    Session::set('permisos',    $__pm->getPermisosUsuario($__uid, $__roleId));
    Session::set('permisos_ts', $__flagTs);
    @unlink($__flag);
})();
// ────────────────────────────────────────────────────────────────────────────

// Si accede a la raíz (/) sin URL específica
if (!isset($_GET['url']) || empty($_GET['url'])) {
    // Si ya tiene sesión activa → Redirigir a Dashboard
    if (Session::get('user_id')) {
        header('Location: ' . URLROOT . '/dashboard');
        exit;
    }
    // Si no tiene sesión → Mostrar Login (se cargará por defecto en App.php)
}

// ── Composer Autoload (DomPDF, TCPDF, etc.) ──────────────────────────────────
// Cargado condicionalmente: el sistema funciona sin él salvo en endpoints PDF.
$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

$app = new App();
