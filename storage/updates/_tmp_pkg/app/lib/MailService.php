<?php
/**
 * MailService — Servicio de Correo Electrónico Institucional y Notificaciones
 * Sistema de Gestión de Pasantes (SGP) — ISPEB
 *
 * Cliente SMTP autónomo (Zero-Dependency) compatible con Brevo, Gmail, Outlook y servidores institucionales.
 */

declare(strict_types=1);

class MailService
{
    /**
     * Obtiene la configuración SMTP desde la tabla `configuracion`
     */
    public static function getConfig(): array
    {
        $cfg = [
            'activo'        => 0,
            'host'          => 'smtp-relay.brevo.com',
            'port'          => 587,
            'user'          => '',
            'pass'          => '',
            'secure'        => 'tls',
            'from_name'     => 'SGP - Instituto de Salud Pública',
            'from_email'    => 'no-reply@ispeb.gob.ve'
        ];

        try {
            $db = Database::getInstance();
            $db->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'smtp_%' OR clave LIKE 'mail_%'");
            $rows = $db->resultSet() ?: [];
            foreach ($rows as $r) {
                if ($r->clave === 'smtp_activo')        $cfg['activo'] = (int)$r->valor;
                if ($r->clave === 'smtp_host')          $cfg['host'] = trim($r->valor);
                if ($r->clave === 'smtp_port')          $cfg['port'] = (int)$r->valor;
                if ($r->clave === 'smtp_user')          $cfg['user'] = trim($r->valor);
                if ($r->clave === 'smtp_pass')          $cfg['pass'] = trim($r->valor);
                if ($r->clave === 'smtp_secure')        $cfg['secure'] = trim($r->valor);
                if ($r->clave === 'mail_from_name')     $cfg['from_name'] = trim($r->valor);
                if ($r->clave === 'mail_from_email')    $cfg['from_email'] = trim($r->valor);
            }
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] getConfig error: ' . $e->getMessage());
        }

