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
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = '', string $eventCode = 'MANUAL', ?int $userId = null, bool $ignoreRules = false, array $attachments = []): array
    {
        // 1. Validar reglas de notificación si no se ignora explícitamente
        if (!$ignoreRules && $eventCode !== 'MANUAL' && $eventCode !== 'TEST' && $eventCode !== 'RESPALDO_NUBE') {
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
        $timeout = 8; // Conexión rápida (8 segundos máximo en vez de 25s)

        // Optimización de red en la nube (Render / Docker):
        // Forzar IPv4 directamente vía bindto 0.0.0.0:0 para eliminar los 15s de espera de timeout de IPv6 de Gmail
        $contextOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
                'peer_name'         => $host,
            ],
            'socket' => [
                'bindto' => '0.0.0.0:0',
            ]
        ];
        $context = stream_context_create($contextOptions);

        $target = ($secure === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

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
                $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (!stream_socket_enable_crypto($socket, true, $crypto)) {
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

            $hasCidLogo      = (strpos($htmlBody, 'cid:logo_sgp') !== false);
            $hasInlineImages = $hasCidLogo;
            $hasAttachments  = !empty($attachments);

            $baseImgDir = defined('APPROOT') ? (APPROOT . '/../public/img/') : (__DIR__ . '/../../public/img/');

            $headers   = [];
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
            $headers[] = "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            $headers[] = "Date: " . date('r');
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "X-Mailer: SGP/2.0";

            $bodyPayload = '';

            if ($hasAttachments) {
                // Estructura con adjuntos: multipart/mixed
                $mixedBoundary = "----=_MixedPart_" . md5(uniqid('mix', true));
                $headers[] = "Content-Type: multipart/mixed; boundary=\"{$mixedBoundary}\"";
                $bodyPayload .= implode("\r\n", $headers) . "\r\n\r\n";

                // Subparte para el cuerpo (relacionado o alternativo)
                $bodyPayload .= "--{$mixedBoundary}\r\n";
                if ($hasInlineImages) {
                    $relBoundary = "----=_RelPart_" . md5(uniqid('rel', true));
                    $altBoundary = "----=_AltPart_" . md5(uniqid('alt', true));
                    $bodyPayload .= "Content-Type: multipart/related; boundary=\"{$relBoundary}\"\r\n\r\n";

                    $bodyPayload .= "--{$relBoundary}\r\n";
                    $bodyPayload .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";
                    $bodyPayload .= "--{$altBoundary}\r\n";
                    $bodyPayload .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
                    $bodyPayload .= chunk_split(base64_encode($textBody ?: strip_tags($htmlBody))) . "\r\n";
                    $bodyPayload .= "--{$altBoundary}\r\n";
                    $bodyPayload .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
                    $bodyPayload .= chunk_split(base64_encode($htmlBody)) . "\r\n";
                    $bodyPayload .= "--{$altBoundary}--\r\n";

                    // Adjunto inline: Logo SGP
                    if ($hasCidLogo) {
                        foreach ([$baseImgDir . 'apple-touch-icon.png', $baseImgDir . 'logo.png', $baseImgDir . 'notif-icon.png'] as $lFile) {
                            if (file_exists($lFile)) {
                                $bodyPayload .= "--{$relBoundary}\r\nContent-Type: image/png; name=\"" . basename($lFile) . "\"\r\nContent-Transfer-Encoding: base64\r\nContent-ID: <logo_sgp>\r\nContent-Disposition: inline; filename=\"" . basename($lFile) . "\"\r\n\r\n";
                                $bodyPayload .= chunk_split(base64_encode(file_get_contents($lFile))) . "\r\n";
                                break;
                            }
                        }
                    }
                    $bodyPayload .= "--{$relBoundary}--\r\n";
                } else {
                    $altBoundary = "----=_AltPart_" . md5(uniqid('alt', true));
                    $bodyPayload .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";
                    $bodyPayload .= "--{$altBoundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($textBody ?: strip_tags($htmlBody))) . "\r\n";
                    $bodyPayload .= "--{$altBoundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n";
                    $bodyPayload .= "--{$altBoundary}--\r\n";
                }

                // Adjuntos físicos (.zip, .sql, etc.)
                foreach ($attachments as $att) {
                    $attPath = $att['path'] ?? '';
                    $attName = $att['name'] ?? basename($attPath);
                    $attType = $att['type'] ?? 'application/octet-stream';
                    if (file_exists($attPath)) {
                        $attData = chunk_split(base64_encode(file_get_contents($attPath)));
                        $bodyPayload .= "--{$mixedBoundary}\r\n";
                        $bodyPayload .= "Content-Type: {$attType}; name=\"{$attName}\"\r\n";
                        $bodyPayload .= "Content-Transfer-Encoding: base64\r\n";
                        $bodyPayload .= "Content-Disposition: attachment; filename=\"{$attName}\"\r\n\r\n";
                        $bodyPayload .= $attData . "\r\n";
                    }
                }
                $bodyPayload .= "--{$mixedBoundary}--\r\n.";

            } elseif ($hasInlineImages) {
                $relBoundary = "----=_RelPart_" . md5(uniqid('rel', true));
                $altBoundary = "----=_AltPart_" . md5(uniqid('alt', true));

                $headers[] = "Content-Type: multipart/related; boundary=\"{$relBoundary}\"";
                $bodyPayload  = implode("\r\n", $headers) . "\r\n\r\n";

                // Subparte alternativa (Texto plano + HTML)
                $bodyPayload .= "--{$relBoundary}\r\n";
                $bodyPayload .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";

                // Texto plano
                $bodyPayload .= "--{$altBoundary}\r\n";
                $bodyPayload .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $bodyPayload .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $bodyPayload .= chunk_split(base64_encode($textBody ?: strip_tags($htmlBody))) . "\r\n";

                // HTML
                $bodyPayload .= "--{$altBoundary}\r\n";
                $bodyPayload .= "Content-Type: text/html; charset=UTF-8\r\n";
                $bodyPayload .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $bodyPayload .= chunk_split(base64_encode($htmlBody)) . "\r\n";
                $bodyPayload .= "--{$altBoundary}--\r\n";

                // Adjunto inline: Logo SGP
                if ($hasCidLogo) {
                    $logoCandidates = [
                        $baseImgDir . 'apple-touch-icon.png',
                        $baseImgDir . 'logo.png',
                        $baseImgDir . 'notif-icon.png'
                    ];
                    foreach ($logoCandidates as $lFile) {
                        if (file_exists($lFile)) {
                            $mime = 'image/png';
                            $fName = basename($lFile);
                            $data = chunk_split(base64_encode(file_get_contents($lFile)));
                            $bodyPayload .= "--{$relBoundary}\r\n";
                            $bodyPayload .= "Content-Type: {$mime}; name=\"{$fName}\"\r\n";
                            $bodyPayload .= "Content-Transfer-Encoding: base64\r\n";
                            $bodyPayload .= "Content-ID: <logo_sgp>\r\n";
                            $bodyPayload .= "Content-Disposition: inline; filename=\"{$fName}\"\r\n\r\n";
                            $bodyPayload .= $data . "\r\n";
                            break;
                        }
                    }
                }

                $bodyPayload .= "--{$relBoundary}--\r\n";
                $bodyPayload .= ".";
            } else {
                $boundary = "----=_NextPart_" . md5((string)time());
                $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

                $bodyPayload  = implode("\r\n", $headers) . "\r\n\r\n";
                $bodyPayload .= "--{$boundary}\r\n";
                $bodyPayload .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $bodyPayload .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $bodyPayload .= chunk_split(base64_encode($textBody ?: strip_tags($htmlBody))) . "\r\n";

                $bodyPayload .= "--{$boundary}\r\n";
                $bodyPayload .= "Content-Type: text/html; charset=UTF-8\r\n";
                $bodyPayload .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $bodyPayload .= chunk_split(base64_encode($htmlBody)) . "\r\n";
                $bodyPayload .= "--{$boundary}--\r\n";
                $bodyPayload .= ".";
            }

            $cmd($bodyPayload, 250);
            $cmd("QUIT", 221);
            self::logDelivery($eventCode, $toEmail, $toName, $userId, $subject, 'enviado', 'Despachado exitosamente vía SMTP' . ($hasAttachments ? ' (con adjuntos)' : ''));
            return ['success' => true, 'message' => 'Correo enviado exitosamente'];

        } catch (\Throwable $e) {
            if (is_resource($socket)) fclose($socket);
            error_log('[SGP-MAIL] Error en transmisión SMTP: ' . $e->getMessage());
            self::logDelivery($eventCode, $toEmail, $toName, $userId, $subject, 'fallido', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtiene los colores configurados en el sistema (Tema/Branding)
     */
    public static function getBrandColors(): array
    {
        $colors = [
            'primary' => '#162660',
            'accent'  => '#2563eb'
        ];
        try {
            $db = Database::getInstance();
            $db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('color_primario', 'color_acento')");
            $rows = $db->resultSet() ?: [];
            foreach ($rows as $r) {
                if ($r->clave === 'color_primario' && !empty($r->valor)) $colors['primary'] = trim($r->valor);
                if ($r->clave === 'color_acento' && !empty($r->valor))   $colors['accent']  = trim($r->valor);
            }
        } catch (\Throwable $e) {
            // Silencioso, usa defaults
        }
        return $colors;
    }

    /**
     * Generador de plantilla HTML oficial Bento/Navy institucional con Logos, Banner y Paleta Dinámica
     */
    public static function renderBentoTemplate(string $badgeText, string $badgeColor, string $headline, string $contentHtml, string $ctaText = '', string $ctaUrl = '', string $footerNote = ''): string
    {
        $brand = self::getBrandColors();
        $primary = $brand['primary'];
        $accent  = $brand['accent'];

        // Comprobante Digital Informativo (Reemplaza botones engañosos con enlaces locales)
        $receiptNotice = "
        <div style='margin-top: 24px; padding: 14px 18px; background-color: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; text-align: center;'>
            <div style='font-size: 12px; font-weight: 700; color: #1e293b; letter-spacing: 0.3px;'>
                🔒 Comprobante Digital de Actividad &bull; SGP
            </div>
            <div style='font-size: 11.5px; color: #64748b; margin-top: 3px;'>
                Registro emitido automáticamente por el servidor del sistema
            </div>
        </div>";

        $footerExtra = $footerNote !== '' ? "<p style='margin-top: 8px; font-size: 11px; color: #94a3b8;'>{$footerNote}</p>" : '';

        return "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$headline}</title>
        </head>
        <body style='margin: 0; padding: 28px 12px; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <table role='presentation' width='100%' border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='100%' style='max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);' border='0' cellspacing='0' cellpadding='0'>
                            
                            <!-- Cabecera SGP Exclusiva (Solo Logo del Sistema y Título) -->
                            <tr>
                                <td style='background-color: #ffffff; padding: 22px 28px; border-bottom: 1.5px solid #f1f5f9;'>
                                    <table role='presentation' width='100%' border='0' cellspacing='0' cellpadding='0'>
                                        <tr>
                                            <td width='48' valign='middle' style='padding-right: 14px;'>
                                                <img src='cid:logo_sgp' alt='Logo SGP' width='44' height='44' style='display: block; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.06);' onerror=\"this.style.display='none'\">
                                            </td>
                                            <td valign='middle'>
                                                <div style='color: {$accent}; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;'>
                                                    SISTEMA DE GESTIÓN DE PASANTÍAS
                                                </div>
                                                <div style='color: #0f172a; font-size: 16px; font-weight: 800; letter-spacing: 0.2px; margin-top: 2px;'>
                                                    Notificación de Actividad &bull; SGP
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Cuerpo del Mensaje -->
                            <tr>
                                <td style='padding: 30px 28px 24px; color: #1e293b; font-size: 14px; line-height: 1.65;'>
                                    
                                    <div style='display: inline-block; background-color: {$badgeColor}; color: #ffffff; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 5px 13px; border-radius: 999px; letter-spacing: 0.6px; margin-bottom: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);'>
                                        {$badgeText}
                                    </div>

                                    <h2 style='margin: 0 0 16px; font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.3;'>
                                        {$headline}
                                    </h2>

                                    <div style='color: #334155; font-size: 14px;'>
                                        {$contentHtml}
                                    </div>

                                    {$receiptNotice}

                                </td>
                            </tr>

                            <!-- Pie de Página Minimalista SGP -->
                            <tr>
                                <td style='background-color: #f8fafc; padding: 20px 28px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #64748b; line-height: 1.55;'>
                                    <div style='font-weight: 700; color: #0f172a; margin-bottom: 4px;'>
                                        Sistema de Gestión de Pasantías (SGP)
                                    </div>
                                    <div>Mensaje automatizado emitido por control y auditoría interna. Por favor, no responda a este correo.</div>
                                    {$footerExtra}
                                    <div style='margin-top: 8px; font-size: 11px; color: #94a3b8;'>
                                        Control de Pasantías y Asistencias &bull; Todos los derechos reservados
                                    </div>
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
                <p>Ingresa al sistema desde la red local institucional para resolver las preguntas asignadas antes de la fecha límite.</p>
            ";

            $html = self::renderBentoTemplate('Evaluación Rápida', '#16a34a', 'Nuevo Examen Asignado', $content, '', '', 'Recuerda que cada intento queda registrado y auditado por la Coordinación de Docencia.');
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

            // Cooldown de 24 horas: evitar spam si el usuario inicia sesión varias veces en el mismo día
            $db->query("
                SELECT id FROM mail_historial
                WHERE usuario_id = :uid
                  AND codigo_evento = 'SEGURIDAD_LOGIN'
                  AND estado IN ('enviado', 'simulado')
                  AND created_at >= NOW() - INTERVAL 24 HOUR
                LIMIT 1
            ");
            $db->bind(':uid', $userId);
            if ($db->single()) {
                return true; // Suprimir duplicados en 24h
            }

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
                <p style='color: #64748b; font-size: 13px;'>Si fuiste tú, puedes ignorar este mensaje. Si no reconoces este acceso, por favor comunícate de inmediato con la administración del sistema o cambia tu contraseña.</p>
            ";

            $html = self::renderBentoTemplate('Alerta de Seguridad', '#2563eb', 'Inicio de Sesión Detectado', $content, '', '', 'Aviso de seguridad del Sistema de Gestión de Pasantes.');
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
                <p>Te confirmamos que se ha activado exitosamente el <strong>Acceso Biométrico Móvil (Huella Dactilar / Face ID)</strong> en tu cuenta del Sistema de Gestión de Pasantes:</p>
                <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'><strong>Dispositivo:</strong> {$deviceName}</div>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'><strong>Fecha y Hora de Vinculación:</strong> {$fechaHora}</div>
                    <div style='font-size: 13px; color: #166534;'><strong>Dirección IP:</strong> {$ip}</div>
                </div>
                <p style='color: #334155;'>A partir de ahora podrás iniciar sesión de forma instantánea pulsando el botón <strong>\"Ingresar con Huella Dactilar\"</strong> sin tener que escribir tu contraseña.</p>
                <p style='color: #64748b; font-size: 13px;'>Si no realizaste esta acción o no reconoces este dispositivo, puedes desvincularlo inmediatamente ingresando a tu Perfil en el sistema.</p>
            ";

            $html = self::renderBentoTemplate('Biometría Activada', '#16a34a', 'Acceso Biométrico Vinculado', $content, '', '', 'Registro de autenticación emitido por el SGP.');
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
                <p style='color: #334155;'>Para iniciar sesión en futuros accesos desde ese dispositivo deberás utilizar tu correo electrónico y tu contraseña.</p>
                <p style='color: #64748b; font-size: 13px;'>Si tú no solicitaste esta desafiliación, por favor cambia tu contraseña de inmediato.</p>
            ";

            $html = self::renderBentoTemplate('Seguridad', '#ea580c', 'Dispositivo Biométrico Desvinculado', $content, '', '', 'Aviso de seguridad del SGP.');
            $res = self::send($user->email, (string)$user->nombre, "⚠️ Dispositivo Biométrico Desvinculado: {$deviceName}", $html, '', 'BIOMETRIA_DESAFILIADA', $userId);
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyBiometricRemoved error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar Código OTP de 6 Dígitos para Autorización Bancaria de Nuevo Dispositivo Frecuente
     */
    public static function notifyDeviceVinculacionOtp(string $email, string $fullName, string $otp, string $deviceName, string $ip): bool
    {
        try {
            $fechaHora = date('d/m/Y h:i:s A');
            // Formatear código con espacio en medio: ej 849 201
            $otpFormateado = strlen($otp) === 6 ? substr($otp, 0, 3) . ' ' . substr($otp, 3, 3) : $otp;

            $content = "
                <p style='color: #1e293b; font-size: 15px;'>Hola <strong>{$fullName}</strong>,</p>
                <p style='color: #334155;'>Hemos recibido una solicitud para <strong>vincular un nuevo dispositivo frecuente</strong> a tu cuenta institucional en el Sistema de Gestión de Pasantes (SGP):</p>
                
                <div style='background: #eff6ff; border: 2px dashed #3b82f6; border-radius: 14px; padding: 22px; margin: 24px 0; text-align: center;'>
                    <div style='font-size: 12px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;'>Código de Autorización Único</div>
                    <div style='font-family: monospace, monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1d4ed8; margin: 6px 0;'>{$otpFormateado}</div>
                    <div style='font-size: 12px; color: #3b82f6; font-weight: 600; margin-top: 8px;'>⏱️ Válido durante 10 minutos (1 solo uso)</div>
                </div>

                <div style='background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;'>
                    <div style='font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase;'>Detalles del Dispositivo Solicitante:</div>
                    <div style='font-size: 13px; color: #334155; margin-bottom: 4px;'>📱 <strong>Dispositivo:</strong> {$deviceName}</div>
                    <div style='font-size: 13px; color: #334155; margin-bottom: 4px;'>🌐 <strong>Dirección IP:</strong> {$ip}</div>
                    <div style='font-size: 13px; color: #334155;'>📅 <strong>Fecha y Hora:</strong> {$fechaHora}</div>
                </div>

                <p style='color: #475569; font-size: 13px; line-height: 1.5;'>Ingresa este código en tu pantalla para autorizar la vinculación. Una vez validado, podrás acceder a tu cuenta de forma rápida y segura utilizando tu <strong>huella dactilar</strong> o tu <strong>patrón gestual</strong>.</p>
                
                <div style='background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 12px; font-size: 12px; color: #9f1239; margin-top: 20px;'>
                    🛡️ <strong>Alerta de Seguridad:</strong> Si tú no iniciaste esta solicitud, alguien está intentando acceder a tu cuenta. Ignora este código y cambia tu contraseña inmediatamente.
                </div>
            ";

            $html = self::renderBentoTemplate('Autorización Bancaria', '#2563eb', 'Código de Vinculación de Dispositivo', $content, '', '', 'Seguridad SGP — Verificación en dos pasos.');
            $res = self::send($email, $fullName, "🔐 Código de Autorización: {$otpFormateado} — Vinculación de Dispositivo", $html, '', 'VINCULACION_DISPOSITIVO_OTP');
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyDeviceVinculacionOtp error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar Confirmación cuando un Dispositivo queda Vinculado Exitosamente
     */
    public static function notifyDeviceVinculadoConfirmacion(string $email, string $fullName, string $deviceName, string $metodo, string $ip): bool
    {
        try {
            $fechaHora = date('d/m/Y h:i:s A');
            $metodoTexto = ($metodo === 'patron') ? 'Patrón de Desbloqueo Gestual' : 'Huella Dactilar / Biometría';

            $content = "
                <p style='color: #1e293b; font-size: 15px;'>Hola <strong>{$fullName}</strong>,</p>
                <p style='color: #334155;'>Te confirmamos que el siguiente dispositivo ha sido <strong>vinculado con éxito</strong> a tu cuenta institucional en el Sistema de Gestión de Pasantes (SGP):</p>
                
                <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                    <div style='font-size: 14px; font-weight: 700; color: #166534; margin-bottom: 8px;'>✅ Dispositivo Frecuente Autorizado</div>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'>📱 <strong>Dispositivo:</strong> {$deviceName}</div>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'>🔑 <strong>Método de Acceso Rápido:</strong> {$metodoTexto}</div>
                    <div style='font-size: 13px; color: #166534; margin-bottom: 4px;'>🌐 <strong>Dirección IP:</strong> {$ip}</div>
                    <div style='font-size: 13px; color: #166534;'>📅 <strong>Fecha:</strong> {$fechaHora}</div>
                </div>

                <p style='color: #334155; font-size: 13px; line-height: 1.5;'>A partir de ahora podrás iniciar sesión de forma rápida y cómoda desde este equipo utilizando tu {$metodoTexto}.</p>
                <p style='color: #64748b; font-size: 13px;'>Si no reconoces este dispositivo o deseas revocar su acceso, puedes desvincularlo en cualquier momento desde la sección de Seguridad en tu Perfil.</p>
            ";

            $html = self::renderBentoTemplate('Dispositivo Vinculado', '#16a34a', 'Nuevo Dispositivo Autorizado', $content, '', '', 'Aviso de seguridad institucional SGP.');
            $res = self::send($email, $fullName, "🛡️ Dispositivo Vinculado Exitosamente: {$deviceName}", $html, '', 'DISPOSITIVO_VINCULADO');
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyDeviceVinculadoConfirmacion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificación cuando el usuario actualiza sus datos personales o departamento (Antispam: solo si hay cambios reales)
     */
    public static function notifyProfileUpdated(string $email, string $fullName, array $cambios, string $ip): bool
    {
        if (empty($cambios)) return false;

        try {
            $fechaHora = date('d/m/Y h:i:s A');
            $filasHtml = '';
            foreach ($cambios as $c) {
                $campo = htmlspecialchars($c['campo'] ?? '');
                $ant = htmlspecialchars($c['anterior'] ?? 'No especificado');
                $nuevo = htmlspecialchars($c['nuevo'] ?? 'No especificado');
                $filasHtml .= "
                    <tr>
                        <td style='padding: 10px 12px; border: 1px solid #e2e8f0; font-weight: 600; color: #1e293b;'>{$campo}</td>
                        <td style='padding: 10px 12px; border: 1px solid #e2e8f0; color: #64748b; text-decoration: line-through;'>{$ant}</td>
                        <td style='padding: 10px 12px; border: 1px solid #e2e8f0; font-weight: 700; color: #16a34a;'>{$nuevo}</td>
                    </tr>
                ";
            }

            $content = "
                <p style='color: #1e293b; font-size: 15px;'>Hola <strong>{$fullName}</strong>,</p>
                <p style='color: #334155;'>Te informamos que se han guardado exitosamente cambios en tu información de perfil en el Sistema de Gestión de Pasantes (SGP):</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 13px; background: #ffffff; border-radius: 10px; overflow: hidden;'>
                    <thead>
                        <tr style='background: #f1f5f9; text-align: left; color: #475569;'>
                            <th style='padding: 10px 12px; border: 1px solid #e2e8f0;'>Dato Actualizado</th>
                            <th style='padding: 10px 12px; border: 1px solid #e2e8f0;'>Valor Anterior</th>
                            <th style='padding: 10px 12px; border: 1px solid #e2e8f0;'>Nuevo Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$filasHtml}
                    </tbody>
                </table>

                <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; font-size: 12px; color: #475569;'>
                    🌐 <strong>IP de la solicitud:</strong> {$ip} &nbsp;·&nbsp; 📅 <strong>Fecha:</strong> {$fechaHora}
                </div>

                <p style='color: #64748b; font-size: 13px;'>Si tú realizaste esta modificación, no es necesario realizar ninguna acción. Si no reconoces estos cambios, por favor cambia tu contraseña y comunícate con la administración.</p>
            ";

            $html = self::renderBentoTemplate('Perfil Actualizado', '#2563eb', 'Actualización de Datos Personales', $content, '', '', 'Notificación de seguridad de cuenta SGP.');
            $res = self::send($email, $fullName, "ℹ️ Actualización de Datos de Perfil — SGP", $html, '', 'ACTUALIZACION_PERFIL');
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyProfileUpdated error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificación cuando un pasante registra su asistencia en el quiosco
     * Notifica al Pasante (comprobante digital), a su Tutor Asignado y a los Administradores
     * Implementa desduplicación estricta para que ningún usuario reciba correos repetidos.
     */
    public static function notifyAttendance(int $pasanteId, string $tipo, string $hora, ?string $fecha = null): int
    {
        try {
            $rule = self::checkRule('ASISTENCIA_INDIVIDUAL');
            if (!$rule['allowed']) return 0;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.cedula, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_pasante,
                       d.nombre AS departamento_nombre,
                       dpa.tutor_id
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                LEFT JOIN departamentos d ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $pasanteId);
            $pasante = $db->single();
            if (!$pasante) return 0;

            $destinatarios = []; // Clave: email normalizado en minúsculas

            // 1. Notificar al propio Pasante (Recibo Digital de Marcaje)
            if (!empty($pasante->correo)) {
                $chkP = self::checkRule('ASISTENCIA_INDIVIDUAL', (int)$pasante->id, (int)$pasante->rol_id);
                if ($chkP['allowed']) {
                    $emailKey = strtolower(trim($pasante->correo));
                    $destinatarios[$emailKey] = [
                        'id'     => (int)$pasante->id,
                        'nombre' => $pasante->nombre_pasante ?: 'Pasante',
                        'rol_id' => (int)$pasante->rol_id,
                        'cargo'  => 'Pasante'
                    ];
                }
            }

            // 2. Tutor Asignado (si tiene y tiene correo válido)
            if (!empty($pasante->tutor_id)) {
                $db->query("
                    SELECT u.id, u.correo, u.rol_id,
                           TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                    FROM usuarios u
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE u.id = :tid AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
                    LIMIT 1
                ");
                $db->bind(':tid', (int)$pasante->tutor_id);
                $tutor = $db->single();
                if ($tutor && !empty($tutor->correo)) {
                    $chk = self::checkRule('ASISTENCIA_INDIVIDUAL', (int)$tutor->id, (int)$tutor->rol_id);
                    if ($chk['allowed']) {
                        $emailKey = strtolower(trim($tutor->correo));
                        if (!isset($destinatarios[$emailKey])) {
                            $destinatarios[$emailKey] = [
                                'id'     => (int)$tutor->id,
                                'nombre' => $tutor->nombre ?: 'Tutor Institucional',
                                'rol_id' => (int)$tutor->rol_id,
                                'cargo'  => 'Tutor Asignado'
                            ];
                        }
                    }
                }
            }

            // 3. Administradores Activos (Rol 0 y 1)
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.rol_id IN (0, 1) AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
            ");
            $admins = $db->resultSet() ?: [];
            foreach ($admins as $adm) {
                if (empty($adm->correo)) continue;
                $emailKey = strtolower(trim($adm->correo));

                // Desduplicación estricta
                if (isset($destinatarios[$emailKey])) {
                    continue;
                }

                $chk = self::checkRule('ASISTENCIA_INDIVIDUAL', (int)$adm->id, (int)$adm->rol_id);
                if (!$chk['allowed']) continue;

                $destinatarios[$emailKey] = [
                    'id'     => (int)$adm->id,
                    'nombre' => $adm->nombre ?: 'Administrador SGP',
                    'rol_id' => (int)$adm->rol_id,
                    'cargo'  => 'Administrador'
                ];
            }

            if (empty($destinatarios)) {
                return 0;
            }

            $fechaStr = $fecha ?: date('d/m/Y');
            $enviados = 0;

            foreach ($destinatarios as $email => $dest) {
                $isSelf = ((int)$dest['id'] === (int)$pasante->id);
                $subject = $isSelf
                    ? "⏰ Comprobante de Asistencia ({$tipo}) — {$fechaStr}"
                    : "⏰ Marcaje de Asistencia: {$pasante->nombre_pasante} ({$tipo})";

                $saludo = $isSelf
                    ? "<p>Hola <strong>{$pasante->nombre_pasante}</strong>,</p><p>Te confirmamos que se ha registrado tu asistencia en el quiosco institucional:</p>"
                    : "<p>Estimado(a) <strong>{$dest['nombre']}</strong>,</p><p>Te notificamos que se ha registrado una marcación de asistencia para el pasante <strong>{$pasante->nombre_pasante}</strong>:</p>";

                $content = "
                    {$saludo}
                    <div style='background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-left: 4px solid #10b981; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 14px; color: #1e293b; margin-bottom: 6px;'><strong>Pasante:</strong> {$pasante->nombre_pasante} &nbsp;|&nbsp; <strong>C.I.:</strong> {$pasante->cedula}</div>
                        <div style='font-size: 14px; color: #1e293b; margin-bottom: 6px;'><strong>Departamento:</strong> " . ($pasante->departamento_nombre ?: 'Sin asignar') . "</div>
                        <div style='font-size: 14px; color: #1e293b; margin-bottom: 6px;'><strong>Evento:</strong> <span style='background:#ecfdf5;color:#047857;font-weight:700;padding:2px 8px;border-radius:6px;'>{$tipo}</span></div>
                        <div style='font-size: 14px; color: #1e293b;'><strong>Hora de Registro:</strong> <strong>{$hora}</strong> &nbsp;|&nbsp; <strong>Fecha:</strong> {$fechaStr}</div>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>Este registro ha sido auditado y guardado en la base de datos de control de asistencias del SGP.</p>
                ";

                $badgeTitle = $isSelf ? 'Comprobante de Asistencia' : 'Asistencia en Vivo';
                $html = self::renderBentoTemplate($badgeTitle, '#10b981', ($isSelf ? "Marcaje Registrado: {$tipo}" : "Marcaje de {$pasante->nombre_pasante}"), $content);

                $res = self::send(
                    $email,
                    $dest['nombre'],
                    $subject,
                    $html,
                    '',
                    'ASISTENCIA_INDIVIDUAL',
                    $dest['id']
                );
                if (!empty($res['success'])) {
                    $enviados++;
                }
            }

            return $enviados;
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyAttendance error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notificación cuando un pasante solicita restablecimiento de PIN en el quiosco
     */
    public static function notifyPinReset(int $pasanteId, string $cedula): int
    {
        try {
            $rule = self::checkRule('SOLICITUD_RESET_PIN');
            if (!$rule['allowed']) return 0;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.cedula,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_pasante,
                       dpa.tutor_id,
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

            $destinatarios = [];

            // 1. Tutor asignado
            if (!empty($pasante->tutor_id)) {
                $db->query("
                    SELECT u.id, u.correo, u.rol_id,
                           TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                    FROM usuarios u
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE u.id = :tid AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
                    LIMIT 1
                ");
                $db->bind(':tid', (int)$pasante->tutor_id);
                $tutor = $db->single();
                if ($tutor && !empty($tutor->correo)) {
                    $chk = self::checkRule('SOLICITUD_RESET_PIN', (int)$tutor->id, (int)$tutor->rol_id);
                    if ($chk['allowed']) {
                        $destinatarios[strtolower(trim($tutor->correo))] = [
                            'id'     => (int)$tutor->id,
                            'nombre' => $tutor->nombre ?: 'Tutor Institucional'
                        ];
                    }
                }
            }

            // 2. Administradores (evitando duplicar si ya está como tutor)
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.rol_id IN (0, 1) AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
            ");
            $admins = $db->resultSet() ?: [];
            foreach ($admins as $adm) {
                if (empty($adm->correo)) continue;
                $emailKey = strtolower(trim($adm->correo));
                if (isset($destinatarios[$emailKey])) continue; // Desduplicación

                $chk = self::checkRule('SOLICITUD_RESET_PIN', (int)$adm->id, (int)$adm->rol_id);
                if (!$chk['allowed']) continue;

                $destinatarios[$emailKey] = [
                    'id'     => (int)$adm->id,
                    'nombre' => $adm->nombre ?: 'Administrador SGP'
                ];
            }

            if (empty($destinatarios)) return 0;

            $horaStr = date('d/m/Y h:i A');
            $subject = "🔑 Solicitud de Restablecimiento de PIN: {$pasante->nombre_pasante}";
            $content = "
                <p>Estimado(a),</p>
                <p>El pasante ha solicitado el restablecimiento de su clave PIN de 4 dígitos para el quiosco de asistencia:</p>
                <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Pasante:</strong> {$pasante->nombre_pasante} &nbsp;|&nbsp; <strong>C.I.:</strong> {$cedula}</div>
                    <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Departamento:</strong> " . ($pasante->departamento_nombre ?: 'Sin asignar') . "</div>
                    <div style='font-size: 14px; color: #1e3a8a;'><strong>Fecha de Solicitud:</strong> {$horaStr}</div>
                </div>
                <p style='color: #64748b; font-size: 13px;'>Puede ingresar a la ficha del pasante en el sistema para generar o asignar un nuevo PIN numérico de acceso.</p>
            ";

            $html = self::renderBentoTemplate('Soporte de Acceso', '#2563eb', "Solicitud de PIN: {$pasante->nombre_pasante}", $content, '', '', 'Notificación para la administración del SGP.');

            $enviados = 0;
            foreach ($destinatarios as $email => $dest) {
                $res = self::send($email, $dest['nombre'], $subject, $html, '', 'SOLICITUD_RESET_PIN', $dest['id']);
                if (!empty($res['success'])) $enviados++;
            }
            return $enviados;

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyPinReset error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notificación de Nueva Asignación de Pasantía al Pasante y a su Tutor Asignado
     */
    public static function notifyAssignment(int $pasanteId, string $departamentoNombre, string $fechaInicio, string $fechaFin, int $horasMeta, ?int $tutorId = null): int
    {
        try {
            $rule = self::checkRule('NUEVA_ASIGNACION');
            if (!$rule['allowed']) return 0;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.correo, u.rol_id, u.cedula,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_pasante
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $pasanteId);
            $pasante = $db->single();
            if (!$pasante || empty($pasante->correo)) return 0;

            $tutorNombre = 'No asignado aún';
            $tutorEmail = null;
            $tutorRolId = 2;
            if ($tutorId > 0) {
                $db->query("
                    SELECT u.id, u.correo, u.rol_id,
                           TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_tutor
                    FROM usuarios u
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE u.id = :tid LIMIT 1
                ");
                $db->bind(':tid', $tutorId);
                $tutor = $db->single();
                if ($tutor) {
                    $tutorNombre = $tutor->nombre_tutor ?: 'Tutor Asignado';
                    $tutorEmail  = $tutor->correo;
                    $tutorRolId  = (int)$tutor->rol_id;
                }
            }

            $inicioFmt = date('d/m/Y', strtotime($fechaInicio));
            $finFmt    = date('d/m/Y', strtotime($fechaFin));
            $enviados  = 0;

            // 1. Notificar al Pasante
            $chkP = self::checkRule('NUEVA_ASIGNACION', (int)$pasante->id, (int)$pasante->rol_id);
            if ($chkP['allowed'] && !empty($pasante->correo)) {
                $content = "
                    <p>Hola <strong>{$pasante->nombre_pasante}</strong>,</p>
                    <p>Has sido asignado formalmente a tu pasantía académica en la institución. A continuación tus datos oficiales de asignación:</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Departamento:</strong> {$departamentoNombre}</div>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Tutor Asignado:</strong> {$tutorNombre}</div>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Fecha de Inicio:</strong> {$inicioFmt}</div>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Culminación Estimada:</strong> {$finFmt}</div>
                        <div style='font-size: 14px; color: #1e3a8a;'><strong>Meta de Horas:</strong> {$horasMeta} horas académicas</div>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>Recuerda registrar puntualmente tu asistencia diaria en el quiosco institucional con tu cédula o código QR.</p>
                ";

                $html = self::renderBentoTemplate('Asignación Formal', '#2563eb', 'Asignación de Pasantía Académica', $content, '', '', 'Registro emitido por la Coordinación de Docencia e Investigación.');
                $res = self::send($pasante->correo, $pasante->nombre_pasante ?: 'Pasante', "📋 Confirmación de Asignación de Pasantía — {$departamentoNombre}", $html, '', 'NUEVA_ASIGNACION', (int)$pasante->id);
                if (!empty($res['success'])) $enviados++;
            }

            // 2. Notificar al Tutor Asignado (si existe y tiene correo)
            if ($tutorEmail) {
                $chkT = self::checkRule('NUEVA_ASIGNACION', $tutorId, $tutorRolId);
                if ($chkT['allowed']) {
                    $contentTutor = "
                        <p>Estimado(a) <strong>{$tutorNombre}</strong>,</p>
                        <p>Le informamos que se le ha asignado un nuevo pasante bajo su supervisión académica:</p>
                        <div style='background-color: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                            <div style='font-size: 14px; color: #166534; margin-bottom: 5px;'><strong>Pasante:</strong> {$pasante->nombre_pasante} &nbsp;|&nbsp; <strong>C.I.:</strong> {$pasante->cedula}</div>
                            <div style='font-size: 14px; color: #166534; margin-bottom: 5px;'><strong>Departamento:</strong> {$departamentoNombre}</div>
                            <div style='font-size: 14px; color: #166534; margin-bottom: 5px;'><strong>Período:</strong> {$inicioFmt} al {$finFmt}</div>
                            <div style='font-size: 14px; color: #166534;'><strong>Meta Requerida:</strong> {$horasMeta} horas</div>
                        </div>
                        <p style='color: #64748b; font-size: 13px;'>Podrá realizar el seguimiento de puntualidad, asistencias y evaluaciones desde su panel de Tutor en la red interna.</p>
                    ";

                    $htmlTutor = self::renderBentoTemplate('Supervisión Académica', '#16a34a', 'Nuevo Pasante Asignado', $contentTutor, '', '', 'Notificación para el tutor de departamento.');
                    $resT = self::send($tutorEmail, $tutorNombre, "👨‍🏫 Nuevo Pasante Asignado: {$pasante->nombre_pasante}", $htmlTutor, '', 'NUEVA_ASIGNACION', $tutorId);
                    if (!empty($resT['success'])) $enviados++;
                }
            }

            return $enviados;
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyAssignment error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notificación al Pasante cuando su Tutor registra una Evaluación
     */
    public static function notifyEvaluation(int $pasanteId, int $tutorId, float $promedio, string $lapso, string $observaciones = ''): int
    {
        try {
            $rule = self::checkRule('EVALUACION_REGISTRADA');
            if (!$rule['allowed']) return 0;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_pasante
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $pasanteId);
            $pasante = $db->single();
            if (!$pasante || empty($pasante->correo)) return 0;

            $chk = self::checkRule('EVALUACION_REGISTRADA', (int)$pasante->id, (int)$pasante->rol_id);
            if (!$chk['allowed']) return 0;

            // Datos del tutor
            $db->query("
                SELECT TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_tutor
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :tid LIMIT 1
            ");
            $db->bind(':tid', $tutorId);
            $tutorRow = $db->single();
            $tutorNombre = $tutorRow ? ($tutorRow->nombre_tutor ?: 'Tu tutor') : 'Tu tutor';

            $lapsoTexto = !empty($lapso) ? "Lapso {$lapso}" : 'Evaluación Periódica';
            $fechaFmt = date('d/m/Y');
            $obsHtml = !empty($observaciones) ? "<div style='font-size: 13px; color: #1e3a8a; margin-top: 6px;'><strong>Observaciones del Tutor:</strong> <em>" . htmlspecialchars($observaciones) . "</em></div>" : '';

            $content = "
                <p>Hola <strong>{$pasante->nombre_pasante}</strong>,</p>
                <p>Tu tutor <strong>{$tutorNombre}</strong> ha registrado una nueva evaluación de desempeño en tu ficha académica:</p>
                <div style='background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                    <div style='font-size: 14px; color: #334155; margin-bottom: 6px;'><strong>Etapa Evaluada:</strong> {$lapsoTexto}</div>
                    <div style='font-size: 14px; color: #334155; margin-bottom: 6px;'><strong>Fecha de Calificación:</strong> {$fechaFmt}</div>
                    <div style='font-size: 18px; font-weight: 800; color: #0284c7; margin-top: 10px; margin-bottom: 4px;'>
                        Nota Promedio: <span style='font-size: 22px; color: #0369a1;'>{$promedio} / 5.00</span>
                    </div>
                    {$obsHtml}
                </div>
                <p style='color: #64748b; font-size: 13px;'>Puedes consultar el desglose completo de competencias evaluadas ingresando a tu perfil en el sistema.</p>
            ";

            $html = self::renderBentoTemplate('Evaluación de Desempeño', '#0284c7', 'Calificación Registrada', $content, '', '', 'Registro académico auditado por la Coordinación de Docencia.');
            $res = self::send($pasante->correo, $pasante->nombre_pasante ?: 'Pasante', "📊 Calificación Registrada: {$promedio}/5 ({$lapsoTexto})", $html, '', 'EVALUACION_REGISTRADA', (int)$pasante->id);
            return !empty($res['success']) ? 1 : 0;

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyEvaluation error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notificación al Pasante al Culminar o Cerrar el Período Académico
     */
    public static function notifyPeriodClosure(int $pasanteId, string $periodoNombre, int $horasAcumuladas, int $horasMeta, bool $completado): bool
    {
        try {
            $rule = self::checkRule('PERIODO_CERRADO');
            if (!$rule['allowed']) return false;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_pasante
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $pasanteId);
            $pasante = $db->single();
            if (!$pasante || empty($pasante->correo)) return false;

            $chk = self::checkRule('PERIODO_CERRADO', (int)$pasante->id, (int)$pasante->rol_id);
            if (!$chk['allowed']) return false;

            $pct = min(100, round(($horasAcumuladas / max(1, $horasMeta)) * 100));

            if ($completado) {
                $badgeText  = '¡Pasantía Culminada!';
                $badgeColor = '#16a34a';
                $subject    = "🎓 ¡Felicidades! Pasantía Culminada — {$periodoNombre}";
                $content = "
                    <p>Estimado(a) <strong>{$pasante->nombre_pasante}</strong>,</p>
                    <p>La Coordinación de Docencia e Investigación del Instituto de Salud Pública te felicita por haber <strong>completado exitosamente</strong> tu pasantía académica correspondiente al período <strong>«{$periodoNombre}»</strong>.</p>
                    <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 15px; font-weight: 700; color: #166534; margin-bottom: 6px;'>Balance Final de Pasantía</div>
                        <div style='font-size: 14px; color: #15803d; margin-bottom: 4px;'><strong>Horas Cumplidas:</strong> {$horasAcumuladas} / {$horasMeta} hrs ({$pct}%)</div>
                        <div style='font-size: 14px; color: #15803d;'><strong>Estado Académico:</strong> Culminado con Éxito</div>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>Tu carta de culminación y constancia oficial pueden ser solicitadas y retiradas en el Departamento de Recursos Humanos / Docencia.</p>
                ";
            } else {
                $badgeText  = 'Período Concluido';
                $badgeColor = '#d97706';
                $subject    = "📋 Notificación de Cierre de Período — {$periodoNombre}";
                $content = "
                    <p>Estimado(a) <strong>{$pasante->nombre_pasante}</strong>,</p>
                    <p>Te informamos que el período académico <strong>«{$periodoNombre}»</strong> ha concluido formalmente en el sistema.</p>
                    <div style='background-color: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 15px; font-weight: 700; color: #92400e; margin-bottom: 6px;'>Registro Final de Asistencias</div>
                        <div style='font-size: 14px; color: #b45309; margin-bottom: 4px;'><strong>Horas Registradas:</strong> {$horasAcumuladas} de {$horasMeta} hrs ({$pct}%)</div>
                        <div style='font-size: 14px; color: #b45309;'><strong>Estado:</strong> Cierre de Período</div>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>Para cualquier consulta sobre tu estatus o continuidad, comunícate con la administración del instituto.</p>
                ";
            }

            $html = self::renderBentoTemplate($badgeText, $badgeColor, $subject, $content, '', '', 'Comunicado oficial del Sistema de Gestión de Pasantes.');
            $res = self::send($pasante->correo, $pasante->nombre_pasante ?: 'Pasante', $subject, $html, '', 'PERIODO_CERRADO', (int)$pasante->id);
            return !empty($res['success']);

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyPeriodClosure error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Alerta a Administradores sobre Bloqueos por Intentos Fallidos o Infracciones de Seguridad
     */
    public static function notifyFailedAttempt(string $identificador, string $nombreUsuario, string $motivo, string $ip, string $origen = 'Quiosco'): int
    {
        try {
            $rule = self::checkRule('BLOQUEO_INTENTO_FALLIDO');
            if (!$rule['allowed']) return 0;

            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.rol_id IN (0, 1) AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
            ");
            $admins = $db->resultSet() ?: [];
            if (empty($admins)) return 0;

            $horaStr = date('d/m/Y h:i:s A');
            $subject = "🚨 Alerta de Seguridad: Bloqueo de Intentos Fallidos ({$origen})";
            $content = "
                <p>Estimada Administración,</p>
                <p>El sistema ha ejecutado un bloqueo automático de seguridad tras detectar múltiples intentos anómalos:</p>
                <div style='background-color: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                    <div style='font-size: 15px; font-weight: 700; color: #991b1b; margin-bottom: 6px;'>Detalle del Incidente</div>
                    <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Origen / Módulo:</strong> {$origen}</div>
                    <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Identificador / C.I.:</strong> {$identificador}</div>
                    <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Usuario Asociado:</strong> {$nombreUsuario}</div>
                    <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Motivo de Bloqueo:</strong> {$motivo}</div>
                    <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Dirección IP:</strong> {$ip}</div>
                    <div style='font-size: 13.5px; color: #b91c1c;'><strong>Fecha y Hora:</strong> {$horaStr}</div>
                </div>
                <p style='color: #64748b; font-size: 13px;'>Esta alerta se emite para fines de auditoría y prevención de accesos no autorizados.</p>
            ";

            $html = self::renderBentoTemplate('Alerta Crítica', '#dc2626', 'Bloqueo Automático por Seguridad', $content, '', '', 'Notificación de seguridad para administradores del SGP.');

            $enviados = 0;
            foreach ($admins as $adm) {
                $chk = self::checkRule('BLOQUEO_INTENTO_FALLIDO', (int)$adm->id, (int)$adm->rol_id);
                if (!$chk['allowed']) continue;

                $res = self::send($adm->correo, $adm->nombre ?: 'Administrador', $subject, $html, '', 'BLOQUEO_INTENTO_FALLIDO', (int)$adm->id);
                if (!empty($res['success'])) $enviados++;
            }
            return $enviados;

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyFailedAttempt error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Envía el Resumen Diario Consolidado de Asistencia y Puntualidad a Tutores y Administradores
     */
    public static function sendDailyAttendanceSummary(?string $fecha = null): array
    {
        try {
            $rule = self::checkRule('ASISTENCIA_RESUMEN_DIARIO');
            if (!$rule['allowed']) {
                return ['success' => false, 'message' => 'La regla ASISTENCIA_RESUMEN_DIARIO está pausada o inactiva'];
            }

            $dia = $fecha ?: date('Y-m-d');
            $db = Database::getInstance();

            // Métricas del día
            $db->query("
                SELECT
                    SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
                    SUM(CASE WHEN estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados,
                    SUM(CASE WHEN estado = 'Ausente' THEN 1 ELSE 0 END) AS ausentes,
                    SUM(CASE WHEN TIME(COALESCE(hora_entrada, hora_registro)) > '08:05:00' AND estado = 'Presente' THEN 1 ELSE 0 END) AS retardos,
                    COUNT(*) AS total_registros
                FROM asistencias
                WHERE fecha = :fecha
            ");
            $db->bind(':fecha', $dia);
            $stats = $db->single();

            $presentes    = (int)($stats->presentes    ?? 0);
            $justificados = (int)($stats->justificados ?? 0);
            $ausentes     = (int)($stats->ausentes     ?? 0);
            $retardos     = (int)($stats->retardos     ?? 0);
            $totalReg     = (int)($stats->total_registros ?? 0);

            $fechaFmt = date('d/m/Y', strtotime($dia));

            // Destinatarios: Administradores y Tutores
            $db->query("
                SELECT u.id, u.correo, u.rol_id,
                       TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.rol_id IN (0, 1, 2) AND u.estado = 'activo' AND u.correo IS NOT NULL AND u.correo != ''
            ");
            $destinatarios = $db->resultSet() ?: [];
            if (empty($destinatarios)) {
                return ['success' => false, 'message' => 'No hay destinatarios activos con correo'];
            }

            $subject = "📊 Resumen Diario de Asistencias SGP — {$fechaFmt}";
            $content = "
                <p>Estimado(a) integrante del equipo institucional,</p>
                <p>A continuación se presenta el balance consolidado de asistencias de la jornada laboral de fecha <strong>{$fechaFmt}</strong>:</p>
                <div style='background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                    <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                        <tr>
                            <td style='padding: 6px 0; color: #166534; font-weight: 700;'>✅ Asistencias Presentes:</td>
                            <td style='padding: 6px 0; text-align: right; font-weight: 800; color: #166534;'>{$presentes}</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #d97706; font-weight: 700;'>⏰ Llegadas con Retardo:</td>
                            <td style='padding: 6px 0; text-align: right; font-weight: 800; color: #d97706;'>{$retardos}</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #0284c7; font-weight: 700;'>📋 Días Justificados / Feriados:</td>
                            <td style='padding: 6px 0; text-align: right; font-weight: 800; color: #0284c7;'>{$justificados}</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #dc2626; font-weight: 700;'>❌ Faltas / Ausencias:</td>
                            <td style='padding: 6px 0; text-align: right; font-weight: 800; color: #dc2626;'>{$ausentes}</td>
                        </tr>
                    </table>
                </div>
                <p style='color: #64748b; font-size: 13px;'>Para auditoría detallada pasante por pasante, ingrese al módulo de Asistencias en el sistema.</p>
            ";

            $html = self::renderBentoTemplate('Resumen Diario', '#0284c7', "Asistencias del Día — {$fechaFmt}", $content, '', '', 'Reporte consolidado emitido automáticamente por el SGP.');

            $enviados = 0;
            foreach ($destinatarios as $dest) {
                $chk = self::checkRule('ASISTENCIA_RESUMEN_DIARIO', (int)$dest->id, (int)$dest->rol_id);
                if (!$chk['allowed']) continue;

                $res = self::send($dest->correo, $dest->nombre ?: 'Usuario SGP', $subject, $html, '', 'ASISTENCIA_RESUMEN_DIARIO', (int)$dest->id);
                if (!empty($res['success'])) $enviados++;
            }

            return ['success' => true, 'enviados' => $enviados, 'fecha' => $fechaFmt];

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] sendDailyAttendanceSummary error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envía el respaldo comprimido de base de datos y sistema a la cuenta oficial de Gmail
     */
    public static function sendCloudBackup(string $zipPath, array $stats = []): array
    {
        try {
            $cfg = self::getConfig();
            $destEmail = !empty($cfg['user']) ? trim($cfg['user']) : 'sgp.ispeb@gmail.com';
            $destName  = 'Dirección SGP';

            if (!file_exists($zipPath)) {
                return ['success' => false, 'message' => 'El archivo de respaldo comprimido no existe: ' . $zipPath];
            }

            $fileSize = filesize($zipPath);
            $sizeFormatted = round($fileSize / (1024 * 1024), 2) . ' MB';
            $fechaStr = date('d/m/Y h:i A');
            $servidorIp = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? (function_exists('gethostname') ? gethostbyname(gethostname()) : '127.0.0.1');

            $totalTablas = $stats['total_tablas'] ?? 'N/D';
            $totalFilas  = $stats['total_filas'] ?? 'N/D';
            $nombreArchivo = basename($zipPath);

            $subject = "💾 [RESPALDO NUBE] Base de Datos SGP — " . date('d/m/Y');
            
            $content = "
                <p>Estimada Administración,</p>
                <p>Se ha generado exitosamente el <strong>Respaldo Integral Automatizado</strong> de la base de datos y configuraciones del Sistema de Gestión de Pasantías (SGP).</p>
                
                <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                    <div style='font-size: 15px; font-weight: 700; color: #166534; margin-bottom: 10px;'>
                        📦 Detalles del Archivo de Respaldo
                    </div>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #14532d;'>
                        <tr>
                            <td style='padding: 4px 0; font-weight: 600;'>Archivo:</td>
                            <td style='padding: 4px 0; text-align: right; font-family: monospace;'>{$nombreArchivo}</td>
                        </tr>
                        <tr>
                            <td style='padding: 4px 0; font-weight: 600;'>Peso del Respaldo:</td>
                            <td style='padding: 4px 0; text-align: right; font-weight: bold;'>{$sizeFormatted}</td>
                        </tr>
                        <tr>
                            <td style='padding: 4px 0; font-weight: 600;'>Fecha y Hora:</td>
                            <td style='padding: 4px 0; text-align: right;'>{$fechaStr}</td>
                        </tr>
                        <tr>
                            <td style='padding: 4px 0; font-weight: 600;'>Servidor Emisor:</td>
                            <td style='padding: 4px 0; text-align: right;'>IP {$servidorIp}</td>
                        </tr>
                        <tr>
                            <td style='padding: 4px 0; font-weight: 600;'>Tablas Exportadas:</td>
                            <td style='padding: 4px 0; text-align: right;'>{$totalTablas} tablas</td>
                        </tr>
                    </table>
                </div>

                <div style='background: #f8fafc; border-left: 4px solid #162660; padding: 12px 16px; border-radius: 6px; font-size: 12.5px; color: #475569; margin-top: 14px;'>
                    <strong>🔐 Aviso de Seguridad y Recuperación ante Desastres (DRP):</strong><br>
                    Este correo almacena una copia íntegra de la base de datos institucional en la nube. Conserve este correo; en caso de fallo físico de la computadora del servidor, este archivo permitirá restaurar el 100% de la información del sistema en minutos.
                </div>
            ";

            $html = self::renderBentoTemplate(
                'Respaldo de Seguridad',
                '#16a34a',
                'Respaldo de Base de Datos Generado',
                $content,
                '',
                '',
                'Copia íntegra enviada a la cuenta de respaldo del SGP.'
            );

            $attachments = [
                [
                    'path' => $zipPath,
                    'name' => $nombreArchivo,
                    'type' => 'application/zip'
                ]
            ];

            return self::send(
                $destEmail,
                $destName,
                $subject,
                $html,
                '',
                'RESPALDO_NUBE',
                null,
                true,
                $attachments
            );

        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] sendCloudBackup error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Notificación de seguridad por cambio de correo electrónico institucional.
     * Envía:
     *  1. Alerta al correo anterior (aviso de seguridad crítico).
     *  2. Confirmación al nuevo correo.
     *  3. Notificación a los administradores para trazabilidad.
     */
    public static function notifyEmailChange(int $userId, string $oldEmail, string $newEmail, string $ip): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("
                SELECT u.id, u.cedula, r.nombre AS rol_nombre,
                       CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, '')) AS nombre_completo
                FROM usuarios u
                LEFT JOIN roles r ON r.id = u.rol_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $db->bind(':id', $userId);
            $user = $db->single();
            $nombre = trim($user->nombre_completo ?? 'Usuario');
            if (empty($nombre)) $nombre = $user->cedula ?? 'Usuario';
            $rol = $user->rol_nombre ?? 'Usuario';
            $fecha = date('d/m/Y h:i:s A');

            // 1. Alerta al correo anterior
            if (!empty($oldEmail)) {
                $contentOld = "
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Te informamos que la dirección de correo asociada a tu cuenta en el <strong>Sistema de Gestión de Pasantías (SGP)</strong> ha sido modificada.</p>
                    <div style='background-color: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 13px; color: #9f1239; margin-bottom: 4px;'><strong>Correo Anterior:</strong> {$oldEmail}</div>
                        <div style='font-size: 13px; color: #9f1239; margin-bottom: 4px;'><strong>Nuevo Correo:</strong> {$newEmail}</div>
                        <div style='font-size: 13px; color: #9f1239; margin-bottom: 4px;'><strong>Fecha y Hora:</strong> {$fecha}</div>
                        <div style='font-size: 13px; color: #9f1239;'><strong>Dirección IP:</strong> {$ip}</div>
                    </div>
                    <p style='color: #b91c1c; font-weight: 700; font-size: 13px;'>⚠️ ¿No realizaste este cambio?</p>
                    <p style='color: #475569; font-size: 13px;'>Si tú no autorizaste esta modificación, comunícate de inmediato con la Administración del Sistema o la Coordinación de Docencia del ISPEB para bloquear el acceso no autorizado a tu cuenta.</p>
                ";
                $htmlOld = self::renderBentoTemplate('Aviso de Seguridad', '#e11d48', 'Modificación de Correo Electrónico', $contentOld, '', '', 'Alerta automática de seguridad y protección de identidad institucional.');
                self::send($oldEmail, $nombre, '🛡️ Alerta de Seguridad: Tu correo de cuenta SGP ha cambiado', $htmlOld, '', 'CAMBIO_CORREO', $userId, true);
            }

            // 2. Confirmación al nuevo correo
            if (!empty($newEmail)) {
                $contentNew = "
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Tu dirección de correo electrónico institucional ha sido actualizada correctamente en el <strong>Sistema de Gestión de Pasantías (SGP)</strong>.</p>
                    <div style='background-color: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 14px; font-weight: 800; color: #166534; margin-bottom: 6px;'>✅ Correo Activo: {$newEmail}</div>
                        <div style='font-size: 13px; color: #15803d; margin-bottom: 4px;'><strong>Usuario / Rol:</strong> {$rol}</div>
                        <div style='font-size: 13px; color: #15803d;'><strong>Fecha de Actualización:</strong> {$fecha}</div>
                    </div>
                    <p>A partir de este momento, deberás utilizar esta nueva dirección de correo para iniciar sesión y para recibir notificaciones institucionales de asistencia, evaluaciones y comunicados.</p>
                ";
                $htmlNew = self::renderBentoTemplate('Confirmación de Cuenta', '#16a34a', 'Correo Electrónico Actualizado', $contentNew, 'Iniciar Sesión', (defined('URLROOT') ? URLROOT : '') . '/auth/login', 'Conserva este mensaje como comprobante de actualización de tu cuenta.');
                self::send($newEmail, $nombre, '✅ Confirmación: Tu nuevo correo institucional en SGP', $htmlNew, '', 'CAMBIO_CORREO', $userId, true);
            }

            // 3. Notificación a los administradores
            $db->query("SELECT id, correo, COALESCE(cedula, 'Admin') AS admin_nom FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo' AND correo IS NOT NULL AND correo != ''");
            $admins = $db->resultSet() ?: [];
            foreach ($admins as $adm) {
                $contentAdm = "
                    <p>Estimado Administrador,</p>
                    <p>Se ha registrado un cambio de correo electrónico en la plataforma:</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Usuario:</strong> {$nombre} ({$rol})</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Correo Anterior:</strong> {$oldEmail}</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Nuevo Correo:</strong> {$newEmail}</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Fecha y Hora:</strong> {$fecha}</div>
                        <div style='font-size: 13px; color: #1e3a8a;'><strong>IP Origen:</strong> {$ip}</div>
                    </div>
                ";
                $htmlAdm = self::renderBentoTemplate('Auditoría de Seguridad', '#2563eb', 'Cambio de Correo de Usuario', $contentAdm, 'Ver Usuarios', (defined('URLROOT') ? URLROOT : '') . '/users', 'Registro de auditoría interna generado automáticamente.');
                self::send($adm->correo, (string)$adm->admin_nom, "📋 Auditoría: Cambio de correo del usuario {$nombre}", $htmlAdm, '', 'CAMBIO_CORREO', (int)$adm->id);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyEmailChange error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificación cuando un pasante se registra desde el portal público de acceso.
     */
    public static function notifyNewPasanteRegistration(int $pasanteId, string $fullName, string $cedula, string $institucion, string $email): bool
    {
        try {
            $db = Database::getInstance();
            $fecha = date('d/m/Y h:i A');

            // 1. Notificar a los administradores
            $db->query("SELECT id, correo, COALESCE(cedula, 'Admin') AS admin_nom FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo' AND correo IS NOT NULL AND correo != ''");
            $admins = $db->resultSet() ?: [];

            foreach ($admins as $adm) {
                $contentAdm = "
                    <p>Estimado Administrador,</p>
                    <p>Un nuevo pasante ha completado su registro en línea en el <strong>Sistema de Gestión de Pasantías (SGP)</strong> y se encuentra en estado <strong>Sin Asignar</strong>:</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 14px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;'>👤 {$fullName}</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Cédula:</strong> {$cedula}</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Institución:</strong> {$institucion}</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 4px;'><strong>Correo:</strong> {$email}</div>
                        <div style='font-size: 13px; color: #1e3a8a;'><strong>Fecha de Registro:</strong> {$fecha}</div>
                    </div>
                    <p>Por favor ingresa al módulo de Asignaciones para determinar el departamento, tutor y período académico correspondiente.</p>
                ";
                $htmlAdm = self::renderBentoTemplate('Nueva Inscripción', '#2563eb', 'Nuevo Pasante Registrado', $contentAdm, 'Asignar Pasante', (defined('URLROOT') ? URLROOT : '') . '/asignaciones', 'Gestión de Pasantías — Instituto de Salud Pública de Bolívar.');
                self::send($adm->correo, (string)$adm->admin_nom, "🎓 Nuevo Pasante Registrado: {$fullName} ({$cedula})", $htmlAdm, '', 'NUEVO_PASANTE_REGISTRADO', (int)$adm->id);
            }

            // 2. Correo de bienvenida y confirmación al pasante
            if (!empty($email)) {
                $contentPasante = "
                    <p>¡Hola <strong>{$fullName}</strong>!</p>
                    <p>Te damos una cordial bienvenida al <strong>Sistema de Gestión de Pasantías (SGP)</strong> del Instituto de Salud Pública del Estado Bolívar (ISPEB).</p>
                    <div style='background-color: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 14px; font-weight: 800; color: #166534; margin-bottom: 6px;'>✅ Registro Exitoso</div>
                        <div style='font-size: 13px; color: #15803d; margin-bottom: 4px;'><strong>Cédula:</strong> {$cedula}</div>
                        <div style='font-size: 13px; color: #15803d; margin-bottom: 4px;'><strong>Institución:</strong> {$institucion}</div>
                        <div style='font-size: 13px; color: #15803d;'><strong>Estado Inicial:</strong> En espera de asignación de tutor y departamento</div>
                    </div>
                    <p>La Coordinación de Docencia e Investigación revisará tu inscripción y te asignará el departamento y tutor correspondiente. Recibirás una notificación una vez formalizada tu asignación.</p>
                ";
                $htmlPasante = self::renderBentoTemplate('Bienvenido a SGP', '#16a34a', '¡Registro Completado con Éxito!', $contentPasante, 'Ingresar al Portal', (defined('URLROOT') ? URLROOT : '') . '/auth/login', 'Conserva tus credenciales en un lugar seguro.');
                self::send($email, $fullName, '🎓 Bienvenido al Sistema de Gestión de Pasantías (ISPEB)', $htmlPasante, '', 'NUEVO_PASANTE_REGISTRADO', $pasanteId, true);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyNewPasanteRegistration error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Alerta por correo a los administradores sobre pasantes sin asignar.
     */
    public static function notifyPasantesSinAsignar(int $totalSinAsignar): bool
    {
        try {
            if ($totalSinAsignar <= 0) return true;

            $db = Database::getInstance();
            $db->query("SELECT id, correo, COALESCE(cedula, 'Admin') AS admin_nom FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo' AND correo IS NOT NULL AND correo != ''");
            $admins = $db->resultSet() ?: [];

            foreach ($admins as $adm) {
                $content = "
                    <p>Estimado Administrador,</p>
                    <p>Se ha detectado un total de <strong>{$totalSinAsignar} pasante(s)</strong> registrados en el sistema que se encuentran en estado <strong>Sin Asignar</strong> o sin departamento/tutor formalizado:</p>
                    <div style='background-color: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 15px; font-weight: 800; color: #92400e; margin-bottom: 6px;'>⚠️ {$totalSinAsignar} Pasante(s) Pendientes</div>
                        <div style='font-size: 13px; color: #b45309;'>
                            Los pasantes sin asignación no pueden registrar asistencia ni acumular horas de pasantía académica hasta que se les vincule a un departamento y tutor.
                        </div>
                    </div>
                    <p>Te recomendamos acceder a la sección de asignaciones para completar su incorporación al período activo vigente.</p>
                ";
                $html = self::renderBentoTemplate('Alerta Académica', '#d97706', 'Pasantes Pendientes por Asignar', $content, 'Gestionar Asignaciones', (defined('URLROOT') ? URLROOT : '') . '/asignaciones', 'Recordatorio del Sistema de Gestión de Pasantías.');
                self::send($adm->correo, (string)$adm->admin_nom, "⚠️ Alerta SGP: {$totalSinAsignar} pasante(s) pendientes por asignar", $html, '', 'PASANTES_SIN_ASIGNAR', (int)$adm->id);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] notifyPasantesSinAsignar error: ' . $e->getMessage());
            return false;
        }
    }
}


