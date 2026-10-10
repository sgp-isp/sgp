<?php
/**
 * Helper compartido para páginas de error — calcular rutas consistentes.
 * Se incluye al inicio de cada página de error, antes de definir $errActions.
 */
$_url = defined('URLROOT') ? URLROOT : '';

// Ruta de inicio: dashboard del rol si hay sesión activa, login si no
$_homeLink = $_url . '/auth/login';
if (session_status() === PHP_SESSION_NONE) @session_start();
$_roleRoutes = [1 => '/admin', 2 => '/tutor', 3 => '/pasante'];
$_roleId = $_SESSION['role_id'] ?? null;
if ($_roleId && isset($_roleRoutes[$_roleId])) {
    $_homeLink = $_url . $_roleRoutes[$_roleId];
}