        return $cfg;
    }

    /**
     * Valida si un evento de correo está habilitado según las reglas de `mail_reglas`
     * y si el usuario/rol está permitido, incluido o excluido.
     */
    public static function checkRule(string $eventCode, ?int $userId = null, ?int $roleId = null): array
    {
        if ($eventCode === 'MANUAL' || $eventCode === 'TEST') {
            return ['allowed' => true, 'reason' => 'Envío directo o prueba manual'];
        }

        try {
            $db = Database::getInstance();
            $db->query("SELECT * FROM mail_reglas WHERE codigo_evento = :cod LIMIT 1");
            $db->bind(':cod', $eventCode);
            $regla = $db->single();

            if (!$regla) {
                return ['allowed' => true, 'reason' => 'Evento sin regla específica'];
            }

            if (empty($regla->activo)) {
                return ['allowed' => false, 'reason' => 'La regla de notificación para este evento está pausada globalmente'];
            }

            // Validar exclusiones específicas de usuarios
            if ($userId !== null && !empty($regla->usuarios_excluidos)) {
                $excluidos = json_decode($regla->usuarios_excluidos, true) ?: [];
                if (in_array($userId, $excluidos, true)) {
                    return ['allowed' => false, 'reason' => 'El usuario ha sido excluido de esta notificación por el administrador'];
                }
            }

            // Validar inclusiones forzadas de usuarios
            if ($userId !== null && !empty($regla->usuarios_incluidos)) {
                $incluidos = json_decode($regla->usuarios_incluidos, true) ?: [];
                if (in_array($userId, $incluidos, true)) {
                    return ['allowed' => true, 'reason' => 'Usuario incluido explícitamente en la regla'];
                }
            }

            // Validar roles permitidos
            if ($roleId !== null && !empty($regla->enviar_a_roles)) {
                $rolesPermitidos = array_map('intval', explode(',', $regla->enviar_a_roles));
                if (!in_array($roleId, $rolesPermitidos, true)) {
                    return ['allowed' => false, 'reason' => 'El rol del usuario (' . $roleId . ') no está autorizado para recibir esta alerta'];
                }
            }

            return ['allowed' => true, 'reason' => 'Regla activa y usuario compatible'];
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] checkRule error: ' . $e->getMessage());
            return ['allowed' => true, 'reason' => 'Fallback por error: ' . $e->getMessage()];
        }
    }

    /**
     * Registra un despacho en la tabla de auditoría `mail_historial`
     */
    public static function logDelivery(string $eventCode, string $email, ?string $name, ?int $userId, string $subject, string $status, ?string $details = null): void
    {
        try {
            $db = Database::getInstance();
            $db->query("
                INSERT INTO mail_historial (codigo_evento, destinatario_email, destinatario_nombre, usuario_id, asunto, estado, detalles, created_at)
                VALUES (:cod, :email, :nombre, :uid, :subj, :est, :det, NOW())
            ");
            $db->bind(':cod',    $eventCode);
            $db->bind(':email',  $email);
            $db->bind(':nombre', $name);
            $db->bind(':uid',    $userId);
            $db->bind(':subj',   $subject);
            $db->bind(':est',    $status);
            $db->bind(':det',    $details);
            $db->execute();
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] logDelivery error: ' . $e->getMessage());
        }
    }

    /**
     * Envío directo garantizado (ignora exclusiones para pruebas o envíos obligatorios)
     */
    public static function sendDirect(string $toEmail, string $toName, string $subject, string $htmlBody, string $eventCode = 'MANUAL', ?int $userId = null): array
    {
        return self::send($toEmail, $toName, $subject, $htmlBody, '', $eventCode, $userId, true);
    }

    /**
     * Envía un correo electrónico mediante SMTP autenticado
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = '', string $eventCode = 'MANUAL', ?int $userId = null, bool $ignoreRules = false): array
    {
        // 1. Validar reglas de notificación si no se ignora explícitamente
        if (!$ignoreRules && $eventCode !== 'MANUAL' && $eventCode !== 'TEST') {
            $ruleCheck = self::checkRule($eventCode, $userId);
            if (!$ruleCheck['allowed']) {
                self::logDelivery($eventCode, $toEmail, $toName, $userId, $subject, 'excluido', $ruleCheck['reason']);
                return [
                    'success'  => false,
                    'excluded' => true,
                    'message'  => $ruleCheck['reason']
                ];
            }
        }

        $cfg = self::getConfig();

        // Modo Simulación si SMTP no está activo
        if (empty($cfg['activo']) || empty($cfg['user']) || empty($cfg['pass'])) {
            error_log("[SGP-MAIL-SIMULADO] Para: {$toEmail} ({$toName}) | Asunto: {$subject}");
            self::logDelivery($eventCode, $toEmail, $toName, $userId, $subject, 'simulado', 'Servidor SMTP en modo simulación / credenciales pendientes');
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Servicio SMTP en modo simulación (configuración pendiente)'
            ];
        }

        $host    = $cfg['host'];
        $port    = $cfg['port'];
        $user    = $cfg['user'];
        $pass    = $cfg['pass'];
        $secure  = strtolower($cfg['secure']);
        $timeout = 15;

        $target = ($secure === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            error_log("[SGP-MAIL] Error de conexión socket: {$errstr} ({$errno})");
            return ['success' => false, 'message' => "No se pudo conectar al servidor SMTP: {$errstr}"];
        }

        stream_set_timeout($socket, $timeout);

        $read = function() use ($socket) {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };

        $cmd = function($c, $expectedCode) use ($socket, $read) {
            fputs($socket, $c . "\r\n");
            $response = $read();
            $code = substr($response, 0, 3);
            if ($code !== (string)$expectedCode) {
                throw new \Exception("Comando [{$c}] falló con respuesta: {$response}");
            }
            return $response;
        };

        try {
            $read(); // Saludo inicial
            $cmd("EHLO " . gethostname(), 250);

            // Iniciar STARTTLS si el puerto es 587 o seguro es tls
            if ($secure === 'tls' || $port === 587) {
                $cmd("STARTTLS", 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \Exception("Negociación TLS fallida");
                }
                $cmd("EHLO " . gethostname(), 250);
            }

            // Autenticación
            $cmd("AUTH LOGIN", 334);
            $cmd(base64_encode($user), 334);
            $cmd(base64_encode($pass), 235);

            // Remitente y Destinatario
            $fromEmail = $cfg['from_email'];
            $fromName  = $cfg['from_name'];
            $cmd("MAIL FROM:<{$fromEmail}>", 250);
            $cmd("RCPT TO:<{$toEmail}>", 250);

            // Enviar Datos
            $cmd("DATA", 354);

            $boundary = "----=_NextPart_" . md5((string)time());
            $headers  = [];
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
            $headers[] = "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            $headers[] = "Date: " . date('r');
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $headers[] = "X-Mailer: SGP-ISPEB/2.0";

            $message  = implode("\r\n", $headers) . "\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($textBody ?: strip_tags($htmlBody))) . "\r\n";

            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $message .= "--{$boundary}--\r\n";
            $message .= ".";

            $cmd($message, 250);
            $cmd("QUIT", 221);
            self::logDelivery($eventCode, $toEmail, $toName, $userId, $subject, 'enviado', 'Despachado exitosamente vía SMTP');
            return ['success' => true, 'message' => 'Correo enviado exitosamente'];

        } catch (\Throwable $e) {
            if (is_resource($socket)) fclose($socket);
            error_log('[SGP-MAIL] Error en transmisión SMTP: ' . $e->getMessage());
            self::logDelivery($eventCode, $toEmail, $toName, $userId, $subject, 'fallido', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generador de plantilla HTML oficial Bento/Navy institucional
     */
    public static function renderBentoTemplate(string $badgeText, string $badgeColor, string $headline, string $contentHtml, string $ctaText = '', string $ctaUrl = '', string $footerNote = ''): string
    {
        $logoUrl = rtrim(URLROOT, '/') . '/img/logo.png';
        $portalUrl = rtrim(URLROOT, '/');

        $ctaButton = '';
        if ($ctaText !== '' && $ctaUrl !== '') {
            $ctaButton = "
            <div style='text-align: center; margin: 28px 0 10px;'>
                <a href='{$ctaUrl}' target='_blank' style='display: inline-block; padding: 13px 28px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 10px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);'>
                    {$ctaText} &nbsp;➜
                </a>
            </div>";
        }

        $footerExtra = $footerNote !== '' ? "<p style='margin-top: 10px; font-size: 11px; color: #94a3b8;'>{$footerNote}</p>" : '';

        return "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$headline}</title>
        </head>
        <body style='margin: 0; padding: 24px 12px; background-color: #0b1329; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <table role='presentation' width='100%' border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='100%' style='max-width: 580px; background-color: #ffffff; border-radius: 18px; overflow: hidden; border: 1.5px solid #1e3a8a; box-shadow: 0 15px 40px rgba(0,0,0,0.3);' border='0' cellspacing='0' cellpadding='0'>
                            
                            <!-- Header Bento Navy -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #0f1d4a 0%, #1e3a8a 100%); padding: 26px 28px; text-align: center;'>
                                    <img src='{$logoUrl}' alt='SGP Logo' style='height: 48px; width: auto; margin-bottom: 8px;' onerror=\"this.style.display='none'\">
                                    <div style='color: #ffffff; font-size: 17px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;'>
                                        Instituto de Salud Pública
                                    </div>
                                    <div style='color: #93c5fd; font-size: 12px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; margin-top: 2px;'>
                                        Sistema de Gestión de Pasantes (SGP)
                                    </div>
                                </td>
                            </tr>

                            <!-- Cuerpo -->
                            <tr>
                                <td style='padding: 32px 28px 24px; color: #1e293b; font-size: 14px; line-height: 1.6;'>
                                    
                                    <div style='display: inline-block; background-color: {$badgeColor}; color: #ffffff; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 999px; letter-spacing: 0.6px; margin-bottom: 14px;'>
                                        {$badgeText}
                                    </div>

                                    <h2 style='margin: 0 0 16px; font-size: 21px; font-weight: 800; color: #0f172a; line-height: 1.25;'>
                                        {$headline}
                                    </h2>

                                    <div style='color: #334155; font-size: 14px;'>
                                        {$contentHtml}
                                    </div>

                                    {$ctaButton}

                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f8fafc; padding: 18px 28px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #64748b; line-height: 1.5;'>
                                    <div style='font-weight: 700; color: #0f172a; margin-bottom: 3px;'>
                                        División de Soporte Técnico y Telemática — ISPEB
                                    </div>
                                    <div>Este es un mensaje institucional automatizado emitido por el SGP.</div>
                                    <div>Portal oficial: <a href='{$portalUrl}' style='color: #2563eb; text-decoration: none; font-weight: 600;'>{$portalUrl}</a></div>
                                    {$footerExtra}
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }

    /**
     * Notificación automática de Examen Rápido asignado
     */
    public static function notifyNewExam(string $titulo, ?string $fechaFin = null, ?string $urlDest = null): int
    {
        $db = Database::getInstance();
        $db->query("
            SELECT u.correo AS email, COALESCE(dp.nombres, 'Pasante') AS nombres, COALESCE(dp.apellidos, '') AS apellidos
            FROM usuarios u
            JOIN datos_pasante dpass ON dpass.usuario_id = u.id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.rol_id = 3 AND u.estado = 'activo' AND dpass.estado_pasantia = 'Activo' AND u.correo IS NOT NULL AND u.correo != ''
        ");
        $pasantes = $db->resultSet() ?: [];

        $url = $urlDest ?: (rtrim(URLROOT, '/') . '/pasante/misExamenes');
        $fechaTexto = $fechaFin ? date('d/m/Y h:i A', strtotime($fechaFin)) : 'Próximamente';

        $enviados = 0;
        foreach ($pasantes as $p) {
            $nombre = trim($p->nombres . ' ' . $p->apellidos);
            $content = "
                <p>Hola <strong>{$nombre}</strong>,</p>
                <p>Se ha publicado una nueva evaluación rápida en el sistema y requiere tu atención:</p>
                <div style='background-color: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 16px; font-weight: 800; color: #166534; margin-bottom: 4px;'>📝 {$titulo}</div>
                    <div style='font-size: 13px; color: #15803d;'><strong>Fecha / Cierre:</strong> {$fechaTexto}</div>
                </div>
                <p>Ingresa a tu cuenta desde tu computadora o teléfono móvil para resolver las preguntas asignadas antes de la fecha límite.</p>
            ";

            $html = self::renderBentoTemplate('Evaluación Rápida', '#16a34a', 'Nuevo Examen Asignado', $content, 'Resolver Examen Ahora', $url, 'Recuerda que cada intento queda registrado y auditado por la Coordinación de Docencia.');
            $res = self::send($p->email, $nombre, "📝 Nuevo Examen Asignado: {$titulo}", $html, '', 'NUEVO_EXAMEN', (int)($p->id ?? 0));
            if (!empty($res['success'])) {
                $enviados++;
            }
        }

        return $enviados;
    }

    /**
     * Alerta de seguridad por inicio de sesión (Biometría o nuevo dispositivo)
     */
    public static function notifySecurityLogin(int $userId, string $authMethod, string $deviceName, string $ip): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("
                SELECT u.correo AS email, COALESCE(dp.nombres, u.cedula) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $userId);
            $user = $db->single();

            if (!$user || empty($user->email)) return false;

            $fechaHora = date('d/m/Y h:i:s A');
            $content = "
                <p>Hola <strong>{$user->nombre}</strong>,</p>
                <p>Detectamos un inicio de sesión en tu cuenta del Sistema de Gestión de Pasantes:</p>
                <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Método:</strong> {$authMethod}</div>
                    <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Dispositivo:</strong> {$deviceName}</div>
                    <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Fecha y Hora:</strong> {$fechaHora}</div>
                    <div style='font-size: 13px; color: #1e3a8a;'><strong>Dirección IP:</strong> {$ip}</div>
                </div>
                <p style='color: #64748b; font-size: 13px;'>Si fuiste tú, no tienes que realizar ninguna acción. Si no reconoces este acceso, por favor comunícate de inmediato con la División de Soporte Técnico o cambia tu contraseña.</p>
            ";

            $html = self::renderBentoTemplate('Alerta de Seguridad', '#2563eb', 'Inicio de Sesión Detectado', $content, 'Revisar mi Cuenta', rtrim(URLROOT, '/') . '/auth/login');
            $res = self::send($user->email, (string)$user->nombre, "🛡️ Alerta de Seguridad: Acceso con {$authMethod}", $html, '', 'SEGURIDAD_LOGIN', $userId);
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifySecurityLogin error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificación cuando se registra/vincula un nuevo dispositivo biométrico
     */
    public static function notifyBiometricEnrolled(int $userId, string $deviceName, string $ip): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("
                SELECT u.correo AS email, COALESCE(dp.nombres, u.cedula) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $userId);
            $user = $db->single();

            if (!$user || empty($user->email)) return false;

            $fechaHora = date('d/m/Y h:i:s A');
            $content = "
                <p>Hola <strong>{$user->nombre}</strong>,</p>
                <p>Te confirmamos que se ha activado exitosamente el <strong>Acceso Biométrico Móvil (Huella Dactilar / Face ID)</strong> en tu cuenta institucional del Sistema de Gestión de Pasantes:</p>
                <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'><strong>Dispositivo:</strong> {$deviceName}</div>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'><strong>Fecha y Hora de Vinculación:</strong> {$fechaHora}</div>
                    <div style='font-size: 13px; color: #166534;'><strong>Dirección IP:</strong> {$ip}</div>
                </div>
                <p style='color: #334155;'>A partir de ahora podrás iniciar sesión de forma instantánea pulsando el botón <strong>\"Ingresar con Huella Dactilar\"</strong> sin tener que escribir tu contraseña.</p>
                <p style='color: #64748b; font-size: 13px;'>Si no realizaste esta acción o no reconoces este dispositivo, puedes desvincularlo inmediatamente ingresando a tu <strong>Perfil &gt; Seguridad</strong> en el sistema.</p>
            ";

            $html = self::renderBentoTemplate('Biometría Activada', '#16a34a', 'Acceso Biométrico Vinculado', $content, 'Ver Mi Perfil', rtrim(URLROOT, '/') . '/perfil/ver');
            $res = self::send($user->email, (string)$user->nombre, "🔐 Acceso Biométrico Activado: {$deviceName}", $html, '', 'BIOMETRIA_VINCULADA', $userId);
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyBiometricEnrolled error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificación cuando se desvincula/elimina un dispositivo biométrico
     */
    public static function notifyBiometricRemoved(int $userId, string $deviceName, string $ip): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("
                SELECT u.correo AS email, COALESCE(dp.nombres, u.cedula) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $userId);
            $user = $db->single();

            if (!$user || empty($user->email)) return false;

            $fechaHora = date('d/m/Y h:i:s A');
            $content = "
                <p>Hola <strong>{$user->nombre}</strong>,</p>
                <p>Te informamos que se ha <strong>desvinculado</strong> un dispositivo biométrico de tu cuenta:</p>
                <div style='background-color: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 13px; color: #9a3412; margin-bottom: 4px;'><strong>Dispositivo Desafiliado:</strong> {$deviceName}</div>
                    <div style='font-size: 13px; color: #9a3412; margin-bottom: 4px;'><strong>Fecha y Hora:</strong> {$fechaHora}</div>
                    <div style='font-size: 13px; color: #9a3412;'><strong>Dirección IP:</strong> {$ip}</div>
                </div>
                <p style='color: #334155;'>Para iniciar sesión en futuros accesos desde ese dispositivo deberás utilizar tu correo electrónico institucional y tu contraseña.</p>
                <p style='color: #64748b; font-size: 13px;'>Si tú no solicitaste esta desafiliación, por favor cambia tu contraseña de inmediato.</p>
            ";

            $html = self::renderBentoTemplate('Seguridad', '#ea580c', 'Dispositivo Biométrico Desvinculado', $content, 'Revisar Seguridad', rtrim(URLROOT, '/') . '/perfil/ver');
            $res = self::send($user->email, (string)$user->nombre, "⚠️ Dispositivo Biométrico Desvinculado: {$deviceName}", $html, '', 'BIOMETRIA_DESAFILIADA', $userId);
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyBiometricRemoved error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificación cuando un pasante registra su asistencia en el quiosco
     */
    public static function notifyAttendance(int $pasanteId, string $tipo, string $hora, ?string $fecha = null): int
    {
        try {
            $rule = self::checkRule('ASISTENCIA_INDIVIDUAL');
            if (!$rule['allowed']) return 0;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.cedula,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_pasante,
                       d.nombre AS departamento_nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                LEFT JOIN departamentos d ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $pasanteId);
            $pasante = $db->single();
            if (!$pasante) return 0;

            // Obtener tutores y administradores habilitados para recibir este correo
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_admin
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.rol_id IN (0, 1, 2) AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
            ");
            $admins = $db->resultSet() ?: [];

            $fechaStr = $fecha ?: date('d/m/Y');
            $subject = "⏰ Marcaje de Asistencia: {$pasante->nombre_pasante} ({$tipo})";
            $content = "
                <p>Estimado(a),</p>
                <p>Le notificamos que se ha registrado una nueva marcación en el quiosco institucional:</p>
                <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 14px; color: #166534; margin-bottom: 4px;'><strong>Pasante:</strong> {$pasante->nombre_pasante} (C.I. {$pasante->cedula})</div>
                    <div style='font-size: 14px; color: #166534; margin-bottom: 4px;'><strong>Departamento:</strong> " . ($pasante->departamento_nombre ?: 'Sin asignar') . "</div>
                    <div style='font-size: 14px; color: #166534; margin-bottom: 4px;'><strong>Registro:</strong> {$tipo} &nbsp;|&nbsp; <strong>Hora:</strong> {$hora}</div>
                    <div style='font-size: 14px; color: #166534;'><strong>Fecha:</strong> {$fechaStr}</div>
                </div>
            ";

            $html = self::renderBentoTemplate('Asistencia en Vivo', '#10b981', "Marcaje de {$pasante->nombre_pasante}", $content, 'Ver Asistencias', rtrim(URLROOT, '/') . '/asistencias');

            $enviados = 0;
            foreach ($admins as $adm) {
                // Comprobar si este admin específico está excluido o permitido
                $chk = self::checkRule('ASISTENCIA_INDIVIDUAL', (int)$adm->id, (int)$adm->rol_id);
                if (!$chk['allowed']) continue;

                $res = self::send($adm->correo, (string)($adm->nombre_admin ?: 'Administrador'), $subject, $html, '', 'ASISTENCIA_INDIVIDUAL', (int)$adm->id);
                if (!empty($res['success'])) $enviados++;
            }

            return $enviados;
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyAttendance error: ' . $e->getMessage());
            return 0;
        }
    }
}

