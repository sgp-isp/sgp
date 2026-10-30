<?php
/**
 * RateLimitMiddleware — Protección contra Ataques de Denegación de Servicio (DoS),
 * Bucles Infinitos y Fuerza Bruta por IP
 */

class RateLimitMiddleware
{
    private static $maxRequestsPerWindow = 120; // 120 peticiones
    private static $windowSeconds = 60;          // por ventana de 60 segundos
    private static $cooldownSeconds = 30;        // bloqueo de 30 segundos si excede

    /**
     * Verifica la tasa de peticiones de la IP solicitante
     */
    public static function check(): void
    {
        try {
            // No limitar en modo consola / CLI
            if (php_sapi_name() === 'cli') {
                return;
            }

            $ip = self::getClientIp();

            // No limitar conexiones de localhost puro si se desea
            if ($ip === '127.0.0.1' || $ip === '::1') {
                return;
            }

            $storageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sgp_ratelimit';
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0777, true);
            }

            $hash = md5($ip);
            $file = $storageDir . DIRECTORY_SEPARATOR . 'rl_' . $hash . '.json';
            $now = time();

            $data = [
                'window_start' => $now,
                'count'        => 0,
                'blocked_until'=> 0
            ];

            if (file_exists($file)) {
                $raw = @file_get_contents($file);
                $parsed = $raw ? json_decode($raw, true) : null;
                if (is_array($parsed)) {
                    $data = $parsed;
                }
            }

            // 1. ¿Está actualmente bloqueado?
            if (!empty($data['blocked_until']) && $now < $data['blocked_until']) {
                $remaining = $data['blocked_until'] - $now;
                self::emit429($remaining);
                exit;
            }

            // 2. ¿La ventana de tiempo ya venció? Reiniciar conteo
            if (($now - $data['window_start']) > self::$windowSeconds) {
                $data['window_start'] = $now;
                $data['count'] = 1;
                $data['blocked_until'] = 0;
            } else {
                // Incrementar conteo dentro de la ventana actual
                $data['count']++;

                if ($data['count'] > self::$maxRequestsPerWindow) {
                    // Activar bloqueo temporal
                    $data['blocked_until'] = $now + self::$cooldownSeconds;
                    @file_put_contents($file, json_encode($data), LOCK_EX);

                    error_log("[SGP-RATELIMIT] IP bloqueada temporalmente por exceso de peticiones: {$ip} ({$data['count']} reqs/min)");
                    self::emit429(self::$cooldownSeconds);
                    exit;
                }
            }

            @file_put_contents($file, json_encode($data), LOCK_EX);
        } catch (\Throwable $e) {
            // Failsafe: nunca romper la aplicación por el rate limiter
            error_log('[SGP-RATELIMIT] error: ' . $e->getMessage());
        }
    }

    /**
     * Emite la respuesta HTTP 429 Too Many Requests
     */
    private static function emit429(int $retryAfter): void
    {
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
               || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'status'  => 429,
                'message' => "Demasiadas peticiones. Por favor, espere {$retryAfter} segundos antes de continuar."
            ]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>429 — Límite de Peticiones</title>';
            echo '<style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;text-align:center;}';
            echo '.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:32px;max-width:480px;box-shadow:0 10px 25px rgba(0,0,0,0.5);}';
            echo 'h1{color:#f87171;font-size:1.8rem;margin-bottom:12px;} p{color:#94a3b8;line-height:1.6;font-size:0.95rem;}';
            echo '.badge{display:inline-block;background:#374151;color:#fbbf24;padding:6px 14px;border-radius:20px;font-weight:bold;margin:16px 0;}</style></head>';
            echo '<body><div class="card"><h1>Límite de Peticiones Superado</h1>';
            echo '<p>El sistema de seguridad SGP ha detectado un volumen inusualmente alto de solicitudes desde su conexión.</p>';
            echo '<div class="badge">Espere ' . $retryAfter . ' segundos</div>';
            echo '<p>La conexión se reanudará automáticamente para salvaguardar la estabilidad del servidor institucional.</p>';
            echo '</div></body></html>';
        }
    }

    /**
     * Obtiene la IP real del cliente considerando cabeceras de proxy
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
