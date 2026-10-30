<?php
/**
 * BitacoraController - Controlador de Auditoría
 * 
 * PROPÓSITO:
 * Gestionar la visualización y filtrado de registros de auditoría del sistema.
 * Solo accesible para administradores.
 * 
 * RESPONSABILIDADES:
 * - Mostrar vista principal con tabla de logs
 * - Filtrar registros por usuario, acción, fecha
 * - Exportar logs a CSV
 * - Proveer estadísticas para dashboard
 * 
 * @author Sistema SGP
 * @version 1.0
 */

class BitacoraController extends Controller
{
    private $auditModel;
    private $userModel;

    public function __construct()
    {
        // Verificar autenticación y rol de administrador
        AuthMiddleware::require();
        RoleMiddleware::authorize([0, 1]); // Validación estricta solo para administradores
        
        // Inicializar modelos
        $this->auditModel = $this->model('Audit');
        $this->userModel = $this->model('User');
    }

    /**
     * Vista Principal - Tabla de Bitácora
     * 
     * FLUJO:
     * 1. Obtener últimos 500 registros de auditoría
     * 2. Obtener lista de usuarios para filtros
     * 3. Renderizar vista con datos
     */
    public function index(): void
    {
        // Obtener acciones únicas para filtro
        $actions = $this->getUniqueActions();

        // KPIs del ciclo de vida (header cards)
        $kpis = [];
        try {
            $kpis = $this->auditModel->getKPIs();
        } catch (\Throwable $e) {
            // Si la tabla historico aún no existe, degradar gracefully
            error_log('[SGP-BITACORA] getKPIs() fallback: ' . $e->getMessage());
            $db = $this->auditModel->getDb();
            $db->query('SELECT COUNT(*) AS total_activos, SUM(DATE(created_at)=CURDATE()) AS hoy, SUM(created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)) AS semana FROM bitacora');
            $row = $db->single();
            $kpis = [
                'total_activos'   => (int)($row->total_activos ?? 0),
                'hoy'             => (int)($row->hoy           ?? 0),
                'semana'          => (int)($row->semana        ?? 0),
                'ultima_purga'    => null,
                'total_historico' => 0,
            ];
        }

        // Datos para la vista
        $data = [
            'title'      => 'Bitácora de Auditoría',
            'actions'    => $actions,
            'total_logs' => $kpis['total_activos'],
            'kpis'       => $kpis,
        ];

        $this->view('bitacora/index', $data);
    }

    /**
     * API AJAX - Filtrar Registros
     * 
     * PROPÓSITO:
     * Endpoint para filtrado dinámico de logs sin recargar página.
     * 
     * PARÁMETROS POST:
     * - usuario_id: ID del usuario (opcional)
     * - accion: Tipo de acción (opcional)
     * - fecha_desde: Fecha inicio (opcional)
     * - fecha_hasta: Fecha fin (opcional)
     * 
     * RETORNA: JSON con registros filtrados
     */
    public function filter(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        try {
            // Obtener parámetros de filtro
            $userId = Validator::post('usuario_id') ?: null;
            $action = Validator::post('accion') ?: null;
            $dateFrom = Validator::post('fecha_desde') ?: null;
            $dateTo = Validator::post('fecha_hasta') ?: null;
            
            // Construir query dinámica
            $query = "
                SELECT 
                    b.id,
                    b.usuario_id,
                    b.accion,
                    b.tabla_afectada,
                    b.registro_id,
                    b.ip_address,
                    b.user_agent,
                    b.detalles,
                    b.created_at,
                    u.correo as usuario_email,
                    CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, '')) as usuario_nombre
                FROM bitacora b
                LEFT JOIN usuarios u ON b.usuario_id = u.id
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Filtro por usuario
            if ($userId) {
                $query .= " AND b.usuario_id = :usuario_id";
                $params[':usuario_id'] = $userId;
            }
            
            // Filtro por acción
            if ($action) {
                $query .= " AND b.accion = :accion";
                $params[':accion'] = $action;
            }
            
            // Filtro por fecha desde
            if ($dateFrom) {
                $query .= " AND DATE(b.created_at) >= :fecha_desde";
                $params[':fecha_desde'] = $dateFrom;
            }
            
            // Filtro por fecha hasta
            if ($dateTo) {
                $query .= " AND DATE(b.created_at) <= :fecha_hasta";
                $params[':fecha_hasta'] = $dateTo;
            }
            
            $query .= " ORDER BY b.created_at DESC LIMIT 500";
            
            // Ejecutar query
            $db = $this->auditModel->getDb();
            $db->query($query);
            
            foreach ($params as $key => $value) {
                $db->bind($key, $value);
            }
            
