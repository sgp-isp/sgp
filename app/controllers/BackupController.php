<?php
/**
 * BackupController
 * Admin-only access for creating, downloading, and managing database backups
 */
class BackupController extends Controller
{
    private $backupDir;

    public function __construct()
    {
        // Security
        CacheControl::noCache();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();
        
        Session::start();
        
        // Solo Admin puede acceder (Validador de Rol Transversal)
        RoleMiddleware::authorize([0, 1]);
        
        $this->backupDir = APPROOT . '/storage/backups';
        
        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * List all backups
     */
    public function index()
    {
        $backups = $this->getBackupList();
        
        $this->view('backup/index', [
            'backups' => $backups
        ]);
    }

    /**
     * Create new backup
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-N-4] Verificación CSRF — antes ausente en las 3 acciones destructivas/costosas.
        $this->verifyCsrf();

        // [FIX-N-4] Cooldown de 5 min entre backups — evita saturar CPU/disco disparando
        // mysqldump en bucle (no había ningún límite de frecuencia).
        $ultimo = $this->getBackupList()[0] ?? null;
        if ($ultimo && (time() - (int)$ultimo['timestamp']) < 300) {
            $espera = 300 - (time() - (int)$ultimo['timestamp']);
            $this->jsonResponse(false, "Espera " . ceil($espera / 60) . " minuto(s) antes de crear otro respaldo.");
        }

        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            // Get database credentials from config
            $config = require APPROOT . '/config/config.php';
            $dbConfig = $config['db'];
            
            $host = $dbConfig['host'];
            $user = $dbConfig['user'];
            $pass = $dbConfig['pass'];
            $dbname = $dbConfig['name'];
            
            // 1. Intentar mysqldump si está disponible
            $mysqldumpPath = file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')
                ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe'
                : 'mysqldump';
            
            $exito = false;
            $portFlag = !empty($dbConfig['port']) ? ' -P ' . (int)$dbConfig['port'] : '';
            
            if (empty($pass)) {
                $command = sprintf(
                    '%s --user=%s --host=%s%s --routines %s > %s 2>&1',
                    $mysqldumpPath,
                    escapeshellarg($user),
                    escapeshellarg($host),
                    $portFlag,
                    escapeshellarg($dbname),
                    escapeshellarg($filepath)
                );
            } else {
                $command = sprintf(
                    '%s --user=%s --password=%s --host=%s%s --routines %s > %s 2>&1',
                    $mysqldumpPath,
                    escapeshellarg($user),
                    escapeshellarg($pass),
                    escapeshellarg($host),
                    $portFlag,
                    escapeshellarg($dbname),
                    escapeshellarg($filepath)
                );
            }
            
            // Probar ejecución de comando externo
            @exec($command, $output, $returnVar);
            if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                $exito = true;
            } else {
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
                // 2. Fallback inteligente: Exportador nativo en PHP vía PDO
                // (Garantizado para TiDB Cloud / Render / Linux sin dependencias externas)
                $exito = $this->exportarConPDO($filepath);
            }
            
            if (!$exito || !file_exists($filepath) || filesize($filepath) === 0) {
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
                $this->jsonResponse(false, 'Error al crear el respaldo. Verifica los permisos del servidor o contacta al administrador.');
            }
            
            $this->jsonResponse(true, 'Respaldo creado exitosamente: ' . $filename);
            
        } catch (Exception $e) {
            // [FIX-C2] Mensaje genérico al cliente — detalle solo en log
            error_log('[SGP-BACKUP] create() Error: ' . $e->getMessage());
            $this->jsonResponse(false, 'Error al crear el respaldo. Intente de nuevo.');
        }
    }

    /**
     * Download backup file
     */
    public function download($filename = null)
    {
        if (!$filename) {
            Session::setFlash('error', 'Archivo no especificado');
            $this->redirect('/backup');
            return;
        }
        
        // Validate filename (prevent directory traversal) - allow _pre_restore
        $filename = basename($filename);
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}(_pre_restore)?\.sql$/', $filename)) {
            Session::setFlash('error', 'Nombre de archivo inválido');
            $this->redirect('/backup');
            return;
        }
        
