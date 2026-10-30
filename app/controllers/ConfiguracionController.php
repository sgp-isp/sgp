<?php
require_once APPROOT . '/lib/MailService.php';

class ConfiguracionController extends Controller {

    private $db;

    public function __construct() {
        Session::start();

        // Determinar si la acción solicitada es pública (ej. Cartel QR imprimible desde el login)
        $url = isset($_GET['url']) ? explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL)) : [];
        $action = $url[1] ?? 'index';

        $publicActions = ['cartelQr'];

        if (!in_array($action, $publicActions, true)) {
            AuthMiddleware::require();
            AuthMiddleware::verificarEstado();
        }

        $config   = require '../app/config/config.php';
        $this->db = new Database($config['db']);
    }

    /**
     * [FIX-CS-1] Verifica CSRF para las acciones tipo formulario clásico (POST + redirect)
     * de index(). A diferencia de $this->verifyCsrf() (que responde JSON y corta la
     * petición — pensado para AJAX), este helper respeta el patrón de esta pantalla:
     * si el token falla, deja un flash de error y redirige de vuelta, igual que
     * cualquier otro fallo de validación de estos mismos bloques.
     */
    private function verifyCsrfOrRedirect(string $redirectTo = '/configuracion'): void
    {
        $token = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', '⚠️ Token de seguridad inválido o expirado. Vuelve a intentarlo.');
            header('Location: ' . URLROOT . $redirectTo);
            exit;
        }
    }

    public function index() {
        RoleMiddleware::authorize([0, 1, 2]);

        // ── POST: Guardar Configuración Servidor SMTP de Correo ──────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_smtp') {
            $this->verifyCsrfOrRedirect();
            if ((int)Session::get('role_id') !== 0) {
                Session::setFlash('error', '⛔ Acceso denegado: Solo el Super Administrador puede configurar el servidor SMTP.');
                header('Location: ' . URLROOT . '/notificaciones/correos#smtp_card');
                exit;
            }

            $smtpActivo    = isset($_POST['smtp_activo']) ? 1 : 0;
            $smtpHost      = trim($_POST['smtp_host'] ?? '');
            $smtpPort      = (int)($_POST['smtp_port'] ?? 587);
            $smtpUser      = trim($_POST['smtp_user'] ?? '');
            $smtpPass      = trim($_POST['smtp_pass'] ?? '');
            $smtpSecure    = trim($_POST['smtp_secure'] ?? 'tls');
            $mailFromName  = trim($_POST['mail_from_name'] ?? 'SGP - Instituto de Salud Pública');
            $mailFromEmail = trim($_POST['mail_from_email'] ?? 'sgp.ispeb@gmail.com');

            $campos = [
                'smtp_activo'     => [$smtpActivo, 'Habilita o pausa el envío de correos SMTP (1=activo, 0=inactivo)'],
                'smtp_host'       => [$smtpHost, 'Host o servidor SMTP para notificaciones'],
                'smtp_port'       => [$smtpPort, 'Puerto del servidor SMTP'],
                'smtp_user'       => [$smtpUser, 'Usuario o correo autenticado SMTP'],
                'smtp_secure'     => [$smtpSecure, 'Cifrado SSL/TLS para SMTP'],
                'mail_from_name'  => [$mailFromName, 'Nombre del remitente institucional'],
                'mail_from_email' => [$mailFromEmail, 'Correo del remitente institucional']
            ];

            if ($smtpPass !== '') {
                $campos['smtp_pass'] = [$smtpPass, 'Contraseña de autenticación SMTP'];
            }

            try {
                foreach ($campos as $clave => [$val, $desc]) {
                    $this->db->query("
                        INSERT INTO configuracion (clave, valor, descripcion)
                        VALUES (:clave, :valor, :desc)
                        ON DUPLICATE KEY UPDATE valor = :valor_up, updated_at = NOW()
                    ");
                    $this->db->bind(':clave',    $clave);
                    $this->db->bind(':valor',    (string)$val);
                    $this->db->bind(':desc',     $desc);
                    $this->db->bind(':valor_up', (string)$val);
                    $this->db->execute();
                }

                if (class_exists('AuditModel')) {
                    AuditModel::log('UPDATE_CONFIG_SMTP', 'configuracion', null, ['host' => $smtpHost, 'activo' => $smtpActivo]);
                }

                Session::setFlash('success', '✅ Configuración del servidor de correo institucional (SMTP) guardada exitosamente.');
            } catch (\Throwable $e) {
                error_log('[SGP-CONFIG] guardar_smtp error: ' . $e->getMessage());
                Session::setFlash('error', '❌ Error al guardar la configuración SMTP: ' . $e->getMessage());
            }

            header('Location: ' . URLROOT . '/notificaciones/correos#smtp_card');
            exit;
        }

        // ── POST: Guardar Datos de ISP ────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_isp') {
            $this->verifyCsrfOrRedirect();
            $nuevoIsp = [
                'nombre'    => trim($_POST['isp_nombre'] ?? ''),
                'rif'       => trim($_POST['isp_rif'] ?? ''),
                'estado'    => trim($_POST['isp_estado'] ?? ''),
                'ciudad'    => trim($_POST['isp_ciudad'] ?? ''),
                'direccion' => trim($_POST['isp_direccion'] ?? '')
            ];

            // [FIX-V-3] Antes no había ninguna validación server-side (ni "no vacío").
            if ($nuevoIsp['nombre'] === '' || $nuevoIsp['rif'] === '') {
                Session::setFlash('error', '⚠️ El nombre y el RIF de la institución son obligatorios.');
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }
            foreach (['nombre' => 150, 'rif' => 20, 'estado' => 100, 'ciudad' => 100, 'direccion' => 255] as $campo => $max) {
                if (strlen($nuevoIsp[$campo]) > $max) {
                    Session::setFlash('error', "⚠️ El campo \"{$campo}\" supera los {$max} caracteres permitidos.");
                    header('Location: ' . URLROOT . '/configuracion');
                    exit;
                }
            }

            $ispFile = APPROOT . '/config/isp_info.json';
            if (!is_dir(APPROOT . '/config')) {
                mkdir(APPROOT . '/config', 0777, true);
            }
            if (file_put_contents($ispFile, json_encode($nuevoIsp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                Session::setFlash('success', '✅ Datos de la Institución actualizados.');
            } else {
                Session::setFlash('error', '❌ Error al guardar los datos.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Guardar Tema y Colores (Fase 5 - Branding) ───────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_tema') {
            $this->verifyCsrfOrRedirect();
            $colorPrimario = trim($_POST['color_primario'] ?? '');
            $colorAcento   = trim($_POST['color_acento']   ?? '');

            $hexPattern = '/^#[0-9a-fA-F]{6}$/';
            if (!preg_match($hexPattern, $colorPrimario) || !preg_match($hexPattern, $colorAcento)) {
                Session::setFlash('error', '⚠️ Los colores deben estar en formato hexadecimal válido (ej. #162660).');
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }

            try {
                $this->db->query("
                    INSERT INTO configuracion (clave, valor, descripcion)
                    VALUES ('color_primario', :cp, 'Color institucional primario')
                    ON DUPLICATE KEY UPDATE valor = :cp, updated_at = NOW()
                ");
                $this->db->bind(':cp', $colorPrimario);
                $this->db->execute();

                $this->db->query("
                    INSERT INTO configuracion (clave, valor, descripcion)
                    VALUES ('color_acento', :ca, 'Color de acento de la interfaz')
                    ON DUPLICATE KEY UPDATE valor = :ca, updated_at = NOW()
                ");
                $this->db->bind(':ca', $colorAcento);
                $this->db->execute();

                if (class_exists('AuditModel')) {
                    AuditModel::log('ACTUALIZAR_TEMA', "Paleta actualizada: Primario: {$colorPrimario}, Acento: {$colorAcento}");
                }
                Session::setFlash('success', '🎨 Paleta de colores de la interfaz actualizada exitosamente.');
            } catch (\Throwable $e) {
                error_log('[SGP-BRANDING] Error guardar_tema: ' . $e->getMessage());
                Session::setFlash('error', '❌ Ocurrió un error al guardar los colores del tema.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Restablecer Tema por Defecto ─────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'restablecer_tema') {
            $this->verifyCsrfOrRedirect();
            try {
                $this->db->query("
                    INSERT INTO configuracion (clave, valor, descripcion)
                    VALUES ('color_primario', '#162660', 'Color institucional primario')
                    ON DUPLICATE KEY UPDATE valor = '#162660', updated_at = NOW()
                ");
                $this->db->execute();

                $this->db->query("
                    INSERT INTO configuracion (clave, valor, descripcion)
                    VALUES ('color_acento', '#2563eb', 'Color de acento de la interfaz')
                    ON DUPLICATE KEY UPDATE valor = '#2563eb', updated_at = NOW()
                ");
                $this->db->execute();

                if (class_exists('AuditModel')) {
                    AuditModel::log('RESET_TEMA', "Paleta institucional restablecida a valores por defecto");
                }
                Session::setFlash('success', '🔄 Tema restablecido a los colores institucionales predeterminados.');
            } catch (\Throwable $e) {
                Session::setFlash('error', '❌ Error al restablecer el tema.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Subir Logo Institucional (Fase 5 - Branding) ─────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'subir_logo') {
            $this->verifyCsrfOrRedirect();
            $tipoLogo = trim($_POST['tipo_logo'] ?? '');

            $archivosMap = [
                'logo_principal' => [
                    'primario' => 'logo.png',
                    'copias'   => ['logo-header.png', 'logo-full.png'],
                    'label'    => 'Logo Institucional Principal'
                ],
                'cintillo' => [
                    'primario' => 'cintillo-salud.png',
                    'copias'   => ['cintillo_pdf.png'],
                    'label'    => 'Cintillo Oficial para Reportes'
                ],
                'cintillo_sistema' => [
                    'primario' => 'cintillo.png',
                    'copias'   => [],
                    'label'    => 'Cintillo Oficial del Sistema'
                ],
                'gobernacion' => [
                    'primario' => 'gobe.png',
                    'copias'   => [],
                    'label'    => 'Logo de Gobernación'
                ]
            ];

            if (!isset($archivosMap[$tipoLogo])) {
                Session::setFlash('error', '⚠️ Tipo de logo inválido seleccionado.');
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }

            if (!isset($_FILES['archivo_logo']) || $_FILES['archivo_logo']['error'] !== UPLOAD_ERR_OK) {
                $errCode = $_FILES['archivo_logo']['error'] ?? -1;
                $msgErr = $errCode === UPLOAD_ERR_INI_SIZE ? 'El archivo excede el tamaño máximo permitido.' : 'No se seleccionó ningún archivo o ocurrió un error al subirlo.';
                Session::setFlash('error', '⚠️ ' . $msgErr);
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }

            $file = $_FILES['archivo_logo'];
            $maxBytes = 3 * 1024 * 1024; // 3MB

            if ($file['size'] > $maxBytes) {
                Session::setFlash('error', '⚠️ El archivo no debe superar los 3 MB.');
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }

            // Validar extensión
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
                Session::setFlash('error', '⚠️ Formato no permitido. Solo se aceptan imágenes PNG o JPG.');
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }

            // Validar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg'], true)) {
                Session::setFlash('error', '⚠️ El archivo no es una imagen válida.');
                header('Location: ' . URLROOT . '/configuracion');
                exit;
            }

            $imgDir = dirname(APPROOT) . '/public/img';
            if (!is_dir($imgDir)) {
                @mkdir($imgDir, 0755, true);
            }

            $targetConfig = $archivosMap[$tipoLogo];
            $destPath = $imgDir . '/' . $targetConfig['primario'];

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                foreach ($targetConfig['copias'] as $copia) {
                    @copy($destPath, $imgDir . '/' . $copia);
                }

                if (class_exists('AuditModel')) {
                    AuditModel::log('ACTUALIZAR_LOGO', "Actualizado: {$targetConfig['label']} ({$targetConfig['primario']})");
                }
                Session::setFlash('success', "✅ {$targetConfig['label']} actualizado correctamente.");
            } else {
                Session::setFlash('error', '❌ No se pudo guardar el archivo en el servidor. Verifica los permisos de la carpeta de imágenes.');
            }

            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_feriado') {
            $this->verifyCsrfOrRedirect();
            $fecha       = trim($_POST['fecha']       ?? '');
            $nombre      = trim($_POST['nombre']      ?? '');
            $tipo        = trim($_POST['tipo']        ?? 'Nacional');
            $esLaborable = isset($_POST['es_laborable']) && $_POST['es_laborable'] === '1' ? 1 : 0;

            $tiposValidos = ['Nacional', 'Regional', 'Institucional'];
            if (!in_array($tipo, $tiposValidos, true)) $tipo = 'Nacional';

            if ($fecha && $nombre) {
                // Validar formato de fecha
                $dt = DateTime::createFromFormat('Y-m-d', $fecha);
                if (!$dt || $dt->format('Y-m-d') !== $fecha) {
                    Session::setFlash('error', '⚠️ Formato de fecha inválido.');
                } else {
                    try {
                        $feriadoModel = new FeriadoModel($this->db);
                        if ($feriadoModel->existeFecha($fecha)) {
                            Session::setFlash('error', '⚠️ Ya existe un feriado registrado para esa fecha.');
                        } else {
                            $feriadoModel->crear($fecha, $nombre, $tipo, $esLaborable);
                            // Sincronizar registros de asistencia existentes para esa fecha
                            $sync = $feriadoModel->sincronizarAsistencias($fecha, $esLaborable);
                            $msg  = '✅ Feriado "' . htmlspecialchars($nombre) . '" registrado correctamente.';
                            if ($sync['corregidos'] > 0) {
                                $msg .= " Se corrigieron {$sync['corregidos']} registro(s) de asistencia.";
                            }
                            if ($sync['conflictos_manuales'] > 0) {
                                $msg .= " ⚠️ {$sync['conflictos_manuales']} pasante(s) tiene(n) marcas manuales 'Presente' en esta fecha — revísalos manualmente.";
                            }
                            Session::setFlash('success', $msg);
                            if (class_exists('AuditModel')) {
                                AuditModel::log('AGREGAR_FERIADO', "Feriado registrado: $nombre ($fecha) | Corregidos: {$sync['corregidos']} | Conflictos: {$sync['conflictos_manuales']}");
                            }
                        }
                    } catch (Exception $e) {
                        error_log('[SGP-CONFIG] agregar_feriado error: ' . $e->getMessage());
                        Session::setFlash('error', '❌ Error al registrar el feriado.');
                    }
                }
            } else {
                Session::setFlash('error', '⚠️ La fecha y el nombre del feriado son obligatorios.');
            }
            if (isset($_POST['origen']) && $_POST['origen'] === 'almanaque') {
                header('Location: ' . URLROOT . '/configuracion/calendario_feriados');
            } else {
                header('Location: ' . URLROOT . '/configuracion');
            }
            exit;
        }

        // ── POST: Editar feriado (nombre, tipo y laborabilidad) ─────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_feriado') {
            $this->verifyCsrfOrRedirect();
            $id          = (int)($_POST['id']     ?? 0);
            $nombre      = trim($_POST['nombre']  ?? '');
            $tipo        = trim($_POST['tipo']    ?? 'Nacional');
            $esLaborable = isset($_POST['es_laborable']) && $_POST['es_laborable'] === '1' ? 1 : 0;

            $tiposValidos = ['Nacional', 'Regional', 'Institucional'];
            if (!in_array($tipo, $tiposValidos, true)) $tipo = 'Nacional';

            if ($id > 0 && $nombre !== '') {
                try {
                    $feriadoModel = new FeriadoModel($this->db);
                    $feriado = $feriadoModel->obtenerPorId($id);
                    if (!$feriado) {
                        Session::setFlash('error', '⚠️ Feriado no encontrado.');
                    } else {
                        $esLaborableAnterior = (int)($feriado->es_laborable ?? 0);
                        $feriadoModel->actualizar($id, $nombre, $tipo, $esLaborable);
                        $msg = '✅ Feriado actualizado correctamente.';
                        // Solo sincronizar si cambia la laborabilidad o si ya era no laborable
                        if ($esLaborableAnterior !== $esLaborable || $esLaborable === 0) {
                            $sync = $feriadoModel->sincronizarAsistencias($feriado->fecha, $esLaborable);
                            if ($sync['corregidos'] > 0) {
                                $msg .= " Se corrigieron {$sync['corregidos']} registro(s) de asistencia.";
                            }
                            if ($sync['conflictos_manuales'] > 0) {
                                $msg .= " ⚠️ {$sync['conflictos_manuales']} pasante(s) tiene(n) 'Presente' manual en esta fecha — revísalos.";
                            }
                        }
                        Session::setFlash('success', $msg);
                        if (class_exists('AuditModel')) {
                            AuditModel::log('EDITAR_FERIADO', "Feriado editado ID: $id → \"$nombre\" ($tipo) es_laborable: $esLaborableAnterior→$esLaborable");
                        }
                    }
                } catch (Exception $e) {
                    error_log('[SGP-CONFIG] editar_feriado error: ' . $e->getMessage());
                    Session::setFlash('error', '❌ Error al editar el feriado.');
                }
            } else {
                Session::setFlash('error', '⚠️ El nombre del feriado es obligatorio.');
            }
            if (isset($_POST['origen']) && $_POST['origen'] === 'almanaque') {
                header('Location: ' . URLROOT . '/configuracion/calendario_feriados');
            } else {
                header('Location: ' . URLROOT . '/configuracion');
            }
            exit;
        }

        // ── POST: Eliminar feriado ────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_feriado') {
            $this->verifyCsrfOrRedirect();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $feriadoModel = new FeriadoModel($this->db);
                    $feriado = $feriadoModel->obtenerPorId($id);
                    if (!$feriado) {
                        Session::setFlash('error', '⚠️ Feriado no encontrado.');
                    } elseif (strtotime($feriado->fecha) < strtotime(date('Y-m-d'))) {
                        Session::setFlash('error', '⚠️ No se pueden eliminar feriados pasados (auditoría histórica).');
                    } else {
                        $feriadoModel->eliminar($id);
                        Session::setFlash('success', '✅ Feriado eliminado correctamente.');
                        if (class_exists('AuditModel')) {
                            AuditModel::log('ELIMINAR_FERIADO', "Feriado eliminado ID: $id ({$feriado->nombre})");
                        }
                    }
                } catch (Exception $e) {
                    Session::setFlash('error', '❌ Error al eliminar feriado.');
                }
            }
            if (isset($_POST['origen']) && $_POST['origen'] === 'almanaque') {
                header('Location: ' . URLROOT . '/configuracion/calendario_feriados');
            } else {
                header('Location: ' . URLROOT . '/configuracion');
            }
            exit;
        }

        // ── POST: Mantenimiento — Limpiar sesiones antiguas ───────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'limpiar_sesiones') {
            $this->verifyCsrfOrRedirect();
            try {
                // Eliminar registros de sesiones con más de 30 días (si existe la tabla)
                $this->db->query("DELETE FROM sesiones WHERE creado_en < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $this->db->execute();
                $afectados = $this->db->rowCount();
                Session::setFlash('success', "✅ Sesiones antiguas limpiadas ($afectados registros eliminados).");
                if (class_exists('AuditModel')) {
                    AuditModel::log('MANTENIMIENTO_SESIONES', "Limpieza de sesiones antiguas: $afectados eliminadas");
                }
            } catch (Exception $e) {
                // La tabla puede no existir; no es error crítico
                Session::setFlash('success', '✅ Mantenimiento ejecutado correctamente.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Mantenimiento — Purgar bitácora antigua ─────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'purgar_bitacora') {
            $this->verifyCsrfOrRedirect();
            $meses = max(1, min(24, (int)($_POST['meses'] ?? 6)));
            try {
                $this->db->query("DELETE FROM bitacora WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL :meses MONTH)");
                $this->db->bind(':meses', $meses);
                $this->db->execute();
                $afectados = $this->db->rowCount();
                Session::setFlash('success', "✅ Bitácora purgada: $afectados registros anteriores a $meses meses eliminados.");
                if (class_exists('AuditModel')) {
                    AuditModel::log('MANTENIMIENTO_BITACORA', "Purga de bitácora: $afectados registros eliminados (>$meses meses)");
                }
            } catch (Exception $e) {
                error_log('[SGP-CONFIG] purgar_bitacora error: ' . $e->getMessage());
                Session::setFlash('error', '❌ Error al purgar la bitácora.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }


        // ── POST: Agregar institución ─────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_institucion') {
            $this->verifyCsrfOrRedirect();
            $nombre    = trim($_POST['nombre']    ?? '');
            $direccion = trim($_POST['direccion'] ?? '');

            if ($nombre && $direccion) {
                try {
                    $this->db->query("SELECT COUNT(*) as total FROM instituciones WHERE nombre = :nombre AND categoria = 'origen'");
                    $this->db->bind(':nombre', $nombre);
                    $row = $this->db->single();
                    if ($row->total > 0) {
                        Session::setFlash('error', '⚠️ Ya existe una institución con ese nombre.');
                    } else {
                        $this->db->query("INSERT INTO instituciones (nombre, direccion, categoria) VALUES (:nombre, :direccion, 'origen')");
                        $this->db->bind(':nombre',    $nombre);
                        $this->db->bind(':direccion', $direccion);
                        $this->db->execute();
                        Session::setFlash('success', '✅ Institución "' . htmlspecialchars($nombre) . '" registrada correctamente.');
                    }
                } catch (Exception $e) {
                    error_log('[SGP-CONFIG] agregar_institucion error: ' . $e->getMessage());
                    Session::setFlash('error', '❌ Error al registrar la institución.');
                }
            } else {
                Session::setFlash('error', '⚠️ Completa todos los campos de la institución.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Editar institución ──────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_institucion') {
            $this->verifyCsrfOrRedirect();
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $rep_nombre = trim($_POST['representante_nombre'] ?? '');
            $rep_cargo = trim($_POST['representante_cargo'] ?? '');
            $rep_correo = trim($_POST['representante_correo'] ?? '');
            $rep_telefono = trim($_POST['representante_telefono'] ?? '');

            if ($id > 0 && $nombre && $direccion) {
                try {
                    $this->db->query("SELECT COUNT(*) as total FROM instituciones WHERE nombre = :nombre AND id != :id AND categoria = 'origen'");
                    $this->db->bind(':nombre', $nombre);
                    $this->db->bind(':id', $id);
                    $row = $this->db->single();

                    if ($row->total > 0) {
                        Session::setFlash('error', '⚠️ Ya existe otra institución con ese nombre.');
                    } else {
                        $this->db->query("
                            UPDATE instituciones 
                            SET nombre = :nombre, 
                                direccion = :direccion,
                                representante_nombre = :rep_nombre,
                                representante_cargo = :rep_cargo,
                                representante_correo = :rep_correo,
                                representante_telefono = :rep_telefono
                            WHERE id = :id
                        ");
                        $this->db->bind(':nombre', $nombre);
                        $this->db->bind(':direccion', $direccion);
                        $this->db->bind(':rep_nombre', $rep_nombre ?: null);
                        $this->db->bind(':rep_cargo', $rep_cargo ?: null);
                        $this->db->bind(':rep_correo', $rep_correo ?: null);
                        $this->db->bind(':rep_telefono', $rep_telefono ?: null);
                        $this->db->bind(':id', $id);
                        $this->db->execute();

                        Session::setFlash('success', '✅ Institución "' . htmlspecialchars($nombre) . '" actualizada correctamente.');
                    }
                } catch (Exception $e) {
                    error_log('[SGP-CONFIG] editar_institucion error: ' . $e->getMessage());
                    Session::setFlash('error', '❌ Error al editar la institución.');
                }
            } else {
                Session::setFlash('error', '⚠️ Faltan datos obligatorios para editar la institución.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Eliminar institución ────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_institucion') {
            $this->verifyCsrfOrRedirect();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Verificar que no tenga pasantes asignados
                    $this->db->query("SELECT COUNT(*) as total FROM datos_pasante WHERE institucion_id = :id");
                    $this->db->bind(':id', $id);
                    $row = $this->db->single();
                    if ($row->total > 0) {
                        Session::setFlash('error', '⚠️ No se puede eliminar: hay ' . $row->total . ' pasante(s) asignados a esta institución.');
                    } else {
                        $this->db->query("DELETE FROM instituciones WHERE id = :id AND categoria = 'origen'");
                        $this->db->bind(':id', $id);
                        $this->db->execute();
                        Session::setFlash('success', '✅ Institución eliminada correctamente.');
                    }
                } catch (Exception $e) {
                    Session::setFlash('error', '❌ Error al eliminar institución.');
                }
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Agregar departamento ────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_departamento') {
            $this->verifyCsrfOrRedirect();
            $nombre      = trim($_POST['nombre']      ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');

            if ($nombre) {
                try {
                    $this->db->query("SELECT COUNT(*) as total FROM departamentos WHERE nombre = :nombre");
                    $this->db->bind(':nombre', $nombre);
                    $row = $this->db->single();
                    if ($row->total > 0) {
                        Session::setFlash('error', '⚠️ Ya existe un departamento con ese nombre.');
                    } else {
                        $this->db->query("INSERT INTO departamentos (nombre, descripcion, activo) VALUES (:nombre, :descripcion, 1)");
                        $this->db->bind(':nombre',      $nombre);
                        $this->db->bind(':descripcion', $descripcion);
                        $this->db->execute();
                        Session::setFlash('success', '✅ Departamento "' . htmlspecialchars($nombre) . '" creado correctamente.');
                    }
                } catch (Exception $e) {
                    error_log('[SGP-CONFIG] agregar_departamento error: ' . $e->getMessage());
                    Session::setFlash('error', '❌ Error al crear el departamento.');
                }
            } else {
                Session::setFlash('error', '⚠️ El nombre del departamento es obligatorio.');
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── POST: Eliminar departamento (lógico) ──────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_departamento') {
            $this->verifyCsrfOrRedirect();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Verificar que no tenga usuarios asignados
                    $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE departamento_id = :id");
                    $this->db->bind(':id', $id);
                    $row = $this->db->single();
                    if ($row->total > 0) {
                        Session::setFlash('error', '⚠️ No se puede eliminar: hay ' . $row->total . ' usuario(s) asignados a este departamento.');
                    } else {
                        $this->db->query("DELETE FROM departamentos WHERE id = :id");
                        $this->db->bind(':id', $id);
                        $this->db->execute();
                        Session::setFlash('success', '✅ Departamento eliminado correctamente.');
                    }
                } catch (Exception $e) {
                    Session::setFlash('error', '❌ Error al eliminar departamento.');
                }
            }
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        // ── GET: Consultar datos ──────────────────────────────────────────
        $instituciones = [];
        $departamentos = [];
        $feriados      = [];
        $statsDB       = [];

        try {
            $this->db->query("SELECT * FROM instituciones WHERE categoria = 'origen' ORDER BY nombre ASC");
            $instituciones = array_map(fn($i) => (array) $i, $this->db->resultSet());
        } catch (Exception $e) { /* tabla vacía o no existe */ }

        try {
            $this->db->query("SELECT * FROM departamentos ORDER BY nombre ASC");
            $departamentos = array_map(fn($d) => (array) $d, $this->db->resultSet());
        } catch (Exception $e) { /* tabla vacía o no existe */ }

        try {
            $feriadoModel = new FeriadoModel($this->db);
            $feriados     = array_map(fn($f) => (array) $f, $feriadoModel->obtenerVigentes());
        } catch (Exception $e) { /* tabla feriados puede no existir aún */ }

        // Pasantes activos sin marcar asistencia hoy (para el reloj de mantenimiento)
        try {
            $this->db->query("
                SELECT COUNT(*) AS total
                FROM usuarios u
                INNER JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                WHERE u.rol_id = 3
                  AND u.estado = 'activo'
                  AND dpa.estado_pasantia = 'Activo'
                  AND NOT EXISTS (
                      SELECT 1 FROM asistencias a
                      WHERE a.pasante_id = u.id AND a.fecha = CURDATE()
                  )
            ");
            $row = $this->db->single();
            $statsDB['sin_marcar_hoy'] = (int)($row->total ?? 0);
        } catch (Exception $e) { $statsDB['sin_marcar_hoy'] = 0; }

        // Estadísticas básicas para la card de mantenimiento
        try {
            $this->db->query("SELECT COUNT(*) AS total FROM usuarios");
            $row = $this->db->single();
            $statsDB['usuarios'] = (int)($row->total ?? 0);
        } catch (Exception $e) { $statsDB['usuarios'] = 0; }

        try {
            $this->db->query("SELECT COUNT(*) AS total FROM asistencias");
            $row = $this->db->single();
            $statsDB['asistencias'] = (int)($row->total ?? 0);
        } catch (Exception $e) { $statsDB['asistencias'] = 0; }

        try {
            $this->db->query("SELECT COUNT(*) AS total FROM bitacora WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $row = $this->db->single();
            $statsDB['bitacora_mes'] = (int)($row->total ?? 0);
        } catch (Exception $e) { $statsDB['bitacora_mes'] = 0; }

        // ── Notificaciones Inteligentes (Fase 4) ─────────────────────────
        $notifAnalytics = [
            'total_enviadas'    => 0,
            'total_leidas'      => 0,
            'total_clickeadas'  => 0,
            'tasa_lectura'      => 0.0,
            'tasa_clicks'       => 0.0,
            'global_activo'     => true,
            'ultimas'           => []
        ];
        $notifUsuarios = [];
        try {
            require_once APPROOT . '/models/NotificationModel.php';
            $notifModel     = new NotificationModel($this->db);
            $notifAnalytics = $notifModel->getAnalytics();
            $notifUsuarios  = $notifModel->getUsersList();
        } catch (\Throwable $e) {
            error_log('[SGP-CONFIG] Error cargando notificaciones: ' . $e->getMessage());
        }

        // Estado del kiosco
        $kioscoActivo = 1;
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'kiosco_activo' LIMIT 1");
            $row = $this->db->single();
            $kioscoActivo = $row ? (int)$row->valor : 1;
        } catch (Exception $e) {
            // Fallback: leer de sesión o asumir activo
            $kioscoActivo = (int)(Session::get('kiosco_activo') ?? 1);
        }

        // ── Leer Datos de Institución (ISP) ──────────────────────────────
        $ispFile = APPROOT . '/config/isp_info.json';
        $ispInfo = [
            'nombre'    => 'Instituto de Salud Pública del Estado Bolívar (ISP)',
            'rif'       => 'G-20000366-9',
            'estado'    => 'Bolívar',
            'ciudad'    => 'Ciudad Bolívar',
            'direccion' => 'Paseo Meneses, Torre ISP, Piso 3'
        ];
        if (file_exists($ispFile)) {
            $json = json_decode(file_get_contents($ispFile), true);
            if (is_array($json)) {
                $ispInfo = array_merge($ispInfo, $json);
            }
        }

        // Branding Institucional (Fase 5)
        $colorPrimario = '#162660';
        $colorAcento   = '#2563eb';
        try {
            $this->db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('color_primario', 'color_acento')");
            foreach ($this->db->resultSet() as $brandRow) {
                if ($brandRow->clave === 'color_primario' && !empty($brandRow->valor)) $colorPrimario = $brandRow->valor;
                if ($brandRow->clave === 'color_acento' && !empty($brandRow->valor)) $colorAcento = $brandRow->valor;
            }
        } catch (\Throwable $e) {}

        // Token del Quiosco QR (Fase 5)
        $kioscoQrToken = 'ISPEB-ASIST-2026-HQ';
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'kiosco_qr_token' LIMIT 1");
            $qrRow = $this->db->single();
            if ($qrRow && !empty($qrRow->valor)) {
                $kioscoQrToken = $qrRow->valor;
            }
        } catch (\Throwable $e) {}

        // URL Pública / Túnel para Cartel QR y móviles
        $urlPublica = '';
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'url_publica' LIMIT 1");
            $uRow = $this->db->single();
            if ($uRow && !empty($uRow->valor)) {
                $urlPublica = $uRow->valor;
            }
        } catch (\Throwable $e) {}

        // Estado de la Geocerca GPS (Geolocalización)
        $geocercaActiva = 1;
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'geocerca_activa' LIMIT 1");
            $gRow = $this->db->single();
            if ($gRow) $geocercaActiva = (int)$gRow->valor;
        } catch (\Throwable $e) {}

        // Configuración de Correo Electrónico Institucional (SMTP)
        $smtpConfig = MailService::getConfig();

        // Estadísticas biométricas y de acceso móvil
        $biometriaStats = [
            'total_dispositivos'  => 0,
            'usuarios_con_huella' => 0,
            'accesos_semana'      => 0
        ];
        try {
            $this->db->query("SELECT COUNT(*) AS total, COUNT(DISTINCT usuario_id) AS usuarios FROM usuarios_biometria");
            $bRow = $this->db->single();
            if ($bRow) {
                $biometriaStats['total_dispositivos']  = (int)($bRow->total ?? 0);
                $biometriaStats['usuarios_con_huella'] = (int)($bRow->usuarios ?? 0);
            }
            $this->db->query("SELECT COUNT(*) AS total FROM bitacora WHERE accion = 'LOGIN_BIOMETRIA' AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $lRow = $this->db->single();
            if ($lRow) {
                $biometriaStats['accesos_semana'] = (int)($lRow->total ?? 0);
            }
        } catch (\Throwable $e) {}

        $data = [
            'title'          => 'Configuración del Sistema',
            'instituciones'  => $instituciones,
            'departamentos'  => $departamentos,
            'feriados'       => $feriados,
            'statsDB'        => $statsDB,
            'kioscoActivo'   => $kioscoActivo,
            'geocercaActiva' => $geocercaActiva,
            'kioscoQrToken'  => $kioscoQrToken,
            'urlPublica'     => $urlPublica,
            'ispInfo'        => $ispInfo,
            'notifAnalytics' => $notifAnalytics,
            'notifUsuarios'  => $notifUsuarios,
            'colorPrimario'  => $colorPrimario,
            'colorAcento'    => $colorAcento,
            'smtp'           => $smtpConfig,
            'biometriaStats' => $biometriaStats,
        ];

        $this->view('configuracion/index', $data);
    }

    /**
     * AJAX: Enviar correo de prueba para validar credenciales SMTP
     */
    public function probarSmtp() {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');
        RoleMiddleware::authorize([0, 1, 2]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $destinatario = trim($_POST['email_destino'] ?? '');

        // Si no se proporcionó destinatario, buscar el correo del usuario en sesión
        if ($destinatario === '') {
            $uid = (int)Session::get('user_id');
            $this->db->query("SELECT correo FROM usuarios WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $uid);
            $u = $this->db->single();
            $destinatario = $u ? ($u->correo ?? '') : '';
        }

        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Ingresa una dirección de correo electrónico válida para la prueba.']);
            return;
        }

        $nombre = Session::get('user_name') ?: 'Administrador SGP';
        $fecha = date('d/m/Y h:i:s A');

        $htmlContent = "
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Este es un <strong>correo de diagnóstico y prueba institucional</strong> emitido por el Sistema de Gestión de Pasantes (SGP) del Instituto de Salud Pública del Estado Bolívar.</p>
            <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                <div style='font-size: 14px; font-weight: 800; color: #166534; margin-bottom: 6px;'>✅ Conexión SMTP Exitosa</div>
                <div style='font-size: 13px; color: #15803d;'>El servidor de correo institucional se encuentra correctamente configurado y listo para despachar notificaciones automáticas, exámenes rápidos y alertas biométricas.</div>
                <div style='font-size: 12px; color: #166534; margin-top: 8px;'><strong>Fecha y Hora de Emisión:</strong> {$fecha}</div>
            </div>
            <p style='color: #64748b; font-size: 13px;'>Si recibiste este mensaje, la autenticación SMTP y los protocolos de entrega funcionan óptimamente.</p>
        ";

        $html = MailService::renderBentoTemplate(
            'Prueba de Conexión',
            '#16a34a',
            'Diagnóstico SMTP — SGP',
            $htmlContent,
            '',
            '',
            'Mensaje emitido para validar el canal de comunicación SMTP.'
        );

        $res = MailService::send($destinatario, $nombre, '🧪 Prueba de Conexión SMTP — SGP', $html);

        if (!empty($res['success'])) {
            $isSimulated = !empty($res['simulated']);
            echo json_encode([
                'success' => true,
                'simulated' => $isSimulated,
                'message' => $isSimulated
                    ? 'Modo Simulación: El servicio SMTP no está activo o faltan credenciales en la base de datos (revisa el log).'
                    : "¡Excelente! Correo de prueba enviado con éxito a: {$destinatario}."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fallo al transmitir vía SMTP: ' . ($res['message'] ?? 'Error desconocido')
            ]);
        }
    }

    /**
     * AJAX: Buscar pasante por cédula para resetear PIN
     */
    public function buscarPasante() {
        header('Content-Type: application/json');

        // [FIX-AC-2] Antes cualquier sesión válida (incluido un Pasante) podía
        // buscar a cualquier otro pasante por cédula. Restringido a Admin/SuperAdmin,
        // igual que el resto de acciones sensibles de este controlador.
        RoleMiddleware::authorize([0, 1]);

        $cedula = trim($_GET['cedula'] ?? '');
        if (!$cedula) {
            echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
            exit;
        }

        // FIX: Normalizar cédula (solo dígitos) para tolerar prefijos V-, E-, etc.
        $cedulaNumeros = preg_replace('/[^0-9]/', '', $cedula);

        try {
            $this->db->query("
                SELECT u.id, u.cedula,
                       COALESCE(dp.nombres, u.correo) AS nombres,
                       COALESCE(dp.apellidos, '')      AS apellidos
                FROM usuarios u
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                WHERE REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :cedula_num
                  AND u.rol_id = 3
                LIMIT 1
            ");
            $this->db->bind(':cedula_num', $cedulaNumeros);
            $pasante = $this->db->single();

            if ($pasante) {
                echo json_encode([
                    'success' => true, 
                    'pasante' => [
                        'id'     => $pasante->id,
                        'nombre' => trim($pasante->nombres . ' ' . $pasante->apellidos),
                        'cedula' => $pasante->cedula
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Pasante no encontrado. Verifica la cédula.']);
            }
        } catch (Exception $e) {
            // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
            error_log('[SGP-CONFIG] buscarPasante error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al realizar la búsqueda.']);
        }
        exit;
    }

    /**
     * AJAX: Resetear PIN de un pasante
     */
    public function resetearPin() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-AC-2] Antes cualquier sesión válida (incluido un Pasante) podía
        // resetear el PIN de kiosco de cualquier otro pasante y recibirlo en
        // texto plano. Restringido a Admin/SuperAdmin.
        RoleMiddleware::authorize([0, 1]);

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido']);
            exit;
        }

        // Generar un nuevo PIN aleatorio de 4 dígitos
        $nuevoPin = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // SEGURIDAD: Hashear el PIN antes de almacenarlo (nunca guardar en texto plano)
        $pinHasheado = password_hash($nuevoPin, PASSWORD_BCRYPT);
        try {
            $this->db->query("UPDATE usuarios SET pin_asistencia = :pin WHERE id = :id AND rol_id = 3");
            $this->db->bind(':pin', $pinHasheado);
            $this->db->bind(':id', $id);
            
            if ($this->db->execute()) {
                // Registrar en bitácora
                if (class_exists('AuditModel')) {
                    $this->db->query("SELECT CONCAT(dp.nombres,' ',dp.apellidos) AS nombre FROM datos_personales dp WHERE dp.usuario_id = :pid LIMIT 1");
                    $this->db->bind(':pid', $id);
                    $nombrePas = trim($this->db->single()->nombre ?? "ID:$id");
                    AuditModel::log('RESET_PIN_KIOSCO', "PIN reseteado — $nombrePas (desde Configuración)");
                }

                // Resolver notificaciones de solicitud_pin para todos (admin + tutor)
                require_once APPROOT . '/models/NotificationModel.php';
                $notifModel = new NotificationModel($this->db);
                $notifModel->resolverPorReferencia('solicitud_pin', $id);

                echo json_encode([
                    'success'  => true,
                    'message'  => 'PIN restablecido correctamente.',
                    'nuevo_pin' => $nuevoPin
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el PIN']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al resetear PIN']);
        }
        exit;
    }

    /**
     * AJAX: Regenerar Token del Quiosco QR de Asistencia (Fase 5)
     */
    public function regenerarQrToken()
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]); // Admin / SuperAdmin

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $csrf = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrf)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o expirado.']);
            exit;
        }

        try {
            // Generar token único y seguro
            $nuevoToken = 'ISPEB-' . strtoupper(bin2hex(random_bytes(5))) . '-' . date('Y');

            $this->db->query("
                INSERT INTO configuracion (clave, valor, descripcion)
                VALUES ('kiosco_qr_token', :token, 'Token secreto para código QR del kiosco de asistencia')
                ON DUPLICATE KEY UPDATE valor = :token, updated_at = NOW()
            ");
            $this->db->bind(':token', $nuevoToken);
            $this->db->execute();

            if (class_exists('AuditModel')) {
                AuditModel::log('REGENERAR_QR_KIOSCO', "Se regeneró el token de asistencia QR del Kiosco: {$nuevoToken}");
            }

            echo json_encode([
                'success'     => true,
                'nuevo_token' => $nuevoToken,
                'qr_url'      => URLROOT . '/kiosco?token=' . $nuevoToken,
                'message'     => 'Nuevo código QR generado con éxito. Recuerda imprimir y colocar el nuevo cartel en la oficina.'
            ]);
        } catch (Exception $e) {
            error_log('[SGP-CONFIG] Error al regenerar token QR: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al guardar el nuevo token en la base de datos.']);
        }
        exit;
    }

    /**
     * AJAX POST: Guardar URL Pública / Túnel para acceso móvil y Cartel QR
     */
    public function guardarUrlPublica()
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $csrf = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrf)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o expirado.']);
            exit;
        }

        $url = trim($_POST['url_publica'] ?? '');
        if (!empty($url)) {
            $url = rtrim($url, '/');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => false, 'message' => 'Ingresa una URL válida (ejemplo: https://mi-tunel.trycloudflare.com/SGP).']);
                exit;
            }
        }

        try {
            $this->db->query("
                INSERT INTO configuracion (clave, valor, descripcion)
                VALUES ('url_publica', :url, 'URL pública o túnel para acceso móvil y generación del Cartel QR')
                ON DUPLICATE KEY UPDATE valor = :url, updated_at = NOW()
            ");
            $this->db->bind(':url', $url);
            $this->db->execute();

            if (class_exists('AuditModel')) {
                AuditModel::log('UPDATE_CONFIG', 'configuracion', null, ['clave' => 'url_publica', 'valor' => $url]);
            }

            $effectiveUrl = !empty($url) ? $url : rtrim(URLROOT, '/');

            echo json_encode([
                'success' => true,
                'url'     => $url,
                'url_efectiva' => $effectiveUrl,
                'message' => !empty($url)
                    ? '✅ URL de red configurada con éxito. El Cartel QR ahora usará esta dirección.'
                    : '✅ Se restableció a la detección automática local.'
            ]);
        } catch (Exception $e) {
            error_log('[SGP-CONFIG] Error al guardar url_publica: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al guardar la URL en la base de datos.']);
        }
        exit;
    }

    /**
     * Vista imprimible del cartel oficial del Quiosco QR para colgar en la oficina (Fase 5)
     */
    public function cartelQr()
    {
        // Acceso público para permitir imprimir y visualizar el cartel oficial directamente desde el login o configuración

        $token = 'ISPEB-ASIST-2026-HQ';
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'kiosco_qr_token' LIMIT 1");
            $row = $this->db->single();
            if ($row && !empty($row->valor)) {
                $token = $row->valor;
            }
        } catch (\Throwable $e) {}

        // Determinar URL base (priorizar url_publica configurada si existe)
        $urlBase = rtrim(URLROOT, '/');
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'url_publica' LIMIT 1");
            $cfgUrl = $this->db->single();
            if ($cfgUrl && !empty(trim($cfgUrl->valor))) {
                $urlBase = rtrim(trim($cfgUrl->valor), '/');
            }
        } catch (\Throwable $e) {}

        // Asegurar que urlBase contenga el subdirectorio /SGP si el proyecto corre bajo /SGP
        if (!preg_match('#/SGP$#i', $urlBase) && strpos(URLROOT, '/SGP') !== false) {
            $urlBase .= '/SGP';
        }

        $ispFile = APPROOT . '/config/isp_info.json';
        $isp = [
            'nombre'    => 'Instituto de Salud Pública del Estado Bolívar (ISPEB)',
            'rif'       => 'G-20000366-9',
            'direccion' => 'Paseo Meneses, Torre ISP, Piso 3'
        ];
        if (file_exists($ispFile)) {
            $json = json_decode(file_get_contents($ispFile), true);
            if (is_array($json)) $isp = array_merge($isp, $json);
        }

        $data = [
            'title'   => 'Cartel Oficial — Registro de Asistencia QR',
            'token'   => $token,
            'urlBase' => $urlBase,
            'qrUrl'   => $urlBase . '/auth/login?token=' . urlencode($token),
            'isp'     => $isp
        ];

        $this->view('configuracion/cartel_qr', $data, false);
    }

    /**
     * AJAX GET: Verificar conexión a base de datos y retornar métricas.
     * Ruta: GET /configuracion/verificarConexionBD
     */
    public function verificarConexionBD()
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        $inicio = microtime(true);
        try {
            $this->db->query("SELECT VERSION() AS ver, (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()) AS tablas");
            $row = $this->db->single();
            $latencia = round((microtime(true) - $inicio) * 1000, 1);

            echo json_encode([
                'success'    => true,
                'latencia_ms'=> $latencia,
                'version'    => $row->ver ?? '',
                'tablas'     => (int)($row->tablas ?? 0),
            ]);
        } catch (Exception $e) {
            error_log('[SGP-CONFIG] verificarConexionBD error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos.']);
        }
        exit;
    }

    /**
     * AJAX POST: Limpiar sesiones expiradas.
     * Ruta: POST /configuracion/limpiarSesiones
     */
    public function limpiarSesiones()
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $eliminadas = 0;

        // Intentar limpiar tabla sesiones si existe
        try {
            $this->db->query("DELETE FROM sesiones WHERE creado_en < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $this->db->execute();
            $eliminadas += $this->db->rowCount();
        } catch (Exception $e) { /* tabla no existe, ignorar */ }

        // Limpiar archivos de sesión PHP expirados
        $sessionPath = session_save_path() ?: sys_get_temp_dir();
        $archivosEliminados = 0;
        if (is_dir($sessionPath)) {
            foreach (glob($sessionPath . '/sess_*') as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 86400) {
                    @unlink($file);
                    $archivosEliminados++;
                }
            }
        }

        if (class_exists('AuditModel')) {
            AuditModel::log('MANTENIMIENTO_SESIONES', "Limpieza de sesiones: $eliminadas BD + $archivosEliminados archivos");
        }

        echo json_encode([
            'success'  => true,
            'message'  => "Limpieza completada: $eliminadas registros BD y $archivosEliminados archivos de sesión eliminados.",
            'eliminadas' => $eliminadas + $archivosEliminados,
        ]);
        exit;
    }

    /**
     * AJAX POST: Purgar registros antiguos de bitácora.
     * Ruta: POST /configuracion/purgarBitacora
     */
    public function purgarBitacora()
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $meses = max(1, min(24, (int)($_POST['meses'] ?? 6)));

        try {
            // Verificar qué columna de fecha usa la tabla bitácora
            $this->db->query("SELECT created_at FROM bitacora LIMIT 1");
            $col = 'created_at';
        } catch (Exception $e) {
            try {
                $this->db->query("SELECT fecha_hora FROM bitacora LIMIT 1");
                $col = 'fecha_hora';
            } catch (Exception $e2) {
                echo json_encode(['success' => false, 'message' => 'No se pudo acceder a la tabla bitácora.']);
                exit;
            }
        }

        try {
            $this->db->query("DELETE FROM bitacora WHERE `$col` < DATE_SUB(NOW(), INTERVAL :meses MONTH)");
            $this->db->bind(':meses', $meses);
            $this->db->execute();
            $eliminados = $this->db->rowCount();

            if (class_exists('AuditModel')) {
                AuditModel::log('MANTENIMIENTO_BITACORA', "Purga de bitácora: $eliminados registros > $meses meses eliminados");
            }

            echo json_encode([
                'success'    => true,
                'message'    => "Bitácora purgada: $eliminados registro(s) anteriores a $meses meses eliminados.",
                'eliminados' => $eliminados,
            ]);
        } catch (Exception $e) {
            error_log('[SGP-CONFIG] purgarBitacora (AJAX) error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al purgar la bitácora.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // GET /configuracion/calendario_feriados — Vista Almanaque
    // ─────────────────────────────────────────────────────────
    public function calendario_feriados() {
        RoleMiddleware::authorize([0, 1, 2]);
        $year = isset($_GET['y']) && is_numeric($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
        
        $feriadoModel = new FeriadoModel($this->db);
        $feriadosArray = $feriadoModel->obtenerTodos(); // Puedes crear un método por año o filtrarlo aquí
        
        // Indexar por fecha para acceso rápido
        $feriados = [];
        foreach ($feriadosArray as $f) {
            $feriados[$f->fecha] = $f;
        }

        $data = [
            'year'     => $year,
            'feriados' => $feriados
        ];

        $this->view('configuracion/calendario_feriados', $data);
    }

    // ─────────────────────────────────────────────────────────
    // POST /configuracion/sincronizarFeriados — Sync API
    // Consulta Nager.Date, inserta solo los feriados faltantes
    // ─────────────────────────────────────────────────────────
    public function sincronizarFeriados(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $anio = (int)($_POST['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > 2035) {
            echo json_encode(['success' => false, 'message' => 'Año inválido.']);
            exit;
        }

        // ── Consultar la API Nager.Date (Venezuela = VE) ──────────────
        $url = "https://date.nager.at/api/v3/PublicHolidays/{$anio}/VE";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            // [FIX-N-8] Antes desactivada ("XAMPP local sin certificados") — exponía la
            // llamada saliente a un MITM que pudiera alterar la lista de feriados
            // recibida. Se reactiva; si el CA bundle de PHP/XAMPP está desactualizado,
            // el curlError de abajo ya maneja el fallo con un mensaje claro al usuario.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'SGP-ISP-Bolivar/1.0',
        ]);
        $respuesta = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$respuesta) {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo conectar con la API de feriados. '
                           . ($curlError ?: "Código HTTP: $httpCode"),
            ]);
            exit;
        }

        $lista = json_decode($respuesta, true);
        if (!is_array($lista)) {
            echo json_encode(['success' => false, 'message' => 'Respuesta de API inválida.']);
            exit;
        }

        // ── Insertar solo los feriados que no existan ─────────────────
        $insertados  = 0;
        $yaExistian  = 0;
        $totalApi    = count($lista);

        // Cargar fechas ya existentes en BD para ese año (consulta única)
        $this->db->query(
            "SELECT fecha FROM dias_feriados WHERE YEAR(fecha) = :anio"
        );
        $this->db->bind(':anio', $anio);
        $rows           = $this->db->resultSet();
        $fechasExistentes = array_column(array_map(fn($r) => (array)$r, $rows), 'fecha');

        foreach ($lista as $item) {
            $fecha  = $item['date']      ?? '';
            $nombre = $item['localName'] ?? ($item['name'] ?? '');

            if (!$fecha || !$nombre) continue;

            if (in_array($fecha, $fechasExistentes, true)) {
                $yaExistian++;
                continue;
            }

            try {
                $this->db->query(
                    "INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at)
                     VALUES (:fecha, :nombre, 'Nacional', NOW())"
                );
                $this->db->bind(':fecha',  $fecha);
                $this->db->bind(':nombre', mb_substr($nombre, 0, 150));
                $this->db->execute();
                $insertados++;
                $fechasExistentes[] = $fecha; // Evitar doble insert en la misma ejecución
            } catch (Exception $e) {
                $yaExistian++; // Si falla INSERT IGNORE, ya existía
            }
        }

        if (class_exists('AuditModel')) {
            AuditModel::log(
                'SYNC_FERIADOS_API',
                "Sincronización año $anio: $insertados nuevos, $yaExistian ya existían (Nager.Date)"
            );
        }

        echo json_encode([
            'success'     => true,
            'insertados'  => $insertados,
            'ya_existian' => $yaExistian,
            'total_api'   => $totalApi,
            'nota'        => 'Los feriados regionales (Aniversario Ciudad Bolívar, etc.) '
                           . 'no están en la API. Agrégalos manualmente si es necesario.',
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // GET /configuracion/getFeriadosParaConfig?anio=XXXX
    // Devuelve todos los feriados del año para el modal de configuración
    // ─────────────────────────────────────────────────────────
    public function getFeriadosParaConfig(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        $anio = (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > 2035) {
            echo json_encode(['success' => false, 'message' => 'Año inválido.']);
            exit;
        }

        $this->db->query(
            "SELECT id, fecha, nombre, tipo, es_laborable
             FROM   dias_feriados
             WHERE  YEAR(fecha) = :anio
             ORDER  BY fecha ASC"
        );
        $this->db->bind(':anio', $anio);
        $feriados = $this->db->resultSet();

        echo json_encode([
            'success'  => true,
            'feriados' => $feriados ? array_map(fn($r) => (array)$r, $feriados) : [],
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /configuracion/actualizarLaborables
    // Actualización batch de es_laborable para múltiples feriados
    // Body JSON: [{id: N, es_laborable: 0|1}, ...]
    // ─────────────────────────────────────────────────────────
    public function actualizarLaborables(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF — el body es JSON crudo, no $_POST, así que
        // el token viaja por header (ya agregado en calendario_feriados.php).
        $this->verifyCsrf();

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data) || empty($data)) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
            exit;
        }

        $actualizados = 0;
        foreach ($data as $item) {
            $id          = (int)($item['id']          ?? 0);
            $esLaborable = ((int)($item['es_laborable'] ?? 0) === 1) ? 1 : 0;
            if (!$id) continue;

            $this->db->query("UPDATE dias_feriados SET es_laborable = :es_lab WHERE id = :id");
            $this->db->bind(':es_lab', $esLaborable, PDO::PARAM_INT);
            $this->db->bind(':id',     $id,          PDO::PARAM_INT);
            $this->db->execute();
            $actualizados++;
        }

        if (class_exists('AuditModel')) {
            AuditModel::log('CONFIG_LABORABLES', "Configuración masiva de feriados laborables: $actualizados registros actualizados");
        }

        echo json_encode([
            'success'      => true,
            'actualizados' => $actualizados,
            'message'      => "$actualizados feriados actualizados correctamente.",
        ]);
        exit;
    }

    /**
     * AJAX POST: Habilitar o deshabilitar el Kiosco
     * Ruta: POST /configuracion/toggleKiosco
     */
    public function toggleKiosco(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $activo = isset($_POST['activo']) && (int)$_POST['activo'] === 1 ? '1' : '0';
        try {
            $this->db->query("
                INSERT INTO configuracion (clave, valor, descripcion)
                VALUES ('kiosco_activo', :val, 'Estado del kiosco de asistencia')
                ON DUPLICATE KEY UPDATE valor = :val2, updated_at = NOW()
            ");
            $this->db->bind(':val', $activo);
            $this->db->bind(':val2', $activo);
            $this->db->execute();

            Session::set('kiosco_activo', (int)$activo);
            if (class_exists('AuditModel')) {
                AuditModel::log('UPDATE_CONFIG', "Kiosco " . ($activo === '1' ? 'habilitado' : 'deshabilitado'));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Estado del kiosco actualizado correctamente (' . ($activo === '1' ? 'Activo' : 'Deshabilitado') . ').'
            ]);
        } catch (\Throwable $e) {
            error_log('[SGP-CONFIG] toggleKiosco error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar estado del kiosco']);
        }
        exit;
    }

    /**
     * AJAX POST: Habilitar o deshabilitar la Geolocalización / Geocerca GPS del Kiosco
     * Ruta: POST /configuracion/toggleGeocerca
     */
    public function toggleGeocerca(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $activo = isset($_POST['activo']) && (int)$_POST['activo'] === 1 ? '1' : '0';
        try {
            $this->db->query("
                INSERT INTO configuracion (clave, valor, descripcion)
                VALUES ('geocerca_activa', :val, 'Habilita o deshabilita la validacion GPS del quiosco')
                ON DUPLICATE KEY UPDATE valor = :val2, updated_at = NOW()
            ");
            $this->db->bind(':val', $activo);
            $this->db->bind(':val2', $activo);
            $this->db->execute();

            if (class_exists('AuditModel')) {
                AuditModel::log('UPDATE_CONFIG', "Geolocalización GPS del Kiosco " . ($activo === '1' ? 'activada' : 'desactivada (Modo Pruebas)'));
            }

            echo json_encode([
                'success' => true,
                'activo'  => $activo === '1',
                'message' => 'Geocerca GPS ' . ($activo === '1' ? 'activada (Restricción por distancia vigente)' : 'desactivada (Permite pruebas sin restricción de GPS)') . '.'
            ]);
        } catch (\Throwable $e) {
            error_log('[SGP-CONFIG] toggleGeocerca error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar estado de la geocerca']);
        }
        exit;
    }

    /**
     * AJAX POST: Switch global de notificaciones del sistema
     * Ruta: POST /configuracion/toggleNotificaciones
     */
    public function toggleNotificaciones(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $activo = isset($_POST['activo']) && (int)$_POST['activo'] === 1 ? '1' : '0';
        try {
            $this->db->query("
                INSERT INTO configuracion (clave, valor, descripcion)
                VALUES ('notificaciones_activas', :val, 'Habilita o pausa globalmente el sistema de notificaciones')
                ON DUPLICATE KEY UPDATE valor = :val2, updated_at = NOW()
            ");
            $this->db->bind(':val', $activo);
            $this->db->bind(':val2', $activo);
            $this->db->execute();

            if (class_exists('AuditModel')) {
                AuditModel::log('UPDATE_CONFIG', "Notificaciones del sistema " . ($activo === '1' ? 'reanudadas' : 'pausadas globalmente'));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Notificaciones ' . ($activo === '1' ? 'activadas globalmente' : 'pausadas globalmente') . '.'
            ]);
        } catch (\Throwable $e) {
            error_log('[SGP-CONFIG] toggleNotificaciones error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar notificaciones']);
        }
        exit;
    }

    /**
     * AJAX POST: Toggle granular de notificaciones por usuario
     * Ruta: POST /configuracion/toggleUsuarioNotificaciones
     */
    public function toggleUsuarioNotificaciones(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::authorize([0, 1]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $userId = (int)($_POST['usuario_id'] ?? 0);
        $habilitado = isset($_POST['habilitado']) && (int)$_POST['habilitado'] === 1 ? 1 : 0;

        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
            exit;
        }

        try {
            $this->db->query("UPDATE usuarios SET notificaciones_habilitadas = :hab WHERE id = :uid");
            $this->db->bind(':hab', $habilitado, PDO::PARAM_INT);
            $this->db->bind(':uid', $userId, PDO::PARAM_INT);
            $this->db->execute();

            if (class_exists('AuditModel')) {
                AuditModel::log('UPDATE_CONFIG', "Notificaciones para usuario ID $userId " . ($habilitado ? 'habilitadas' : 'silenciadas'));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Preferencia de notificaciones de usuario actualizada correctamente.'
            ]);
        } catch (\Throwable $e) {
            error_log('[SGP-CONFIG] toggleUsuarioNotificaciones error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar preferencia']);
        }
        exit;
    }
}
