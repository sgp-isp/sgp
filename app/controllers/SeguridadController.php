<?php
/**
 * SeguridadController — Centro de Seguridad Institucional y Antifraude (SGP ISPEB)
 * 
 * Exclusivo para SuperAdministrador / Dirección de Tecnología:
 * - Supervisión y control de dispositivos vinculados (Kiosco QR y Biometría WebAuthn)
 * - Trazabilidad de eventos críticos: reseteos de PIN, cambios de clave y preguntas de seguridad
 * - Monitoreo de alertas antifraude y bloqueos de suplantación de identidad
 * - Despacho manual y supervisión de respaldos cifrados a la nube (Gmail oficial)
 */

require_once APPROOT . '/lib/MailService.php';

class SeguridadController extends Controller
{
    private $db;
    private $backupDir;

    public function __construct()
    {
        CacheControl::noCache();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        Session::start();
        RoleMiddleware::authorize([0, 1]); // Exclusivo SuperAdministrador y Administrador

        $this->db = Database::getInstance();
        $this->backupDir = APPROOT . '/storage/backups';

        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Vista principal del Centro de Seguridad Institucional
     */
    public function index()
    {
        // 1. Estadísticas Generales
        $stats = [
            'total_dispositivos_qr'  => 0,
            'total_biometria'        => 0,
            'alertas_fraude'         => 0,
            'reseteos_pin_30d'       => 0,
            'cambios_clave_30d'      => 0,
            'preguntas_seguridad_30d'=> 0,
            'ultimo_respaldo_nube'   => null
        ];

        // Total dispositivos QR (pasante_dispositivos)
        try {
            $this->db->query("SELECT COUNT(*) AS total FROM pasante_dispositivos WHERE estado = 'activo'");
            $row = $this->db->single();
            $stats['total_dispositivos_qr'] = (int)($row->total ?? 0);
        } catch (\Throwable $e) {}

        // Total biometría
        try {
            $this->db->query("SELECT COUNT(*) AS total FROM usuarios_biometria");
            $row = $this->db->single();
            $stats['total_biometria'] = (int)($row->total ?? 0);
        } catch (\Throwable $e) {}

        // Alertas de fraude y suplantación en los últimos 30 días
        try {
            $this->db->query("
                SELECT COUNT(*) AS total 
                FROM bitacora 
                WHERE accion IN (
                    'BLOQUEO_SUPLANTACION_DISPOSITIVO', 
                    'BLOQUEO_DISPOSITIVO_NO_AUTORIZADO', 
                    'INTENTO_SUPLANTACION_BIOMETRICA', 
                    'INTENTO_DUPLICAR_BIOMETRIA',
                    'LOGIN_FALLIDO_BLOQUEO'
                ) 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $row = $this->db->single();
            $stats['alertas_fraude'] = (int)($row->total ?? 0);
        } catch (\Throwable $e) {}

        // Reseteos de PIN en los últimos 30 días
        try {
            $this->db->query("
                SELECT COUNT(*) AS total 
                FROM bitacora 
                WHERE accion IN ('SOLICITUD_RESET_PIN', 'CAMBIO_PIN', 'RESET_PIN_ADMIN')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $row = $this->db->single();
            $stats['reseteos_pin_30d'] = (int)($row->total ?? 0);
        } catch (\Throwable $e) {}

        // Cambios de contraseña en los últimos 30 días
        try {
            $this->db->query("
                SELECT COUNT(*) AS total 
                FROM bitacora 
                WHERE accion IN ('CAMBIO_CLAVE', 'RESET_PASSWORD', 'PASSWORD_RESET_SOLICITUD')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $row = $this->db->single();
            $stats['cambios_clave_30d'] = (int)($row->total ?? 0);
        } catch (\Throwable $e) {}

        // Preguntas de seguridad en los últimos 30 días
        try {
            $this->db->query("
                SELECT COUNT(*) AS total 
                FROM bitacora 
                WHERE accion IN ('CAMBIO_PREGUNTAS_SEGURIDAD', 'UPDATE_PREGUNTAS', 'CONFIG_PREGUNTAS')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $row = $this->db->single();
            $stats['preguntas_seguridad_30d'] = (int)($row->total ?? 0);
        } catch (\Throwable $e) {}

        // Último despacho a la nube registrado
        try {
            $this->db->query("
                SELECT id, created_at, estado, detalles, asunto 
                FROM mail_historial 
                WHERE codigo_evento = 'RESPALDO_NUBE' 
                ORDER BY id DESC LIMIT 1
            ");
            $stats['ultimo_respaldo_nube'] = $this->db->single();
        } catch (\Throwable $e) {}

        // 2. Lista de Dispositivos Registrados (QR y Biometría)
        $dispositivosQr = [];
        try {
            $this->db->query("
                SELECT pd.id, pd.usuario_id, pd.device_uuid, pd.nombre_dispositivo, pd.estado,
                       pd.ip_registro, pd.creado_at, pd.ultimo_uso_at,
                       u.cedula, u.correo,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_usuario
                FROM pasante_dispositivos pd
                JOIN usuarios u ON u.id = pd.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                ORDER BY pd.ultimo_uso_at DESC
            ");
            $dispositivosQr = $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {}

        $dispositivosBio = [];
        try {
            $this->db->query("
                SELECT ub.id, ub.usuario_id, ub.credential_id, ub.device_name, ub.creado_at, ub.ultimo_uso_at,
                       u.cedula, u.correo,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_usuario
                FROM usuarios_biometria ub
                JOIN usuarios u ON u.id = ub.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                ORDER BY ub.ultimo_uso_at DESC
            ");
            $dispositivosBio = $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {}

        // 3. Bitácora de Alertas Antifraude y Eventos Críticos
        $alertas = [];
        try {
            $this->db->query("
                SELECT b.id, b.usuario_id, b.accion, b.tabla_afectada, b.registro_id,
                       b.ip_address, b.user_agent, b.detalles, b.created_at,
                       u.cedula, u.correo,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_usuario
                FROM bitacora b
                LEFT JOIN usuarios u ON u.id = b.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE b.accion IN (
                    'BLOQUEO_SUPLANTACION_DISPOSITIVO', 
                    'BLOQUEO_DISPOSITIVO_NO_AUTORIZADO', 
                    'INTENTO_SUPLANTACION_BIOMETRICA', 
                    'INTENTO_DUPLICAR_BIOMETRIA',
                    'LOGIN_FALLIDO_BLOQUEO',
                    'SOLICITUD_RESET_PIN',
                    'CAMBIO_PIN',
                    'RESET_PASSWORD',
                    'CAMBIO_CLAVE',
                    'ENROLL_BIOMETRIA',
                    'REMOVE_BIOMETRIA',
                    'DESVINCULAR_DISPOSITIVO_CONFIANZA',
                    'RESPALDO_NUBE'
                )
                ORDER BY b.id DESC
                LIMIT 60
            ");
            $alertas = $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {}

        // 4. Configuración SMTP
        $mailConfig = MailService::getConfig();

        $data = [
            'title'           => 'Centro de Seguridad Institucional — SGP',
            'stats'           => $stats,
            'dispositivosQr'  => $dispositivosQr,
            'dispositivosBio' => $dispositivosBio,
            'alertas'         => $alertas,
            'mailConfig'      => $mailConfig,
            'csrf_token'      => Session::generateCsrfToken()
        ];

        $this->view('seguridad/index', $data);
    }

    /**
     * AJAX POST: Desvincular un dispositivo (Kiosco QR o Biometría)
     */
    public function desvincular()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? $_POST['_csrf'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada.']);
            exit;
        }

        $tipo = trim($_POST['tipo'] ?? ''); // 'qr' o 'biometria'
        $id   = (int)($_POST['id'] ?? 0);

        if ($id <= 0 || !in_array($tipo, ['qr', 'biometria'], true)) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }

        try {
            if ($tipo === 'qr') {
                $this->db->query("
                    SELECT pd.usuario_id, pd.nombre_dispositivo, u.cedula,
                           TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                    FROM pasante_dispositivos pd
                    JOIN usuarios u ON u.id = pd.usuario_id
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE pd.id = :id LIMIT 1
                ");
                $this->db->bind(':id', $id);
                $disp = $this->db->single();

                if (!$disp) {
                    echo json_encode(['success' => false, 'message' => 'El dispositivo QR no fue encontrado']);
                    exit;
                }

                $this->db->query("DELETE FROM pasante_dispositivos WHERE id = :id");
                $this->db->bind(':id', $id);
                $this->db->execute();

                AuditModel::log('DESVINCULAR_DISPOSITIVO_CONFIANZA', 'pasante_dispositivos', $id, [
                    'usuario_id'   => $disp->usuario_id,
                    'pasante'      => $disp->nombre,
                    'cedula'       => $disp->cedula,
                    'dispositivo'  => $disp->nombre_dispositivo,
                    'desvinculado_por' => Session::get('user_name') ?? 'SuperAdmin'
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => "Dispositivo móvil '{$disp->nombre_dispositivo}' desvinculado exitosamente del pasante {$disp->nombre}."
                ]);
                exit;

            } else {
                // Biometría
                $this->db->query("
                    SELECT ub.usuario_id, ub.device_name, u.cedula,
                           TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                    FROM usuarios_biometria ub
                    JOIN usuarios u ON u.id = ub.usuario_id
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE ub.id = :id LIMIT 1
                ");
                $this->db->bind(':id', $id);
                $bio = $this->db->single();

                if (!$bio) {
                    echo json_encode(['success' => false, 'message' => 'La credencial biométrica no fue encontrada']);
                    exit;
                }

                $this->db->query("DELETE FROM usuarios_biometria WHERE id = :id");
                $this->db->bind(':id', $id);
                $this->db->execute();

                AuditModel::log('REMOVE_BIOMETRIA_ADMIN', 'usuarios_biometria', $id, [
                    'usuario_id'   => $bio->usuario_id,
                    'usuario'      => $bio->nombre,
                    'cedula'       => $bio->cedula,
                    'dispositivo'  => $bio->device_name,
                    'desvinculado_por' => Session::get('user_name') ?? 'SuperAdmin'
                ]);

                // Notificar por correo
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    MailService::notifyBiometricRemoved((int)$bio->usuario_id, (string)$bio->device_name, $ip);
                } catch (\Throwable $m) {}

                echo json_encode([
                    'success' => true,
                    'message' => "Credencial biométrica '{$bio->device_name}' revocada exitosamente para {$bio->nombre}."
                ]);
                exit;
            }

        } catch (\Throwable $e) {
            error_log('[SGP-SEGURIDAD] desvincular error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al desvincular el dispositivo: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * AJAX POST: Genera el respaldo MySQL completo, lo comprime en ZIP y lo envía a Gmail
     */
    public function respaldoNube()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? $_POST['_csrf'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada.']);
            exit;
        }

        // Prevenir ejecuciones concurrentes / cooldown de 60 segundos
        $lockFile = $this->backupDir . '/cloud_backup.lock';
        if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 60) {
            echo json_encode(['success' => false, 'message' => 'Un proceso de respaldo en la nube ya está en curso o finalizó hace menos de 1 minuto. Por favor, espere.']);
            exit;
        }
        @touch($lockFile);

        try {
            $config = require APPROOT . '/config/config.php';
            $dbConfig = $config['db'];

            $host = $dbConfig['host'];
            $user = $dbConfig['user'];
            $pass = $dbConfig['pass'];
            $dbname = $dbConfig['name'];

            $timestamp = date('Y-m-d_H-i-s');
            $sqlFile   = $this->backupDir . '/sgp_db_' . $timestamp . '.sql';
            $zipFile   = $this->backupDir . '/RESPALDO_SGP_NUBE_' . $timestamp . '.zip';

            // 1. Exportación MySQL
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

            // Si mysqldump falla, usar generador PHP nativo como fallback
            if ($returnVar !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
                $this->generarDumpPhp($sqlFile);
            }

            if (!file_exists($sqlFile) || filesize($sqlFile) === 0) {
                @unlink($lockFile);
                echo json_encode(['success' => false, 'message' => 'No se pudo generar el archivo de volcado SQL.']);
                exit;
            }

            // 2. Comprimir en archivo ZIP de alta eficiencia
            if (!class_exists('ZipArchive')) {
                @unlink($lockFile);
                echo json_encode(['success' => false, 'message' => 'La extensión PHP ZipArchive no está activa en el servidor.']);
                exit;
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                @unlink($lockFile);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el archivo ZIP de respaldo.']);
                exit;
            }

            $zip->addFile($sqlFile, 'database/sgp_institucional_' . $timestamp . '.sql');
            
            // Adjuntar archivo de información del respaldo
            $infoContent = "SISTEMA DE GESTIÓN DE PASANTÍAS (SGP ISPEB)\n";
            $infoContent .= "RESPALDO INTEGRAL PARA RECUPERACIÓN ANTE DESASTRES (DRP)\n";
            $infoContent .= "--------------------------------------------------------\n";
            $infoContent .= "Fecha de Generación: " . date('Y-m-d H:i:s') . "\n";
            $infoContent .= "Base de Datos: " . $dbname . "\n";
            $infoContent .= "Servidor: " . (gethostname() ?: 'Localhost') . "\n";
            $infoContent .= "Generado por: " . (Session::get('user_name') ?? 'SuperAdmin') . "\n";
            $zip->addFromString('INFO_RESPALDO.txt', $infoContent);
            $zip->close();

            // Eliminar el archivo .sql plano para ahorrar espacio en disco
            @unlink($sqlFile);

            // 3. Obtener estadísticas de la base de datos
            $this->db->query("SHOW TABLES");
            $tables = $this->db->resultSet() ?: [];
            $totalTablas = count($tables);

            // 4. Enviar a Gmail institucional con el adjunto ZIP
            $res = MailService::sendCloudBackup($zipFile, [
                'total_tablas' => $totalTablas
            ]);

            @unlink($lockFile);

            if (!empty($res['success'])) {
                AuditModel::log('RESPALDO_NUBE_EXITOSO', 'backups', 0, [
                    'archivo'   => basename($zipFile),
                    'peso'      => round(filesize($zipFile) / (1024 * 1024), 2) . ' MB',
                    'tablas'    => $totalTablas,
                    'receptor'  => 'sgp.ispeb@gmail.com'
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => '¡Respaldo enviado a la nube exitosamente! El archivo comprimido fue entregado a sgp.ispeb@gmail.com (' . round(filesize($zipFile) / (1024 * 1024), 2) . ' MB).'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'El respaldo se creó localmente pero falló el envío por correo: ' . ($res['message'] ?? 'Error desconocido')
                ]);
            }

        } catch (\Throwable $e) {
            @unlink($lockFile);
            error_log('[SGP-SEGURIDAD] respaldoNube error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error durante el respaldo: ' . $e->getMessage()]);
        }
    }

    /**
     * Fallback de volcado PHP nativo si mysqldump.exe no está en PATH
     */
    private function generarDumpPhp(string $destinationFile): void
    {
        $handle = fopen($destinationFile, 'w');
        if (!$handle) return;

        fwrite($handle, "-- SGP ISPEB NATIVE PHP SQL DUMP\n-- " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $this->db->query("SHOW TABLES");
        $tables = $this->db->resultSet() ?: [];

        foreach ($tables as $tObj) {
            $tArr = (array)$tObj;
            $tableName = reset($tArr);

            $this->db->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = (array)$this->db->single();
            $createSql = $createRow['Create Table'] ?? '';

            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n" . $createSql . ";\n\n");

            $this->db->query("SELECT * FROM `{$tableName}`");
            $rows = $this->db->resultSet() ?: [];

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