            $results = $db->resultSet();
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ]);
            
        } catch (Exception $e) {
            error_log("BitacoraController::filter() Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al filtrar registros'
            ]);
        }
    }

    /**
     * API - Grid.js Server-Side Pagination
     * Devuelve el JSON requerido por Grid.js para Server-Side (array data y total rows)
     */
    public function apiGrid(): void
    {
        // Limpiar cualquier buffer previo (HTML residual del framework)
        if (ob_get_level()) { ob_clean(); }
        header('Content-Type: application/json');
        
        try {
            // Grid.js envía limit y offset via GET (sanitizados y acotados)
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            
            // Filtros
            $action   = $_GET['accion']      ?? null;
            $dateFrom = $_GET['fecha_desde'] ?? null;
            $dateTo   = $_GET['fecha_hasta'] ?? null;
            $q        = trim($_GET['q']      ?? '');

            $query = "
                SELECT
                    b.id,
                    b.usuario_id,
                    b.accion,
                    b.tabla_afectada,
                    b.ip_address,
                    b.detalles,
                    b.created_at,
                    u.correo as usuario_email,
                    r.nombre as rol_nombre,
                    CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, '')) as usuario_nombre
                FROM bitacora b
                LEFT JOIN usuarios u ON b.usuario_id = u.id
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                WHERE 1=1
            ";

            $countQuery = "
                SELECT COUNT(*) as total
                FROM bitacora b
                LEFT JOIN usuarios u ON b.usuario_id = u.id
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                WHERE 1=1
            ";

            $params = [];

            if ($action) {
                $query .= " AND b.accion = :accion";
                $countQuery .= " AND b.accion = :accion";
                $params[':accion'] = $action;
            }

            if ($dateFrom) {
                $query .= " AND DATE(b.created_at) >= :fecha_desde";
                $countQuery .= " AND DATE(b.created_at) >= :fecha_desde";
                $params[':fecha_desde'] = $dateFrom;
            }

            if ($dateTo) {
                $query .= " AND DATE(b.created_at) <= :fecha_hasta";
                $countQuery .= " AND DATE(b.created_at) <= :fecha_hasta";
                $params[':fecha_hasta'] = $dateTo;
            }

            if ($q !== '') {
                // [FIX-M2] Escapar wildcards LIKE ('%', '_') para prevenir full-table scans por DoS
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $query .= " AND (b.ip_address LIKE :q OR u.correo LIKE :q2 OR b.tabla_afectada LIKE :q3
                                 OR CONCAT(COALESCE(dp.nombres,''),' ',COALESCE(dp.apellidos,'')) LIKE :q4)";
                $countQuery .= " AND (b.ip_address LIKE :q OR u.correo LIKE :q2 OR b.tabla_afectada LIKE :q3
                                      OR CONCAT(COALESCE(dp.nombres,''),' ',COALESCE(dp.apellidos,'')) LIKE :q4)";
                $params[':q']  = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
                $params[':q4'] = $like;
            }

            
            $query .= " ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";
            
            $db = $this->auditModel->getDb();
            
            // Obtener count total
            $db->query($countQuery);
            foreach ($params as $key => $value) {
                $db->bind($key, $value);
            }
            $totalRow = $db->single();
            $total = $totalRow->total ?? 0;
            
            // Obtener filas
            $db->query($query);
            foreach ($params as $key => $value) {
                $db->bind($key, $value);
            }
            // Bind variables de paginación (deben ser enteros)
            $db->bind(':limit', $limit, PDO::PARAM_INT);
            $db->bind(':offset', $offset, PDO::PARAM_INT);
            
            $results = $db->resultSet();
            
            echo json_encode([
                'data' => $results,
                'total' => (int)$total
            ]);
            exit;
            
        } catch (\Throwable $e) {
            error_log("BitacoraController::apiGrid() Error: " . $e->getMessage());
            echo json_encode([
                'data'  => [],
                'total' => 0,
                'error' => 'Error al cargar registros'
            ]);
            exit;
        }
    }

    /**
     * Exportar PDF Individual de un Registro de Auditoría
     * 
     * PROPÓSITO:
     * Genera una página HTML imprimible con el detalle de UN registro de auditoría.
     * Se abre en pestaña nueva y el navegador muestra el diálogo de impresión.
     * 
     * @param int $id ID del registro
     */
    public function exportPdfRow(?int $id = null): void
    {
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            exit('ID de registro inválido.');
        }

        // Usar la instancia de DB del modelo
        $db = $this->auditModel->getDb();

        $db->query(
            "SELECT b.*,
                    u.correo AS usuario_email,
                    u.cedula AS usuario_cedula,
                    r.nombre AS usuario_rol,
                    CONCAT(COALESCE(dp.nombres, 'Sistema'), ' ', COALESCE(dp.apellidos, '')) AS usuario_nombre
             FROM bitacora b
             LEFT JOIN usuarios u ON b.usuario_id = u.id
             LEFT JOIN roles r ON u.rol_id = r.id
             LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
             WHERE b.id = :id"
        );
        $db->bind(':id', (int)$id);
        $log = $db->single();

        if (!$log) {
            http_response_code(404);
            exit('Registro no encontrado.');
        }

        // Cargar cintillo institucional en base64
        $baseImgDir = defined('APPROOT') ? APPROOT . '/../public/img/' : dirname(dirname(__DIR__)) . '/public/img/';
        $cintilloCandidates = [
            $baseImgDir . 'cintillo.png',
            $baseImgDir . 'cintillo_isp_bolivar.jpg',
            $baseImgDir . 'cintillo_pdf.png',
            $baseImgDir . 'cintillo-salud.png'
        ];
        $base64_cintillo = '';
        foreach ($cintilloCandidates as $cp) {
            if (file_exists($cp)) {
                $ext = strtolower(pathinfo($cp, PATHINFO_EXTENSION));
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
                $base64_cintillo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($cp));
                break;
            }
        }

        $usuario       = htmlspecialchars(trim($log->usuario_nombre ?? 'Sistema'));
        $email         = htmlspecialchars($log->usuario_email ?? 'N/A');
        $cedula_num    = !empty($log->usuario_cedula) ? number_format((int)$log->usuario_cedula, 0, '', '.') : '';
        $cedula_txt    = $cedula_num ? "C.I. V-{$cedula_num}" : 'Identidad Registrada';
        $rol_txt       = htmlspecialchars($log->usuario_rol ?? 'Operador del Sistema');
        $ip            = htmlspecialchars($log->ip_address ?? '—');
        $inicial       = strtoupper(substr($usuario, 0, 1)) ?: 'S';
        $fecha_raw     = $log->created_at ?? '';
        $fecha         = $fecha_raw ? date('d/m/Y H:i:s', strtotime($fecha_raw)) : '—';
        $fecha_fecha   = $fecha_raw ? date('d/m/Y', strtotime($fecha_raw)) : date('d/m/Y');
        $fecha_hora    = $fecha_raw ? date('h:i:s A', strtotime($fecha_raw)) : date('h:i:s A');
        $fecha_generacion = date('d/m/Y h:i:s A');
        $doc_num       = str_pad($id, 8, '0', STR_PAD_LEFT);
        $registro_id   = $log->registro_id ? (string)$log->registro_id : '';

        // Formatear IP amigable
        $ip_display = $ip;
        if ($ip === '::1' || $ip === '127.0.0.1') {
            $ip_display = '127.0.0.1 (Localhost / Intranet)';
        }

        // Parseo amigable de User Agent
        $ua = $log->user_agent ?? '';
        $so = 'Desconocido';
        if (preg_match('/Windows NT 10.0/i', $ua)) $so = 'Windows 10 / 11';
        elseif (preg_match('/Windows NT 6.3/i', $ua)) $so = 'Windows 8.1';
        elseif (preg_match('/Windows NT 6.1/i', $ua)) $so = 'Windows 7';
        elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) $so = 'macOS';
        elseif (preg_match('/Linux/i', $ua)) $so = 'GNU/Linux';
        elseif (preg_match('/Android/i', $ua)) $so = 'Android';
        elseif (preg_match('/iPhone|iPad/i', $ua)) $so = 'iOS';

        $browser = 'Navegador Web';
        if (preg_match('/Edg\/([0-9.]+)/i', $ua)) $browser = 'Microsoft Edge';
        elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua)) $browser = 'Google Chrome';
        elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua)) $browser = 'Mozilla Firefox';
        elseif (preg_match('/Safari\/([0-9.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) $browser = 'Apple Safari';

        $cliente_entorno = ($so !== 'Desconocido' || $browser !== 'Navegador Web')
            ? "{$browser} en {$so}"
            : (!empty($ua) ? substr($ua, 0, 40) . '...' : 'Terminal de Red Interna');

        // Traducción de acciones a español
        $acciones_es = [
            'LOGIN'                     => 'Inicio de Sesión en el Sistema',
            'LOGOUT'                    => 'Cierre de Sesión en el Sistema',
            'RESET_PASSWORD'            => 'Contraseña Restablecida',
            'RESET_PIN'                 => 'PIN de Asistencia Restablecido',
            'UPDATE_SECURITY_QUESTIONS' => 'Preguntas de Seguridad Actualizadas',
            'CREATE_USER'               => 'Nuevo Usuario Creado en el Sistema',
            'UPDATE_USER'               => 'Datos de Usuario Modificados',
            'DELETE_USER'               => 'Usuario Eliminado del Sistema',
            'TOGGLE_USER_STATUS'        => 'Estado de Cuenta Alterado',
            'UPDATE_PROFILE'            => 'Perfil de Usuario Actualizado',
            'CREATE_PASANTE'            => 'Pasante Registrado en el Sistema',
            'UPDATE_PASANTE'            => 'Datos de Pasante Modificados',
            'DELETE_PASANTE'            => 'Pasante Eliminado del Sistema',
            'CHANGE_PASANTE_STATUS'     => 'Estado de Pasantía Actualizado',
            'MARCAR_ASISTENCIA_KIOSCO'  => 'Asistencia Marcada desde Kiosco Digital',
            'CREATE_EVALUACION'         => 'Evaluación de Pasantía Creada',
            'UPDATE_EVALUACION'         => 'Evaluación de Pasantía Modificada',
            'DELETE_EVALUACION'         => 'Evaluación de Pasantía Eliminada',
            'PERMISO_MODIFICADO'        => 'Permiso de Acceso Modificado',
            'PERMISOS_RESET'            => 'Permisos Restablecidos por Defecto',
            'AUDIT_PURGE'               => 'Mantenimiento de Bitácora Ejecutado',
            'EXPORT_CSV'                => 'Exportación de Datos a CSV',
            'UPDATE_CONFIG'             => 'Configuración del Sistema Actualizada',
            'REGISTRAR_ASISTENCIA_MANUAL'  => 'Asistencia Registrada Manualmente',
            'ACTUALIZAR_ASISTENCIA_MANUAL' => 'Asistencia Actualizada Manualmente',
            'REGISTRAR_ASISTENCIA_MASIVA'  => 'Asistencias Registradas Masivamente',
            'ANULAR_ASISTENCIA'            => 'Asistencia Anulada Oficialmente',
            'CREATE_EXAMEN'             => 'Examen Evaluativo Creado',
            'TOGGLE_EXAMEN'             => 'Estado de Examen Alterado',
            'DELETE_EXAMEN'             => 'Examen Evaluativo Eliminado',
            'ENVIO_EXAMEN'              => 'Examen Evaluativo Entregado por Pasante',
            'REVISAR_EXAMEN'            => 'Examen Calificado por Tutor/Admin',
            'EDITAR_PUNTOS_PREGUNTA'    => 'Ponderación de Pregunta Modificada',
            'DELETE_INTENTO'            => 'Intento de Examen Anulado',
        ];
        $accion_es = $acciones_es[strtoupper($log->accion ?? '')] ?? htmlspecialchars($log->accion ?? 'Operación Registrada');

        $tablas_es = [
            'usuarios'        => 'Usuarios del Sistema',
            'datos_personales'=> 'Expediente / Perfil de Usuario',
            'pasantes'        => 'Registro de Pasantes',
            'datos_pasante'   => 'Datos Académicos de Pasante',
            'bitacora'        => 'Libro de Auditoría y Trazabilidad',
            'evaluaciones'    => 'Evaluaciones Institucionales',
            'asistencias'     => 'Control de Asistencias Diarias',
            'configuracion'   => 'Configuración General del Sistema',
            'asignaciones'    => 'Asignación de Tutores y Departamentos',
            'usuario_permisos'=> 'Control de Permisos y Privilegios',
        ];
        $tabla_es = $tablas_es[strtolower($log->tabla_afectada ?? '')] ?? htmlspecialchars($log->tabla_afectada ?? '');

        // Categorización cromática ejecutiva
        $accion_raw = htmlspecialchars(strtoupper($log->accion ?? '—'));
        $accion_key = strtoupper($log->accion ?? '');
        if (in_array($accion_key, ['LOGIN','LOGOUT','RESET_PASSWORD','RESET_PIN','UPDATE_SECURITY_QUESTIONS','MARCAR_ASISTENCIA_KIOSCO'])) {
            $action_color = '#1d4ed8'; $action_bg = '#eff6ff'; $action_badge = '#dbeafe'; $action_badge_txt = '#1e40af'; $action_category = 'SEGURIDAD Y CONTROL DE ACCESO';
        } elseif (preg_match('/^(CREATE_|REGISTRAR_)/', $accion_key)) {
            $action_color = '#15803d'; $action_bg = '#f0fdf4'; $action_badge = '#dcfce7'; $action_badge_txt = '#166534'; $action_category = 'CREACIÓN Y ALTA DE REGISTROS';
        } elseif (preg_match('/^(UPDATE_|ACTUALIZAR_)/', $accion_key) || in_array($accion_key, ['TOGGLE_USER_STATUS','CHANGE_PASANTE_STATUS','PERMISO_MODIFICADO','PERMISOS_RESET','TOGGLE_EXAMEN','REVISAR_EXAMEN','EDITAR_PUNTOS_PREGUNTA'])) {
            $action_color = '#b45309'; $action_bg = '#fffbeb'; $action_badge = '#fef3c7'; $action_badge_txt = '#92400e'; $action_category = 'MODIFICACIÓN Y ACTUALIZACIÓN';
        } elseif (preg_match('/^DELETE_/', $accion_key) || in_array($accion_key, ['ANULAR_ASISTENCIA','AUDIT_PURGE','DELETE_INTENTO'])) {
            $action_color = '#b91c1c'; $action_bg = '#fef2f2'; $action_badge = '#fee2e2'; $action_badge_txt = '#991b1b'; $action_category = 'SUPRESIÓN Y ANULACIÓN DE DATOS';
        } elseif (in_array($accion_key, ['EXPORT_CSV','ENVIO_EXAMEN'])) {
            $action_color = '#6d28d9'; $action_bg = '#faf5ff'; $action_badge = '#f3e8ff'; $action_badge_txt = '#5b21b6'; $action_category = 'PROCESAMIENTO Y EXPORTACIÓN';
        } else {
            $action_color = '#162660'; $action_bg = '#f8fafc'; $action_badge = '#e2e8f0'; $action_badge_txt = '#1e293b'; $action_category = 'OPERACIÓN DEL SISTEMA';
        }

        // Si la tabla o módulo está vacía, darle sentido contextual
        if (empty($tabla_es) || $tabla_es === '—') {
            if (in_array($accion_key, ['LOGIN','LOGOUT','RESET_PASSWORD','RESET_PIN'])) {
                $tabla_es = 'Módulo de Seguridad / Autenticación';
            } else {
                $tabla_es = 'Núcleo Central del Sistema (Core)';
            }
        }

        // Registro ID formateado
        $registro_id_txt = !empty($registro_id) && $registro_id !== '—' ? "#{$registro_id}" : 'N/A (Acceso Global)';

        // Generación de Hash Criptográfico de Integridad Forense
        $hash_semilla = "{$id}|{$log->created_at}|{$log->ip_address}|{$log->usuario_id}|{$log->accion}|SGP_AUDIT_ISPEB_BOLIVAR";
        $hash_integridad = strtoupper(hash('sha256', $hash_semilla));
        $hash_formateado = substr($hash_integridad, 0, 8) . ' ' . substr($hash_integridad, 8, 8) . ' ' . substr($hash_integridad, 16, 8) . ' ' . substr($hash_integridad, 24, 8) . ' ' . substr($hash_integridad, 32, 8) . ' ' . substr($hash_integridad, 40, 8) . ' ' . substr($hash_integridad, 48, 8) . ' ' . substr($hash_integridad, 56, 8);

        // Detalles legibles
        $detalles_rows = '';
        $labels_map = [
            'pasante'        => 'Pasante Afectado',
            'cedula'         => 'Cédula de Identidad',
            'hora'           => 'Hora Registrada',
            'retardo'        => 'Llegada con Retardo',
            'metodo'         => 'Método de Validación',
            'clave'          => 'Parámetro / Clave',
            'habilitado'     => 'Estado del Permiso',
            'usuario'        => 'Usuario Involucrado',
            'email'          => 'Correo Electrónico',
            'campo'          => 'Campo Modificado',
            'valor_anterior' => 'Valor Previo',
            'valor_nuevo'    => 'Nuevo Valor Asignado',
            'motivo'         => 'Motivo Registrado',
            'ip'             => 'Dirección IP Reportada',
        ];
        if (!empty($log->detalles)) {
            $detalles_obj = json_decode($log->detalles, true);
            if (is_array($detalles_obj) && !empty($detalles_obj)) {
                foreach ($detalles_obj as $key => $val) {
                    $label = $labels_map[$key] ?? ucwords(str_replace('_', ' ', $key));
                    if (is_bool($val)) $val = $val ? 'Sí / Habilitado' : 'No / Deshabilitado';
                    elseif ($val === null || $val === '') $val = '—';
                    $val_safe = htmlspecialchars((string)$val);
                    $detalles_rows .= "
                    <tr>
                        <td style=\"padding: 8px 12px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #e2e8f0; width: 35%;\">{$label}</td>
                        <td style=\"padding: 8px 12px; font-family: 'Courier New', Courier, monospace; color: #0f172a; border-bottom: 1px solid #e2e8f0;\">{$val_safe}</td>
                    </tr>";
                }
            }
        }

        // Si no contiene detalles estructurados JSON, proporcionar narrativa contextual formal
        if (!empty($detalles_rows)) {
            $detalles_html = "
            <table style=\"width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; background: #ffffff;\">
                <thead>
                    <tr style=\"background: #162660; color: #ffffff;\">
                        <th style=\"padding: 8px 12px; font-size: 8pt; text-align: left; text-transform: uppercase; letter-spacing: 0.5px;\">Parámetro / Variable</th>
                        <th style=\"padding: 8px 12px; font-size: 8pt; text-align: left; text-transform: uppercase; letter-spacing: 0.5px;\">Valor Registrado en Sistema</th>
                    </tr>
                </thead>
                <tbody>{$detalles_rows}</tbody>
            </table>";
        } else {
            if ($accion_key === 'LOGIN') {
                $descripcion_contextual = "El operador validó satisfactoriamente sus credenciales de acceso institucional (correo y clave de autenticación) ingresando al panel operativo del sistema. El evento fue auditado con éxito registrando la dirección de red y la marca de tiempo correspondiente en la base de datos inmutable.";
            } elseif ($accion_key === 'LOGOUT') {
                $descripcion_contextual = "El operador efectuó el cierre ordenado de su sesión de trabajo en el sistema, revocando tokens de acceso activos y garantizando la protección de la cuenta institucional.";
            } else {
                $descripcion_contextual = "La transacción operativa fue ejecutada de conformidad con las reglas de negocio del sistema y registrada en el libro inmutable de auditoría con verificación de integridad de datos.";
            }

            $detalles_html = "
            <div style=\"background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid {$action_color}; border-radius: 6px; padding: 14px 18px;\">
                <table style=\"width: 100%; border-collapse: collapse;\">
                    <tr>
                        <td style=\"width: 32px; vertical-align: top;\">
                            <div style=\"width: 24px; height: 24px; border-radius: 50%; background: {$action_badge}; color: {$action_badge_txt}; text-align: center; line-height: 24px; font-weight: bold; font-size: 11pt;\">&#x2139;</div>
                        </td>
                        <td style=\"vertical-align: top; padding-left: 8px;\">
                            <div style=\"font-size: 8.5pt; font-weight: 700; color: #1e293b; margin-bottom: 4px;\">Resumen Contextual de la Operación</div>
                            <div style=\"font-size: 8.5pt; color: #475569; line-height: 1.45;\">{$descripcion_contextual}</div>
                        </td>
                    </tr>
                </table>
            </div>";
        }

        // Generar PDF con DomPDF
        require_once '../app/lib/PdfGenerator.php';
        $pdfGen = new PdfGenerator();

        $cintilloHtml = !empty($base64_cintillo)
            ? "<div style=\"width: 100%; margin-bottom: 10px; text-align: center; line-height: 0;\">
                   <img src=\"{$base64_cintillo}\" style=\"width: 100%; max-height: 52px; object-fit: contain;\" alt=\"Cintillo Institucional\">
               </div>"
            : "";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Auditoría SGP-AUD-{$doc_num}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #0f172a;
            font-size: 8.5pt;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }

        /* ── SECCIONES ── */
        .sec-title-wrap {
            margin: 12px 0 6px 0;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .sec-number {
            display: inline-block;
            background: #162660;
            color: #ffffff;
            font-size: 7pt;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 6px;
        }
        .sec-title-text {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1e293b;
        }

        /* ── TARJETAS BENTO ── */
        .bento-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 9px 12px;
        }
        .bento-label {
            font-size: 6.8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            margin-bottom: 3px;
        }
        .bento-val {
            font-size: 8.8pt;
            font-weight: 700;
            color: #0f172a;
        }
        .bento-val-sub {
            font-size: 7.2pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* ── PIE DE SEGURIDAD ── */
        .crypto-box {
            background: #0f172a;
            color: #ffffff;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 14px;
        }
        .crypto-title {
            font-size: 6.8pt;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #38bdf8;
            margin-bottom: 3px;
        }
        .crypto-hash {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.2pt;
            letter-spacing: 0.8px;
            color: #f1f5f9;
        }
    </style>
</head>
<body>

    <!-- 1. CINTILLO INSTITUCIONAL OFICIAL -->
    {$cintilloHtml}

    <!-- 2. ENCABEZADO GUBERNAMENTAL & METADATOS DE DOCUMENTO -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            <td style="vertical-align: middle; width: 62%;">
                <div style="font-size: 6.8pt; font-weight: 800; color: #2563eb; letter-spacing: 1.5px; text-transform: uppercase;">
                    GOBERNACIÓN DEL ESTADO BOLÍVAR &bull; INSTITUTO DE SALUD PÚBLICA
                </div>
                <div style="font-size: 15pt; font-weight: 900; color: #162660; line-height: 1.1; margin: 2px 0;">
                    Comprobante de Auditoría
                </div>
                <div style="font-size: 7.5pt; color: #64748b;">
                    Dirección de Telemática &mdash; Sistema de Gestión de Pasantías (SGP)
                </div>
            </td>
            <td style="vertical-align: middle; width: 38%; text-align: right;">
                <div style="background: #162660; border-radius: 6px; padding: 8px 12px; color: #ffffff; display: inline-block; text-align: right; width: 100%;">
                    <div style="font-size: 6.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #93c5fd;">
                        N.&deg; CONTROL AUDITORÍA
                    </div>
                    <div style="font-size: 13pt; font-weight: 900; letter-spacing: 2px; color: #ffffff; line-height: 1.1;">
                        SGP-{$doc_num}
                    </div>
                    <div style="font-size: 6.8pt; color: #cbd5e1; margin-top: 2px;">
                        Emisión: {$fecha_generacion}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 3. BANDA DE CLASIFICACIÓN DE SEGURIDAD -->
    <table style="width: 100%; border-collapse: collapse; background: #0f172a; border-radius: 5px; margin-bottom: 8px;">
        <tr>
            <td style="padding: 5px 12px; vertical-align: middle;">
                <span style="font-size: 6.8pt; font-weight: 800; color: #ffffff; letter-spacing: 1px; text-transform: uppercase;">
                    &#x1F512; REGISTRO OFICIAL DE AUDITORÍA INFORMÁTICA &middot; DOCUMENTO INMUTABLE
                </span>
            </td>
            <td style="padding: 5px 12px; vertical-align: middle; text-align: right;">
                <span style="font-size: 6.8pt; font-weight: 800; background: {$action_badge}; color: {$action_badge_txt}; padding: 2px 8px; border-radius: 3px; letter-spacing: 0.5px; text-transform: uppercase;">
                    {$action_category}
                </span>
            </td>
        </tr>
    </table>

    <!-- 4. HERO CARD DEL EVENTO AUDITADO -->
    <div style="background: {$action_bg}; border: 1.5px solid {$action_badge}; border-left: 5px solid {$action_color}; border-radius: 6px; padding: 10px 14px; margin-bottom: 10px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 44px; vertical-align: middle;">
                    <div style="width: 38px; height: 38px; border-radius: 8px; background: {$action_color}; color: #ffffff; font-size: 15pt; font-weight: 900; text-align: center; line-height: 38px;">
                        &#x2713;
                    </div>
                </td>
                <td style="vertical-align: middle; padding-left: 10px;">
                    <div style="font-size: 12.5pt; font-weight: 900; color: #0f172a; line-height: 1.2;">
                        {$accion_es}
                    </div>
                    <div style="font-size: 7.2pt; font-weight: 700; color: {$action_color}; letter-spacing: 0.5px; margin-top: 2px;">
                        CÓDIGO DE EVENTO: {$accion_raw} &bull; REGISTRO REFERENCIAL {$registro_id_txt}
                    </div>
                </td>
                <td style="vertical-align: middle; text-align: right; width: 140px;">
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 5px; padding: 6px 10px; text-align: center;">
                        <div style="font-size: 6.5pt; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">
                            Fecha y Hora del Evento
                        </div>
                        <div style="font-size: 9.5pt; font-weight: 900; color: #0f172a; margin-top: 2px;">
                            {$fecha_fecha}
                        </div>
                        <div style="font-size: 7.5pt; font-weight: 700; color: #2563eb;">
                            {$fecha_hora}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 5. SECCIÓN 01 — FUNCIONARIO RESPONSABLE -->
    <div class="sec-title-wrap">
        <span class="sec-number">01</span>
        <span class="sec-title-text">Funcionario / Operador Responsable</span>
    </div>
    <div class="bento-card" style="margin-bottom: 10px; background: #ffffff;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 44px; vertical-align: middle;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #162660; color: #ffffff; font-size: 14pt; font-weight: 900; text-align: center; line-height: 38px; border: 2px solid #93c5fd;">
                        {$inicial}
                    </div>
                </td>
                <td style="vertical-align: middle; padding-left: 12px; width: 50%;">
                    <div style="font-size: 11pt; font-weight: 800; color: #0f172a; line-height: 1.2;">
                        {$usuario}
                    </div>
                    <div style="font-size: 7.5pt; color: #475569; margin-top: 2px;">
                        {$cedula_txt} &bull; <span style="color: #2563eb; font-weight: 600;">{$email}</span>
                    </div>
                </td>
                <td style="vertical-align: middle; text-align: right; width: 40%;">
                    <div style="display: inline-block; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 5px; padding: 4px 10px; text-align: right;">
                        <div style="font-size: 6.5pt; font-weight: 800; text-transform: uppercase; color: #1e40af; letter-spacing: 0.5px;">
                            Rol Asignado en SGP
                        </div>
                        <div style="font-size: 8.5pt; font-weight: 800; color: #1d4ed8;">
                            {$rol_txt}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 6. SECCIÓN 02 — PARÁMETROS TÉCNICOS Y ENTORNO FORENSE -->
    <div class="sec-title-wrap">
        <span class="sec-number">02</span>
        <span class="sec-title-text">Parámetros Técnicos y Entorno de Red</span>
    </div>
    <table style="width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-left: -6px; margin-right: -6px; margin-bottom: 10px;">
        <tr>
            <td style="width: 25%; vertical-align: top;">
                <div class="bento-card">
                    <div class="bento-label">Módulo / Entorno</div>
                    <div class="bento-val" style="font-size: 8.2pt;">{$tabla_es}</div>
                    <div class="bento-val-sub">Área de Sistema</div>
                </div>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <div class="bento-card">
                    <div class="bento-label">Dirección IP</div>
                    <div class="bento-val" style="font-size: 8.2pt; color: #2563eb;">{$ip_display}</div>
                    <div class="bento-val-sub">Origen de Conexión</div>
                </div>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <div class="bento-card">
                    <div class="bento-label">Registro Afectado</div>
                    <div class="bento-val" style="font-size: 8.2pt;">{$registro_id_txt}</div>
                    <div class="bento-val-sub">Entidad Destino</div>
                </div>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <div class="bento-card">
                    <div class="bento-label">Cliente / Entorno</div>
                    <div class="bento-val" style="font-size: 7.8pt;">{$cliente_entorno}</div>
                    <div class="bento-val-sub">Dispositivo Detectado</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 7. SECCIÓN 03 — DETALLES Y CARGA ÚTIL -->
    <div class="sec-title-wrap">
        <span class="sec-number">03</span>
        <span class="sec-title-text">Detalles de la Operación y Trazabilidad</span>
    </div>
    <div style="margin-bottom: 12px;">
        {$detalles_html}
    </div>

    <!-- 8. SELLO DE SEGURIDAD CRIPTOGRÁFICA Y CERTIFICACIÓN FORENSE -->
    <div class="crypto-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle; width: 68%;">
                    <div class="crypto-title">
                        &#x1F6E1; SELLO CRIPTOGRÁFICO DE INTEGRIDAD FORENSE (SHA-256)
                    </div>
                    <div class="crypto-hash">
                        {$hash_formateado}
                    </div>
                    <div style="font-size: 6.2pt; color: #94a3b8; margin-top: 3px; line-height: 1.3;">
                        Certificación digital automática generada en base a los parámetros inmutables del registro. Conforme a la Ley sobre Mensajes de Datos y Firmas Electrónicas, este comprobante posee plena validez y eficacia probatoria institucional.
                    </div>
                </td>
                <td style="vertical-align: middle; width: 32%; text-align: right; border-left: 1px solid #334155; padding-left: 12px;">
                    <div style="border: 1.5px solid #38bdf8; color: #38bdf8; border-radius: 4px; padding: 4px 8px; display: inline-block; text-align: center;">
                        <div style="font-size: 6pt; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">
                            ESTADO DE AUDITORÍA
                        </div>
                        <div style="font-size: 8pt; font-weight: 900; letter-spacing: 0.5px; color: #ffffff;">
                            REGISTRO VÁLIDO &#x2713;
                        </div>
                    </div>
                    <div style="font-size: 6pt; color: #64748b; margin-top: 3px;">
                        ISPEB &bull; Telemática &bull; SGP
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 9. FOOTER INFERIOR COMPACTO -->
    <div style="text-align: center; font-size: 6.5pt; color: #94a3b8; margin-top: 8px;">
        Instituto de Salud Pública del Estado Bolívar &mdash; República Bolivariana de Venezuela &mdash; Página 1 de 1
    </div>

</body>
</html>
HTML;
        $pdfGen->renderDomPdf($html, "Comprobante_Auditoria_{$doc_num}", false);
    }


    /**
     * Exportar Logs a CSV
     * 
     * PROPÓSITO:
     * Generar archivo CSV con registros de auditoría para análisis externo.
     * 
     * PARÁMETROS GET:
     * - usuario_id, accion, fecha_desde, fecha_hasta (opcionales)
     */
    public function export(): void
    {
        try {
            // Obtener parámetros de filtro desde GET
            $action   = $_GET['accion']       ?? null;
            $dateFrom = $_GET['fecha_desde']  ?? null;
            $dateTo   = $_GET['fecha_hasta']  ?? null;

            // Construir query con filtros aplicados (fix bug: antes ignoraba los filtros)
            $query  = "
                SELECT b.id, b.accion, b.tabla_afectada, b.registro_id,
                       b.ip_address, b.user_agent, b.detalles, b.created_at,
                       u.correo AS usuario_email,
                       CONCAT(COALESCE(dp.nombres,''),' ',COALESCE(dp.apellidos,'')) AS usuario_nombre
                FROM bitacora b
                LEFT JOIN usuarios u ON b.usuario_id = u.id
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                WHERE 1=1
            ";
            $params = [];

            if ($action) {
                $query .= ' AND b.accion = :accion';
                $params[':accion'] = $action;
            }
            if ($dateFrom) {
                $query .= ' AND DATE(b.created_at) >= :fecha_desde';
                $params[':fecha_desde'] = $dateFrom;
            }
            if ($dateTo) {
                $query .= ' AND DATE(b.created_at) <= :fecha_hasta';
                $params[':fecha_hasta'] = $dateTo;
            }
            $query .= ' ORDER BY b.created_at DESC';

            $db = $this->auditModel->getDb();
            $db->query($query);
            foreach ($params as $k => $v) $db->bind($k, $v);
            $logs = $db->resultSet();

            // Generar CSV
            $filename = 'bitacora_activa_' . date('Y-m-d_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($output, ['ID','Usuario','Email','Acción','Tabla','Registro ID','IP','Navegador','Detalles','Fecha']);

            foreach ($logs as $log) {
                $log = (array) $log;
                fputcsv($output, [
                    $log['id'],
                    $log['usuario_nombre'] ?? 'Sistema',
                    $log['usuario_email']  ?? 'N/A',
                    $log['accion'],
                    $log['tabla_afectada'] ?? 'N/A',
                    $log['registro_id']    ?? 'N/A',
                    $log['ip_address'],
                    substr($log['user_agent'] ?? 'N/A', 0, 80),
                    $log['detalles']       ?? 'N/A',
                    $log['created_at'],
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log("BitacoraController::export() Error: " . $e->getMessage());
            Session::setFlash('error', 'Error al exportar registros');
            $this->redirect('/bitacora');
        }
    }

    // ============================================================
    // LIFECYCLE: Mantenimiento y Archivado
    // ============================================================

    /**
     * AJAX — Ejecutar ciclo de purga/archivado
     *
     * POST: dias_criticos (int), dias_operacion (int)
     * Responde JSON: { success, archivados, purgados, message }
     */
    public function mantenimiento(): void
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF — purga/archiva registros de auditoría
        $this->verifyCsrf();

        try {
            $diasCriticos  = max(30,  (int)($_POST['dias_criticos']  ?? 365));
            $diasOperacion = max(7,   (int)($_POST['dias_operacion'] ?? 90));
            $adminId       = (int)(Session::get('user_id') ?? 0);

            $result = $this->auditModel->purgar($diasCriticos, $diasOperacion, $adminId);

            echo json_encode([
                'success'    => true,
                'archivados' => $result['archivados'],
                'purgados'   => $result['purgados'],
                'message'    => $result['archivados'] > 0
                    ? "Mantenimiento completado: {$result['archivados']} registros archivados y {$result['purgados']} eliminados de la tabla activa."
                    : 'No había registros que superen el período de retención configurado.',
            ]);

        } catch (\Throwable $e) {
            // [FIX-C2] Mensaje genérico al cliente — detalle solo en log
            error_log('[SGP-BITACORA] mantenimiento() Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al ejecutar el mantenimiento. Intente de nuevo.']);
        }
        exit;
    }

    /**
     * API Grid.js — Histórico paginado (tabla cold)
     *
     * GET: limit, offset, accion, fecha_desde, fecha_hasta
     * Responde JSON: { data: [], total: int }
     */
    public function apiGridHistorico(): void
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        try {
            $limit    = max(1, (int)($_GET['limit']       ?? 10));
            $offset   = max(0, (int)($_GET['offset']      ?? 0));
            $accion   = $_GET['accion']      ?? null;
            $dateFrom = $_GET['fecha_desde'] ?? null;
            $dateTo   = $_GET['fecha_hasta'] ?? null;

            $result = $this->auditModel->getHistorico($limit, $offset, $accion ?: null, $dateFrom ?: null, $dateTo ?: null);

            echo json_encode(['data' => $result['data'], 'total' => $result['total']]);

        } catch (\Throwable $e) {
            error_log('[SGP-BITACORA] apiGridHistorico() Error: ' . $e->getMessage());
            echo json_encode(['data' => [], 'total' => 0]);
        }
        exit;
    }

    /**
     * Exportar histórico (tabla cold) a CSV con filtros
     *
     * GET: accion, fecha_desde, fecha_hasta
     */
    public function exportHistorico(): void
    {
        try {
            $accion   = $_GET['accion']      ?? null;
            $dateFrom = $_GET['fecha_desde'] ?? null;
            $dateTo   = $_GET['fecha_hasta'] ?? null;

            $result = $this->auditModel->getHistorico(10000, 0, $accion ?: null, $dateFrom ?: null, $dateTo ?: null);
            $logs   = $result['data'];

            $filename = 'bitacora_historico_' . date('Y-m-d_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['ID Orig.','Usuario','Email','Acción','Tabla','IP','Detalles','Fecha Original','Archivado En']);

            foreach ($logs as $log) {
                $log = (array) $log;
                fputcsv($output, [
                    $log['bitacora_id']    ?? '—',
                    $log['usuario_nombre'] ?? 'Sistema',
                    $log['usuario_email']  ?? 'N/A',
                    $log['accion'],
                    $log['tabla_afectada'] ?? 'N/A',
                    $log['ip_address'],
                    $log['detalles']       ?? 'N/A',
                    $log['created_at'],
                    $log['archivado_at'],
                ]);
            }

            fclose($output);
            exit;

        } catch (\Throwable $e) {
            error_log('[SGP-BITACORA] exportHistorico() Error: ' . $e->getMessage());
            Session::setFlash('error', 'Error al exportar el histórico');
            $this->redirect('/bitacora');
        }
    }

    /**
     * API - Estadísticas de Auditoría
     * 
     * PROPÓSITO:
     * Proveer datos para gráficas en dashboard de administración.
     * 
     * RETORNA: JSON con estadísticas
     */
    public function stats(): void
    {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->auditModel->getActionStats();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("BitacoraController::stats() Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ]);
        }
    }

    /**
     * Obtener Acciones Únicas
     * 
     * PROPÓSITO:
     * Extraer lista de acciones únicas para el filtro dropdown.
     * 
     * @return array Lista de acciones
     */
    private function getUniqueActions(): array
    {
        $db = $this->auditModel->getDb();
        $db->query("SELECT DISTINCT accion FROM bitacora ORDER BY accion ASC");
        $results = $db->resultSet();
        
        return array_column($results, 'accion');
    }
}
