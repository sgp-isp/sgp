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
        $config  = require '../app/config/config.php';
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

        $data = ['title' => 'Kiosco de Asistencia — SGP'];
        $this->view('kiosco/index', $data, false);
    }

    /**
     * Endpoint JSON — Marcar Asistencia.
     *
     * POST /kiosco/marcar
     * Body: cedula, pin_asistencia
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

        $cedula = trim($_POST['cedula']        ?? '');
        $pin    = trim($_POST['pin_asistencia'] ?? '');

        // ── Validación de formato ──────────────────────────────────
        if (!$cedula || !$pin) {
            echo json_encode(['success' => false, 'message' => 'Cédula y PIN son obligatorios.']);
            exit;
        }

        if (!preg_match('/^[0-9]{4}$/', $pin)) {
            echo json_encode(['success' => false, 'message' => 'El PIN debe tener 4 dígitos numéricos.']);
            exit;
        }

        // ── Normalizar cédula: extraer solo los dígitos numéricos ──
        // El usuario ingresa solo números (ej: 12345678), pero en la BD
        // puede estar guardada como "V-12345678", "V12345678" o "12345678".
        // Se normaliza para buscar el sufijo numérico en ambos casos.
        $cedulaNumeros = preg_replace('/[^0-9]/', '', $cedula);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // ── [FIX-N-2] Límite de intentos de PIN — bloqueo temporal ──
        $bloqueoMsg = $this->kioscoBloqueado($ip, $cedulaNumeros, 'marcar');
        if ($bloqueoMsg !== null) {
            echo json_encode(['success' => false, 'message' => $bloqueoMsg]);
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
        // [SGP-FIX-KIOSCO-FERIADO-LABORABLE] Un feriado con es_laborable=1
        // no debe bloquear el registro — las asistencias se marcan con
        // normalidad ese día (mismo criterio que AutoFillService::cargarFeriados()
        // y la notificación de login en AuthController).
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
                'message'    => "Hoy es feriado: {$feriadoHoy->nombre}. La asistencia de este día se registrará automáticamente como Justificado.",
                'feriado'    => $feriadoHoy->nombre,
            ]);
            exit;
        }

        // ── Buscar pasante activo por cédula (3NF: JOINs a tablas normalizadas) ──
        // FIX: Comparamos el sufijo numérico de la cédula para tolerar prefijos
        // como "V-", "E-", "V", etc., que pueden existir en la BD.
        $this->db->query("
            SELECT
                u.id,
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

        // ── Verificar que existe ───────────────────────────────────
        if (!$pasante) {
            // Buscar si es un pasante finalizado/inactivo para dar mensaje personalizado
            $this->db->query("
                SELECT COALESCE(dp.nombres, u.correo) AS nombres,
                       COALESCE(dp.apellidos, '') AS apellidos,
                       COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
                       u.estado AS estado_cuenta
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                WHERE REGEXP_REPLACE(u.cedula, '[^0-9]', '') = :ced AND u.rol_id = 3
                ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
                LIMIT 1
            ");
            $this->db->bind(':ced', $cedulaNumeros);
            $inactivo = $this->db->single();

            if ($inactivo) {
                $nombre = trim(($inactivo->nombres ?? '') . ' ' . ($inactivo->apellidos ?? ''));
                $estado = $inactivo->estado_pasantia ?? 'Sin Asignar';

                if ($estado === 'Finalizado') {
                    echo json_encode([
                        'success' => false,
                        'tipo'    => 'finalizado',
                        'nombre'  => $nombre,
                        'message' => "¡Felicidades, {$nombre}! Tu pasantía ha sido completada exitosamente. Ya no es necesario registrar asistencia. Si necesitas algún documento o tienes consultas, comunícate con el Administrador de tu departamento.",
                    ]);
                } elseif ($estado === 'Retirado') {
                    echo json_encode([
                        'success' => false,
                        'tipo'    => 'retirado',
                        'nombre'  => $nombre,
                        'message' => "{$nombre}, tu pasantía se encuentra en estado Retirado. Para más información, comunícate con el Administrador.",
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'tipo'    => 'inactivo',
                        'nombre'  => $nombre,
                        'message' => "{$nombre}, tu cuenta se encuentra inactiva. Comunícate con el Administrador para verificar tu estado.",
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Cédula no encontrada en el sistema. Verifica el número e intenta de nuevo.']);
            }
            exit;
        }

        // ── Verificar que está activo (asignado a departamento) ───
        // FIX: Comparación case-insensitive para tolerar variaciones de capitalización
        if (strtolower($pasante->estado_pasantia ?? '') !== 'activo') {
            echo json_encode([
                'success' => false,
                'message' => 'Tu pasantía aún no ha sido activada. Contacta al Administrador.',
            ]);
            exit;
        }

        // ── Verificar que el período académico del pasante está activo ─
        $this->db->query("
            SELECT pa.estado FROM datos_pasante dp
            LEFT JOIN periodos_academicos pa ON dp.periodo_id = pa.id
            WHERE dp.usuario_id = :pid
            LIMIT 1
        ");
        $this->db->bind(':pid', (int)$pasante->id);
        $periodo = $this->db->single();
        if ($periodo && !in_array(strtolower($periodo->estado ?? ''), ['activo', 'planificado'])) {
            echo json_encode([
                'success' => false,
                'message' => 'El período académico actual no está activo. Contacta al Administrador.',
            ]);
            exit;
        }

        // ── Verificar que el pasante tenga PIN configurado ────────
        if (empty($pasante->pin_asistencia)) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes un PIN de asistencia configurado. Contacta al Administrador para que te asigne uno.',
            ]);
            exit;
        }

        // ── Verificar PIN (BCRYPT hash) ───────────────────────────
        if (!password_verify($pin, $pasante->pin_asistencia)) {
            // [FIX-N-2] Registrar intento fallido — bloquea tras 5 intentos por 15 min
            $this->kioscoRegistrarIntento($ip, $cedulaNumeros, 'marcar', 5, 15);
            echo json_encode(['success' => false, 'message' => 'PIN incorrecto. Inténtalo de nuevo.']);
            exit;
        }

        // [FIX-N-2] PIN correcto — limpiar contador de intentos fallidos
        $this->kioscoLimpiarIntentos($ip, $cedulaNumeros, 'marcar');

        // ── VULN-02: Validar departamento asignado ────────────────
        if (empty($pasante->departamento_nombre)) {
            echo json_encode(['success' => false, 'message' => 'No tienes departamento asignado. Contacta al Administrador.']);
            exit;
        }

        // ── VULN-02: Validar rango de fechas de pasantía y del período académico ──
        $this->db->query("
            SELECT dp.fecha_inicio_pasantia, dp.fecha_fin_estimada,
                   pa.fecha_fin AS periodo_fecha_fin, pa.nombre AS periodo_nombre, pa.estado AS periodo_estado
            FROM datos_pasante dp
            LEFT JOIN periodos_academicos pa ON pa.id = dp.periodo_id
            WHERE dp.usuario_id = :pid
        ");
        $this->db->bind(':pid', (int)$pasante->id);
        $fechas = $this->db->single();
        if ($fechas) {
            $hoyCheck = date('Y-m-d');
            if ($fechas->fecha_inicio_pasantia && $hoyCheck < $fechas->fecha_inicio_pasantia) {
                echo json_encode(['success' => false, 'message' => 'Tu pasantía aún no ha iniciado. Fecha de inicio: ' . date('d/m/Y', strtotime($fechas->fecha_inicio_pasantia))]);
                exit;
            }
            if ($fechas->fecha_fin_estimada && $hoyCheck > $fechas->fecha_fin_estimada) {
                echo json_encode(['success' => false, 'message' => 'Tu pasantía ha finalizado. Contacta al Administrador.']);
                exit;
            }
            if ($fechas->periodo_estado && strtolower($fechas->periodo_estado) === 'cerrado') {
                echo json_encode(['success' => false, 'message' => 'El período académico ha sido cerrado. Contacta al Administrador.']);
                exit;
            }
        }

        $hoy       = date('Y-m-d');
        $pasanteId = (int)$pasante->id;

        // ── Verificar si ya marcó hoy ──────────────────────────────
        $this->db->query("
            SELECT id FROM asistencias
            WHERE pasante_id = :pid AND fecha = :hoy
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':hoy', $hoy);
        $yaMarco = $this->db->single();

        if ($yaMarco) {
            echo json_encode([
                'success'     => false,
                'message'     => '✅ Ya registraste tu asistencia hoy. ¡Hasta mañana!',
                'ya_registro' => true,
            ]);
            exit;
        }

        // ── Verificar límite de horas_meta ─────────────────────────
        $this->db->query("
            SELECT COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                   COALESCE((SELECT COUNT(*) * 8
                             FROM asistencias a2
                             WHERE a2.pasante_id  = :pid2
                               AND a2.estado != 'Anulado'
                               AND a2.fecha >= COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)), 0) AS horas_acumuladas
            FROM datos_pasante dpa
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE dpa.usuario_id = :pid
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

        // ── INSERT — Registrar asistencia ──────────────────────────
        // Detectar retardo: hora_registro > 09:00:00 → es_retardo = 1
        $horaActual = date('H:i:s');
        $esRetardo  = ($horaActual > '09:00:00') ? 1 : 0;

        $this->db->query("
            INSERT INTO asistencias
                (pasante_id, fecha, hora_registro, estado, metodo, es_retardo, es_auto_fill)
            VALUES
                (:pid, :fecha, :hora, 'Presente', 'Kiosco', :retardo, 0)
        ");
        $this->db->bind(':pid',     $pasanteId);
        $this->db->bind(':fecha',   $hoy);
        $this->db->bind(':hora',    $horaActual);
        $this->db->bind(':retardo', $esRetardo);

        $ok = $this->db->execute();

        if ($ok) {
            // ── LOG BITÁCORA (sin sesión — contexto público del Kiosco) ───────
            // El pasante ya está autenticado (cédula + bcrypt PIN), así que usamos
            // su ID directamente como usuario_id. No se requiere $_SESSION.
            try {
                $asistenciaId = (int)$this->db->lastInsertId();
                $this->db->query("
                    INSERT INTO bitacora
                        (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent, detalles)
                    VALUES
                        (:uid, 'MARCAR_ASISTENCIA_KIOSCO', 'asistencias', :asistencia_id, :ip, :ua, :detalles)
                ");
                $this->db->bind(':uid',          $pasanteId);
                $this->db->bind(':asistencia_id', $asistenciaId);
                $this->db->bind(':ip',            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');
                $this->db->bind(':ua',            $_SERVER['HTTP_USER_AGENT'] ?? null);
                $this->db->bind(':detalles', json_encode([
                    'pasante' => trim($pasante->nombres . ' ' . $pasante->apellidos),
                    'cedula'  => $cedula,
                    'hora'    => $horaActual,
                    'retardo' => (bool)$esRetardo,
                    'metodo'  => 'Kiosco',
                ], JSON_UNESCAPED_UNICODE));
                $this->db->execute();
            } catch (\Throwable $e) {
                // El log de bitácora nunca debe interrumpir el flujo principal
                error_log('[SGP-KIOSCO] bitacora log error: ' . $e->getMessage());
            }

            // ── Notificación en campana al pasante ───────────────────────────
            try {
                require_once '../app/models/NotificationModel.php';
                $notifModel = new NotificationModel($this->db);
                $horaFmt    = date('h:i A');
                if ($esRetardo) {
                    $notifModel->create(
                        $pasanteId,
                        'asistencia_registrada',
                        'Asistencia registrada con retardo',
                        "Llegaste a las {$horaFmt}. Recuerda llegar antes de las 9:00 AM.",
                        URLROOT . '/pasante/asistencia'
                    );
                } else {
                    $notifModel->create(
                        $pasanteId,
                        'asistencia_registrada',
                        'Asistencia registrada',
                        "Tu asistencia del día de hoy fue registrada a las {$horaFmt}.",
                        URLROOT . '/pasante/asistencia'
                    );
                }
            } catch (\Throwable $e) {
                error_log('[SGP-KIOSCO] notif error: ' . $e->getMessage());
            }
            // ────────────────────────────────────────────────────────────────

            // ✅ PRO-RATA: El progreso se calcula dinámicamente desde la tabla 'asistencias'.
            // NO se suma ni resta a horas_acumuladas — 'asistencias' es la única fuente de verdad.
            echo json_encode([
                'success' => true,
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

        // Insertar notificaciones para Admin (user 1) y Tutor (si tiene)
        require_once APPROOT . '/models/NotificationModel.php';
        $this->notificationModel = new NotificationModel($this->db);
        $titulo = "Solicitud de reseteo de PIN";
        $mensaje = "El pasante {$pasante->nombres} {$pasante->apellidos} (V-{$cedula}) ha olvidado su PIN y solicita un reseteo.";
        $url = URLROOT . "/configuracion#restablecer-pin";

        // Admin (enviamos a todos los admins activos)
        $this->db->query("SELECT id FROM usuarios WHERE rol_id = 1 AND estado = 'activo'");
        $admins = $this->db->resultSet();
        foreach ($admins as $admin) {
            $this->notificationModel->createWithRef(
                $admin->id, 'solicitud_pin', $titulo, $mensaje, $url,
                $pasante->id  // referencia_id = pasante para resolución global
            );
        }

        // Tutor (si existe)
        if (!empty($pasante->tutor_id)) {
            $this->notificationModel->createWithRef(
                $pasante->tutor_id, 'solicitud_pin', $titulo, $mensaje, $url,
                $pasante->id
            );
        }

        // [FIX-N-5] Contabilizar esta solicitud — bloquea tras 3 solicitudes por 30 min
        $this->kioscoRegistrarIntento($ip, $cedulaNumerosPinReset, 'reset_pin', 3, 30);

        echo json_encode(['success' => true, 'message' => 'Solicitud enviada a tu administrador y tutor.']);
        exit;
    }
}
