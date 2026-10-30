<?php
// SGP-FIX-v2 [1] aplicado — Timeout de sesión por inactividad
if (!defined('SESSION_TIMEOUT_SECONDS')) {
    define('SESSION_TIMEOUT_SECONDS', 1500); // 25 minutos
}

// Helper para obtener variables de entorno de forma agnóstica (getenv, $_SERVER o $_ENV)
$getEnvVar = function(string $key, ?string $default = null): ?string {
    $val = getenv($key);
    if ($val !== false && $val !== '') return (string)$val;
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return (string)$_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
    return $default;
};

$dbHost = $getEnvVar('MYSQLHOST', $getEnvVar('DB_HOST', 'localhost'));
$dbName = $getEnvVar('MYSQLDATABASE', $getEnvVar('DB_NAME', 'proyecto_sgp'));
$dbUser = $getEnvVar('MYSQLUSER', $getEnvVar('DB_USER', 'root'));
$dbPass = $getEnvVar('MYSQLPASSWORD', $getEnvVar('DB_PASS', ''));
$dbPort = $getEnvVar('MYSQLPORT', $getEnvVar('DB_PORT', '3306'));
$dbSslRaw = $getEnvVar('MYSQLSSL', $getEnvVar('DB_SSL', null));
$dbSsl = $dbSslRaw !== null ? filter_var($dbSslRaw, FILTER_VALIDATE_BOOLEAN) : null;

return [
    'db' => [
        'host'    => $dbHost,
        'name'    => $dbName,
        'user'    => $dbUser,
        'pass'    => $dbPass,
        'port'    => $dbPort,
        'ssl'     => $dbSsl,
        'charset' => 'utf8mb4'
    ],
];