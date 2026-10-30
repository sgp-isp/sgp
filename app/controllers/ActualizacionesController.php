<?php
/**
 * ActualizacionesController - Módulo de Actualizaciones del Sistema en Caliente
 * 
 * Permite al administrador o desarrollador desplegar actualizaciones de código
 * (PHP, JS, CSS, Vistas) y migraciones SQL directamente desde la red local a través
 * del navegador web, sin necesidad de usar pendrives.
 */

class ActualizacionesController extends Controller
{
    private $db;
    private $updatesDir;
    private $backupsDir;
    private $historialFile;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();
        RoleMiddleware::authorize([0]); // Exclusivo SuperAdministrador / Desarrollador

        $config = require '../app/config/config.php';
        $this->db = new Database($config['db']);

        $storageRoot = APPROOT . '/storage/updates';
        $this->updatesDir = $storageRoot;
        $this->backupsDir = $storageRoot . '/backups';
        $this->historialFile = $storageRoot . '/historial.json';

        if (!is_dir($this->updatesDir)) @mkdir($this->updatesDir, 0755, true);
        if (!is_dir($this->backupsDir)) @mkdir($this->backupsDir, 0755, true);
    }

    /**
     * Vista principal del módulo de actualizaciones
     */
    public function index()
    {
        $historial = $this->obtenerHistorial();
        $espacioLibreBytes = @disk_free_space(dirname(APPROOT));
        $espacioTotalBytes = @disk_total_space(dirname(APPROOT));
        $espacioUsadoBytes = ($espacioTotalBytes && $espacioLibreBytes) ? ($espacioTotalBytes - $espacioLibreBytes) : 0;
        $espacioPorcentajeUsado = ($espacioTotalBytes > 0) ? round(($espacioUsadoBytes / $espacioTotalBytes) * 100, 1) : 0;

        $serverIp = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? (function_exists('gethostname') ? gethostbyname(gethostname()) : '10.36.0.54');
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $data = [
            'title'                  => 'Actualizaciones del Sistema — SGP',
            'historial'              => $historial,
            'ultima_actualizacion'   => !empty($historial) ? $historial[0] : null,
            'espacio_libre'          => $espacioLibreBytes ? round($espacioLibreBytes / (1024 * 1024 * 1024), 2) . ' GB' : 'N/D',
            'espacio_total'          => $espacioTotalBytes ? round($espacioTotalBytes / (1024 * 1024 * 1024), 2) . ' GB' : 'N/D',
            'espacio_usado'          => $espacioUsadoBytes ? round($espacioUsadoBytes / (1024 * 1024 * 1024), 2) . ' GB' : '0 GB',
            'espacio_porcentaje'     => $espacioPorcentajeUsado,
            'server_ip'              => $serverIp,
            'client_ip'              => $clientIp,
            'php_version'            => PHP_VERSION,
            'zip_enabled'            => class_exists('ZipArchive'),
            'max_upload_size'        => ini_get('upload_max_filesize'),
            'post_max_size'          => ini_get('post_max_size'),
            'csrf_token'             => Session::generateCsrfToken()
        ];

        $this->view('actualizaciones/index', $data);
    }

    /**
     * AJAX POST: Recibe el archivo .zip, valida, hace backup previo y aplica cambios
     */
    public function aplicar()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Si el archivo superó post_max_size, PHP vacía $_POST y $_FILES automáticamente
        if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'El paquete supera el límite máximo de subida del servidor (' . ini_get('post_max_size') . '). Reduce el tamaño del paquete.'
            ]);
            exit;
        }

        $token = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada.']);
            exit;
        }

        if (empty($_FILES['paquete_zip']) || $_FILES['paquete_zip']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['paquete_zip']['error'] ?? 4;
            $msg = 'No se recibió ningún archivo o hubo un error en la subida (Código: ' . $errCode . ').';
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'El archivo supera el tamaño máximo permitido por el servidor (' . ini_get('upload_max_filesize') . ').';
            }
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }

        $file = $_FILES['paquete_zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            echo json_encode(['success' => false, 'message' => 'Solo se permiten paquetes de actualización en formato .ZIP']);
            exit;
        }

        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'La extensión ZipArchive no está habilitada en este servidor PHP.']);
            exit;
        }

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            echo json_encode(['success' => false, 'message' => 'El archivo ZIP no pudo abrirse o está corrupto.']);
            exit;
        }

        $projectRoot = dirname(APPROOT); // C:\xampp\htdocs\SGP
        $archivosModificados = [];
        $archivosOmitidos = [];
        $sqlEjecutado = false;
        $sqlConsultasCount = 0;
        $sqlDetalles = [];

        // 1. Verificación de seguridad y lista de archivos
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Evitar Path Traversal / Zip Slip
            if (strpos($filename, '..') !== false || strpos($filename, ':') !== false) {
                $zip->close();
                echo json_encode(['success' => false, 'message' => 'Riesgo de seguridad detectado en el paquete (Ruta relativa inválida: ' . htmlspecialchars($filename) . ')']);
                exit;
            }

            // Omitir carpetas del control de versiones si vinieran en el zip
            if (preg_match('#^(\.git|\.kilo|\.idea)/#i', $filename)) {
                $archivosOmitidos[] = $filename;
                continue;
            }

            // Omitir configuración de base de datos local para no pisar credenciales de la PC del jefe
            if (preg_match('#^app/config/config\.php$#i', $filename)) {
                $archivosOmitidos[] = $filename;
                continue;
            }

            $archivosModificados[] = $filename;
        }

        // 2. Crear respaldo de seguridad antes de aplicar (Rollback Point)
        $timestamp = date('Y-m-d_H-i-s');
        $backupZipName = 'rollback_' . $timestamp . '.zip';
        $backupZipPath = $this->backupsDir . '/' . $backupZipName;
        $backupZip = new ZipArchive();

        if ($backupZip->open($backupZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($archivosModificados as $relPath) {
                $fullPath = $projectRoot . '/' . $relPath;
                if (file_exists($fullPath) && !is_dir($fullPath)) {
                    $backupZip->addFile($fullPath, $relPath);
                }
            }
            $backupZip->close();
        }

        // 3. Extraer y sobreescribir archivos en caliente
        $archivosExtraidosCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if (in_array($entryName, $archivosOmitidos, true)) {
                continue;
            }

            $targetPath = $projectRoot . '/' . $entryName;

            // Si es directorio, crearlo si no existe
            if (substr($entryName, -1) === '/' || substr($entryName, -1) === '\\') {
                if (!is_dir($targetPath)) {
                    @mkdir($targetPath, 0755, true);
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $stream = $zip->getStream($entryName);
            if ($stream) {
                file_put_contents($targetPath, stream_get_contents($stream));
                fclose($stream);
                $archivosExtraidosCount++;
            }
        }

        // 4. Verificar si venía un script SQL de migración (update.sql o migracion.sql)
        $sqlFiles = ['update.sql', 'migracion.sql', 'database/update.sql'];
        foreach ($sqlFiles as $sqlRelPath) {
            $sqlFullPath = $projectRoot . '/' . $sqlRelPath;
            if (file_exists($sqlFullPath)) {
                $sqlContent = trim(file_get_contents($sqlFullPath));
                if (!empty($sqlContent)) {
                    try {
                        $this->db->query($sqlContent);
                        $this->db->execute();
                        $sqlEjecutado = true;
                        $sqlConsultasCount++;
                        $sqlDetalles[] = $sqlRelPath . ' ejecutado exitosamente.';
                        // Renombrar para no volver a ejecutar en el futuro
                        @rename($sqlFullPath, $projectRoot . '/database/ejecutado_' . $timestamp . '_' . basename($sqlRelPath));
                    } catch (\Throwable $ex) {
                        $sqlDetalles[] = 'Error en ' . $sqlRelPath . ': ' . $ex->getMessage();
                    }
                }
            }
        }

        $zip->close();

        // 5. Registrar en el historial
        $registro = [
            'id'                  => uniqid('upd_'),
            'fecha'               => date('Y-m-d H:i:s'),
            'archivo_paquete'     => $file['name'],
            'tamano_kb'           => round($file['size'] / 1024, 1),
            'usuario_responsable' => Session::get('user_name') ?? 'Administrador',
            'archivos_actualizados_count' => $archivosExtraidosCount,
            'backup_previo'       => $backupZipName,
            'sql_ejecutado'       => $sqlEjecutado,
            'sql_detalles'        => $sqlDetalles
        ];

        $this->guardarEnHistorial($registro);

        echo json_encode([
            'success'                     => true,
            'message'                     => '¡Actualización aplicada con éxito en este equipo!',
            'archivos_actualizados_count' => $archivosExtraidosCount,
            'backup_rollback'             => $backupZipName,
            'sql_ejecutado'               => $sqlEjecutado,
            'sql_detalles'                => $sqlDetalles
        ]);
        exit;
    }

    /**
     * Generador de Paquete .ZIP en la laptop (Útil cuando estás desarrollando)
     */
    public function empaquetar()
    {
        if (!class_exists('ZipArchive')) {
            Session::setFlash('error', 'ZipArchive no está disponible.');
            header('Location: ' . URLROOT . '/actualizaciones');
            exit;
        }

        $projectRoot = dirname(APPROOT);
        $zipName = 'actualizacion_SGP_' . date('Ymd_His') . '.zip';
        $tmpZip = sys_get_temp_dir() . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Session::setFlash('error', 'No se pudo crear el archivo ZIP temporal.');
            header('Location: ' . URLROOT . '/actualizaciones');
            exit;
        }

        $carpetasAIncluir = ['app', 'public'];
        foreach ($carpetasAIncluir as $carpeta) {
            $dirPath = $projectRoot . '/' . $carpeta;
            if (is_dir($dirPath)) {
                $this->agregarDirectorioAlZip($dirPath, $carpeta, $zip);
            }
        }

        // Incluir archivos raíz importantes
        $archivosRaiz = ['.htaccess', 'index.php'];
        foreach ($archivosRaiz as $arc) {
            $filePath = $projectRoot . '/' . $arc;
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $arc);
            }
        }

        $zip->close();

        // Forzar descarga del archivo ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }

    /**
     * Función recursiva para agregar carpetas al ZIP
     */
    private function agregarDirectorioAlZip($source, $insideZip, $zip)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subPath = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            $zipEntryPath = $insideZip . '/' . $subPath;

            // Excluir uploads pesados, temporales o backups de código
            if (preg_match('#(public/uploads/|storage/backups/|storage/updates/|\.git)#i', $zipEntryPath)) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($zipEntryPath);
            } else {
                $zip->addFile($item->getPathname(), $zipEntryPath);
            }
        }
    }

    /**
     * AJAX POST: Revierte el sistema a un punto de rollback previo
     */
    public function revertir()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $token = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada.']);
            exit;
        }

        $backupName = basename($_POST['backup_name'] ?? '');
        if (empty($backupName) || !preg_match('#^rollback_[0-9_\-]+\.zip$#i', $backupName)) {
            echo json_encode(['success' => false, 'message' => 'Nombre de archivo de restauración no válido.']);
            exit;
        }

        $backupPath = $this->backupsDir . '/' . $backupName;
        if (!file_exists($backupPath)) {
            echo json_encode(['success' => false, 'message' => 'El archivo de rollback seleccionado no existe en el servidor.']);
            exit;
        }

        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'La extensión ZipArchive no está habilitada.']);
            exit;
        }

        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            echo json_encode(['success' => false, 'message' => 'No se pudo abrir el archivo ZIP de restauración.']);
            exit;
        }

        $projectRoot = dirname(APPROOT);
        $restauradosCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            // Seguridad contra Path Traversal
            if (strpos($entryName, '..') !== false || strpos($entryName, ':') !== false) {
                continue;
            }

            // Proteger credenciales locales de la base de datos
            if (preg_match('#^app/config/config\.php$#i', $entryName)) {
                continue;
            }

            $targetPath = $projectRoot . '/' . $entryName;

            if (substr($entryName, -1) === '/' || substr($entryName, -1) === '\\') {
                if (!is_dir($targetPath)) @mkdir($targetPath, 0755, true);
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);

            $stream = $zip->getStream($entryName);
            if ($stream) {
                file_put_contents($targetPath, stream_get_contents($stream));
                fclose($stream);
                $restauradosCount++;
            }
        }

        $zip->close();

        // Registrar evento de reversión en el historial
        $registro = [
            'id'                  => uniqid('roll_'),
            'fecha'               => date('Y-m-d H:i:s'),
            'archivo_paquete'     => 'REVERSIÓN A ' . $backupName,
            'tamano_kb'           => round(filesize($backupPath) / 1024, 1),
            'usuario_responsable' => Session::get('user_name') ?? 'Administrador',
            'archivos_actualizados_count' => $restauradosCount,
            'backup_previo'       => 'Punto original: ' . $backupName,
            'sql_ejecutado'       => false,
            'sql_detalles'        => ['Sistema restaurado con éxito desde ' . $backupName]
        ];
        $this->guardarEnHistorial($registro);

        echo json_encode([
            'success' => true,
            'message' => "¡Sistema revertido exitosamente! Se restablecieron {$restauradosCount} archivos al estado anterior."
        ]);
        exit;
    }

    /**
     * AJAX POST: Elimina un registro del historial y su archivo de respaldo físico
     */
    public function eliminar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $token = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada.']);
            exit;
        }

        $id = trim($_POST['id'] ?? '');
        $historial = $this->obtenerHistorial();
        $nuevoHistorial = [];
        $eliminado = false;

        foreach ($historial as $item) {
            if ($item['id'] === $id) {
                $eliminado = true;
                $backupName = $item['backup_previo'] ?? '';
                if (!empty($backupName) && strpos($backupName, 'rollback_') === 0) {
                    $backupPath = $this->backupsDir . '/' . basename($backupName);
                    if (file_exists($backupPath)) {
                        @unlink($backupPath);
                    }
                }
            } else {
                $nuevoHistorial[] = $item;
            }
        }

        if ($eliminado) {
            file_put_contents($this->historialFile, json_encode($nuevoHistorial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'message' => 'Registro y archivo de respaldo eliminados con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el registro seleccionado.']);
        }
        exit;
    }

    /**
     * Descarga segura de un archivo de rollback ZIP
     */
    public function descargarBackup($filename = '')
    {
        $safeName = basename($filename);
        if (empty($safeName) || !preg_match('#^rollback_[0-9_\-]+\.zip$#i', $safeName)) {
            Session::setFlash('error', 'Nombre de archivo no válido.');
            $this->redirect('actualizaciones');
            exit;
        }

        $filePath = $this->backupsDir . '/' . $safeName;
        if (!file_exists($filePath)) {
            Session::setFlash('error', 'El archivo de respaldo solicitado no existe.');
            $this->redirect('actualizaciones');
            exit;
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }

    /**
     * AJAX POST: Limpieza masiva (Conserva los últimos 5 y purga el resto)
     */
    public function purgarRespaldos()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $token = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada.']);
            exit;
        }

        $historial = $this->obtenerHistorial();
        $purgadosCount = 0;
        $bytesLiberados = 0;

        if (count($historial) > 5) {
            $mantener = array_slice($historial, 0, 5);
            $aPurgar = array_slice($historial, 5);

            foreach ($aPurgar as $item) {
                $bName = $item['backup_previo'] ?? '';
                if (!empty($bName) && strpos($bName, 'rollback_') === 0) {
                    $bPath = $this->backupsDir . '/' . basename($bName);
                    if (file_exists($bPath)) {
                        $bytesLiberados += filesize($bPath);
                        @unlink($bPath);
                        $purgadosCount++;
                    }
                }
            }

            file_put_contents($this->historialFile, json_encode($mantener, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $mbLiberados = round($bytesLiberados / (1024 * 1024), 2);
        echo json_encode([
            'success' => true,
            'message' => "Limpieza completada: Se eliminaron {$purgadosCount} respaldos antiguos, liberando {$mbLiberados} MB de espacio en disco."
        ]);
        exit;
    }

    /**
     * Exporta y descarga la base de datos actual en formato .SQL
     */
    public function exportarBd()
    {
        $config = require '../app/config/config.php';
        $dbName = $config['db']['name'] ?? 'proyecto_sgp';
        $dbHost = $config['db']['host'] ?? 'localhost';
        $dbUser = $config['db']['user'] ?? 'root';
        $dbPass = $config['db']['pass'] ?? '';
        $dbPort = $config['db']['port'] ?? 3306;

        $fileName = 'backup_bd_sgp_' . date('Y-m-d_His') . '.sql';
        $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        if (file_exists($mysqldumpPath)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $cmd = "\"{$mysqldumpPath}\" --host={$dbHost} --port={$dbPort} --user={$dbUser} ";
            if (!empty($dbPass)) $cmd .= "--password=\"{$dbPass}\" ";
            $cmd .= "--single-transaction --quick {$dbName}";
            passthru($cmd);
            exit;
        }

        Session::setFlash('error', 'La utilidad mysqldump no está disponible en este servidor.');
        $this->redirect('actualizaciones');
        exit;
    }

    private function obtenerHistorial(): array
    {
        if (file_exists($this->historialFile)) {
            $json = json_decode(file_get_contents($this->historialFile), true);
            if (is_array($json)) return $json;
        }
        return [];
    }

    private function guardarEnHistorial(array $registro): void
    {
        $historial = $this->obtenerHistorial();
        array_unshift($historial, $registro);
        // Guardar hasta los últimos 30 registros
        $historial = array_slice($historial, 0, 30);
        file_put_contents($this->historialFile, json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
