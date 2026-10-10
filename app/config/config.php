<?php
// SGP-FIX-v2 [1] aplicado — Timeout de sesión por inactividad
if (!defined('SESSION_TIMEOUT_SECONDS')) {
    define('SESSION_TIMEOUT_SECONDS', 1500); // 25 minutos
}
return [
    'db' => [
        // conexion a la base de datos 
        'host' => $_ENV['MYSQLHOST'] ?? 'localhost',
        'name' => $_ENV['MYSQLDATABASE'] ?? 'proyecto_sgp', // Tu base de datos local
        'user' => $_ENV['MYSQLUSER'] ?? 'root',            // Tu usuario local
        'pass' => $_ENV['MYSQLPASSWORD'] ?? '',             // Tu contraseña local (vacía)
        'port' => $_ENV['MYSQLPORT'] ?? '3306',
        'charset' => 'utf8mb4'
    ],
    ];