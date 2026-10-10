<?php
// Página de error genérica — recibe $errCodeCustom y $errMsgCustom opcionales
require_once __DIR__ . '/_error_helpers.php';
$errCode   = $errCodeCustom  ?? 'Error';
$errIcon   = 'ti-alert-triangle';
$errAccent = '#d97706';
$errBg     = '#fffbeb';
$errTitle  = $errTitleCustom ?? 'Ocurrió un error inesperado';
$errSub    = $errMsgCustom   ?? 'El sistema encontró un problema al procesar tu solicitud. Por favor intenta de nuevo.';
$errHint   = 'Si el error se repite, comunícate con el administrador.';
$errActions = [
    ['label' => 'Ir al Inicio', 'href' => $_homeLink,                  'icon' => 'ti-home',       'primary' => true],
    ['label' => 'Volver Atrás', 'href' => 'javascript:history.back()', 'icon' => 'ti-arrow-left', 'primary' => false],
];
require __DIR__ . '/_error_layout.php';
