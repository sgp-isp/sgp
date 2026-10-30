<?php
// SGP-FIX-v2 [1] aplicado — Timeout de sesión por inactividad
if (!defined('SESSION_TIMEOUT_SECONDS')) {
    define('SESSION_TIMEOUT_SECONDS', 1500); // 25 minutos
}
return [
    'db' => [
        // Conexión a la base de datos (Compatible con Local XAMPP y Cloud: Render / TiDB Cloud)
        'host'    => $_ENV['MYSQLHOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
        'name'    => $_ENV['MYSQLDATABASE'] ?? $_ENV['DB_NAME'] ?? 'proyecto_sgp',
        'user'    => $_ENV['MYSQLUSER'] ?? $_ENV['DB_USER'] ?? 'root',
        'pass'    => $_ENV['MYSQLPASSWORD'] ?? $_ENV['DB_PASS'] ?? '',
        'port'    => $_ENV['MYSQLPORT'] ?? $_ENV['DB_PORT'] ?? '3306',
        'ssl'     => isset($_ENV['MYSQLSSL']) ? filter_var($_ENV['MYSQLSSL'], FILTER_VALIDATE_BOOLEAN) : null,
        'charset' => 'utf8mb4'
    ],
];