<?php
/**
 * cron_backup_cloud.php — Tarea de Respaldo Automatizado a la Nube (Gmail Oficial)
 * 
 * Este script puede ser ejecutado de forma desatendida mediante:
 * 1. El Programador de Tareas de Windows (Task Scheduler)
 * 2. Un archivo por lotes .bat
 * 3. Línea de comandos: php cron_backup_cloud.php
 */

define('APPROOT', __DIR__ . '/app');
define('URLROOT', 'http://localhost/SGP');

require_once APPROOT . '/config/config.php';
require_once APPROOT . '/core/Database.php';
require_once APPROOT . '/models/AuditModel.php';
require_once APPROOT . '/lib/MailService.php';

echo "====================================================\n";
echo "   SGP ISPEB - RESPALDO AUTOMATIZADO A LA NUBE      \n";
echo "   Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n";

$backupDir = APPROOT . '/storage/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

$lockFile = $backupDir . '/cron_backup.lock';
if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 300) {
    echo "[AVISO] Un respaldo ya se encuentra en ejecución o finalizó hace menos de 5 min.\n";
    exit(0);
}
@touch($lockFile);

try {
    $config = require APPROOT . '/config/config.php';
    $dbConfig = $config['db'];
    $db = Database::getInstance();

    $host = $dbConfig['host'];
    $user = $dbConfig['user'];
    $pass = $dbConfig['pass'];
    $dbname = $dbConfig['name'];

    $timestamp = date('Y-m-d_H-i-s');
    $sqlFile   = $backupDir . '/sgp_cron_' . $timestamp . '.sql';
    $zipFile   = $backupDir . '/RESPALDO_SGP_NUBE_' . $timestamp . '.zip';

    echo "[1/4] Extrayendo base de datos MySQL ({$dbname})...\n";

    $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (!file_exists($mysqldumpPath)) {
        $mysqldumpPath = 'mysqldump';
    }

    if (empty($pass)) {
        $command = sprintf(
            '%s --user=%s --host=%s --routines --triggers --single-transaction %s > %s 2>&1',
            escapeshellarg($mysqldumpPath),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($dbname),
            escapeshellarg($sqlFile)
        );
    } else {
        $command = sprintf(
            '%s --user=%s --password=%s --host=%s --routines --triggers --single-transaction %s > %s 2>&1',
            escapeshellarg($mysqldumpPath),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($host),
            escapeshellarg($dbname),
            escapeshellarg($sqlFile)
        );
    }

    exec($command, $output, $returnVar);

    // Si mysqldump falla, usar generador PHP nativo
    if ($returnVar !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
        echo "   -> mysqldump no disponible en PATH, usando volcado nativo PHP...\n";
        $handle = fopen($sqlFile, 'w');
        if ($handle) {
            fwrite($handle, "-- SGP ISPEB NATIVE PHP SQL DUMP\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            $db->query("SHOW TABLES");
            $tables = $db->resultSet() ?: [];
            foreach ($tables as $tObj) {
                $tArr = (array)$tObj;
                $tableName = reset($tArr);
                $db->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = (array)$db->single();
                $createSql = $createRow['Create Table'] ?? '';
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n" . $createSql . ";\n\n");
                $db->query("SELECT * FROM `{$tableName}`");
                $rows = $db->resultSet() ?: [];
                foreach ($rows as $r) {
                    $fields = [];
                    $vals   = [];
                    foreach ((array)$r as $k => $v) {
                        $fields[] = "`{$k}`";
                        $vals[]   = ($v === null) ? "NULL" : "'" . addslashes($v) . "'";
                    }
                    fwrite($handle, "INSERT INTO `{$tableName}` (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ");\n");
                }
                fwrite($handle, "\n");
            }
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
        }
    }

    if (!file_exists($sqlFile) || filesize($sqlFile) === 0) {
        throw new \Exception("No se pudo generar el archivo SQL de respaldo.");
    }

    $rawSize = round(filesize($sqlFile) / (1024 * 1024), 2);
    echo "   -> Volcado generado con éxito ({$rawSize} MB).\n";

    echo "[2/4] Comprimiendo en formato ZIP institucional...\n";
    if (!class_exists('ZipArchive')) {
        throw new \Exception("Extensión ZipArchive no disponible en PHP.");
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new \Exception("No se pudo crear el archivo ZIP: {$zipFile}");
    }

    $zip->addFile($sqlFile, 'database/sgp_institucional_' . $timestamp . '.sql');
    $infoContent = "SISTEMA DE GESTIÓN DE PASANTÍAS (SGP ISPEB)\n";
    $infoContent .= "RESPALDO INTEGRAL CRON PROGRAMADO\n";
    $infoContent .= "--------------------------------------------------------\n";
    $infoContent .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $infoContent .= "Base de Datos: " . $dbname . "\n";
    $infoContent .= "Servidor: " . (gethostname() ?: 'Localhost') . "\n";
    $zip->addFromString('INFO_RESPALDO.txt', $infoContent);
    $zip->close();

    @unlink($sqlFile);
    $zipSize = round(filesize($zipFile) / (1024 * 1024), 2);
    echo "   -> Archivo comprimido generado: " . basename($zipFile) . " ({$zipSize} MB).\n";

    echo "[3/4] Transmitiendo a la cuenta de Gmail oficial (sgp.ispeb@gmail.com)...\n";
    $db->query("SHOW TABLES");
    $tables = $db->resultSet() ?: [];
    $totalTablas = count($tables);

    $res = MailService::sendCloudBackup($zipFile, [
        'total_tablas' => $totalTablas
    ]);

    @unlink($lockFile);

    if (!empty($res['success'])) {
        echo "[4/4] ¡ÉXITO! Respaldo enviado exitosamente por correo a la nube.\n";
        AuditModel::log('RESPALDO_NUBE_CRON_EXITOSO', 'backups', 0, [
            'archivo'   => basename($zipFile),
            'peso'      => $zipSize . ' MB',
            'tablas'    => $totalTablas,
            'ejecucion' => 'Cron / Tarea Programada'
        ]);
        exit(0);
    } else {
        echo "[ERROR] El archivo se creó pero falló el envío por correo: " . ($res['message'] ?? 'Error desconocido') . "\n";
        exit(1);
    }

} catch (\Throwable $e) {
    @unlink($lockFile);
    echo "[EXCEPCIÓN] " . $e->getMessage() . "\n";
    exit(1);
}