        $filepath = $this->backupDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            Session::setFlash('error', 'Archivo no encontrado');
            $this->redirect('/backup');
            return;
        }
        
        // Send file for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache');
        
        readfile($filepath);
        exit;
    }

    /**
     * Delete backup file
     */
    public function delete($filename = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-3] Verificación CSRF
        $this->verifyCsrf();

        if (!$filename) {
            $this->jsonResponse(false, 'Archivo no especificado');
        }
        
        // Validate filename (allow _pre_restore)
        $filename = basename($filename);
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}(_pre_restore)?\.sql$/', $filename)) {
            $this->jsonResponse(false, 'Nombre de archivo inválido');
        }
        
        $filepath = $this->backupDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            $this->jsonResponse(false, 'Archivo no encontrado');
        }
        
        if (unlink($filepath)) {
            $this->jsonResponse(true, 'Respaldo eliminado exitosamente');
        } else {
            $this->jsonResponse(false, 'Error al eliminar el respaldo');
        }
    }

    /**
     * Restore database from backup
     * VULN-07: Protección con backup previo + confirmación obligatoria
     */
    public function restore($filename = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-3] Verificación CSRF — restaura toda la BD, además de la confirmación
        // de texto que ya exigía el método (VULN-07).
        $this->verifyCsrf();

        if (!$filename) {
            $this->jsonResponse(false, 'Archivo no especificado');
        }
        
        // Validate filename (allow _pre_restore)
        $filename = basename($filename);
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}(_pre_restore)?\.sql$/', $filename)) {
            $this->jsonResponse(false, 'Nombre de archivo inválido');
        }
        
        $filepath = $this->backupDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            $this->jsonResponse(false, 'Archivo no encontrado');
        }

        // VULN-07: Confirmación obligatoria — el frontend debe enviar el nombre exacto del archivo
        $confirmacion = trim($_POST['confirmacion_texto'] ?? '');
        if ($confirmacion !== $filename) {
            $this->jsonResponse(false, 'Confirmación incorrecta. Escribe el nombre exacto del archivo para restaurar.');
        }

        // VULN-07: Backup de seguridad automático antes de restaurar
        $safetyBackup = 'backup_' . date('Y-m-d_H-i-s') . '_pre_restore.sql';
        $safetyPath   = $this->backupDir . '/' . $safetyBackup;
        $safetyOk     = false;

        try {
            $config   = require APPROOT . '/config/config.php';
            $dbConfig = $config['db'];
            $mysqldumpPath = file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe') 
                ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump';
            $portFlag = !empty($dbConfig['port']) ? ' -P ' . (int)$dbConfig['port'] : '';

            $dumpCmd = empty($dbConfig['pass'])
                ? sprintf('%s --user=%s --host=%s%s --routines %s > %s 2>&1',
                    $mysqldumpPath, escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['host']), $portFlag, escapeshellarg($dbConfig['name']),
                    escapeshellarg($safetyPath))
                : sprintf('%s --user=%s --password=%s --host=%s%s --routines %s > %s 2>&1',
                    $mysqldumpPath, escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['pass']), escapeshellarg($dbConfig['host']),
                    $portFlag, escapeshellarg($dbConfig['name']), escapeshellarg($safetyPath));
            
            @exec($dumpCmd, $dumpOut, $dumpRet);
            if ($dumpRet === 0 && file_exists($safetyPath) && filesize($safetyPath) > 0) {
                $safetyOk = true;
            } else {
                $safetyOk = $this->exportarConPDO($safetyPath);
            }
        } catch (Exception $e) {
            $safetyOk = $this->exportarConPDO($safetyPath);
        }

        if (!$safetyOk || !file_exists($safetyPath) || filesize($safetyPath) === 0) {
            $this->jsonResponse(false, 'No se pudo crear el backup de seguridad previo. Restauración cancelada.');
        }
        
        try {
            $config   = require APPROOT . '/config/config.php';
            $dbConfig = $config['db'];
            
            $host   = $dbConfig['host'];
            $user   = $dbConfig['user'];
            $pass   = $dbConfig['pass'];
            $dbname = $dbConfig['name'];
            $portFlag = !empty($dbConfig['port']) ? ' -P ' . (int)$dbConfig['port'] : '';
            
            $mysqlPath = file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')
                ? 'C:\\xampp\\mysql\\bin\\mysql.exe' : 'mysql';
            
            if (empty($pass)) {
                $command = sprintf(
                    '%s --user=%s --host=%s%s %s < %s 2>&1',
                    $mysqlPath,
                    escapeshellarg($user),
                    escapeshellarg($host),
                    $portFlag,
                    escapeshellarg($dbname),
                    escapeshellarg($filepath)
                );
            } else {
                $command = sprintf(
                    '%s --user=%s --password=%s --host=%s%s %s < %s 2>&1',
                    $mysqlPath,
                    escapeshellarg($user),
                    escapeshellarg($pass),
                    escapeshellarg($host),
                    $portFlag,
                    escapeshellarg($dbname),
                    escapeshellarg($filepath)
                );
            }
            
            $restoreOk = false;
            @exec($command, $output, $returnVar);
            if ($returnVar === 0) {
                $restoreOk = true;
            } else {
                // Fallback nativo PDO
                $restoreOk = $this->restaurarConPDO($filepath);
            }
            
            if (!$restoreOk) {
                $this->jsonResponse(false, 'Error al restaurar la base de datos. Verifica el archivo SQL o contacta al administrador.');
            }
            
            $this->jsonResponse(true, 'Base de datos restaurada exitosamente desde: ' . $filename);
            
        } catch (Exception $e) {
            error_log('[SGP-BACKUP] restore() Error: ' . $e->getMessage());
            $this->jsonResponse(false, 'Error al restaurar la base de datos. Intente de nuevo.');
        }
    }

    /**
     * [SGP-FEATURE-BACKUP-IMPORTAR] Importar y restaurar la base de datos desde un
     * archivo .sql subido por el usuario (complementa restore(), que solo opera sobre
     * backups generados por el propio sistema y ya presentes en storage/backups).
     * Reutiliza exactamente las mismas protecciones que restore(): confirmación de
     * texto obligatoria, backup de seguridad automático previo, y mensajes de error
     * genéricos (el detalle solo va al log).
     */
    public function importar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        $this->verifyCsrf();

        // ── Validar archivo subido ──────────────────────────────
        if (!isset($_FILES['archivo_sql']) || $_FILES['archivo_sql']['error'] !== UPLOAD_ERR_OK) {
            $errores = [
                UPLOAD_ERR_INI_SIZE  => 'El archivo excede el tamaño máximo permitido por el servidor.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
                UPLOAD_ERR_PARTIAL   => 'El archivo se subió parcialmente. Intenta de nuevo.',
                UPLOAD_ERR_NO_FILE   => 'No se seleccionó ningún archivo.',
            ];
            $codigo = $_FILES['archivo_sql']['error'] ?? UPLOAD_ERR_NO_FILE;
            $this->jsonResponse(false, $errores[$codigo] ?? 'Error al subir el archivo.');
        }

        $tmpPath        = $_FILES['archivo_sql']['tmp_name'];
        $nombreOriginal = basename($_FILES['archivo_sql']['name']);

        // Validar extensión
        if (strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) !== 'sql') {
            $this->jsonResponse(false, 'El archivo debe tener extensión .sql');
        }

        // Validar tamaño
        $tamanoMaximo = 100 * 1024 * 1024; // 100 MB
        $tamanoActual = filesize($tmpPath);
        if ($tamanoActual === 0) {
            $this->jsonResponse(false, 'El archivo está vacío.');
        }
        if ($tamanoActual > $tamanoMaximo) {
            $this->jsonResponse(false, 'El archivo supera el límite de 100 MB.');
        }

        // Validación heurística de contenido — evita ejecutar contra la BD un archivo
        // renombrado a .sql que en realidad no es un dump válido.
        $muestra = file_get_contents($tmpPath, false, null, 0, 8192);
        if ($muestra === false || !preg_match('/\b(CREATE\s+TABLE|INSERT\s+INTO|SET\s+NAMES|DROP\s+TABLE|-- MySQL dump)\b/i', $muestra)) {
            $this->jsonResponse(false, 'El archivo no parece ser un dump SQL válido.');
        }

        // Confirmación obligatoria — mismo patrón que restore() (VULN-07)
        $confirmacion = trim($_POST['confirmacion_texto'] ?? '');
        if ($confirmacion !== 'IMPORTAR Y RESTAURAR') {
            $this->jsonResponse(false, 'Confirmación incorrecta. Escribe exactamente "IMPORTAR Y RESTAURAR" para continuar.');
        }

        // Backup de seguridad automático antes de restaurar (idéntico a restore())
        $safetyBackup = 'backup_' . date('Y-m-d_H-i-s') . '_pre_restore.sql';
        $safetyPath   = $this->backupDir . '/' . $safetyBackup;
        $safetyOk     = false;

        try {
            $config   = require APPROOT . '/config/config.php';
            $dbConfig = $config['db'];
            $mysqldumpPath = file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')
                ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump';
            $portFlag = !empty($dbConfig['port']) ? ' -P ' . (int)$dbConfig['port'] : '';

            $dumpCmd = empty($dbConfig['pass'])
                ? sprintf('%s --user=%s --host=%s%s --routines %s > %s 2>&1',
                    $mysqldumpPath, escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['host']), $portFlag, escapeshellarg($dbConfig['name']),
                    escapeshellarg($safetyPath))
                : sprintf('%s --user=%s --password=%s --host=%s%s --routines %s > %s 2>&1',
                    $mysqldumpPath, escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['pass']), escapeshellarg($dbConfig['host']),
                    $portFlag, escapeshellarg($dbConfig['name']), escapeshellarg($safetyPath));
            @exec($dumpCmd, $dumpOut, $dumpRet);
            if ($dumpRet === 0 && file_exists($safetyPath) && filesize($safetyPath) > 0) {
                $safetyOk = true;
            } else {
                $safetyOk = $this->exportarConPDO($safetyPath);
            }
        } catch (Exception $e) {
            $safetyOk = $this->exportarConPDO($safetyPath);
        }

        if (!$safetyOk || !file_exists($safetyPath) || filesize($safetyPath) === 0) {
            $this->jsonResponse(false, 'No se pudo crear el backup de seguridad previo. Importación cancelada.');
        }

        // Mover el archivo subido a una ubicación estable (temporal) para poder
        // ejecutarlo con mysql < archivo. move_uploaded_file() valida internamente
        // que el archivo provenga de una subida HTTP real.
        $importPath = $this->backupDir . '/_importado_' . date('Y-m-d_H-i-s') . '.sql';
        if (!move_uploaded_file($tmpPath, $importPath)) {
            $this->jsonResponse(false, 'Error al procesar el archivo subido.');
        }

        try {
            $config   = require APPROOT . '/config/config.php';
            $dbConfig = $config['db'];

            $host   = $dbConfig['host'];
            $user   = $dbConfig['user'];
            $pass   = $dbConfig['pass'];
            $dbname = $dbConfig['name'];
            $portFlag = !empty($dbConfig['port']) ? ' -P ' . (int)$dbConfig['port'] : '';

            $mysqlPath = file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')
                ? 'C:\\xampp\\mysql\\bin\\mysql.exe' : 'mysql';

            $command = empty($pass)
                ? sprintf('%s --user=%s --host=%s%s %s < %s 2>&1',
                    $mysqlPath, escapeshellarg($user), escapeshellarg($host),
                    $portFlag, escapeshellarg($dbname), escapeshellarg($importPath))
                : sprintf('%s --user=%s --password=%s --host=%s%s %s < %s 2>&1',
                    $mysqlPath, escapeshellarg($user), escapeshellarg($pass), escapeshellarg($host),
                    $portFlag, escapeshellarg($dbname), escapeshellarg($importPath));

            $importOk = false;
            @exec($command, $output, $returnVar);
            if ($returnVar === 0) {
                $importOk = true;
            } else {
                $importOk = $this->restaurarConPDO($importPath);
            }

            // El archivo importado es temporal — no se conserva ni se lista como backup.
            @unlink($importPath);

            if (!$importOk) {
                $this->jsonResponse(false, 'Error al importar la base de datos. Verifica que el archivo sea un dump SQL válido o contacta al administrador.');
            }

            $this->jsonResponse(true, 'Base de datos restaurada exitosamente desde el archivo importado: ' . $nombreOriginal);

        } catch (Exception $e) {
            @unlink($importPath);
            error_log('[SGP-BACKUP] importar() Error: ' . $e->getMessage());
            $this->jsonResponse(false, 'Error al importar la base de datos. Intente de nuevo.');
        }
    }

    /**
     * Get list of backup files
     */
    private function getBackupList()
    {
        $files = glob($this->backupDir . '/backup_*.sql');
        $backups = [];
        
        if ($files) {
            // Sort by modification time (newest first)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => $this->formatBytes(filesize($file)),
                    'date' => date('d/m/Y H:i:s', filemtime($file)),
                    'timestamp' => filemtime($file),
                    'is_safety' => (strpos(basename($file), '_pre_restore') !== false)
                ];
            }
        }
        
        return $backups;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * JSON response helper
     */
    private function jsonResponse($success, $message)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit;
    }

    /**
     * Motor nativo de exportación vía PDO
     * Permite respaldar la base de datos sin depender del binario externo mysqldump.
     * Funciona en Render, TiDB Cloud con SSL, Railway, Docker, Linux y Windows.
     */
    private function exportarConPDO(string $filepath): bool
    {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();

            $fp = fopen($filepath, 'w');
            if (!$fp) {
                return false;
            }

            fwrite($fp, "-- =========================================================\n");
            fwrite($fp, "-- SGP ISPEB - Respaldo de Base de Datos (Motor PHP PDO)\n");
            fwrite($fp, "-- Fecha: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- =========================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fp, "SET NAMES utf8mb4;\n\n");

            // Obtener tablas base
            $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $stmt->fetchAll(PDO::FETCH_NUM);

            foreach ($tables as $tRow) {
                $table = $tRow[0];

                // Estructura
                fwrite($fp, "-- ---------------------------------------------------------\n");
                fwrite($fp, "-- Estructura de tabla: `{$table}`\n");
                fwrite($fp, "-- ---------------------------------------------------------\n");
                fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                if (!empty($createRow[1])) {
                    fwrite($fp, $createRow[1] . ";\n\n");
                }

                // Datos
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $totalRows = (int)$countStmt->fetchColumn();
                if ($totalRows === 0) {
                    continue;
                }

                fwrite($fp, "-- Volcado de datos: `{$table}`\n");
                $dataStmt = $pdo->query("SELECT * FROM `{$table}`");

                $batch = [];
                while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                    $values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = $pdo->quote((string)$val);
                        }
                    }
                    $batch[] = "(" . implode(", ", $values) . ")";
                    if (count($batch) >= 100) {
                        fwrite($fp, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }
                if (!empty($batch)) {
                    fwrite($fp, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $batch) . ";\n");
                }
                fwrite($fp, "\n");
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($fp);

            return file_exists($filepath) && filesize($filepath) > 0;
        } catch (\Throwable $e) {
            error_log('[SGP-BACKUP] exportarConPDO Error: ' . $e->getMessage());
            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            return false;
        }
    }

    /**
     * Motor nativo de restauración vía PDO
     */
    private function restaurarConPDO(string $filepath): bool
    {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();

            if (!file_exists($filepath) || filesize($filepath) === 0) {
                return false;
            }

            $handle = fopen($filepath, "r");
            if (!$handle) {
                return false;
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            $query = '';
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                // Ignorar comentarios y líneas vacías
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                $query .= $line;
                // Si la línea termina en punto y coma, ejecutar la sentencia
                if (str_ends_with($trimmed, ';')) {
                    try {
                        $pdo->exec($query);
                    } catch (\Throwable $qe) {
                        error_log('[SGP-BACKUP] Error ejecutando sentencia de restauración: ' . substr($query, 0, 100));
                    }
                    $query = '';
                }
            }
            fclose($handle);

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return true;
        } catch (\Throwable $e) {
            error_log('[SGP-BACKUP] restaurarConPDO Error: ' . $e->getMessage());
            return false;
        }
    }
}
