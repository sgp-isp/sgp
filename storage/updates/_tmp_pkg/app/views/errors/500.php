<?php
require_once __DIR__ . '/_error_helpers.php';
$errCode   = '500';
$errIcon   = 'ti-server-off';
$errAccent = '#dc2626';
$errBg     = '#fef2f2';
$errTitle  = 'Error Interno del Servidor';
$errSub    = 'Algo salió mal en el servidor. El equipo técnico ha sido notificado. Por favor intenta de nuevo en unos momentos.';
$errHint   = 'Si el problema persiste, contacta al administrador del sistema.';
$errActions = [
    ['label' => 'Reintentar',   'href' => 'javascript:location.reload()', 'icon' => 'ti-refresh', 'primary' => true],
    ['label' => 'Ir al Inicio', 'href' => $_homeLink,                     'icon' => 'ti-home',    'primary' => false],
];
require __DIR__ . '/_error_layout.php';
