<?php
/**
 * KioscoController — Registro de Asistencia Público
 *
 * SEGURIDAD: Acceso público — NO requiere sesión.
 * El pasante se autentica con su Cédula + PIN de 4 dígitos.
 *
 * ARQUITECTURA 3NF:
 *   - cedula/nombres/apellidos  → datos_personales (JOIN)
 *   - estado_pasantia           → datos_pasante (JOIN)
 *   - horas_acumuladas          → datos_pasante (UPDATE)
 *
 * RUTAS:
 *   GET  /kiosco          → index()   Vista fullscreen del kiosco
 *   POST /kiosco/marcar   → marcar()  JSON endpoint — registra asistencia
 *
 * @version 3.0 — Arquitectura 3NF
 */
class KioscoController extends Controller
{
    private $db;

    public function __construct()
    {
        // ⚠️ NO Session::start() — El Kiosco es 100% independiente de cualquier sesión de administrador
        // ⚠️ NO AuthMiddleware — acceso público para pasantes
        $config  = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
    }

    // ─────────────────────────────────────────────────────────────
    // [FIX-N-2 / FIX-N-5] Límite de intentos por IP + cédula.
    // El Kiosco es intencionalmente stateless (sin sesión PHP), así que
    // el throttling no puede vivir en $_SESSION — se persiste en la
    // tabla `intentos_pin_kiosco` (ver database/migrations/
    // create_intentos_pin_kiosco.sql), con el mismo patrón atómico
    // INSERT...ON DUPLICATE KEY que ya usa AuthController para el login.
    // A diferencia del bloqueo permanente de AuthController (que un
    // atacante puede usar para sabotear una cuenta ajena, ver hallazgo
    // AS-1), aquí el bloqueo es TEMPORAL — se libera solo tras el
    // cooldown, nunca requiere intervención manual de un Admin.
    // ─────────────────────────────────────────────────────────────

