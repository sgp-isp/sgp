<?php
require_once __DIR__ . '/_error_helpers.php';
$errCode   = '404';
$errIcon   = 'ti-compass-off';
$errAccent = '#2563eb';
$errBg     = '#eff6ff';
$errTitle  = 'Página no encontrada';
$errSub    = 'La página que buscas no existe o fue movida a otra dirección. Verifica la URL e intenta de nuevo.';
$errHint   = 'Si escribiste la URL manualmente, revisa que no haya errores tipográficos.';
$errActions = [
    ['label' => 'Ir al Inicio', 'href' => $_homeLink,                  'icon' => 'ti-home',       'primary' => true],
    ['label' => 'Volver Atrás', 'href' => 'javascript:history.back()', 'icon' => 'ti-arrow-left', 'primary' => false],
];
require __DIR__ . '/_error_layout.php';
