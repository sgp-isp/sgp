<?php
/**
 * AsistenciasController — Panel de Control de Asistencias (Admin)
 *
 * RUTAS:
 *   GET  /asistencias                → index()            Lista de hoy
 *   GET  /asistencias/historial      → historial()        Búsqueda por rango/pasante
 *   POST /asistencias/registro_manual → registro_manual() JSON — justificar/registrar manual
 *
 * @version 2.0 — Fase 6
 */
class AsistenciasController extends Controller
{
    private $db;
    private $asistenciaModel;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        $config  = require '../app/config/config.php';
        $this->db = new Database($config['db']);
        $this->asistenciaModel = $this->model('Asistencia');
        require_once APPROOT . '/models/AuditModel.php';
    }

    // ────────────────────────────────────────────────────────────────
    // VISTA PRINCIPAL — Asistencias de Hoy
    // ────────────────────────────────────────────────────────────────

    /**
     * index() — Lista registros de asistencia según rango de fecha.
     * Soporta vistas Diaria (hoy), Semanal (semana actual) y Mensual (mes actual).
     */
    public function index(): void
    {
        $rolId  = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        // Permitir acceso a SuperAdmin (0), Admin (1), Tutor (2) y Pasante (3)
        if (!in_array($rolId, [0, 1, 2, 3])) {
            Session::setFlash('error', 'Sin permisos para esta sección.');
            $this->redirect('/dashboard');
            return;
        }

        // ── AUTO-FILL SILENCIOSO ────────────────────────────────────
        // Solo Admins y Tutores disparan el relleno (no por cada pasante que entra).
        // Se ejecuta al cargar la vista; INSERT IGNORE garantiza idempotencia.
        if ($rolId === 1 || $rolId === 2) {
            $this->asistenciaModel->rellenarDiasVacios();
            $this->asistenciaModel->autoFinalizarVencidos();
        }

        $vista = $_GET['vista'] ?? 'diaria'; // 'diaria', 'semanal', 'mensual', 'anual'
        $hoy = date('Y-m-d');
        
        // Determinar fechas de inicio y fin según el rango
        $fechaInicio = $hoy;
        $fechaFin    = $hoy;
        $tituloRango = 'del día';

        $paramsUrl = [
            'fecha'      => $_GET['fecha']      ?? $hoy,
            'semana'     => $_GET['semana']      ?? date('W'),
            'mes'        => str_pad($_GET['mes'] ?? date('m'), 2, '0', STR_PAD_LEFT),
            'anio'       => $_GET['anio']        ?? date('Y'),
            'periodo_id' => (int)($_GET['periodo_id'] ?? 0)
        ];

        // Variables período (para vista anual)
        $periodosFiltro      = [];
        $periodoSeleccionado = null;
        $horasMetaPeriodo    = 1440;

        // Inicializar variables para las vistas (evitar Undefined Warnings)
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $nombreMes  = $meses[$paramsUrl['mes']] ?? '';
        $anioActual = $paramsUrl['anio'];
        $mesNum     = (int)$paramsUrl['mes'];
        $anioNum    = (int)$paramsUrl['anio'];
        $mesAnt     = $mesNum === 1  ? 12 : $mesNum - 1;
        $anioAnt    = $mesNum === 1  ? $anioNum - 1 : $anioNum;
        $mesSig     = $mesNum === 12 ? 1  : $mesNum + 1;
        $anioSig    = $mesNum === 12 ? $anioNum + 1 : $anioNum;
        $urlAnt     = URLROOT . '/asistencias?vista=mensual&mes=' . str_pad($mesAnt, 2, '0', STR_PAD_LEFT) . '&anio=' . $anioAnt;
        $urlSig     = URLROOT . '/asistencias?vista=mensual&mes=' . str_pad($mesSig, 2, '0', STR_PAD_LEFT) . '&anio=' . $anioSig;

        // KPIs Anuales (Inicialización)
        $historicoAnual   = 0;
        $finalizadosAnual = 0;
        $enCursoAnual     = 0;

        // Cargar solo períodos que tengan al menos un pasante regular asignado
        $this->db->query("
            SELECT pa.id, pa.nombre, pa.fecha_inicio, pa.fecha_fin, pa.estado
            FROM periodos_academicos pa
            WHERE EXISTS (
                SELECT 1 FROM datos_pasante dpa
                WHERE dpa.periodo_id = pa.id
                  AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
            )
            ORDER BY pa.fecha_inicio DESC
        ");
        $periodosFiltro = $this->db->resultSet();

        $fechaInicioQuery = null;
        $fechaFinQuery    = null;

        if ($vista === 'diaria') {
            $fechaInicio = $paramsUrl['fecha'];
            $fechaFin    = $paramsUrl['fecha'];
            $tituloRango = 'del ' . date('d/m/Y', strtotime($fechaInicio));
        } elseif ($vista === 'semanal') {
            $dto = new DateTime();
            $dto->setISODate((int)$paramsUrl['anio'], (int)$paramsUrl['semana']);
            $fechaInicio = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $fechaFin = $dto->format('Y-m-d');
            $tituloRango = "de la semana " . $paramsUrl['semana'] . " del " . $paramsUrl['anio'];
        } elseif ($vista === 'mensual') {
            $fechaInicio = $paramsUrl['anio'] . '-' . str_pad($paramsUrl['mes'], 2, '0', STR_PAD_LEFT) . '-01';
            $fechaFin    = date('Y-m-t', strtotime($fechaInicio));
            $tituloRango = "de " . ($nombreMes) . " " . $anioActual;
        } elseif ($vista === 'anual') {
            // Determinar período seleccionado: GET param → activo → más reciente
            $periodoIdSel = $paramsUrl['periodo_id'];
            if ($periodoIdSel === 0) {
                foreach ($periodosFiltro as $_p) {
                    if ($_p->estado === 'Activo') { $periodoIdSel = (int)$_p->id; break; }
                }
                if ($periodoIdSel === 0 && !empty($periodosFiltro)) {
                    $periodoIdSel = (int)$periodosFiltro[0]->id;
                }
            }
            $paramsUrl['periodo_id'] = $periodoIdSel;

            foreach ($periodosFiltro as $_p) {
                if ((int)$_p->id === $periodoIdSel) { $periodoSeleccionado = $_p; break; }
            }

            if ($periodoSeleccionado) {
                $fechaInicio = $periodoSeleccionado->fecha_inicio;
                $fechaFin    = $periodoSeleccionado->fecha_fin;
                $tituloRango = "del período " . $periodoSeleccionado->nombre;
                // Contar días L-V reales del período (para eje de gráfico)
                $_pc = new DateTime($fechaInicio); $_pe = new DateTime($fechaFin); $_lv = 0;
                while ($_pc <= $_pe) { if ((int)$_pc->format('N') < 6) $_lv++; $_pc->modify('+1 day'); }
                $horasMetaPeriodo = max(160, $_lv * 8);
                // La query de asistencias NO se acota a las fechas del período:
                // dpa.periodo_id ya filtra correctamente, y las fechas individuales
                // (fecha_inicio_pasantia/fecha_fin_estimada) pueden exceder las del
                // período nominal — acotar aquí recortaba horas de pasantes cuya
                // fecha real de culminación cae después del fecha_fin del período.
                $fechaInicioQuery = '2000-01-01';
                $fechaFinQuery    = date('Y-m-d', strtotime('+1 year'));
            } else {
                $fechaInicio = date('Y-01-01');
                $fechaFin    = date('Y-12-31');
                $tituloRango = "del año " . date('Y');
                $horasMetaPeriodo = 1440;
            }
        }

        // Filtro por departamento para tutor (rol 2)
        $whereTutor = "";
        $tutorDeptId = 0;
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $tutorDeptId = (int)($this->db->single()->departamento_id ?? 0);
            if ($tutorDeptId > 0) {
                $whereTutor = " AND dpa.departamento_asignado_id = {$tutorDeptId} ";
            }
        }

        // Filtro por período (solo en vista anual)
        $wherePeriodo = "";
        if ($vista === 'anual' && isset($periodoIdSel) && $periodoIdSel > 0) {
            $wherePeriodo = " AND dpa.periodo_id = {$periodoIdSel} ";
        }

        // Si es pasante, solo ve sus propios registros
        $wherePasante = "";
        $filtroBusquedaPasante = (int)($_GET['pasante_id'] ?? 0);

        if ($rolId === 3) {
            $wherePasante = " AND a.pasante_id = :uid_pasante ";
        } elseif ($filtroBusquedaPasante > 0) {
            $wherePasante = " AND a.pasante_id = :uid_filtro ";
        }

        // Excluir Finalizados en vistas operacionales (diaria/semanal/mensual).
        // La vista anual es histórica → muestra a todos (activo + finalizado).
        $whereNoFinalizado = ($filtroBusquedaPasante > 0 || $vista === 'anual')
            ? ""
            : " AND COALESCE(dpa.estado_pasantia, 'Sin Asignar') != 'Finalizado' ";

        // Registros en el rango con datos del pasante (3NF: JOINs a tablas normalizadas)
        $this->db->query("
            SELECT
                a.id,
                a.fecha,
                a.hora_registro,
                a.estado,
                a.metodo,
                a.motivo_justificacion,
                df.nombre      AS feriado_nombre,
                a.ruta_evidencia,
                a.es_auto_fill,
                u.id           AS pasante_id,
                u.cedula,
                u.avatar,
                u.estado                                             AS estado_cuenta,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar')        AS estado_pasantia,
                dp.nombres,
                dp.apellidos,
                d.nombre       AS departamento_nombre,
                COALESCE(NULLIF(dpa.horas_meta, 0), NULLIF(ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 0), 1440) AS horas_meta,
                COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion_nombre,
                pa.nombre      AS periodo_nombre,
                a.es_retardo
            FROM asistencias a
            INNER JOIN usuarios          u   ON u.id          = a.pasante_id
            LEFT  JOIN datos_personales  dp  ON dp.usuario_id = u.id
            LEFT  JOIN datos_pasante     dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos     d   ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            LEFT  JOIN instituciones     inst ON inst.id = dpa.institucion_id
            LEFT  JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT  JOIN dias_feriados     df  ON df.fecha = a.fecha AND df.es_laborable = 0
            WHERE a.fecha >= :fecha_inicio AND a.fecha <= :fecha_fin $whereTutor $wherePasante $wherePeriodo $whereNoFinalizado
              AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
            ORDER BY a.fecha DESC, a.hora_registro DESC
        ");
        $this->db->bind(':fecha_inicio', $fechaInicioQuery ?? $fechaInicio);
        $this->db->bind(':fecha_fin', $fechaFinQuery ?? $fechaFin);
        // tutor_id no se bindea — dept_id ya está interpolado en $whereTutor
        if ($rolId === 3) {
            $this->db->bind(':uid_pasante', $userId);
        } elseif ($filtroBusquedaPasante > 0) {
            $this->db->bind(':uid_filtro', $filtroBusquedaPasante);
        }
        $registrosLista = $this->db->resultSet();

        // Obtener la cantidad de pasantes activos en total (para los cálculos y el select de 'filtro')
        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos,
                   d.nombre AS departamento_nombre,
                   COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                   COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin,
                   COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                   COALESCE((
                       SELECT COUNT(*) * 8
                       FROM asistencias a2
                       WHERE a2.pasante_id = u.id
                         AND a2.estado != 'Anulado'
                         AND a2.fecha >= COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)
                   ), 0) AS horas_acumuladas
            FROM   usuarios u
            LEFT JOIN datos_personales  dp  ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante     dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos     d   ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE  u.rol_id = 3
              AND  u.estado = 'activo'
              AND  COALESCE(dpa.estado_pasantia, 'Sin Asignar') = 'Activo'
              AND  COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
              $whereTutor $wherePeriodo
            ORDER BY IFNULL(dp.apellidos, u.correo) ASC
        ");
        // dept_id ya interpolado — no requiere bind adicional
        $todosActivos = $this->db->resultSet();
        $totalActivos = count($todosActivos);

        // Cálculos dinámicos
        $presentes    = count(array_filter($registrosLista, fn($r) => $r->estado === 'Presente'));
        $justificados = count(array_filter($registrosLista, fn($r) => $r->estado === 'Justificado'));
        $ausentes     = count(array_filter($registrosLista, fn($r) => $r->estado === 'Ausente'));
        $sinMarcar    = [];
        $porcentajeAsistencia = -1;

        if ($vista === 'diaria') {
            // Ausentes HOY / Diaria: activos sin registro hoy Y que aún no completaron sus horas
            $marcadasIds = array_map(fn($r) => (int)$r->pasante_id, $registrosLista);
            $sinMarcar = array_filter(
                $todosActivos,
                fn($p) => !in_array((int)$p->id, $marcadasIds)
                       && (float)($p->horas_acumuladas ?? 0) < (float)($p->horas_meta ?? 1440)
            );
            // Actualizamos la métrica visual sumando los que no marcaron a los que están "Ausente" formalmente.
            $ausentes += count($sinMarcar);

            if ($totalActivos > 0) {
                $porcentajeAsistencia = round(($presentes + $justificados) / $totalActivos * 100);
            }
        }

        // ==========================================
        // PROCESADOR DE DATOS: VISTA SEMANAL
        // ==========================================
        $datosSemanales = [];
        $navSemana = [];
        
        if ($vista === 'semanal') {
            // 1. Inicializar la estructura con TODOS los pasantes activos (Incluso si no han marcado)
            foreach ($todosActivos as $pasante) {
                // Adaptado para usar objetos (como es costumbre de PDO en este proyecto)
                $depto = $pasante->departamento_nombre ?? 'Sin Asignar';
                $pasanteId = $pasante->id;
                $nombreCompleto = trim(($pasante->apellidos ?? '') . ', ' . ($pasante->nombres ?? ''));
            
                if (!isset($datosSemanales[$depto])) {
                    $datosSemanales[$depto] = [];
                }
                $datosSemanales[$depto][$pasanteId] = [
                    'nombre'       => $nombreCompleto,
                    'dias'         => [1 => '-', 2 => '-', 3 => '-', 4 => '-', 5 => '-'],
                    'motivos'      => [1 => '', 2 => '', 3 => '', 4 => '', 5 => ''],
                    'totales'      => ['P' => 0, 'A' => 0, 'J' => 0],
                    'fecha_inicio' => $pasante->fecha_inicio ?? null,
                    'fecha_fin'    => $pasante->fecha_fin    ?? null,
                ];
            }
            
            // 2. Rellenar el "Mapa de Calor" con los registros reales de la semana
            foreach ($registrosLista as $reg) {
                $depto = $reg->departamento_nombre ?? 'Sin Asignar';
                $pasanteId = $reg->pasante_id ?? null; 
                if ($pasanteId && isset($datosSemanales[$depto][$pasanteId])) {
                    $diaSemana = date('N', strtotime($reg->fecha)); // 1 (Lun) a 7 (Dom)
                    if ($diaSemana <= 5) { // Solo procesamos de Lunes a Viernes
                        $letra = '-';
                        if (stripos($reg->estado, 'Presente') !== false) { 
                            $letra = 'P'; 
                            $datosSemanales[$depto][$pasanteId]['totales']['P']++; 
                        } elseif (stripos($reg->estado, 'Ausente') !== false) { 
                            $letra = 'A'; 
                            $datosSemanales[$depto][$pasanteId]['totales']['A']++; 
                        } elseif (stripos($reg->estado, 'Justificado') !== false) { 
                            $letra = 'J'; 
                            $datosSemanales[$depto][$pasanteId]['totales']['J']++; 
                        }
                        $datosSemanales[$depto][$pasanteId]['dias'][$diaSemana] = $letra;
                        $feriadoNombre = trim($reg->feriado_nombre ?? '');
                        $motivoRaw     = trim($reg->motivo_justificacion ?? '');
                        $datosSemanales[$depto][$pasanteId]['motivos'][$diaSemana] =
                            ($letra === 'J' && $feriadoNombre !== '') ? $feriadoNombre : $motivoRaw;
                    }
                }
            }
            
            // 3. Lógica de Paginación Semanal (Botones Ant y Sig)
            $semanaActual = $paramsUrl['semana'] ?? date('W');
            $anioActual = $paramsUrl['anio'] ?? date('Y');
            
            $fechaBase = new DateTime();
            $fechaBase->setISODate((int)$anioActual, (int)$semanaActual);
            $fechaAnt = clone $fechaBase;
            $fechaAnt->modify('-1 week');
            $fechaSig = clone $fechaBase;
            $fechaSig->modify('+1 week');
            
            $navSemana = [
                'ant_url' => URLROOT . "/asistencias?vista=semanal&semana=" . $fechaAnt->format('W') . "&anio=" . $fechaAnt->format('Y'),
                'sig_url' => URLROOT . "/asistencias?vista=semanal&semana=" . $fechaSig->format('W') . "&anio=" . $fechaSig->format('Y'),
                'texto'   => "Semana " . $semanaActual . " - " . $anioActual
            ];
        }

        // ==========================================
        // PROCESADOR DE DATOS: VISTA ANUAL (Kpis)
        // ==========================================
        if ($vista === 'anual') {
            $pasantesVistos = [];
            foreach ($registrosLista as $reg) {
                $pid = $reg->pasante_id;
                if (!isset($pasantesVistos[$pid])) {
                    $pasantesVistos[$pid] = [
                        'presentes' => 0,
                        'horas_meta' => (int)($reg->horas_meta ?? 1440)
                    ];
                }
                if (strtolower($reg->estado) === 'presente' || strtolower($reg->estado) === 'justificado') {
                    $pasantesVistos[$pid]['presentes']++;
                }
            }
            $historicoAnual = count($pasantesVistos);
            foreach ($pasantesVistos as $pData) {
                $hrs = $pData['presentes'] * 8;
                if ($hrs >= $pData['horas_meta']) {
                    $finalizadosAnual++;
                } else {
                    $enCursoAnual++;
                }
            }
        }

        // ==========================================
        // PROCESADOR DE DATOS: VISTA MENSUAL
        // ==========================================
        $bentoMensual     = [];
        $pasantesRetardos = [];
        $healthIndex      = 100;
        $totalEventos     = 0;
        $chartSemanasJson = "[]";
        $chartPctsJson    = "[]";
        $pasantesParaJS   = [];
        $resumenDeptosJS  = [];
        $calendarioJS     = [];
        $daysJS           = [];
        
        if ($vista === 'mensual') {
            $totalAsistencias = 0;
            $totalFaltasGlobal = 0;
            $totalJustificadosGlobal = 0;

            foreach($registrosLista as $reg) {
                $depto = $reg->departamento_nombre ?? 'Sin Asignar';
                $pid        = $reg->pasante_id ?? 'P'.$reg->id;
                $pasanteId  = (int)($reg->pasante_id ?? 0); // ID numérico real para el modal
                
                if(!isset($bentoMensual[$depto])) {
                    $bentoMensual[$depto] = [];
                }
                if(!isset($bentoMensual[$depto][$pid])) {
                    $rawApellidos = trim($reg->apellidos ?? '');
                    $rawNombres   = trim($reg->nombres ?? '');
                    $apellido1    = trim(explode(' ', $rawApellidos)[0] ?? 'A');
                    $nombre1      = trim(explode(' ', $rawNombres)[0]   ?? 'A');
                    $iniciales    = strtoupper(substr($nombre1, 0, 1) . substr($apellido1, 0, 1));
                    if(strlen(trim($iniciales)) < 2) $iniciales = strtoupper(substr($rawApellidos, 0, 2));
                    $nombreDisplay = ucwords(strtolower($rawNombres . ' ' . $rawApellidos));

                    $bentoMensual[$depto][$pid] = [
                        'nombre'      => $nombreDisplay,
                        'cedula'      => $reg->cedula ?? '',
                        'iniciales'   => $iniciales,
                        'pasante_id'  => $pasanteId, // ID real para el endpoint de detalle
                        'presentes'   => 0,
                        'ausentes'    => 0,
                        'justificados'=> 0,
                        'retardos'    => 0,
                        'history'     => []
                    ];
                }
                $estado = strtolower($reg->estado);
                if(strpos($estado, 'presente') !== false) {
                    $bentoMensual[$depto][$pid]['presentes']++;
                    array_unshift($bentoMensual[$depto][$pid]['history'], 'presente');
                } elseif(strpos($estado, 'ausente') !== false) {
                    $bentoMensual[$depto][$pid]['ausentes']++;
                    array_unshift($bentoMensual[$depto][$pid]['history'], 'ausente');
                } else {
                    $bentoMensual[$depto][$pid]['justificados']++;
                    array_unshift($bentoMensual[$depto][$pid]['history'], 'justificado');
                }
                if((int)($reg->es_retardo ?? 0) === 1) {
                    $bentoMensual[$depto][$pid]['retardos']++;
                }
                if(count($bentoMensual[$depto][$pid]['history']) > 5) {
                    array_pop($bentoMensual[$depto][$pid]['history']);
                }
            }

            $pasantesRetardos = [];
            foreach($bentoMensual as $depto => &$pasantesObj) {
                foreach($pasantesObj as $pid => &$rm) {
                    $totalAsistencias += $rm['presentes'];
                    $totalFaltasGlobal += $rm['ausentes'];
                    $totalJustificadosGlobal += $rm['justificados'];
                    if($rm['retardos'] > 0) {
                        $pasantesRetardos[] = [
                            'id'        => (int)($rm['pasante_id'] ?? 0), // ID numérico real
                            'nombre'    => $rm['nombre'], 
                            'depto'     => $depto, 
                            'retardos'  => $rm['retardos'], 
                            'iniciales' => $rm['iniciales']
                        ];
                    }
                }
            }
            usort($pasantesRetardos, function($a, $b) { return $b['retardos'] <=> $a['retardos']; });
            // No hacemos slice aquí, lo haremos en la vista con paginación JS si es necesario, 
            // o pasamos todo el histórico. El usuario pidió historial con paginación.
            $totalEventos = $totalAsistencias + $totalFaltasGlobal + $totalJustificadosGlobal;
            $healthIndex = $totalEventos > 0 ? round(($totalAsistencias / $totalEventos) * 100) : 100;

            // ===== ÁREA CHART: Datos por semana del mes =====
            $semanaData  = []; 
            if(!empty($registrosLista)) {
                foreach($registrosLista as $reg) {
                    $fechaReg = $reg->fecha ?? null;
                    if($fechaReg) {
                        $dia = (int)date('j', strtotime($fechaReg));
                        $semana = (int)ceil($dia / 7);
                        if(!isset($semanaData[$semana])) $semanaData[$semana] = ['pres'=>0,'total'=>0];
                        $semanaData[$semana]['total']++;
                        if(strpos(strtolower($reg->estado ?? ''), 'presente') !== false) {
                            $semanaData[$semana]['pres']++;
                        }
                    }
                }
            }
            $chartSemanas = []; $chartPcts = [];
            // Forzar 4 o 5 semanas según el mes
            $ultimoDiaMes = (int)date('t', strtotime($fechaInicio));
            $numSemanas = (int)ceil($ultimoDiaMes / 7);

            for($sw = 1; $sw <= $numSemanas; $sw++) {
                $chartSemanas[] = 'Sem ' . $sw;
                $d = $semanaData[$sw] ?? null;
                $chartPcts[] = ($d && $d['total'] > 0) ? round(($d['pres'] / $d['total']) * 100) : 0;
            }
            if(empty($chartPcts) || count(array_filter($chartPcts, fn($v) => $v !== null)) === 0) {
                $chartSemanas = ['Sem 1','Sem 2','Sem 3','Sem 4'];
                $chartPcts    = [$healthIndex, $healthIndex, $healthIndex, $healthIndex];
            }
            // Use JSON_HEX tags to prevent XSS issues
            $chartSemanasJson = json_encode($chartSemanas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $chartPctsJson    = json_encode($chartPcts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

            // ===== JS DATA: Pasantes para la Tabla Maestra y Top List =====
            $pasantesParaJS = [];
            foreach($bentoMensual as $depto => $pasantes) {
                foreach($pasantes as $pid => $rm) {
                    $pasantesParaJS[] = [
                        'n'  => $rm['nombre'],
                        'ci' => $rm['cedula'],
                        'd'  => $depto,
                        'p'  => $rm['presentes'],
                        'f'  => $rm['ausentes'],
                        'j'  => $rm['justificados'],
                        'av' => $rm['iniciales'],
                        'c'  => 'linear-gradient(135deg, #3b82f6, #2563eb)' // Color base
                    ];
                }
            }

            // ===== JS DATA: Resumen por Departamento =====
            $resumenDeptosJS = [];
            foreach($bentoMensual as $depto => $pasantes) {
                $dp = 0; $df = 0; $dj = 0;
                foreach($pasantes as $rm) {
                    $dp += $rm['presentes']; $df += $rm['ausentes']; $dj += $rm['justificados'];
                }
                $resumenDeptosJS[$depto] = [
                    'p' => $dp, 'f' => $df, 'j' => $dj, 't' => ($dp + $df + $dj)
                ];
            }

            // ===== FERIADOS DEL MES =====
            $feriadosMes = $this->asistenciaModel->getFeriadosEnRango($fechaInicio, $fechaFin);
            // Convertir a mapa dia => nombre para fácil acceso en vista
            $feriadosDia = [];
            foreach ($feriadosMes as $fecha => $desc) {
                $feriadosDia[(int)date('j', strtotime($fecha))] = $desc;
            }

            // ===== JS DATA: Calendario heatmap por día × departamento =====
            $daysJS  = [];
            $numDays = (int)date('t', strtotime($fechaInicio));
            for ($i = 1; $i <= $numDays; $i++) { $daysJS[] = $i; }

            // calPorDia['Todos'][7] = ['p'=>13,'a'=>2,'j'=>1]
            // calPorDia['RRHH'][7]  = ['p'=>4,'a'=>1,'j'=>0]
            $calPorDia = ['Todos' => []];
            foreach ($registrosLista as $reg) {
                $dia   = (int)date('j', strtotime($reg->fecha));
                $depto = $reg->departamento_nombre ?? 'Sin Asignar';
                $est   = strtolower($reg->estado ?? '');
                $key   = str_contains($est, 'presente') ? 'p' : (str_contains($est, 'ausente') ? 'a' : 'j');

                if (!isset($calPorDia['Todos'][$dia])) $calPorDia['Todos'][$dia] = ['p'=>0,'a'=>0,'j'=>0];
                $calPorDia['Todos'][$dia][$key]++;

                if (!isset($calPorDia[$depto][$dia])) $calPorDia[$depto][$dia] = ['p'=>0,'a'=>0,'j'=>0];
                $calPorDia[$depto][$dia][$key]++;
            }

            // ===== % POR PASANTE (para ranking riesgo/top) =====
            $pctPorPasante = [];
            foreach ($bentoMensual as $depto => $pasantes) {
                foreach ($pasantes as $pid => $rm) {
                    $total = $rm['presentes'] + $rm['ausentes'] + $rm['justificados'];
                    $pctPorPasante[] = [
                        'id'      => $pid,
                        'nombre'  => $rm['nombre'],
                        'iniciales'=> $rm['iniciales'],
                        'depto'   => $depto,
                        'p'       => $rm['presentes'],
                        'a'       => $rm['ausentes'],
                        'j'       => $rm['justificados'],
                        'total'   => $total,
                        'pct'     => $total > 0 ? round(($rm['presentes'] + $rm['justificados']) / $total * 100) : 0,
                    ];
                }
            }
            usort($pctPorPasante, fn($a,$b) => $b['pct'] <=> $a['pct']);

            // ===== AUSENCIAS DEL MES (para panel retroactivo) =====
            $ausenciasMes = [];
            foreach ($registrosLista as $reg) {
                if (strtolower($reg->estado ?? '') === 'ausente') {
                    $ausenciasMes[] = $reg;
                }
            }
            // Orden cronológico ascendente para la tabla
            usort($ausenciasMes, fn($a,$b) => strcmp($a->fecha, $b->fecha));

            // Legado — mantener compatibilidad con código que aún use $calendarioJS
            $calendarioJS = [];
            foreach ($calPorDia['Todos'] as $dia => $cnt) {
                $total = $cnt['p'] + $cnt['a'] + $cnt['j'];
                if ($total === 0) { $calendarioJS[$dia] = 'em'; continue; }
                $pct = ($cnt['p'] + $cnt['j']) / $total * 100;
                $calendarioJS[$dia] = $pct >= 85 ? 'p' : ($pct >= 60 ? 'j' : 'a');
            }
        }

        // Todos los departamentos activos (para placeholders en vista mensual)
        $this->db->query("SELECT nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        $todosDeptos = array_column($this->db->resultSet(), 'nombre');

        // ── Determinar si hoy es día hábil (L-V y no feriado no-laborable) ──
        $dowHoy = (int) date('N'); // 1=Lun … 6=Sáb, 7=Dom
        $esHabilHoy      = true;
        $motivoNoHabilHoy = '';

        if ($dowHoy >= 6) {
            $esHabilHoy       = false;
            $motivoNoHabilHoy = $dowHoy === 6 ? 'Hoy es sábado' : 'Hoy es domingo';
        } else {
            $this->db->query("SELECT nombre FROM dias_feriados WHERE fecha = :f AND es_laborable = 0 LIMIT 1");
            $this->db->bind(':f', $hoy);
            $feriadoHoy = $this->db->single();
            if ($feriadoHoy) {
                $esHabilHoy       = false;
                $motivoNoHabilHoy = 'Hoy es feriado: ' . $feriadoHoy->nombre;
            }
        }

        $this->view('asistencias/index', [
            'title'         => 'Asistencias — ' . ucfirst($vista),
            'vista'         => $vista,
            'paramsUrl'     => $paramsUrl, // Para repoblar los selects en la UI
            'filtroPasante' => $filtroBusquedaPasante,
            'fechaInicio'   => $fechaInicio,
            'fechaFin'      => $fechaFin,
            'tituloRango'   => $tituloRango,
            'hoy'           => $hoy,
            'registrosLista'=> $registrosLista,
            'sinMarcar'     => array_values($sinMarcar),
            'sinMarcarMes'  => array_values($sinMarcar), // Reutilizamos si no hay lógica específica
            'presentes'     => $presentes,
            'justificados'  => $justificados,
            'ausentes'      => $ausentes,
            'totalActivos'  => $totalActivos,
            'pasantesActivos' => $todosActivos, // para modals y listados
            'porcAsistencia'=> $porcentajeAsistencia,
            'datosSemanales'=> $datosSemanales,
            'navSemana'     => $navSemana,
            'bentoMensual'  => $bentoMensual,
            'pasantesRetardos'=> $pasantesRetardos,
            'healthIndex'   => $healthIndex,
            'totalEventosMensual'=> $totalEventos,
            'chartSemanasJson' => $chartSemanasJson,
            'chartPctsJson' => $chartPctsJson,
            'pasantesParaJS' => $pasantesParaJS,
            'resumenDeptosJS' => $resumenDeptosJS,
            'calendarioJS' => (object)$calendarioJS,
            'daysJS' => $daysJS,
            'nombreMes' => $nombreMes,
            'anioActual' => $anioActual,
            'urlAnt' => $urlAnt,
            'urlSig' => $urlSig,
            'historicoAnual' => $historicoAnual,
            'finalizadosAnual' => $finalizadosAnual,
            'enCursoAnual' => $enCursoAnual,
            'calPorDia'    => $calPorDia    ?? [],
            'feriadosDia'  => $feriadosDia  ?? [],
            'pctPorPasante'=> $pctPorPasante ?? [],
            'ausenciasMes' => $ausenciasMes  ?? [],
            'todosDeptos'         => $todosDeptos        ?? [],
            'periodosFiltro'      => $periodosFiltro      ?? [],
            'periodoSeleccionado' => $periodoSeleccionado ?? null,
            'periodoIdSel'        => $periodoIdSel        ?? 0,
            'horasMetaPeriodo'    => $horasMetaPeriodo    ?? 1440,
            'esHabilHoy'          => $esHabilHoy,
            'motivoNoHabilHoy'    => $motivoNoHabilHoy,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // ENDPOINT JSON — Registro Manual / Justificación
    // ────────────────────────────────────────────────────────────────

    /**
     * registro_manual() — El Admin justifica una falta o registra manualmente.
     *
     * POST /asistencias/registro_manual
     * Body (form-data):
     *   pasante_id          int      requerido
     *   fecha               Y-m-d    requerido  (puede ser hoy o retroactivo)
     *   estado              'Presente'|'Justificado'
     *   motivo_justificacion text    requerido si estado = Justificado
     *   evidencia           file     opcional (imagen del récipe médico)
     */
    public function registro_manual(): void
    {
        header('Content-Type: application/json');

        if (!in_array((int)Session::get('role_id'), [0, 1, 2])) {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos para esta acción.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        // Captura de datos
        $pasanteId = (int)($_POST['pasante_id']           ?? 0);
        $fecha     = trim($_POST['fecha']                  ?? date('Y-m-d'));
        $estado    = trim($_POST['estado']                 ?? 'Justificado');
        $motivo    = trim($_POST['motivo_justificacion']   ?? '');

        // Validaciones
        if ($pasanteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Selecciona un pasante válido.']);
            exit;
        }

        if (!in_array($estado, ['Presente', 'Justificado', 'Ausente'])) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido.']);
            exit;
        }

        if ($estado === 'Justificado' && !$motivo) {
            echo json_encode(['success' => false, 'message' => 'El motivo de justificación es obligatorio.']);
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido.']);
            exit;
        }

        // [FIX-N-7] No se validaba que la fecha no fuera futura — un Admin/Tutor
        // podía registrar asistencia para una fecha que aún no ha ocurrido.
        if ($fecha > date('Y-m-d')) {
            echo json_encode(['success' => false, 'message' => 'No se puede registrar asistencia para una fecha futura.']);
            exit;
        }

        // ── Tutor: verificar que el pasante pertenece a su departamento ──
        if ((int)Session::get('role_id') === 2) {
            $tutorUserId = (int)Session::get('user_id');
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $tutorUserId);
            $tutorDept = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $pasanteDept = (int)($this->db->single()->departamento_asignado_id ?? -1);

            if ($tutorDept !== $pasanteDept) {
                echo json_encode(['success' => false, 'message' => 'No puede registrar asistencia de pasantes fuera de su departamento.']);
                exit;
            }
        }

        // ── Validar que la fecha no cae en fin de semana ──────────
        $diaSemana = (int)(new DateTime($fecha))->format('N'); // 1=Lun…7=Dom
        if ($diaSemana >= 6) {
            $nombreDia = $diaSemana === 6 ? 'sábado' : 'domingo';
            echo json_encode([
                'success' => false,
                'message' => "La fecha seleccionada es {$nombreDia}. Solo se permite registrar asistencia en días hábiles (lunes a viernes).",
            ]);
            exit;
        }

        // ── Verificar si la fecha es un día feriado NO laborable ─────
        // Los feriados laborables (es_laborable=1) se tratan como días normales.
        $this->db->query("SELECT nombre FROM dias_feriados WHERE fecha = :fecha AND es_laborable = 0 LIMIT 1");
        $this->db->bind(':fecha', $fecha);
        $esFeriado = $this->db->single();
        $feriadoNombre = $esFeriado ? $esFeriado->nombre : null;

        // Bloquear Presente en día feriado no laborable (igual que el bloqueo de fin de semana)
        if ($feriadoNombre !== null && $estado === 'Presente') {
            echo json_encode([
                'success' => false,
                'message' => "\"{$feriadoNombre}\" es un día feriado no laborable. No se puede registrar asistencia como Presente.",
            ]);
            exit;
        }

        // ── Bloquear Presente para pasantes con pasantía finalizada o horas completas ──
        if ($estado === 'Presente') {
            $this->db->query("SELECT estado_pasantia FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $dp = $this->db->single();
            if ($dp && $dp->estado_pasantia === 'Finalizado') {
                echo json_encode([
                    'success' => false,
                    'message' => 'La pasantía de este estudiante ya está finalizada. Solo se permiten justificaciones retroactivas.',
                ]);
                exit;
            }

            // Bloquear si ya acumuló todas sus horas meta (evitar superar 1440h o horas_meta)
            $this->db->query("
                SELECT COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                       COALESCE((
                           SELECT COUNT(*) * 8
                           FROM asistencias a2
                           WHERE a2.pasante_id = :pid2
                             AND a2.estado != 'Anulado'
                             AND a2.fecha >= COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)
                       ), 0) AS horas_acumuladas
                FROM datos_pasante dpa
                LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                WHERE dpa.usuario_id = :pid3
                LIMIT 1
            ");
            $this->db->bind(':pid2', $pasanteId);
            $this->db->bind(':pid3', $pasanteId);
            $limitInfo = $this->db->single();
            if ($limitInfo && (float)$limitInfo->horas_acumuladas >= (float)$limitInfo->horas_meta) {
                $hMeta = (int)$limitInfo->horas_meta;
                echo json_encode([
                    'success' => false,
                    'message' => "Este pasante ya completó sus {$hMeta} horas de pasantía. No se registrará más asistencia.",
                ]);
                exit;
            }
        }

        // ── Upload de evidencia (récipe médico) ────────────────────
        $rutaEvidencia = null;

        if (!empty($_FILES['evidencia']['tmp_name'])) {
            $file      = $_FILES['evidencia'];
            $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed   = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Formato de archivo no permitido. Use JPG, PNG o PDF.']);
                exit;
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5 MB máx
                echo json_encode(['success' => false, 'message' => 'El archivo supera el límite de 5 MB.']);
                exit;
            }

            // [FIX-V-4] Validar el tipo MIME real (no confiar en la extensión del nombre).
            $finfoEvid = new finfo(FILEINFO_MIME_TYPE);
            $mimeEvid  = $finfoEvid->file($file['tmp_name']);
            $mimesPermitidosEvid = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (!in_array($mimeEvid, $mimesPermitidosEvid, true)) {
                echo json_encode(['success' => false, 'message' => 'El contenido del archivo no corresponde a una imagen o PDF válido.']);
                exit;
            }

            // [FIX-ED-2] Fuera de public/ — antes cualquiera con la URL podía ver
            // récipes médicos sin sesión ni verificación de pertenencia. Ahora se
            // sirven solo a través de verEvidencia(), que sí valida acceso.
            $uploadDir = APPROOT . '/storage/uploads/evidencias/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $nombreArchivo   = 'evidencia_' . $pasanteId . '_' . str_replace('-', '', $fecha) . '_' . time() . '.' . $ext;
            $destinoCompleto = $uploadDir . $nombreArchivo;

            if (!move_uploaded_file($file['tmp_name'], $destinoCompleto)) {
                echo json_encode(['success' => false, 'message' => 'Error al subir el archivo.']);
                exit;
            }

            // Se guarda solo el nombre — filas antiguas con el prefijo
            // "/uploads/evidencias/" siguen resolviendo bien porque
            // verEvidencia() y las vistas usan basename() al consumir este campo.
            $rutaEvidencia = $nombreArchivo;
        }

        $registroId = (int)($_POST['registro_id'] ?? 0);
        $existente = null;

        if ($registroId > 0) {
            // Edición de un registro existente por su ID explícito
            $this->db->query("SELECT id, estado FROM asistencias WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $registroId);
            $existente = $this->db->single();
        } else {
            // ── Verificar si ya existe registro para esa fecha ─────────
            $this->db->query("
                SELECT id, estado FROM asistencias
                WHERE pasante_id = :pid AND fecha = :fecha
                LIMIT 1
            ");
            $this->db->bind(':pid',   $pasanteId);
            $this->db->bind(':fecha', $fecha);
            $existente = $this->db->single();
        }

        // ── DETECCIÓN DE RETARDO ──────────────────────────────────
        // Un registro es_retardo cuando:
        //   estado = 'Presente'  Y  hora_registro > 09:00:00
        $horaRegistro    = date('H:i:s');
        $esRetardo       = ($estado === 'Presente' && $horaRegistro > '09:00:00') ? 1 : 0;
        $horasCalculadas = 8.00;

        if ($existente) {
            // Actualizar el registro existente
            $this->db->query("
                UPDATE asistencias
                SET estado               = :estado,
                    motivo_justificacion = :motivo,
                    horas_calculadas     = :horas,
                    ruta_evidencia       = COALESCE(:evidencia, ruta_evidencia),
                    metodo               = 'Manual',
                    es_retardo           = :retardo,
                    es_auto_fill         = 0
                WHERE id = :id
            ");
            $this->db->bind(':estado',    $estado);
            $this->db->bind(':motivo',    $motivo ?: null);
            $this->db->bind(':horas',     $horasCalculadas);
            $this->db->bind(':evidencia', $rutaEvidencia);
            $this->db->bind(':retardo',   $esRetardo);
            $this->db->bind(':id',        (int)$existente->id);
        } else {
            // Insertar nuevo registro manual
            $this->db->query("
                INSERT INTO asistencias
                    (pasante_id, fecha, hora_registro, estado, metodo,
                     horas_calculadas, motivo_justificacion, ruta_evidencia, es_retardo, es_auto_fill)
                VALUES
                    (:pid, :fecha, :hora, :estado, 'Manual',
                     :horas, :motivo, :evidencia, :retardo, 0)
            ");
            $this->db->bind(':pid',       $pasanteId);
            $this->db->bind(':fecha',     $fecha);
            $this->db->bind(':hora',      $horaRegistro);
            $this->db->bind(':estado',    $estado);
            $this->db->bind(':horas',     $horasCalculadas);
            $this->db->bind(':motivo',    $motivo ?: null);
            $this->db->bind(':evidencia', $rutaEvidencia);
            $this->db->bind(':retardo',   $esRetardo);
        }

        $ok = $this->db->execute();

        // ── Calcular horas actualizadas para feedback inmediato ───────
        $proRataActual = null;
        if ($ok) {
            $regId  = $existente ? (int)$existente->id : (int)$this->db->lastInsertId();
            $accion = $existente ? 'ACTUALIZAR_ASISTENCIA_MANUAL' : 'REGISTRAR_ASISTENCIA_MANUAL';
            AuditModel::log($accion, 'asistencias', $regId, [
                'pasante_id' => $pasanteId,
                'fecha'      => $fecha,
                'estado'     => $estado,
                'motivo'     => $motivo ?: null,
                'retardo'    => (bool)$esRetardo,
            ]);

            // Obtener meta del pasante y calcular proRata actualizado
            $this->db->query("
                SELECT COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                       COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) AS fecha_fin
                FROM datos_pasante dpa
                LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                WHERE dpa.usuario_id = :pid LIMIT 1
            ");
            $this->db->bind(':pid', $pasanteId);
            $metaRow = $this->db->single();
            $horasMeta = (int)($metaRow->horas_meta ?? 1440);
            $fechaFin  = $metaRow->fecha_fin ?? null;

            $pr = $this->asistenciaModel->calcularProgresoProRata($pasanteId, $horasMeta, $fechaFin);
            $proRataActual = [
                'horas_acumuladas' => (int)$pr->horas_mostradas,
                'horas_meta'       => (int)$pr->horas_meta,
                'porcentaje'       => (int)$pr->porcentaje,
            ];
        }

        echo json_encode([
            'success'             => $ok,
            'message'             => $ok
                ? ($existente ? '✅ Registro actualizado correctamente.' : '✅ Asistencia manual registrada.')
                : 'Error al guardar el registro.',
            'feriado_advertencia' => $feriadoNombre !== null,
            'feriado_nombre'      => $feriadoNombre,
            'horas'               => $proRataActual,
        ]);

        exit;
    }

    // ────────────────────────────────────────────────────────────────
    // [FIX-ED-2] GET /asistencias/verEvidencia/{filename}
    // Sirve el récipe médico de una justificación SOLO si el usuario
    // autenticado tiene derecho a verlo: el propio pasante, su tutor
    // (mismo departamento), o Admin/SuperAdmin. Reemplaza el acceso
    // directo por URL estática que existía antes en public/uploads/.
    // ────────────────────────────────────────────────────────────────
    public function verEvidencia(string $filename = ''): void
    {
        $filename = basename($filename);
        if (!preg_match('/^[A-Za-z0-9_\-]+\.(jpg|jpeg|png|gif|webp|pdf)$/i', $filename)) {
            App::renderError(404);
            return;
        }

        // Buscar a qué pasante pertenece (tolera el formato legado con prefijo de ruta)
        $this->db->query("
            SELECT pasante_id FROM asistencias
            WHERE ruta_evidencia = :f1 OR ruta_evidencia LIKE :f2
            ORDER BY id DESC LIMIT 1
        ");
        $this->db->bind(':f1', $filename);
        $this->db->bind(':f2', '%/' . $filename);
        $row = $this->db->single();

        if (!$row) {
            App::renderError(404);
            return;
        }

        $rolId  = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        if ($rolId === 3) {
            // Pasante: solo su propia evidencia
            if ((int)$row->pasante_id !== $userId) {
                App::renderError(403);
                return;
            }
        } elseif ($rolId === 2) {
            // Tutor: solo pasantes de su departamento
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $_deptId = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $row->pasante_id);
            $chk = $this->db->single();
            if (!$chk || (int)($chk->departamento_asignado_id ?? 0) !== $_deptId) {
                App::renderError(403);
                return;
            }
        }
        // Admin (1) y SuperAdmin (0): sin restricción adicional

        $filepath = APPROOT . '/storage/uploads/evidencias/' . $filename;
        if (!file_exists($filepath)) {
            App::renderError(404);
            return;
        }

        $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                  'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, no-store');
        readfile($filepath);
        exit;
    }

    // ────────────────────────────────────────────────────────────────
    // ENDPOINT JSON — Registro Masivo (Marcar Todo)
    // ────────────────────────────────────────────────────────────────
    /**
     * registro_masivo() — Marca como "Presente" a todos los pasantes
     * enviados en el array POST['ids[]'], para la fecha actual.
     * Solo Admin (rol 1) o SuperAdmin (rol 0) pueden ejecutarlo.
     */
    public function registro_masivo(): void
    {
        header('Content-Type: application/json');

        if (!in_array((int)Session::get('role_id'), [0, 1, 2])) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $ids    = $_POST['ids'] ?? [];
        $fecha  = date('Y-m-d');
        $hora   = date('H:i:s');
        $estado = 'Presente';

        // Tutor: filtrar IDs a solo su departamento
        if ((int)Session::get('role_id') === 2 && !empty($ids)) {
            $tutorUserId = (int)Session::get('user_id');
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $tutorUserId);
            $tutorDept = (int)($this->db->single()->departamento_id ?? 0);

            $idsLimpios = [];
            foreach ($ids as $pid) {
                $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
                $this->db->bind(':pid', (int)$pid);
                $dp = $this->db->single();
                if ($dp && (int)$dp->departamento_asignado_id === $tutorDept) {
                    $idsLimpios[] = (int)$pid;
                }
            }
            $ids = $idsLimpios;
        }

        // Validar que sea día hábil
        $dow = (int)(new DateTime($fecha))->format('N');
        if ($dow >= 6) {
            echo json_encode(['success' => false, 'message' => 'No se puede registrar en fin de semana.']);
            exit;
        }

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No se recibieron pasantes.']);
            exit;
        }

        $ok       = 0;
        $errores  = 0;
        $esRetardo = ($hora > '09:00:00') ? 1 : 0;

        foreach ($ids as $rawId) {
            $pid = (int)$rawId;
            if ($pid <= 0) { $errores++; continue; }

            // Saltar pasantes que ya completaron sus horas meta
            $this->db->query("
                SELECT COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                       COALESCE((
                           SELECT COUNT(*) * 8
                           FROM asistencias a2
                           WHERE a2.pasante_id = :pid2
                             AND a2.estado != 'Anulado'
                             AND a2.fecha >= COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)
                       ), 0) AS horas_acumuladas
                FROM datos_pasante dpa
                LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                WHERE dpa.usuario_id = :pid3
                LIMIT 1
            ");
            $this->db->bind(':pid2', $pid);
            $this->db->bind(':pid3', $pid);
            $limitInfo = $this->db->single();
            if ($limitInfo && (float)$limitInfo->horas_acumuladas >= (float)$limitInfo->horas_meta) {
                continue; // Horas completas — no registrar
            }

            // Verificar si ya existe registro hoy
            $this->db->query("SELECT id FROM asistencias WHERE pasante_id = :pid AND fecha = :fecha LIMIT 1");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':fecha', $fecha);
            $existente = $this->db->single();

            if ($existente) {
                // Actualizar si existe; preservar horas si ya marcó por Kiosco
                $this->db->query("
                    UPDATE asistencias
                    SET estado = :estado, metodo = 'Manual', es_retardo = :retardo, es_auto_fill = 0,
                        hora_entrada = COALESCE(hora_entrada, '08:00:00'),
                        hora_salida  = COALESCE(hora_salida,  '16:00:00')
                    WHERE id = :id
                ");
                $this->db->bind(':estado',  $estado);
                $this->db->bind(':retardo', $esRetardo);
                $this->db->bind(':id',      (int)$existente->id);
            } else {
                $this->db->query("
                    INSERT INTO asistencias
                        (pasante_id, fecha, hora_registro, estado, metodo,
                         hora_entrada, hora_salida, horas_calculadas, es_retardo, es_auto_fill)
                    VALUES
                        (:pid, :fecha, :hora, :estado, 'Manual',
                         '08:00:00', '16:00:00', 8.00, :retardo, 0)
                ");
                $this->db->bind(':pid',     $pid);
                $this->db->bind(':fecha',   $fecha);
                $this->db->bind(':hora',    $hora);
                $this->db->bind(':estado',  $estado);
                $this->db->bind(':retardo', $esRetardo);
            }

            if ($this->db->execute()) { $ok++; } else { $errores++; }
        }

        if ($ok > 0) {
            AuditModel::log('REGISTRAR_ASISTENCIA_MASIVA', 'asistencias', null, [
                'fecha'    => $fecha,
                'marcados' => $ok,
                'errores'  => $errores,
                'retardo'  => (bool)$esRetardo,
            ]);
        }

        echo json_encode([
            'success'  => $ok > 0,
            'message'  => $ok > 0
                ? "✅ Se marcaron {$ok} pasante(s) como Presente." . ($errores > 0 ? " ({$errores} error(es))." : '')
                : 'No se pudo registrar ninguna asistencia.',
            'marcados' => $ok,
            'errores'  => $errores,
        ]);
        exit;
    }


    /**
     * exportar_datos() — Devuelve los registros en formato JSON según el rango.
     * Usado por el frontend para generar CSV o PDF desde el cliente.
     */
    public function exportar_datos(): void
    {
        header('Content-Type: application/json');

        $rolId = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        // Permitir acceso a SuperAdmin (0), Admin (1), Tutor (2) y Pasante (3)
        if (!in_array($rolId, [0, 1, 2, 3])) {
            echo json_encode(['success' => false, 'message' => 'Permiso denegado.']);
            exit;
        }

        $vista = $_GET['vista'] ?? 'diaria'; // 'diaria', 'semanal', 'mensual', 'anual'
        $hoy = date('Y-m-d');
        
        $fechaInicio = $hoy;
        $fechaFin    = $hoy;

        $paramsUrl = [
            'fecha'  => $_GET['fecha'] ?? $hoy,
            'semana' => $_GET['semana'] ?? date('W'),
            'mes'    => str_pad($_GET['mes'] ?? date('m'), 2, '0', STR_PAD_LEFT),
            'anio'   => $_GET['anio'] ?? date('Y')
        ];

        if ($vista === 'diaria') {
            $fechaInicio = $paramsUrl['fecha'];
            $fechaFin    = $paramsUrl['fecha'];
        } elseif ($vista === 'semanal') {
            $dto = new DateTime();
            $dto->setISODate((int)$paramsUrl['anio'], (int)$paramsUrl['semana']);
            $fechaInicio = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $fechaFin = $dto->format('Y-m-d');
        } elseif ($vista === 'mensual') {
            $fechaInicio = $paramsUrl['anio'] . '-' . str_pad($paramsUrl['mes'], 2, '0', STR_PAD_LEFT) . '-01';
            $fechaFin    = date('Y-m-t', strtotime($fechaInicio));
        } elseif ($vista === 'anual') {
            $periodoId = (int)($_GET['periodo_id'] ?? 0);
            if ($periodoId > 0) {
                $this->db->query("SELECT fecha_inicio, fecha_fin FROM periodos_academicos WHERE id = :id LIMIT 1");
                $this->db->bind(':id', $periodoId);
                $p = $this->db->single();
                
                if (!$p) {
                    $this->db->query("SELECT fecha_inicio, fecha_fin FROM periodos WHERE id = :id LIMIT 1");
                    $this->db->bind(':id', $periodoId);
                    $p = $this->db->single();
                }

                if ($p) {
                    $fechaInicio = $p->fecha_inicio;
                    $fechaFin    = $p->fecha_fin;
                } else {
                    $fechaInicio = $paramsUrl['anio'] . '-01-01';
                    $fechaFin    = $paramsUrl['anio'] . '-12-31';
                }
            } else {
                // Si no envían periodo, intentamos buscar el activo
                $this->db->query("SELECT fecha_inicio, fecha_fin FROM periodos_academicos WHERE estado = 'Activo' ORDER BY fecha_inicio DESC LIMIT 1");
                $p = $this->db->single();
                
                if (!$p) {
                    $this->db->query("SELECT fecha_inicio, fecha_fin FROM periodos WHERE estado = 'Activo' ORDER BY fecha_inicio DESC LIMIT 1");
                    $p = $this->db->single();
                }

                if ($p) {
                    $fechaInicio = $p->fecha_inicio;
                    $fechaFin    = $p->fecha_fin;
                } else {
                    $fechaInicio = $paramsUrl['anio'] . '-01-01';
                    $fechaFin    = $paramsUrl['anio'] . '-12-31';
                }
            }
        }

        $whereTutor = "";
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $_deptId = (int)($this->db->single()->departamento_id ?? 0);
            $whereTutor = " AND dpa.departamento_asignado_id = {$_deptId} ";
        }

        $wherePasante = "";
        $filtroBusquedaPasante = (int)($_GET['pasante_id'] ?? 0);

        if ($rolId === 3) {
            $wherePasante = " AND a.pasante_id = :uid_pasante ";
        } elseif ($filtroBusquedaPasante > 0) {
            $wherePasante = " AND a.pasante_id = :uid_filtro ";
        }

        $this->db->query("
            SELECT
                a.id,
                a.fecha,
                a.hora_registro,
                a.estado,
                a.metodo,
                a.motivo_justificacion,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                d.nombre AS departamento_nombre
            FROM asistencias a
            INNER JOIN usuarios          u   ON u.id          = a.pasante_id
            LEFT  JOIN datos_personales  dp  ON dp.usuario_id = u.id
            LEFT  JOIN datos_pasante     dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos     d   ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            WHERE a.fecha >= :fecha_inicio AND a.fecha <= :fecha_fin $whereTutor $wherePasante
            ORDER BY a.fecha DESC, a.hora_registro DESC
        ");

        $this->db->bind(':fecha_inicio', $fechaInicio);
        $this->db->bind(':fecha_fin', $fechaFin);
        if ($rolId === 2) {
            // dept_id ya está interpolado en $whereTutor — no se requiere bind adicional
        }
        if ($rolId === 3) {
            $this->db->bind(':uid_pasante', $userId);
        } elseif ($filtroBusquedaPasante > 0) {
            $this->db->bind(':uid_filtro', $filtroBusquedaPasante);
        }
        
        $registrosLista = $this->db->resultSet();

        echo json_encode([
            'success' => true,
            'vista'   => $vista,
            'data'    => $registrosLista
        ]);
        exit;
    }

    // ────────────────────────────────────────────────────────────────
    // ENDPOINT JSON — Buscador Autocomplete
    // ────────────────────────────────────────────────────────────────

    /**
     * buscar_pasantes() — Endpoint para el Autocomplete del Modal de Búsqueda
     */
    public function buscar_pasantes(): void
    {
        header('Content-Type: application/json');

        $rolId  = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        $term = $_GET['q'] ?? '';
        if (strlen(trim($term)) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $termSql = "%" . trim($term) . "%";

        // Para tutores: filtrar por departamento (no por tutor_id directamente)
        $whereTutor = "";
        $deptId     = 0;
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $deptId     = (int)($this->db->single()->departamento_id ?? 0);
            $whereTutor = " AND dpa.departamento_asignado_id = :dept_id ";
        }

        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos,
                   d.nombre AS departamento_nombre,
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS institucion_nombre
            FROM   usuarios u
            LEFT JOIN datos_personales    dp   ON dp.usuario_id   = u.id
            LEFT JOIN datos_pasante       dpa  ON dpa.usuario_id  = u.id
            LEFT JOIN departamentos       d    ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            LEFT JOIN instituciones       inst ON inst.id = dpa.institucion_id
            WHERE  u.rol_id = 3
              AND  COALESCE(dpa.estado_pasantia, 'Sin Asignar') IN ('Activo', 'Finalizado')
              AND  COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
              $whereTutor
              AND  (u.cedula LIKE :q OR dp.nombres LIKE :q OR dp.apellidos LIKE :q)
            ORDER BY
                FIELD(dpa.estado_pasantia, 'Activo', 'Finalizado') ASC,
                IFNULL(dp.apellidos, u.correo) ASC
            LIMIT 15
        ");
        $this->db->bind(':q', $termSql);
        if ($rolId === 2) {
            $this->db->bind(':dept_id', $deptId);
        }
        $resultados = $this->db->resultSet();

        echo json_encode(['success' => true, 'data' => $resultados]);
        exit;
    }

    // ────────────────────────────────────────────────────────────────
    // ENDPOINT JSON — Anular Registro
    // ────────────────────────────────────────────────────────────────

    /**
     * anular_registro() — Borrado lógico o cambio a estado 'Anulado'
     */
    public function anular_registro(): void
    {
        header('Content-Type: application/json');
        if (!in_array((int)Session::get('role_id'), [0, 1, 2])) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de registro inválido.']);
            exit;
        }

        // Capturar datos previos para auditoría antes de modificar el registro
        $this->db->query("SELECT pasante_id, fecha, estado FROM asistencias WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $regAnterior = $this->db->single();

        // Tutor: verificar que el pasante pertenece a su departamento
        if ((int)Session::get('role_id') === 2 && $regAnterior) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $tutorDept = (int)($this->db->single()->departamento_id ?? 0);
            $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', (int)$regAnterior->pasante_id);
            $pDept = (int)($this->db->single()->departamento_asignado_id ?? -1);
            if ($tutorDept !== $pDept) {
                echo json_encode(['success' => false, 'message' => 'No puede anular registros fuera de su departamento.']);
                exit;
            }
        }

        // ✅ PRO-RATA: No se restan horas al anular. El conteo Pro-Rata excluye estado 'Anulado' automáticamente.
        $this->db->query("UPDATE asistencias SET estado = 'Anulado' WHERE id = :id");
        $this->db->bind(':id', $id);
        $ok = $this->db->execute();

        if ($ok && $regAnterior) {
            AuditModel::log('ANULAR_ASISTENCIA', 'asistencias', $id, [
                'pasante_id'      => (int)$regAnterior->pasante_id,
                'fecha'           => $regAnterior->fecha,
                'estado_anterior' => $regAnterior->estado,
            ]);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? '✅ Asistencia anulada correctamente.' : 'Error al anular.'
        ]);
        exit;
    }

    // ==========================================
    // API AJAX: MÓDULO DE CONSULTA RÁPIDA
    // ==========================================

    /**
     * Endpoint 1: Buscador en vivo para el autocompletado del Modal
     */
    public function buscarPasanteAjax() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $rolId   = (int)Session::get('role_id');
            $userId  = (int)Session::get('user_id');
            $query   = trim($_POST['query'] ?? '');
            $tutorId = $rolId === 2 ? $userId : 0;

            $resultados = $this->asistenciaModel->buscarPasanteLive($query, $tutorId);

            header('Content-Type: application/json');
            echo json_encode($resultados);
            exit;
        }
    }

    /**
     * Endpoint 2: Extrae todo el expediente y marcajes de un pasante
     */
    public function obtenerDatosAuditoriaAjax() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idPasante = intval($_POST['id_pasante'] ?? 0);
            
            if ($idPasante > 0) {
                // A. Obtener el perfil (Nombres, Cédula, Depto, fecha_inicio, fecha_fin)
                $perfil = $this->asistenciaModel->obtenerPerfilParaAuditoria($idPasante);
                
                // B. Obtener el historial completo de marcajes
                $historial = $this->asistenciaModel->obtenerHistorialCompletoPasante($idPasante);

                // C. ✅ PRO-RATA: Calcular progreso dinámicamente desde la tabla asistencias
                //    horasMostradas = COUNT(Presente|Justificado) * 8
                //    porcentaje     = horasMostradas / 1440 * 100
                $horasMeta = (int)(($perfil->horas_meta ?? 0) > 0 ? $perfil->horas_meta : 1440);
                $proRata   = $this->asistenciaModel->calcularProgresoProRata($idPasante, $horasMeta);
                
                // D. ✅ CALENDARIO: Calcular progreso estrictamente por calendario (L-V)
                $fechaInicio = $perfil->fecha_inicio ?? date('Y-m-d');
                $calendario  = $this->asistenciaModel->calcularProgresoPorCalendario($fechaInicio, $horasMeta);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'status'   => 'success',
                    'perfil'   => $perfil,
                    'historial'=> $historial,
                    'pro_rata' => [
                        'dias_presentes'  => $proRata->dias_presentes,
                        'horas_mostradas' => $proRata->horas_mostradas,
                        'horas_meta'      => $proRata->horas_meta,
                        'porcentaje'      => $proRata->porcentaje,
                    ],
                    'calendario' => [
                        'dias_habiles_transcurridos' => $calendario->dias_habiles_transcurridos,
                        'horas_calendario'           => $calendario->horas_calendario,
                        'porcentaje_calendario'      => $calendario->porcentaje_calendario,
                        'dias_habiles_restantes'     => $calendario->dias_habiles_restantes
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            }
            exit;
        }
    }

    /**
     * Endpoint 3: obtenerResumenMensual() — Almanaque Inteligente (Heatmap)
     *
     * Devuelve el historial de asistencias de un pasante para un mes específico,
     * enriquecido con flags de retardo, feriados y KPI de retardos del mes.
     *
     * Respuesta JSON:
     *   {
     *     success: true,
     *     datos: { "YYYY-MM-DD": { estado, es_retardo, es_auto_fill } },
     *     feriados: { "YYYY-MM-DD": "Descripción" },
     *     kpi: { presentes, ausentes, justificados, retardos, auto_fills }
     *   }
     */
    public function obtenerResumenMensual(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        $mesAnio   = trim($_POST['mes_anio'] ?? ''); // Formato YYYY-MM

        if ($pasanteId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $mesAnio)) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
            exit;
        }

        $fechaInicio = $mesAnio . '-01';
        $fechaFin    = date('Y-m-t', strtotime($fechaInicio));

        // ── Registros del mes para este pasante ──────────────────
        $this->db->query("
            SELECT fecha,
                   hora_registro,
                   estado,
                   metodo,
                   COALESCE(es_retardo,  0) AS es_retardo,
                   COALESCE(es_auto_fill, 0) AS es_auto_fill
            FROM   asistencias
            WHERE  pasante_id = :pid
              AND  fecha >= :inicio AND fecha <= :fin
            ORDER  BY fecha ASC
        ");
        $this->db->bind(':pid',    $pasanteId);
        $this->db->bind(':inicio', $fechaInicio);
        $this->db->bind(':fin',    $fechaFin);
        $registros = $this->db->resultSet();

        // ── Feriados del mes ──────────────────────────────────────
        $feriados = $this->asistenciaModel->getFeriadosEnRango($fechaInicio, $fechaFin);

        // ── Construir mapa de datos + KPIs ────────────────────────
        $datosGrid  = [];
        $kpi = ['presentes' => 0, 'ausentes' => 0, 'justificados' => 0, 'retardos' => 0, 'auto_fills' => 0];

        foreach ($registros as $reg) {
            $datosGrid[$reg->fecha] = [
                'estado'       => $reg->estado,
                'hora'         => $reg->hora_registro,
                'metodo'       => $reg->metodo,
                'es_retardo'   => (int)$reg->es_retardo,
                'es_auto_fill' => (int)$reg->es_auto_fill,
            ];

            $est = strtolower($reg->estado);
            if (strpos($est, 'presente')    !== false) $kpi['presentes']++;
            elseif (strpos($est, 'ausente') !== false) $kpi['ausentes']++;
            else                                        $kpi['justificados']++;

            if ((int)$reg->es_retardo   === 1) $kpi['retardos']++;
            if ((int)$reg->es_auto_fill === 1) $kpi['auto_fills']++;
        }

        echo json_encode([
            'success'  => true,
            'datos'    => $datosGrid,
            'feriados' => $feriados,
            'kpi'      => $kpi,
        ]);
        exit;
    }

    // ────────────────────────────────────────────────────────────────
    // VISTA ALMANAQUE INDIVIDUAL — Historial Premium del Pasante
    // ────────────────────────────────────────────────────────────────

    /**
     * almanaque($pasanteId) — Panel histórico de un pasante individual.
     * Incluye heatmap anual, justificaciones con evidencias y evaluaciones.
     *
     * GET /asistencias/almanaque/{pasante_id}?anio=YYYY
     */
    public function almanaque($pasanteId = null): void
    {
        $rolId  = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        if (!in_array($rolId, [0, 1, 2])) {
            $this->redirect('/dashboard');
            return;
        }

        $pasanteId = (int)($pasanteId ?? 0);
        if ($pasanteId <= 0) {
            $this->redirect('/asistencias');
            return;
        }

        // Verificar que el tutor accede solo al almanaque de pasantes de su departamento
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $_deptId = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("
                SELECT 1 FROM datos_pasante
                WHERE usuario_id = :pid AND departamento_asignado_id = :dept_id
                LIMIT 1
            ");
            $this->db->bind(':pid', $pasanteId);
            $this->db->bind(':dept_id', $_deptId);
            if (!$this->db->single()) {
                $this->redirect('tutor/pasantes');
                return;
            }
        }

        $anio = (int)($_GET['anio'] ?? date('Y'));

        // ── Perfil del pasante ────────────────────────────────────
        $this->db->query("
            SELECT
                u.id,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                dp.telefono,
                d.nombre        AS departamento,
                COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion_nombre,
                dpa.institucion_procedencia,
                dpa.estado_pasantia,
                dpa.horas_acumuladas,
                COALESCE(
                    NULLIF(dpa.horas_meta, 0),
                    ROUND(DATEDIFF(pa_ref.fecha_fin, pa_ref.fecha_inicio) * 5/7) * 8,
                    1440
                ) AS horas_meta,
                COALESCE(dpa.fecha_inicio_pasantia, pa_ref.fecha_inicio) AS fecha_inicio,
                COALESCE(dpa.fecha_fin_estimada,    pa_ref.fecha_fin)   AS fecha_fin,
                dpa.fecha_inicio_pasantia,
                dpa.fecha_fin_estimada,
                CONCAT(tp.nombres, ' ', tp.apellidos) AS tutor_nombre
            FROM usuarios u
            LEFT JOIN datos_personales  dp      ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante     dpa     ON dpa.usuario_id = u.id
            LEFT JOIN departamentos     d       ON d.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones     inst    ON inst.id = dpa.institucion_id
            LEFT JOIN usuarios          tu      ON tu.id = dpa.tutor_id
            LEFT JOIN datos_personales  tp      ON tp.usuario_id = tu.id
            LEFT JOIN periodos_academicos pa_ref ON pa_ref.id = dpa.periodo_id
            WHERE u.id = :id AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':id', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            $this->redirect('/asistencias');
            return;
        }

        // ── Pro-Rata dinámico (horas reales desde tabla asistencias) ──
        $horasMeta   = (int)(($pasante->horas_meta ?? 0) > 0 ? $pasante->horas_meta : 1440);
        $fechaFinPas = $pasante->fecha_fin ?? null; // cap para no contar días fuera del período
        $proRataAlm  = $this->asistenciaModel->calcularProgresoProRata($pasanteId, $horasMeta, $fechaFinPas);
        $pasante->horas_acumuladas = $proRataAlm->horas_mostradas;
        $pasante->horas_meta       = $proRataAlm->horas_meta;

        // ── LAZY AUTOFILL — rellenar días laborables sin registro como Ausente ──
        // Solo aplica si la pasantía está activa y tiene fecha de inicio.
        // No sobreescribe registros existentes (INSERT IGNORE).
        // Días sin marcaje = Ausente (0h). Feriados L-V = Justificado (8h).
        if (($pasante->estado_pasantia ?? '') === 'Activo' && !empty($pasante->fecha_inicio)) {
            $ayer      = date('Y-m-d', strtotime('-1 day'));
            $fechaFinPas = $pasante->fecha_fin ?? null;
            $limiteAutofill = ($fechaFinPas && $fechaFinPas < $ayer) ? $fechaFinPas : $ayer;

            $feriados_ = $this->asistenciaModel->getFeriadosEnRango($pasante->fecha_inicio, $limiteAutofill);

            $this->db->query("
                SELECT fecha FROM asistencias
                WHERE pasante_id = :pid
                  AND fecha >= :fi AND fecha <= :fl
            ");
            $this->db->bind(':pid', $pasanteId);
            $this->db->bind(':fi',  $pasante->fecha_inicio);
            $this->db->bind(':fl',  $limiteAutofill);
            $registradas_ = $this->db->resultSet();
            $fechasRegistradas_ = array_flip(array_column($registradas_, 'fecha'));

            $cursor_ = new DateTime($pasante->fecha_inicio);
            $fino_   = new DateTime($limiteAutofill);
            while ($cursor_ <= $fino_) {
                $dow_  = (int)$cursor_->format('N');
                $fstr_ = $cursor_->format('Y-m-d');
                if ($dow_ > 5 || isset($fechasRegistradas_[$fstr_])) {
                    $cursor_->modify('+1 day');
                    continue;
                }
                if (!isset($feriados_[$fstr_])) {
                    // Día laborable sin registro → Ausente (0 horas)
                    $this->db->query("
                        INSERT IGNORE INTO asistencias
                            (pasante_id, fecha, hora_registro, hora_entrada, hora_salida,
                             horas_calculadas, estado, metodo, es_auto_fill, observacion, created_at)
                        VALUES
                            (:pid, :fecha, '00:00:00', '00:00:00', '00:00:00',
                             0.00, 'Ausente', 'AutoFill', 1,
                             'Sin registro — marcado ausente automáticamente', :created)
                    ");
                    $this->db->bind(':pid',     $pasanteId);
                    $this->db->bind(':fecha',   $fstr_);
                    $this->db->bind(':created', $fstr_ . ' 23:59:00');
                    $this->db->execute();
                } else {
                    // Feriado no laborable en L-V → Justificado (8 horas)
                    $this->db->query("
                        INSERT IGNORE INTO asistencias
                            (pasante_id, fecha, hora_registro, hora_entrada, hora_salida,
                             horas_calculadas, estado, metodo, es_auto_fill,
                             motivo_justificacion, observacion, created_at)
                        VALUES
                            (:pid, :fecha, '08:00:00', '08:00:00', '16:00:00',
                             8.00, 'Justificado', 'AutoFill', 1,
                             :motivo, 'Feriado — completado automáticamente', :created)
                    ");
                    $this->db->bind(':pid',     $pasanteId);
                    $this->db->bind(':fecha',   $fstr_);
                    $this->db->bind(':motivo',  $feriados_[$fstr_]);
                    $this->db->bind(':created', $fstr_ . ' 23:59:00');
                    $this->db->execute();
                }
                $cursor_->modify('+1 day');
            }
        }

        // ── Años con registros (para navegación) ────────────────────
        $this->db->query("
            SELECT DISTINCT YEAR(fecha) AS anio
            FROM asistencias
            WHERE pasante_id = :pid
            ORDER BY anio ASC
        ");
        $this->db->bind(':pid', $pasanteId);
        $aniosRows = $this->db->resultSet();
        $anios = array_map(fn($r) => (int)$r->anio, $aniosRows);
        if (!in_array($anio, $anios)) $anios[] = $anio;
        sort($anios);

        // ── Período / Cohorte del pasante (para el banner) ───────────
        $this->db->query("
            SELECT pe.nombre AS periodo_nombre, pe.estado AS periodo_estado
            FROM datos_pasante dpa
            LEFT JOIN periodos_academicos pe ON pe.id = dpa.periodo_id
            WHERE dpa.usuario_id = :pid
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $periodoRow = $this->db->single();
        $periodoDatos = [
            'nombre' => $periodoRow->periodo_nombre ?? null,
            'estado' => $periodoRow->periodo_estado ?? null,
        ];

        // ── Registros del año (para el heatmap y stats) ───────────
        $fechaInicio = "{$anio}-01-01";
        $fechaFin    = "{$anio}-12-31";

        $this->db->query("
            SELECT fecha, hora_registro, estado, metodo, motivo_justificacion, ruta_evidencia
            FROM asistencias
            WHERE pasante_id = :pid
              AND fecha >= :fi AND fecha <= :ff
              AND estado != 'Anulado'
            ORDER BY fecha ASC
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':fi',  $fechaInicio);
        $this->db->bind(':ff',  $fechaFin);
        $registrosAnio = $this->db->resultSet();

        // Feriados del año
        $feriados = $this->asistenciaModel->getFeriadosEnRango($fechaInicio, $fechaFin);

        // Mapa fecha → datos
        $mapaRegistros = [];
        foreach ($registrosAnio as $r) {
            $mapaRegistros[$r->fecha] = $r;
        }

        // ── Construir grilla anual ─────────────────────────────────
        $hoy = date('Y-m-d');
        // Racha máxima: días P/J consecutivos; se resetea en Ausente.
        // Los feriados se saltan (no cuentan ni rompen la racha) para que
        // rachaMax sea coherente con $stats['P'] + $stats['J'] (que tampoco cuentan feriados).
        $rachaActual = 0; $rachaMax = 0; $corriente = 0;
        foreach ($mapaRegistros as $fecha => $r) {
            if ((int)substr($fecha, 0, 4) !== $anio) { $corriente = 0; continue; }
            if ($fecha > $hoy) continue;            // Ignorar días futuros
            if (isset($feriados[$fecha])) continue; // Feriados: transparentes en la racha
            if (in_array($r->estado, ['Presente', 'Justificado'])) {
                $corriente++;
                if ($corriente > $rachaMax) $rachaMax = $corriente;
            } else {
                $corriente = 0;
            }
        }
        // Racha actual = días P/J consecutivos hacia atrás desde hoy
        $tmpRacha = 0;
        $mapaReverso = array_reverse($mapaRegistros);
        foreach ($mapaReverso as $fecha => $r) {
            if ($fecha > $hoy) continue;
            if ((int)substr($fecha, 0, 4) !== $anio) break; // No cruzar al año anterior
            if (isset($feriados[$fecha])) continue;         // Feriados transparentes
            if (in_array($r->estado, ['Presente', 'Justificado'])) {
                $tmpRacha++;
            } else {
                break;
            }
        }
        $rachaActual = $tmpRacha;

        $grilla = []; $weekLabels = [];
        $stats = ['P' => 0, 'A' => 0, 'J' => 0, 'laborables' => 0];

        $cursor = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $startDow = (int)$cursor->format('N');
        // Startamos el cursor en Lunes de la primera semana
        if ($startDow > 1) $cursor->modify('-' . ($startDow - 1) . ' days');

        $wk = 0;
        while ($cursor <= $fin) {
            $dow = (int)$cursor->format('N');
            $fechaS = $cursor->format('Y-m-d');
            $yearOfCell = (int)$cursor->format('Y');

            if ($dow === 1) {
                $wk++;
                $weekLabels[$wk] = $cursor->format('d/m');
                $grilla[$wk] = [];
            }

            if ($dow <= 5) { // Solo L-V
                if ($yearOfCell !== $anio) {
                    $grilla[$wk][$dow] = ['fecha'=>$fechaS,'estado'=>'fuera','hora'=>null,'metodo'=>null,'feriadoNombre'=>null];
                } elseif ($fechaS > $hoy) {
                    $grilla[$wk][$dow] = ['fecha'=>$fechaS,'estado'=>'futuro','hora'=>null,'metodo'=>null,'feriadoNombre'=>null];
                } elseif (isset($feriados[$fechaS])) {
                    $grilla[$wk][$dow] = ['fecha'=>$fechaS,'estado'=>'feriado','hora'=>null,'metodo'=>null,'feriadoNombre'=>$feriados[$fechaS]];
                } elseif (isset($mapaRegistros[$fechaS])) {
                    $r = $mapaRegistros[$fechaS];
                    $e = $r->estado === 'Presente' ? 'P' : ($r->estado === 'Justificado' ? 'J' : 'A');
                    if (in_array($e, ['P','J','A'])) {
                        $stats[$e]++;
                        $stats['laborables']++;
                    }
                    $grilla[$wk][$dow] = ['fecha'=>$fechaS,'estado'=>$e,'hora'=>$r->hora_registro,'metodo'=>$r->metodo,'feriadoNombre'=>null];
                } else {
                    $stats['laborables']++;
                    $grilla[$wk][$dow] = ['fecha'=>$fechaS,'estado'=>'sin_dato','hora'=>null,'metodo'=>null,'feriadoNombre'=>null];
                }
            }
            $cursor->modify('+1 day');
        }

        // ── Stats adicionales ─────────────────────────────────────
        // Denominador = días hábiles del año filtrado DENTRO del rango efectivo
        // de la pasantía (fecha_inicio … min(hoy, fecha_fin)), sin feriados.
        $rangoIniEfectivo = $pasante->fecha_inicio
            ? max("{$anio}-01-01", $pasante->fecha_inicio)
            : "{$anio}-01-01";
        $rangoFinEfectivo = "{$anio}-12-31";
        if ($rangoFinEfectivo > $hoy) $rangoFinEfectivo = $hoy;
        if ($pasante->fecha_fin && $rangoFinEfectivo > $pasante->fecha_fin) {
            $rangoFinEfectivo = $pasante->fecha_fin;
        }

        $laborablesEfectivos = 0;
        if ($rangoIniEfectivo <= $rangoFinEfectivo) {
            $cRango = new DateTime($rangoIniEfectivo);
            $fRango = new DateTime($rangoFinEfectivo);
            while ($cRango <= $fRango) {
                $dowR   = (int)$cRango->format('N');
                $fechaR = $cRango->format('Y-m-d');
                if ($dowR <= 5 && !isset($feriados[$fechaR])) {
                    $laborablesEfectivos++;
                }
                $cRango->modify('+1 day');
            }
        }
        $pctAsistencia = $laborablesEfectivos > 0
            ? round(($stats['P'] + $stats['J']) / $laborablesEfectivos * 100, 1) : 0;



        // Timeline — días HÁBILES (L-V), coherente con horas_meta
        // horas_meta / 8h por día = total de días laborables de la pasantía
        // Ej: 1440h / 8 = 180 días hábiles (no 252 días calendario)
        $diasTrans = 0; $diasTotal = 0; $diasRest = 0; $pctTiempo = 0;
        if ($pasante->fecha_inicio) {
            $fI = new DateTime($pasante->fecha_inicio);
            $fF = $pasante->fecha_fin ? new DateTime($pasante->fecha_fin) : null;
            $fH = new DateTime($hoy);
            if ($fF) {
                // Total de días hábiles = horas_meta / 8h por día laboral
                $diasTotal = max(1, (int)round($horasMeta / 8));

                // Contar días hábiles (lunes a viernes) desde inicio hasta hoy (inclusive)
                // Se limita al fin estimado para no exceder el 100%
                if ($fH >= $fI) {
                    $cursor = clone $fI;
                    $limite = clone ($fH < $fF ? $fH : $fF);
                    $conteo = 0;
                    while ($cursor <= $limite) {
                        if ((int)$cursor->format('N') <= 5) { // 1=Lun … 5=Vie
                            $conteo++;
                        }
                        $cursor->modify('+1 day');
                    }
                    $diasTrans = min($diasTotal, $conteo);
                }

                $diasRest  = max(0, $diasTotal - $diasTrans);
                $pctTiempo = round($diasTrans / $diasTotal * 100, 1);
            }
        }

        // ── Historial completo (todas las fechas) ─────────────────
        $this->db->query("
            SELECT id, fecha, hora_registro AS hora_entrada, hora_entrada AS hora_entrada_real,
                   estado, metodo, observacion, horas_calculadas,
                   motivo_justificacion, ruta_evidencia, es_auto_fill
            FROM asistencias
            WHERE pasante_id = :pid AND fecha >= :fi AND fecha <= :ff
              AND estado != 'Anulado'
            ORDER BY fecha DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':fi',  $fechaInicio);
        $this->db->bind(':ff',  $fechaFin);
        $historialCompleto = $this->db->resultSet();

        // ── Justificaciones personales (excluye feriados auto-generados) ──
        $this->db->query("
            SELECT fecha, motivo_justificacion, ruta_evidencia, metodo
            FROM asistencias
            WHERE pasante_id = :pid
              AND estado = 'Justificado'
              AND estado != 'Anulado'
              AND COALESCE(es_auto_fill, 0) = 0
            ORDER BY fecha DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $justificaciones = $this->db->resultSet();

        // ── Evaluaciones (para la card Bento) ─────────────────────
        $this->db->query("
            SELECT
                e.id,
                e.fecha_evaluacion,
                e.lapso_academico,
                e.promedio_final,
                e.observaciones,
                e.criterio_iniciativa,   e.criterio_interes,
                e.criterio_conocimiento, e.criterio_analisis,
                e.criterio_comunicacion, e.criterio_aprendizaje,
                e.criterio_companerismo, e.criterio_cooperacion,
                e.criterio_puntualidad,  e.criterio_presentacion,
                e.criterio_desarrollo,   e.criterio_analisis_res,
                e.criterio_conclusiones, e.criterio_recomendacion,
                CONCAT(tp.nombres, ' ', tp.apellidos) AS tutor_nombre
            FROM evaluaciones e
            LEFT JOIN datos_personales tp ON tp.usuario_id = e.tutor_id
            WHERE e.pasante_id = :pid
            ORDER BY e.fecha_evaluacion DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $evaluaciones = $this->db->resultSet();

        // Instituciones para el select del modal editar
        $this->db->query("SELECT id, nombre FROM instituciones ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        // ── Desglose mensual completo (toda la pasantía, multi-año) ──
        $fi_desglose = $pasante->fecha_inicio ?? "{$anio}-01-01";
        $ff_desglose = $pasante->fecha_fin    ?? date('Y-m-d');

        $this->db->query("
            SELECT fecha, estado
            FROM asistencias
            WHERE pasante_id = :pid
              AND fecha >= :fi AND fecha <= :ff
              AND estado IN ('Presente','Ausente','Justificado')
            ORDER BY fecha ASC
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':fi',  $fi_desglose);
        $this->db->bind(':ff',  $ff_desglose);
        $registrosDesglose = $this->db->resultSet();

        $statsDesglose = [];
        foreach ($registrosDesglose as $r) {
            $yrD  = (int)substr($r->fecha, 0, 4);
            $mesD = (int)substr($r->fecha, 5, 2);
            if (!isset($statsDesglose[$yrD])) $statsDesglose[$yrD] = [];
            if (!isset($statsDesglose[$yrD][$mesD])) $statsDesglose[$yrD][$mesD] = ['P'=>0,'A'=>0,'J'=>0];
            $letraD = $r->estado === 'Presente' ? 'P' : ($r->estado === 'Justificado' ? 'J' : 'A');
            $statsDesglose[$yrD][$mesD][$letraD]++;
        }

        // ── Actividades diarias del pasante (para inspección) ───────
        $this->db->query("
            SELECT ap.id, ap.fecha, ap.titulo, ap.descripcion,
                ap.correccion, ap.corrector_id, ap.correccion_at,
                DATE_FORMAT(ap.fecha, '%d/%m/%Y')   AS fecha_fmt,
                DATE_FORMAT(ap.fecha, '%Y-%m')      AS mes_key,
                DATE_FORMAT(ap.fecha, '%b %Y')      AS mes_label,
                CONCAT(dp.nombres, ' ', dp.apellidos) AS corrector_nombre
            FROM actividades_pasante ap
            LEFT JOIN datos_personales dp ON dp.usuario_id = ap.corrector_id
            WHERE ap.pasante_id = :pid
              AND ap.fecha >= :fi AND ap.fecha <= :ff
            ORDER BY ap.fecha DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $this->db->bind(':fi',  $fi_desglose);
        $this->db->bind(':ff',  $ff_desglose);
        $actividadesPasante = $this->db->resultSet() ?: [];

        // ── Resultados de exámenes del pasante ────────────────────
        $this->db->query("
            SELECT
                e.id                                           AS examen_id,
                e.titulo,
                i.id                                           AS intento_id,
                i.puntaje_obtenido,
                i.puntaje_maximo,
                i.porcentaje,
                i.enviado_at,
                DATE_FORMAT(i.enviado_at, '%d/%m/%Y %H:%i')   AS fecha_fmt
            FROM examen_intentos i
            JOIN examenes e ON e.id = i.examen_id
            WHERE i.pasante_id = :pid
              AND i.enviado_at IS NOT NULL
            ORDER BY i.enviado_at DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $examenesResultados = $this->db->resultSet() ?: [];

        // Registros reales (no auto-fill) de toda la pasantía: determina si el botón Rellenar está habilitado
        $this->db->query("SELECT COUNT(*) AS cnt FROM asistencias WHERE pasante_id = :pid AND es_auto_fill = 0 AND estado != 'Anulado'");
        $this->db->bind(':pid', $pasanteId);
        $totalRegistrosReales = (int)($this->db->single()->cnt ?? 0);

        $this->view('asistencias/almanaque', [
            'title'              => 'Almanaque — ' . trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? '')),
            'pasante'            => $pasante,
            'anio'               => $anio,
            'anios'              => $anios,
            'periodo'            => $periodoDatos,
            'grilla'             => $grilla,
            'weekLabels'         => $weekLabels,
            'stats'              => $stats,
            'pctAsistencia'      => $pctAsistencia,
            'rachaActual'        => $rachaActual,
            'rachaMax'           => $rachaMax,
            'historialCompleto'  => $historialCompleto,
            'diasTrans'          => $diasTrans,
            'diasTotal'          => $diasTotal,
            'diasRest'           => $diasRest,
            'pctTiempo'          => $pctTiempo,
            'laborablesAnio'     => $laborablesEfectivos,
            'justificaciones'    => $justificaciones,
            'evaluaciones'       => $evaluaciones,
            'instituciones'      => $instituciones,
            'statsDesglose'      => $statsDesglose,
            'fi_pasantia'        => $fi_desglose,
            'ff_pasantia'        => $ff_desglose,
            'actividadesPasante'    => $actividadesPasante,
            'examenesResultados'    => $examenesResultados,
            'totalRegistrosReales'  => $totalRegistrosReales,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // PDF ALMANAQUE INDIVIDUAL
    // ────────────────────────────────────────────────────────────────

    /**
     * pdfAlmanaque($pasanteId) — Genera PDF del historial completo del pasante.
     *
     * GET /asistencias/pdfAlmanaque/{pasante_id}?anio=YYYY
     */
    public function pdfAlmanaque($pasanteId = null): void
    {
        $rolId  = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        if (!in_array($rolId, [0, 1, 2])) {
            http_response_code(403);
            exit;
        }

        $pasanteId = (int)($pasanteId ?? $_GET['id'] ?? 0);
        if ($pasanteId <= 0) {
            $this->redirect('/asistencias');
            return;
        }

        if ($rolId === 2) {
            $this->db->query("SELECT 1 FROM datos_pasante WHERE usuario_id = :pid AND tutor_id = :tid LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $this->db->bind(':tid', $userId);
            if (!$this->db->single()) {
                http_response_code(403);
                exit;
            }
        }

        // ── Datos del pasante ──
        $this->db->query("
            SELECT
                u.id, u.cedula,
                dp.nombres, dp.apellidos,
                d.nombre AS departamento,
                COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion_nombre,
                dpa.estado_pasantia,
                dpa.horas_acumuladas,
                COALESCE(
                    NULLIF(dpa.horas_meta, 0),
                    ROUND(DATEDIFF(pa_ref.fecha_fin, pa_ref.fecha_inicio) * 5/7) * 8,
                    1440
                ) AS horas_meta,
                COALESCE(dpa.fecha_inicio_pasantia, pa_ref.fecha_inicio) AS fecha_inicio,
                COALESCE(dpa.fecha_fin_estimada,    pa_ref.fecha_fin)   AS fecha_fin
            FROM usuarios u
            LEFT JOIN datos_personales dp      ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante    dpa     ON dpa.usuario_id = u.id
            LEFT JOIN departamentos    d       ON d.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones    inst    ON inst.id = dpa.institucion_id
            LEFT JOIN periodos_academicos pa_ref ON pa_ref.id = dpa.periodo_id
            WHERE u.id = :id AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':id', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            $this->redirect('/asistencias');
            return;
        }

        // ── Historial de asistencias (todo el período) ──
        $this->db->query("
            SELECT fecha, hora_entrada, estado
            FROM asistencias
            WHERE pasante_id = :pid
            ORDER BY fecha ASC
        ");
        $this->db->bind(':pid', $pasanteId);
        $asistenciasRaw = $this->db->resultSet();

        // Indexar por fecha e indexar por mes para la grilla
        $porFecha = [];
        $stats    = ['P' => 0, 'A' => 0, 'J' => 0];
        foreach ($asistenciasRaw as $a) {
            $porFecha[$a->fecha] = $a;
            $e = strtoupper(substr($a->estado ?? '', 0, 1));
            if ($e === 'P' || strtolower($a->estado) === 'presente')    $stats['P']++;
            elseif ($e === 'A' || strtolower($a->estado) === 'ausente') $stats['A']++;
            elseif ($e === 'J' || strtolower($a->estado) === 'justificado') $stats['J']++;
        }

        $totalDias = $stats['P'] + $stats['J'];
        $pct       = ($stats['P'] + $stats['J'] + $stats['A']) > 0
            ? round(($stats['P'] + $stats['J']) / ($stats['P'] + $stats['J'] + $stats['A']) * 100, 1)
            : 0;

        $horasMeta = (int)(($pasante->horas_meta ?? 0) > 0 ? $pasante->horas_meta : 1440);
        $proRata   = $this->asistenciaModel->calcularProgresoProRata($pasanteId, $horasMeta);
        $horasAcum = $proRata->horas_mostradas;
        $pctHoras  = $horasMeta > 0 ? min(100, round($horasAcum / $horasMeta * 100)) : 0;

        // ── Evaluaciones ──
        $this->db->query("
            SELECT
                e.fecha_evaluacion, e.lapso_academico,
                e.promedio_final, e.observaciones,
                CONCAT(tp.nombres, ' ', tp.apellidos) AS evaluador
            FROM evaluaciones e
            LEFT JOIN datos_personales tp ON tp.usuario_id = e.tutor_id
            WHERE e.pasante_id = :pid
            ORDER BY e.fecha_evaluacion ASC
        ");
        $this->db->bind(':pid', $pasanteId);
        $evaluaciones = $this->db->resultSet();

        // ── Jefe que firma el documento (primer admin activo) ──
        $this->db->query("
            SELECT dp2.nombres, dp2.apellidos, dp2.cargo
            FROM datos_personales dp2
            JOIN usuarios u2 ON u2.id = dp2.usuario_id
            WHERE u2.rol_id = 1 AND u2.estado = 'activo'
            ORDER BY u2.id ASC
            LIMIT 1
        ");
        $firmante = $this->db->single();
        $jefeNombre = mb_strtoupper(
            trim(($firmante->nombres ?? '') . ' ' . ($firmante->apellidos ?? '')), 'UTF-8'
        );
        $jefeCargo = htmlspecialchars($firmante->cargo ?? '');

        require_once '../app/lib/PdfGenerator.php';
        $pdf = new PdfGenerator();

        ob_start();
        include '../app/views/reportes/pdf_almanaque_pasante.php';
        $html = ob_get_clean();

        $cedula = preg_replace('/[^0-9]/', '', $pasante->cedula ?? (string)$pasanteId);
        $pdf->renderDomPdf($html, 'Almanaque_' . $cedula, false);
    }

    /**
     * POST /asistencias/rellenarAdmin  (AJAX → JSON)
     * SuperAdmin y Admin: marca Presente todos los días L-V sin registro
     * en el rango [fecha_inicio, fecha_fin] para un pasante dado.
     * Los días ya registrados se omiten (no se sobreescriben).
     */
    public function rellenarAdmin(): void
    {
        header('Content-Type: application/json');
        $rolId = (int)Session::get('role_id');
        if (!in_array($rolId, [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']); exit;
        }

        $raw   = file_get_contents('php://input');
        $json  = json_decode($raw, true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }

        $pasanteId   = (int)($json['pasante_id']  ?? 0);
        $fechaInicio = trim($json['fecha_inicio'] ?? '');
        $fechaFin    = trim($json['fecha_fin']    ?? '');
        $hoy         = date('Y-m-d');

        // Cap 1: nunca fechas futuras
        if ($fechaFin > $hoy)    $fechaFin    = $hoy;
        if ($fechaInicio > $hoy) {
            echo json_encode(['success' => false, 'message' => 'La fecha de inicio no puede ser una fecha futura.']); exit;
        }

        if (!$pasanteId || !$fechaInicio || !$fechaFin || $fechaFin < $fechaInicio) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']); exit;
        }

        $db = $this->db;
        try {

        // Verificar que el pasante existe
        $db->query("SELECT id FROM usuarios WHERE id = :id AND rol_id = 3 LIMIT 1");
        $db->bind(':id', $pasanteId);
        if (!$db->single()) {
            echo json_encode(['success' => false, 'message' => 'Pasante no encontrado.']); exit;
        }

        // Cap 2: respetar fecha_fin_estimada del pasante — nunca rellenar más allá
        // de cuando realmente terminó, aunque el admin haya puesto una fecha posterior.
        $db->query("
            SELECT dpa.fecha_fin_estimada,
                   COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) AS fecha_fin_efectiva
            FROM   datos_pasante dpa
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE  dpa.usuario_id = :pid
            LIMIT 1
        ");
        $db->bind(':pid', $pasanteId);
        $dpRow = $db->single();
        if ($dpRow && !empty($dpRow->fecha_fin_efectiva) && $fechaFin > $dpRow->fecha_fin_efectiva) {
            $fechaFin = $dpRow->fecha_fin_efectiva;
        }

        if ($fechaFin < $fechaInicio) {
            echo json_encode(['success' => false, 'message' => 'La pasantía ya concluyó antes de la fecha de inicio indicada.']); exit;
        }

        // Cargar días ya registrados en el rango
        $db->query("SELECT DATE_FORMAT(fecha,'%Y-%m-%d') AS f FROM asistencias WHERE pasante_id = :pid AND fecha BETWEEN :fi AND :ff");
        $db->bind(':pid', $pasanteId);
        $db->bind(':fi',  $fechaInicio);
        $db->bind(':ff',  $fechaFin);
        $yaRegistrados = [];
        foreach ($db->resultSet() as $r) { $yaRegistrados[$r->f] = true; }

        // Cargar feriados NO laborables del rango (para marcarlos como Justificado)
        // Los feriados LABORABLES se tratan como días normales (Presente)
        $db->query("SELECT DATE_FORMAT(fecha,'%Y-%m-%d') AS f FROM dias_feriados WHERE es_laborable = 0 AND fecha BETWEEN :fi AND :ff");
        $db->bind(':fi', $fechaInicio);
        $db->bind(':ff', $fechaFin);
        $feriadosNoLab = [];
        foreach ($db->resultSet() as $r) { $feriadosNoLab[$r->f] = true; }

        // Iterar todos los días L-V — feriados no laborables → Justificado, el resto → Presente
        $cur        = new DateTime($fechaInicio);
        $end        = new DateTime($fechaFin);
        $insertados = 0;
        $omitidos   = 0;

        while ($cur <= $end) {
            $ymd = $cur->format('Y-m-d');
            $dow = (int)$cur->format('N');

            if ($dow < 6) { // Solo L-V
                if (isset($yaRegistrados[$ymd])) {
                    $omitidos++;
                } else {
                    $esFeriadoNoLab = isset($feriadosNoLab[$ymd]);
                    $estado   = $esFeriadoNoLab ? 'Justificado' : 'Presente';
                    $horaEnt  = $esFeriadoNoLab ? '00:00:00'    : '08:00:00';
                    $horaSal  = $esFeriadoNoLab ? '00:00:00'    : '16:00:00';
                    $motivo   = $esFeriadoNoLab ? 'Feriado no laborable' : 'Rellenado por administrador';

                    $db->query("
                        INSERT INTO asistencias
                            (pasante_id, fecha, estado, hora_entrada, hora_salida, horas_calculadas, es_auto_fill, observacion)
                        VALUES
                            (:pid, :fecha, :est, :hent, :hsal, 8.00, 0, :obs)
                    ");
                    $db->bind(':pid',   $pasanteId);
                    $db->bind(':fecha', $ymd);
                    $db->bind(':est',   $estado);
                    $db->bind(':hent',  $horaEnt);
                    $db->bind(':hsal',  $horaSal);
                    $db->bind(':obs',   $motivo);
                    $db->execute();
                    $insertados++;
                }
            }
            $cur->modify('+1 day');
        }

        // Recalcular horas_acumuladas en datos_pasante para que quede consistente
        // con los registros reales de asistencias (suma de horas_calculadas).
        if ($insertados > 0) {
            $db->query("
                UPDATE datos_pasante
                SET    horas_acumuladas = (
                           SELECT COUNT(*) * 8
                           FROM   asistencias a
                           WHERE  a.pasante_id = :pid
                             AND  a.estado != 'Anulado'
                       )
                WHERE  usuario_id = :pid2
            ");
            $db->bind(':pid',  $pasanteId);
            $db->bind(':pid2', $pasanteId);
            $db->execute();
        }

        // Bitácora
        $adminId = (int)Session::get('user_id');
        $db->query("INSERT INTO bitacora (usuario_id, accion, ip_address, detalles) VALUES (:uid, 'RELLENAR_ASISTENCIA_ADMIN', :ip, :det)");
        $db->bind(':uid', $adminId);
        $db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $db->bind(':det', json_encode(['pasante_id' => $pasanteId, 'desde' => $fechaInicio, 'hasta' => $fechaFin, 'insertados' => $insertados]));
        $db->execute();

        echo json_encode([
            'success'    => true,
            'insertados' => $insertados,
            'omitidos'   => $omitidos,
        ]);
        exit;

        } catch (\Throwable $e) {
            // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
            $prev = $e->getPrevious();
            error_log('[SGP-ASISTENCIAS] rellenarAsistenciaAdmin error: ' . ($prev ? $prev->getMessage() : $e->getMessage()));
            echo json_encode(['success' => false, 'message' => 'Error al rellenar la asistencia. Intente de nuevo.']);
            exit;
        }
    }

    /**
     * POST /asistencias/corregirAusente  (AJAX → JSON)
     * SuperAdmin/Admin: cambia un registro Ausente → Presente
     */
    public function corregirAusente(): void
    {
        header('Content-Type: application/json');
        if (!in_array((int)Session::get('role_id'), [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']); exit;
        }
        $raw   = file_get_contents('php://input');
        $json  = json_decode($raw, true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }
        $asistenciaId = (int)($json['asistencia_id'] ?? 0);
        if (!$asistenciaId) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']); exit;
        }
        try {
            $this->db->query("UPDATE asistencias SET estado = 'Presente', hora_entrada = COALESCE(hora_entrada,'08:00:00'), horas_calculadas = 8.00, observacion = 'Corregido por administrador' WHERE id = :id AND estado = 'Ausente'");
            $this->db->bind(':id', $asistenciaId);
            $this->db->execute();
            $afectados = $this->db->rowCount();
            echo json_encode(['success' => true, 'afectados' => $afectados]);
        } catch (\Throwable $e) {
            // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
            $prev = $e->getPrevious();
            error_log('[SGP-ASISTENCIAS] corregirAusente error: ' . ($prev ? $prev->getMessage() : $e->getMessage()));
            echo json_encode(['success' => false, 'message' => 'Error al corregir el registro. Intente de nuevo.']);
        }
        exit;
    }
}