    /**
     * Devuelve un mensaje de bloqueo si (ip, cedula, accion) está en cooldown, o null si puede continuar.
     */
    private function kioscoBloqueado(string $ip, string $cedula, string $accion): ?string
    {
        $this->db->query("
            SELECT bloqueado_hasta FROM intentos_pin_kiosco
            WHERE direccion_ip = :ip AND cedula = :cedula AND accion = :accion
            LIMIT 1
        ");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':cedula', $cedula);
        $this->db->bind(':accion', $accion);
        $fila = $this->db->single();

        if ($fila && !empty($fila->bloqueado_hasta) && $fila->bloqueado_hasta > date('Y-m-d H:i:s')) {
            $minutosRestantes = max(1, (int)ceil((strtotime($fila->bloqueado_hasta) - time()) / 60));
            return "Demasiados intentos. Vuelve a intentarlo en {$minutosRestantes} minuto(s).";
        }
        return null;
    }

    /**
     * Registra un intento (fallido o de solicitud) y aplica bloqueo temporal si supera el máximo.
     */
    private function kioscoRegistrarIntento(string $ip, string $cedula, string $accion, int $maxIntentos, int $bloqueoMinutos): void
    {
        $this->db->query("
            INSERT INTO intentos_pin_kiosco (direccion_ip, cedula, accion, intentos, ultimo_intento)
            VALUES (:ip, :cedula, :accion, 1, NOW())
            ON DUPLICATE KEY UPDATE
                intentos       = intentos + 1,
                ultimo_intento = NOW()
        ");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':cedula', $cedula);
        $this->db->bind(':accion', $accion);
        $this->db->execute();

        $this->db->query("
            SELECT intentos FROM intentos_pin_kiosco
            WHERE direccion_ip = :ip AND cedula = :cedula AND accion = :accion
            LIMIT 1
        ");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':cedula', $cedula);
        $this->db->bind(':accion', $accion);
        $fila = $this->db->single();

        if ($fila && (int)$fila->intentos >= $maxIntentos) {
            $this->db->query("
                UPDATE intentos_pin_kiosco
                SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :min MINUTE)
                WHERE direccion_ip = :ip AND cedula = :cedula AND accion = :accion
            ");
            $this->db->bind(':min', $bloqueoMinutos);
            $this->db->bind(':ip', $ip);
            $this->db->bind(':cedula', $cedula);
            $this->db->bind(':accion', $accion);
            $this->db->execute();
        }
    }

    /**
     * Limpia el contador tras una autenticación exitosa.
     */
    private function kioscoLimpiarIntentos(string $ip, string $cedula, string $accion): void
    {
        $this->db->query("
            DELETE FROM intentos_pin_kiosco
            WHERE direccion_ip = :ip AND cedula = :cedula AND accion = :accion
        ");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':cedula', $cedula);
        $this->db->bind(':accion', $accion);
        $this->db->execute();
    }

    /**
     * Vista fullscreen del kiosco — sin layout maestro.
     */
    public function index(): void
    {
        // ⚔️ ARQUITECTURA STATELESS: El Kiosco no utiliza ni inicia sesiones PHP.
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        $urlToken = trim($_GET['token'] ?? '');
        $data = [
            'title'    => 'Kiosco de Asistencia — SGP',
            'urlToken' => $urlToken
        ];
        $this->view('kiosco/index', $data, false);
    }

    /**
     * Endpoint JSON — Consultar y Validar Pasante por Cédula (Bento UI Paso 1).
     *
     * POST /kiosco/consultarPasante
     * Body: cedula
     */
    public function consultarPasante(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $cedula = trim($_POST['cedula'] ?? '');
        $cedulaNumeros = preg_replace('/[^0-9]/', '', $cedula);

        if (!$cedulaNumeros) {
            echo json_encode(['success' => false, 'message' => 'Ingresa un número de cédula válido.']);
            exit;
        }

        // ── Validar que no es fin de semana ───────────────────────
        $diaSemana = (int)date('N'); // 1=Lun … 5=Vie, 6=Sáb, 7=Dom
        if ($diaSemana >= 6) {
            $nombreDia = $diaSemana === 6 ? 'sábado' : 'domingo';
            echo json_encode([
                'success' => false,
                'message' => "Hoy es {$nombreDia}. No se registra asistencia los fines de semana — solo días hábiles de lunes a viernes.",
            ]);
            exit;
        }

        // ── Validar que no es feriado NO laborable ─────────────────
        $hoyFecha = date('Y-m-d');
        $this->db->query("
            SELECT nombre, es_laborable FROM dias_feriados
            WHERE fecha = :hoy
            LIMIT 1
        ");
        $this->db->bind(':hoy', $hoyFecha);
        $feriadoHoy = $this->db->single();
        if ($feriadoHoy && (int)$feriadoHoy->es_laborable === 0) {
            echo json_encode([
                'success'    => false,
                'is_feriado' => true,
                'message'    => "Hoy es feriado: {$feriadoHoy->nombre}. La asistencia se registra automáticamente como Justificado.",
                'feriado'    => $feriadoHoy->nombre,
            ]);
            exit;
        }

        // ── Buscar pasante activo por cédula ───────────────────────
        $this->db->query("
            SELECT
                u.id,
                u.cedula,
                COALESCE(dp.nombres, u.correo)               AS nombres,
                COALESCE(dp.apellidos, '')                     AS apellidos,
                u.pin_asistencia,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar')  AS estado_pasantia,
                dpa.tipo_pasantia,
                dpa.fecha_inicio_pasantia,
                dpa.fecha_fin_estimada,
                COALESCE(NULLIF(dpa.horas_meta, 0), 1440)     AS horas_meta,
                d.nombre                                       AS departamento_nombre,
                COALESCE(i.nombre, 'No especificada')         AS institucion_nombre,
                pa.estado                                      AS periodo_estado,
                pa.nombre                                      AS periodo_nombre
            FROM   usuarios u
            LEFT  JOIN datos_personales dp  ON dp.usuario_id  = u.id
            LEFT  JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos    d   ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            LEFT  JOIN instituciones    i   ON i.id = dpa.institucion_id
            LEFT  JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE  REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :cedula_num
              AND  u.rol_id  = 3
              AND  LOWER(u.estado) = 'activo'
            ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':cedula_num', $cedulaNumeros);
        $pasante = $this->db->single();

        if (!$pasante) {
            // Verificar si es inactivo o no existe
            $this->db->query("
                SELECT COALESCE(dp.nombres, u.correo) AS nombres,
                       COALESCE(dp.apellidos, '') AS apellidos,
                       COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                WHERE REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :ced AND u.rol_id = 3
                ORDER BY dpa.id DESC LIMIT 1
            ");
            $this->db->bind(':ced', $cedulaNumeros);
            $inactivo = $this->db->single();
            if ($inactivo) {
                $nombre = trim(($inactivo->nombres ?? '') . ' ' . ($inactivo->apellidos ?? ''));
                echo json_encode([
                    'success' => false,
                    'message' => "Hola {$nombre}. Tu pasantía se encuentra en estado '{$inactivo->estado_pasantia}'. Comunícate con el Administrador."
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cédula no encontrada en el sistema. Verifica el número e intenta nuevamente.'
                ]);
            }
            exit;
        }

        // Validar estado de pasantía activo
        if (strtolower($pasante->estado_pasantia ?? '') !== 'activo') {
            echo json_encode([
                'success' => false,
                'message' => 'Tu pasantía aún no ha sido activada o asignada. Contacta al Administrador.',
            ]);
            exit;
        }

        // Validar período académico
        if ($pasante->periodo_estado && strtolower($pasante->periodo_estado) === 'cerrado') {
            echo json_encode([
                'success' => false,
                'message' => 'El período académico ha sido cerrado. Contacta al Administrador.',
            ]);
            exit;
        }

        $pasanteId = (int)$pasante->id;

        // ── Verificar si ya marcó hoy ──────────────────────────────
        $this->db->query("
            SELECT id, hora_registro FROM asistencias
            WHERE pasante_id = :pid AND fecha = :hoy
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':hoy', $hoyFecha);
        $asistHoy = $this->db->single();

        // ── Calcular progreso de horas ─────────────────────────────
        $this->db->query("
            SELECT 
                COUNT(*) * 8 AS horas_acumuladas,
                COUNT(*)     AS dias_asistidos
            FROM asistencias
            WHERE pasante_id = :pid AND estado != 'Anulado'
        ");
        $this->db->bind(':pid', $pasanteId);
        $prog = $this->db->single();

        $horasAcum = (int)($prog->horas_acumuladas ?? 0);
        $diasAsist = (int)($prog->dias_asistidos ?? 0);
        $horasMeta = (int)$pasante->horas_meta;
        $pct       = $horasMeta > 0 ? min(100, round(($horasAcum / $horasMeta) * 100)) : 0;
        $diasRest  = $horasMeta > 0 ? max(0, (int)ceil(($horasMeta - $horasAcum) / 8)) : 0;

        // Iniciales para el avatar
        $nomParts = explode(' ', trim($pasante->nombres));
        $apeParts = explode(' ', trim($pasante->apellidos));
        $iniciales = mb_strtoupper(mb_substr($nomParts[0] ?? '', 0, 1) . mb_substr($apeParts[0] ?? '', 0, 1));

        // ── Calcular Racha de Días Consecutivos (Gamificación 🔥) ──
        $racha = 0;
        try {
            $this->db->query("
                SELECT DISTINCT fecha
                FROM asistencias
                WHERE pasante_id = :pid
                  AND estado IN ('Presente', 'Justificado')
                  AND fecha <= :hoy
                ORDER BY fecha DESC
            ");
            $this->db->bind(':pid', $pasanteId);
            $this->db->bind(':hoy', $hoyFecha);
            $asistList = $this->db->resultSet();
            $fechasPresentes = array_map(function($row) { return $row->fecha; }, $asistList);

            $diasRacha = 0;
            $cursor = new DateTime($hoyFecha);
            if (!in_array($hoyFecha, $fechasPresentes)) {
                do {
                    $cursor->modify('-1 day');
                } while ((int)$cursor->format('N') >= 6);
            }
            while (in_array($cursor->format('Y-m-d'), $fechasPresentes)) {
                $diasRacha++;
                do {
                    $cursor->modify('-1 day');
                } while ((int)$cursor->format('N') >= 6);
            }
            $racha = $diasRacha;
        } catch (\Throwable $e) {
            $racha = 0;
        }

        echo json_encode([
            'success'      => true,
            'ya_marco_hoy' => !empty($asistHoy),
            'hora_hoy'     => !empty($asistHoy->hora_registro) ? date('h:i A', strtotime($asistHoy->hora_registro)) : null,
            'racha'        => $racha,
            'pasante'      => [
                'id'               => $pasanteId,
                'cedula'           => $pasante->cedula,
                'nombres'          => $pasante->nombres,
                'apellidos'        => $pasante->apellidos,
                'nombre_completo'  => trim($pasante->nombres . ' ' . $pasante->apellidos),
                'iniciales'        => $iniciales,
                'departamento'     => $pasante->departamento_nombre ?? 'Sin Asignar',
                'institucion'      => $pasante->institucion_nombre,
                'tipo_pasantia'    => $pasante->tipo_pasantia ?? 'Pasantía Profesional',
                'horas_acumuladas' => $horasAcum,
                'horas_meta'       => $horasMeta,
                'porcentaje'       => $pct,
                'dias_asistidos'   => $diasAsist,
                'dias_restantes'   => $diasRest,
                'tiene_pin'        => !empty($pasante->pin_asistencia),
                'racha'            => $racha,
            ]
        ]);
        exit;
    }

    /**
     * Endpoint JSON — Marcar Asistencia (Vía PIN o Vía QR).
     *
     * POST /kiosco/marcar
     * Body: cedula, metodo ('PIN'|'QR'), pin_asistencia, qr_token
     *
     * Responde con:
     *   { success: bool, message: string, pasante?: { nombres, apellidos, departamento } }
     */
    public function marcar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $cedula  = trim($_POST['cedula'] ?? '');
        $metodo  = strtoupper(trim($_POST['metodo'] ?? 'PIN')); // 'PIN' o 'QR'
        $pin     = trim($_POST['pin_asistencia'] ?? '');
        $qrToken = trim($_POST['qr_token'] ?? '');

        // ── Normalizar cédula ──────────────────────────────────────
        $cedulaNumeros = preg_replace('/[^0-9]/', '', $cedula);
        if (!$cedulaNumeros) {
            echo json_encode(['success' => false, 'message' => 'Número de cédula inválido.']);
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // ── 1. Buscar pasante activo por cédula (Identificar usuario antes de auditoría) ──
        $this->db->query("
            SELECT
                u.id,
                u.cedula,
                COALESCE(dp.nombres, u.correo)  AS nombres,
                COALESCE(dp.apellidos, '')        AS apellidos,
                u.pin_asistencia,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
                d.nombre AS departamento_nombre
            FROM   usuarios u
            LEFT  JOIN datos_personales dp  ON dp.usuario_id  = u.id
            LEFT  JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos    d   ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            WHERE  REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :cedula_num
              AND  u.rol_id  = 3
              AND  LOWER(u.estado) = 'activo'
            ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':cedula_num', $cedulaNumeros);
        $pasante = $this->db->single();

        if (!$pasante) {
            echo json_encode(['success' => false, 'message' => 'Cédula no encontrada en el sistema.']);
            exit;
        }

        if (strtolower($pasante->estado_pasantia ?? '') !== 'activo') {
            echo json_encode(['success' => false, 'message' => 'Tu pasantía no está activa. Contacta al Administrador.']);
            exit;
        }

        if (empty($pasante->departamento_nombre)) {
            echo json_encode(['success' => false, 'message' => 'No tienes departamento asignado. Contacta al Administrador.']);
            exit;
        }

        $pasanteId = (int)$pasante->id;
        $pasanteNombre = trim($pasante->nombres . ' ' . $pasante->apellidos);

        // ── 2. Validar parámetros según método ────────────────────────
        if ($metodo === 'QR') {
            if (empty($qrToken)) {
                echo json_encode(['success' => false, 'message' => 'No se detectó el token del código QR. Intenta escanear nuevamente.']);
                exit;
            }

            // Consultar token oficial activo en la tabla configuracion
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'kiosco_qr_token' LIMIT 1");
            $cfgToken = $this->db->single();
            $tokenOficial = trim($cfgToken->valor ?? 'ISPEB-ASIST-2026-HQ');

            // Si el código escaneado es una URL completa, extraer el parámetro token
            if (strpos($qrToken, 'token=') !== false) {
                $queryStr = parse_url($qrToken, PHP_URL_QUERY);
                if ($queryStr) {
                    parse_str($queryStr, $params);
                    if (!empty($params['token'])) {
                        $qrToken = trim($params['token']);
                    }
                }
                // Respaldo de extracción vía regex
                if ($qrToken !== $tokenOficial && preg_match('/[?&]token=([^&#]+)/', $qrToken, $matches)) {
                    $qrToken = trim(urldecode($matches[1]));
                }
            }

            if (!hash_equals($tokenOficial, trim($qrToken))) {
                // Registrar intento con QR inválido en bitácora
                try {
                    $this->db->query("
                        INSERT INTO bitacora
                            (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                        VALUES
                            (:uid, 'BLOQUEO_QR_INVALIDO', 'asistencias', :reg_id, :ip, :ua, :detalles)
                    ");
                    $this->db->bind(':uid',     $pasanteId);
                    $this->db->bind(':reg_id',  $pasanteId);
                    $this->db->bind(':ip',      $ip);
                    $this->db->bind(':ua',      $_SERVER['HTTP_USER_AGENT'] ?? null);
                    $this->db->bind(':detalles', json_encode([
                        'motivo'       => 'Intento de marcaje con código QR no reconocido o adulterado',
                        'pasante'      => $pasanteNombre,
                        'cedula'       => $cedula,
                        'departamento' => $pasante->departamento_nombre
                    ], JSON_UNESCAPED_UNICODE));
                    $this->db->execute();
                } catch (\Throwable $e) {}

                echo json_encode(['success' => false, 'message' => 'Código QR inválido o no reconocido. Escanea el código oficial en la sede.']);
                exit;
            }

            // ── Dispositivo de Confianza / Anti-Buddy Punching (Anti-Suplantación) ──
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'kiosco_vincular_dispositivo' LIMIT 1");
            $cfgVinc = $this->db->single();
            $verificarDispositivo = ($cfgVinc ? (int)$cfgVinc->valor : 1) === 1;

            if ($verificarDispositivo) {
                $deviceUuid = trim($_POST['device_uuid'] ?? '');
                $deviceName = trim($_POST['device_name'] ?? 'Dispositivo Móvil');

                if (empty($deviceUuid)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Identificador de dispositivo no detectado. Por favor, asegúrate de habilitar almacenamiento local o recargar la página.'
                    ]);
                    exit;
                }

                // A. Regla Anti-Buddy Punching: ¿Este dispositivo ya está vinculado a OTRO pasante?
                $this->db->query("
                    SELECT d.usuario_id, dp.nombres, dp.apellidos, u.cedula
                    FROM pasante_dispositivos d
                    JOIN usuarios u ON u.id = d.usuario_id
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE d.device_uuid = :dev_uuid AND d.usuario_id != :uid AND d.estado = 'activo'
                    LIMIT 1
                ");
                $this->db->bind(':dev_uuid', $deviceUuid);
                $this->db->bind(':uid',      $pasanteId);
                $otroPasante = $this->db->single();

                if ($otroPasante) {
                    $otroNombre = trim(($otroPasante->nombres ?? '') . ' ' . ($otroPasante->apellidos ?? ''));
                    try {
                        $this->db->query("
                            INSERT INTO bitacora
                                (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                            VALUES
                                (:uid, 'BLOQUEO_SUPLANTACION_DISPOSITIVO', 'asistencias', :reg_id, :ip, :ua, :detalles)
                        ");
                        $this->db->bind(':uid',     $pasanteId);
                        $this->db->bind(':reg_id',  $pasanteId);
                        $this->db->bind(':ip',      $ip);
                        $this->db->bind(':ua',      $_SERVER['HTTP_USER_AGENT'] ?? null);
                        $this->db->bind(':detalles', json_encode([
                            'motivo'           => 'Intento de marcar con un dispositivo ya registrado a otro pasante (Buddy Punching)',
                            'pasante_intento'  => $pasanteNombre,
                            'cedula_intento'   => $cedula,
                            'dueno_dispositivo'=> $otroNombre ?: $otroPasante->cedula,
                            'device_uuid'      => $deviceUuid
                        ], JSON_UNESCAPED_UNICODE));
                        $this->db->execute();
                    } catch (\Throwable $e) {}

                    echo json_encode([
                        'success' => false,
                        'message' => '🚫 Dispositivo compartido no permitido: Este teléfono ya está registrado por otro pasante. Cada pasante debe marcar asistencia exclusivamente desde su propio equipo personal.'
                    ]);
                    exit;
                }

                // B. Verificar si este pasante ya tiene un dispositivo registrado
                $this->db->query("
                    SELECT id, device_uuid, nombre_dispositivo, estado
                    FROM pasante_dispositivos
                    WHERE usuario_id = :uid
                    ORDER BY id ASC
                    LIMIT 1
                ");
                $this->db->bind(':uid', $pasanteId);
                $dispRegistrado = $this->db->single();

                if ($dispRegistrado) {
                    if ($dispRegistrado->estado === 'bloqueado') {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Tu dispositivo de marcaje se encuentra suspendido. Contacta a tu tutor o administrador.'
                        ]);
                        exit;
                    }

                    if ($dispRegistrado->device_uuid !== $deviceUuid) {
                        try {
                            $this->db->query("
                                INSERT INTO bitacora
                                    (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                                VALUES
                                    (:uid, 'BLOQUEO_DISPOSITIVO_NO_AUTORIZADO', 'asistencias', :reg_id, :ip, :ua, :detalles)
                            ");
                            $this->db->bind(':uid',     $pasanteId);
                            $this->db->bind(':reg_id',  $pasanteId);
                            $this->db->bind(':ip',      $ip);
                            $this->db->bind(':ua',      $_SERVER['HTTP_USER_AGENT'] ?? null);
                            $this->db->bind(':detalles', json_encode([
                                'motivo'              => 'Intento de marcar desde un teléfono no autorizado para este pasante',
                                'pasante'             => $pasanteNombre,
                                'dispositivo_esperado'=> $dispRegistrado->nombre_dispositivo,
                                'device_recibido'     => $deviceUuid
                            ], JSON_UNESCAPED_UNICODE));
                            $this->db->execute();
                        } catch (\Throwable $e) {}

                        echo json_encode([
                            'success' => false,
                            'message' => "🔒 Dispositivo no autorizado: Tu cuenta está vinculada a tu teléfono registrado (" . htmlspecialchars($dispRegistrado->nombre_dispositivo) . "). Si cambiaste de equipo, solicita a tu tutor o administrador que desvincule tu teléfono anterior."
                        ]);
                        exit;
                    }

                    // Actualizar último uso
                    $this->db->query("
                        UPDATE pasante_dispositivos
                        SET ultimo_uso_at = NOW(), ip_registro = :ip
                        WHERE id = :id
                    ");
                    $this->db->bind(':ip', $ip);
                    $this->db->bind(':id', $dispRegistrado->id);
                    $this->db->execute();

                } else {
                    // Primer uso: Auto-vincular este dispositivo al pasante
                    $this->db->query("
                        INSERT INTO pasante_dispositivos
                            (usuario_id, device_uuid, nombre_dispositivo, user_agent, ip_registro, estado, creado_at, ultimo_uso_at)
                        VALUES
                            (:uid, :dev_uuid, :dev_name, :ua, :ip, 'activo', NOW(), NOW())
                    ");
                    $this->db->bind(':uid',      $pasanteId);
                    $this->db->bind(':dev_uuid', $deviceUuid);
                    $this->db->bind(':dev_name', $deviceName ?: 'Teléfono Móvil');
                    $this->db->bind(':ua',       $_SERVER['HTTP_USER_AGENT'] ?? null);
                    $this->db->bind(':ip',       $ip);
                    $this->db->execute();

                    try {
                        $this->db->query("
                            INSERT INTO bitacora
                                (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                            VALUES
                                (:uid, 'VINCULAR_DISPOSITIVO_CONFIANZA', 'pasante_dispositivos', :reg_id, :ip, :ua, :detalles)
                        ");
                        $this->db->bind(':uid',     $pasanteId);
                        $this->db->bind(':reg_id',  $pasanteId);
                        $this->db->bind(':ip',      $ip);
                        $this->db->bind(':ua',      $_SERVER['HTTP_USER_AGENT'] ?? null);
                        $this->db->bind(':detalles', json_encode([
                            'motivo'      => 'Dispositivo de confianza vinculado exitosamente en el primer marcaje QR',
                            'dispositivo' => $deviceName,
                            'pasante'     => $pasanteNombre
                        ], JSON_UNESCAPED_UNICODE));
                        $this->db->execute();
                    } catch (\Throwable $e) {}
                }
            }

            $metodoRegistro = 'Kiosco QR';
        } else {
            // Método PIN tradicional
            if (!$pin || !preg_match('/^[0-9]{4}$/', $pin)) {
                echo json_encode(['success' => false, 'message' => 'El PIN debe contener 4 dígitos numéricos.']);
                exit;
            }

            // Límite de intentos de PIN — bloqueo temporal
            $bloqueoMsg = $this->kioscoBloqueado($ip, $cedulaNumeros, 'marcar');
            if ($bloqueoMsg !== null) {
                echo json_encode(['success' => false, 'message' => $bloqueoMsg]);
                exit;
            }

            $metodoRegistro = 'Kiosco PIN';
        }

        // ── 3. Validar Geocerca GPS (Anti-Marcaje Remoto / Presencialidad) ──
        $this->db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('geocerca_activa', 'geocerca_lat', 'geocerca_lng', 'geocerca_radio')");
        $cfgRows = $this->db->resultSet();
        $cfgGeo = [];
        foreach ($cfgRows as $r) {
            $cfgGeo[$r->clave] = $r->valor;
        }

        $geoActiva = (isset($cfgGeo['geocerca_activa']) && (int)$cfgGeo['geocerca_activa'] === 1);
        if ($geoActiva) {
            $latUser = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
            $lngUser = isset($_POST['lng']) && $_POST['lng'] !== '' ? floatval($_POST['lng']) : null;

            if ($latUser === null || $lngUser === null) {
                echo json_encode([
                    'success'      => false,
                    'requiere_gps' => true,
                    'message'      => '📍 Se requiere la ubicación GPS de tu dispositivo para verificar que te encuentras en las instalaciones del ISPEB.'
                ]);
                exit;
            }

            $centroLat = floatval($cfgGeo['geocerca_lat'] ?? 8.128700);
            $centroLng = floatval($cfgGeo['geocerca_lng'] ?? -63.543500);
            $radioPerm = floatval($cfgGeo['geocerca_radio'] ?? 200); // metros

            // Fórmula de Haversine para cálculo de distancia exacta
            $earthRadius = 6371000;
            $dLat = deg2rad($latUser - $centroLat);
            $dLon = deg2rad($lngUser - $centroLng);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($centroLat)) * cos(deg2rad($latUser)) *
                 sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distanciaMts = $earthRadius * $c;

            if ($distanciaMts > $radioPerm) {
                $distanciaFmt = round($distanciaMts);

                // 🛡️ AUDITORÍA BITÁCORA: Registrar intento de marcaje bloqueado por distancia GPS
                try {
                    $this->db->query("
                        INSERT INTO bitacora
                            (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                        VALUES
                            (:uid, 'BLOQUEO_ASISTENCIA_UBICACION', 'asistencias', :reg_id, :ip, :ua, :detalles)
                    ");
                    $this->db->bind(':uid',     $pasanteId);
                    $this->db->bind(':reg_id',  $pasanteId);
                    $this->db->bind(':ip',      $ip);
                    $this->db->bind(':ua',      $_SERVER['HTTP_USER_AGENT'] ?? null);
                    $this->db->bind(':detalles', json_encode([
                        'motivo'           => 'Marcaje bloqueado: Fuera del perímetro de la sede ISPEB',
                        'pasante'          => $pasanteNombre,
                        'cedula'           => $cedula,
                        'departamento'     => $pasante->departamento_nombre,
                        'distancia_mts'    => $distanciaFmt,
                        'radio_maximo_mts' => (int)$radioPerm,
                        'metodo'           => $metodoRegistro,
                        'coordenadas'      => ['lat' => round($latUser, 6), 'lng' => round($lngUser, 6)]
                    ], JSON_UNESCAPED_UNICODE));
                    $this->db->execute();
                } catch (\Throwable $e) {
                    error_log('[SGP-KIOSCO] Error registrando bitacora bloqueo ubicacion: ' . $e->getMessage());
                }

                echo json_encode([
                    'success'         => false,
                    'fuera_geocerca'  => true,
                    'distancia'       => $distanciaFmt,
                    'radio_permitido' => (int)$radioPerm,
                    'message'         => "📍 Te encuentras fuera del perímetro permitido del ISPEB (a {$distanciaFmt}m de la sede, máximo permitido: " . (int)$radioPerm . "m). Debes estar físicamente en la institución para registrar asistencia."
                ]);
                exit;
            }
        }

        // ── 4. Validar que no es fin de semana ─────────────────────
        $diaSemana = (int)date('N'); // 1=Lun … 5=Vie, 6=Sáb, 7=Dom
        if ($diaSemana >= 6) {
            $nombreDia = $diaSemana === 6 ? 'sábado' : 'domingo';
            echo json_encode([
                'success' => false,
                'message' => "Hoy es {$nombreDia}. No se registra asistencia los fines de semana — solo días hábiles de lunes a viernes.",
            ]);
            exit;
        }

        // ── 5. Validar feriado no laborable ────────────────────────
        $hoyFecha = date('Y-m-d');
        $this->db->query("
            SELECT nombre, es_laborable FROM dias_feriados
            WHERE fecha = :hoy
            LIMIT 1
        ");
        $this->db->bind(':hoy', $hoyFecha);
        $feriadoHoy = $this->db->single();
        if ($feriadoHoy && (int)$feriadoHoy->es_laborable === 0) {
            echo json_encode([
                'success'    => false,
                'is_feriado' => true,
                'message'    => "Hoy es feriado: {$feriadoHoy->nombre}. La asistencia se registrará automáticamente como Justificado.",
            ]);
            exit;
        }

        // ── 6. Si el método es PIN, verificar hash BCRYPT ──────────
        if ($metodo === 'PIN') {
            if (empty($pasante->pin_asistencia)) {
                echo json_encode(['success' => false, 'message' => 'No tienes un PIN configurado. Contacta al Administrador.']);
                exit;
            }
            if (!password_verify($pin, $pasante->pin_asistencia)) {
                $this->kioscoRegistrarIntento($ip, $cedulaNumeros, 'marcar', 5, 15);
                echo json_encode(['success' => false, 'message' => 'PIN incorrecto. Inténtalo de nuevo.']);
                exit;
            }
            $this->kioscoLimpiarIntentos($ip, $cedulaNumeros, 'marcar');
        }

        // ── 7. Validar período académico y fechas ───────────────────
        $this->db->query("
            SELECT dp.fecha_inicio_pasantia, dp.fecha_fin_estimada,
                   pa.fecha_fin AS periodo_fecha_fin, pa.nombre AS periodo_nombre, pa.estado AS periodo_estado
            FROM datos_pasante dp
            LEFT JOIN periodos_academicos pa ON pa.id = dp.periodo_id
            WHERE dp.usuario_id = :pid
            ORDER BY FIELD(COALESCE(dp.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dp.id DESC
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $fechas = $this->db->single();
        if ($fechas) {
            if ($fechas->fecha_inicio_pasantia && $hoyFecha < $fechas->fecha_inicio_pasantia) {
                echo json_encode(['success' => false, 'message' => 'Tu pasantía aún no ha iniciado. Fecha de inicio: ' . date('d/m/Y', strtotime($fechas->fecha_inicio_pasantia))]);
                exit;
            }
            if ($fechas->fecha_fin_estimada && $hoyFecha > $fechas->fecha_fin_estimada) {
                echo json_encode(['success' => false, 'message' => 'Tu pasantía ha finalizado. Contacta al Administrador.']);
                exit;
            }
            if ($fechas->periodo_estado && strtolower($fechas->periodo_estado) === 'cerrado') {
                echo json_encode(['success' => false, 'message' => 'El período académico ha sido cerrado. Contacta al Administrador.']);
                exit;
            }
        }

        // ── 8. Verificar si ya marcó hoy (Doble marcaje) ────────────
        $this->db->query("
            SELECT id, hora_registro FROM asistencias
            WHERE pasante_id = :pid AND fecha = :hoy
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':hoy', $hoyFecha);
        $asistHoy = $this->db->single();

        if ($asistHoy) {
            // 🛡️ AUDITORÍA BITÁCORA: Registrar intento de marcaje duplicado bloqueado
            try {
                $this->db->query("
                    INSERT INTO bitacora
                        (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                    VALUES
                        (:uid, 'BLOQUEO_ASISTENCIA_DUPLICADA', 'asistencias', :asistencia_id, :ip, :ua, :detalles)
                ");
                $this->db->bind(':uid',           $pasanteId);
                $this->db->bind(':asistencia_id', (int)$asistHoy->id);
                $this->db->bind(':ip',            $ip);
                $this->db->bind(':ua',            $_SERVER['HTTP_USER_AGENT'] ?? null);
                $this->db->bind(':detalles', json_encode([
                    'motivo'        => 'Marcaje bloqueado: Intento de registro duplicado en la misma fecha',
                    'pasante'       => $pasanteNombre,
                    'cedula'        => $cedula,
                    'departamento'  => $pasante->departamento_nombre,
                    'hora_original' => $asistHoy->hora_registro ?? 'N/A',
                    'hora_intento'  => date('H:i:s'),
                    'metodo'        => $metodoRegistro
                ], JSON_UNESCAPED_UNICODE));
                $this->db->execute();
            } catch (\Throwable $e) {
                error_log('[SGP-KIOSCO] Error registrando bitacora duplicado: ' . $e->getMessage());
            }

            echo json_encode([
                'success'     => false,
                'ya_registro' => true,
                'message'     => '✅ Ya registraste tu asistencia hoy. ¡Hasta mañana!',
            ]);
            exit;
        }

        // ── Verificar límite de horas meta ─────────────────────────
        $this->db->query("
            SELECT COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                   COALESCE((SELECT COUNT(*) * 8
                             FROM asistencias a2
                             WHERE a2.pasante_id  = :pid2
                               AND a2.estado != 'Anulado'
                               AND a2.fecha BETWEEN COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)
                                                AND COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin, CURDATE())), 0) AS horas_acumuladas
            FROM datos_pasante dpa
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE dpa.usuario_id = :pid
            ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':pid',  $pasanteId);
        $this->db->bind(':pid2', $pasanteId);
        $limitInfo = $this->db->single();
        if ($limitInfo && (float)$limitInfo->horas_acumuladas >= (float)$limitInfo->horas_meta) {
            echo json_encode([
                'success' => false,
                'message' => '✅ Has completado tus ' . (int)$limitInfo->horas_meta . ' horas de pasantía.',
            ]);
            exit;
        }

        // ── Insertar Asistencia ────────────────────────────────────
        $horaActual = date('H:i:s');
        $esRetardo  = ($horaActual > '09:00:00') ? 1 : 0;

        $this->db->query("
            INSERT INTO asistencias
                (pasante_id, fecha, hora_registro, estado, metodo, es_retardo, es_auto_fill)
            VALUES
                (:pid, :fecha, :hora, 'Presente', :metodo, :retardo, 0)
        ");
        $this->db->bind(':pid',     $pasanteId);
        $this->db->bind(':fecha',   $hoyFecha);
        $this->db->bind(':hora',    $horaActual);
        $this->db->bind(':metodo',  $metodoRegistro);
        $this->db->bind(':retardo', $esRetardo);
        $ok = $this->db->execute();

        if ($ok) {
            $asistenciaId = (int)$this->db->lastInsertId();

            // ── Bitácora ───────────────────────────────────────────
            try {
                $this->db->query("
                    INSERT INTO bitacora
                        (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                    VALUES
                        (:uid, 'MARCAR_ASISTENCIA_KIOSCO', 'asistencias', :asistencia_id, :ip, :ua, :detalles)
                ");
                $this->db->bind(':uid',           $pasanteId);
                $this->db->bind(':asistencia_id', $asistenciaId);
                $this->db->bind(':ip',            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');
                $this->db->bind(':ua',            $_SERVER['HTTP_USER_AGENT'] ?? null);
                $this->db->bind(':detalles', json_encode([
                    'pasante' => trim($pasante->nombres . ' ' . $pasante->apellidos),
                    'cedula'  => $cedula,
                    'hora'    => $horaActual,
                    'retardo' => (bool)$esRetardo,
                    'metodo'  => $metodoRegistro,
                ], JSON_UNESCAPED_UNICODE));
                $this->db->execute();
            } catch (\Throwable $e) {
                error_log('[SGP-KIOSCO] bitacora log error: ' . $e->getMessage());
            }

            // ── Calcular Racha de Días Consecutivos (Gamificación 🔥) ──
            $racha = 1;
            try {
                $this->db->query("
                    SELECT DISTINCT fecha
                    FROM asistencias
                    WHERE pasante_id = :pid
                      AND estado IN ('Presente', 'Justificado')
                      AND fecha <= :hoy
                    ORDER BY fecha DESC
                ");
                $this->db->bind(':pid', $pasanteId);
                $this->db->bind(':hoy', $hoyFecha);
                $asistList = $this->db->resultSet();
                $fechasPresentes = array_map(function($row) { return $row->fecha; }, $asistList);

                $diasRacha = 0;
                $cursor = new DateTime($hoyFecha);
                while (in_array($cursor->format('Y-m-d'), $fechasPresentes)) {
                    $diasRacha++;
                    // Retroceder al día hábil anterior (saltar sábado y domingo)
                    do {
                        $cursor->modify('-1 day');
                    } while ((int)$cursor->format('N') >= 6);
                }
                $racha = max(1, $diasRacha);
            } catch (\Throwable $e) {
                error_log('[SGP-KIOSCO] racha calc error: ' . $e->getMessage());
                $racha = 1;
            }

            // ── Notificaciones: Pasante, Tutor Asignado y Administradores ──
            try {
                require_once APPROOT . '/models/NotificationModel.php';
                require_once APPROOT . '/lib/MailService.php';

                $notifModel = new NotificationModel($this->db);
                $horaFmt    = date('h:i A');
                $nombrePas  = trim($pasante->nombres . ' ' . $pasante->apellidos);
                $deptoPas   = $pasante->departamento_nombre ?? 'Sin asignar';
                $metodoStr  = ($metodo === 'QR') ? 'Código QR' : 'PIN';
                $retardoTxt = $esRetardo ? ' (con retardo)' : '';

                // 1. Notificación al Pasante
                $notifModel->create(
                    $pasanteId,
                    'asistencia_registrada',
                    $esRetardo ? 'Asistencia registrada con retardo' : 'Asistencia registrada',
                    "Asistencia registrada a las {$horaFmt} vía {$metodoRegistro}." . ($esRetardo ? ' Recuerda llegar antes de las 9:00 AM.' : ''),
                    URLROOT . '/pasante/asistencia'
                );

                // Control de usuarios ya notificados en campana para evitar duplicados
                $notificadosCampana = [(int)$pasanteId => true];

                // 2. Notificación al Tutor Asignado (si tiene)
                $this->db->query("SELECT tutor_id FROM datos_pasante WHERE usuario_id = :pid ORDER BY FIELD(COALESCE(estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, id DESC LIMIT 1");
                $this->db->bind(':pid', $pasanteId);
                $dpaTutor = $this->db->single();
                if ($dpaTutor && !empty($dpaTutor->tutor_id)) {
                    $tutorId = (int)$dpaTutor->tutor_id;
                    $notifModel->create(
                        $tutorId,
                        'asistencia_pasante',
                        "Asistencia de Pasante: {$nombrePas}",
                        "El pasante {$nombrePas} marcó asistencia hoy a las {$horaFmt} vía {$metodoStr}{$retardoTxt}.",
                        URLROOT . '/asistencias'
                    );
                    $notificadosCampana[$tutorId] = true;
                }

                // 3. Notificación a los Administradores Activos (evitando duplicar si el admin ya es tutor)
                $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND LOWER(estado) = 'activo'");
                $adminsList = $this->db->resultSet() ?: [];
                foreach ($adminsList as $adm) {
                    $admId = (int)$adm->id;
                    if (isset($notificadosCampana[$admId])) {
                        continue; // Ya notificado como tutor, no duplicar campana
                    }
                    $notificadosCampana[$admId] = true;
                    $notifModel->create(
                        $admId,
                        'asistencia_pasante',
                        "Asistencia ({$metodoStr}): {$nombrePas}",
                        "El pasante {$nombrePas} ({$deptoPas}) marcó asistencia a las {$horaFmt} vía {$metodoStr}{$retardoTxt}.",
                        URLROOT . '/asistencias'
                    );
                }

                // 4. Despacho Oficial por Correo Electrónico (Con desduplicación garantizada)
                MailService::notifyAttendance($pasanteId, $metodoStr . $retardoTxt, $horaFmt, $hoyFecha);

            } catch (\Throwable $e) {
                error_log('[SGP-KIOSCO] notif error: ' . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'metodo'  => $metodo,
                'racha'   => $racha,
                'message' => $esRetardo
                    ? '¡Registro exitoso! Recuerda llegar antes de las 9:00 AM.'
                    : '¡Asistencia registrada exitosamente!',
                'hora'    => date('h:i A'),
                'retardo' => (bool)$esRetardo,
                'pasante' => [
                    'nombres'      => $pasante->nombres,
                    'apellidos'    => $pasante->apellidos,
                    'departamento' => $pasante->departamento_nombre ?? 'Sin asignar',
                ],
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el registro. Intenta de nuevo.']);
        }
        exit;
    }

    /**
     * Endpoint JSON — Consultar si hoy (o una fecha dada) es feriado.
     * GET /kiosco/esFeriado
     * GET /kiosco/esFeriado?fecha=YYYY-MM-DD
     *
     * Responde: { is_feriado: bool, nombre: string|null, fecha: string }
     */
    public function esFeriado(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $this->db->query("
            SELECT nombre, es_laborable FROM dias_feriados
            WHERE fecha = :fecha
            LIMIT 1
        ");
        $this->db->bind(':fecha', $fecha);
        $feriado = $this->db->single();

        echo json_encode([
            'is_feriado'   => (bool)$feriado,
            'nombre'       => $feriado ? $feriado->nombre : null,
            'es_laborable' => $feriado ? (bool)((int)$feriado->es_laborable === 1) : false,
            'fecha'        => $fecha,
        ]);
        exit;
    }

    /**
     * Endpoint JSON — Solicitar reseteo de PIN
     * POST /kiosco/solicitarResetPin
     * Body: cedula
     */
    public function solicitarResetPin(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $cedula = trim($_POST['cedula'] ?? '');

        if (!$cedula) {
            echo json_encode(['success' => false, 'message' => 'La cédula es obligatoria.']);
            exit;
        }

        // FIX: Normalizar cédula (solo dígitos) para tolerar prefijos V-, E-, etc.
        $cedulaNumerosPinReset = preg_replace('/[^0-9]/', '', $cedula);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // ── [FIX-N-5] Límite de solicitudes — evita inundar de notificaciones
        // a todo el staff repitiendo la misma cédula en bucle ──
        $bloqueoMsg = $this->kioscoBloqueado($ip, $cedulaNumerosPinReset, 'reset_pin');
        if ($bloqueoMsg !== null) {
            echo json_encode(['success' => false, 'message' => $bloqueoMsg]);
            exit;
        }

        // Buscar pasante interactuando con 3NF
        // FIX: LEFT JOIN para tolerar pasantes sin datos_personales completos
        $this->db->query("
            SELECT u.id,
                   COALESCE(dp.nombres, u.correo) AS nombres,
                   COALESCE(dp.apellidos, '')      AS apellidos,
                   dpa.tutor_id
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            WHERE REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :cedula_num
              AND u.rol_id = 3
              AND LOWER(u.estado) = 'activo'
            ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':cedula_num', $cedulaNumerosPinReset);
        $pasante = $this->db->single();

        if (!$pasante) {
            // Verificar si es finalizado/inactivo
            $this->db->query("
                SELECT COALESCE(dp.nombres, u.correo) AS nombres,
                       COALESCE(dp.apellidos, '') AS apellidos,
                       COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                WHERE REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :ced AND u.rol_id = 3
                ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
                LIMIT 1
            ");
            $this->db->bind(':ced', $cedulaNumerosPinReset);
            $inactivo = $this->db->single();

            if ($inactivo && $inactivo->estado_pasantia === 'Finalizado') {
                $nombre = trim(($inactivo->nombres ?? '') . ' ' . ($inactivo->apellidos ?? ''));
                echo json_encode([
                    'success' => false,
                    'tipo'    => 'finalizado',
                    'nombre'  => $nombre,
                    'message' => "¡Felicidades, {$nombre}! Tu pasantía ha sido completada exitosamente. Ya no necesitas el PIN de asistencia. Si requieres algún documento, comunícate con el Administrador de tu departamento.",
                ]);
            } elseif ($inactivo) {
                $nombre = trim(($inactivo->nombres ?? '') . ' ' . ($inactivo->apellidos ?? ''));
                echo json_encode([
                    'success' => false,
                    'tipo'    => 'inactivo',
                    'nombre'  => $nombre,
                    'message' => "{$nombre}, tu cuenta se encuentra inactiva. Comunícate con el Administrador para verificar tu estado.",
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cédula no encontrada en el sistema. Verifica el número e intenta de nuevo.']);
            }
            exit;
        }

        // Insertar notificaciones para Admin y Tutor (si tiene) sin duplicados
        require_once APPROOT . '/models/NotificationModel.php';
        require_once APPROOT . '/lib/MailService.php';

        $this->notificationModel = new NotificationModel($this->db);
        $titulo = "Solicitud de reseteo de PIN";
        $mensaje = "El pasante {$pasante->nombres} {$pasante->apellidos} (V-{$cedula}) ha olvidado su PIN y solicita un reseteo.";
        $url = URLROOT . "/configuracion#restablecer-pin";

        $notificadosCampana = [];

        // Tutor (si existe)
        if (!empty($pasante->tutor_id)) {
            $tutorId = (int)$pasante->tutor_id;
            $this->notificationModel->createWithRef(
                $tutorId, 'solicitud_pin', $titulo, $mensaje, $url,
                $pasante->id
            );
            $notificadosCampana[$tutorId] = true;
        }

        // Admin (enviamos a todos los admins activos que no sean el tutor ya notificado)
        $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
        $admins = $this->db->resultSet() ?: [];
        foreach ($admins as $admin) {
            $admId = (int)$admin->id;
            if (isset($notificadosCampana[$admId])) {
                continue; // Ya notificado como tutor, no duplicar campana
            }
            $notificadosCampana[$admId] = true;
            $this->notificationModel->createWithRef(
                $admId, 'solicitud_pin', $titulo, $mensaje, $url,
                $pasante->id
            );
        }

        // Despacho Oficial por Correo Electrónico
        try {
            MailService::notifyPinReset((int)$pasante->id, $cedula);
        } catch (\Throwable $e) {
            error_log('[SGP-KIOSCO] notifyPinReset error: ' . $e->getMessage());
        }

        // [FIX-N-5] Contabilizar esta solicitud — bloquea tras 3 solicitudes por 30 min
        $this->kioscoRegistrarIntento($ip, $cedulaNumerosPinReset, 'reset_pin', 3, 30);

        echo json_encode(['success' => true, 'message' => 'Solicitud enviada a tu administrador y tutor.']);
        exit;
    }
}
