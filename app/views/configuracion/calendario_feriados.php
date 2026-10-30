<?php
/**
 * Vista: Almanaque de Feriados
 * Muestra el año completo en 12 tarjetas (estilo heatmap/github)
 */

$year = $data['year'] ?? date('Y');
$feriados = $data['feriados'] ?? [];

$mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
?>

<div class="container-fluid py-4" style="max-width:100%;">
    
    <!-- Header Navegación -->
    <div class="almanac-banner" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#2563eb 100%);padding:24px 32px;border-radius:20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div class="almanac-banner-left" style="display:flex;align-items:center;gap:16px;">
            <a href="<?= URLROOT ?>/configuracion" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:12px;padding:10px 16px;color:#fff;text-decoration:none;font-size:0.88rem;font-weight:600;display:flex;align-items:center;gap:8px;transition:background .2s;white-space:nowrap;" onmouseover="this.style.background='rgba(255,255,255,.22)'" onmouseout="this.style.background='rgba(255,255,255,.12)'">
                <i class="ti ti-arrow-left"></i> <span class="txt-volver">Volver a Configuración</span>
            </a>
            <div>
                <h1 style="color:#fff;font-size:1.4rem;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                    <i class="ti ti-calendar-event" style="color:#bfdbfe;"></i> Almanaque de Feriados
                </h1>
                <p style="color:#93c5fd;font-size:0.85rem;margin:2px 0 0;font-weight:600;">Haz clic en un día para registrar o eliminar un feriado</p>
            </div>
        </div>

        <!-- Controles: Botón Sync + Paginador Año -->
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">

            <!-- Botón Sincronizar Feriados -->
            <button type="button" id="btnSyncFeriados" onclick="sincronizarFeriados()"
                style="background:linear-gradient(135deg,#059669,#10b981);color:white;padding:10px 18px;border:none;border-radius:12px;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;transition:all .2s;box-shadow:0 4px 14px rgba(5,150,105,0.4);white-space:nowrap;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 18px rgba(5,150,105,0.5)'"
                onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 14px rgba(5,150,105,0.4)'">
                <i class="ti ti-refresh" id="iconSyncFeriados"></i>
                Sincronizar <?= $year ?>
            </button>

            <!-- Botón Configurar Laborables -->
            <button type="button" id="btnConfigLaborables" onclick="abrirConfigLaborables()"
                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;padding:10px 18px;border:none;border-radius:12px;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;transition:all .2s;box-shadow:0 4px 14px rgba(79,70,229,0.4);white-space:nowrap;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 18px rgba(79,70,229,0.5)'"
                onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 14px rgba(79,70,229,0.4)'">
                <i class="ti ti-adjustments-horizontal"></i>
                Configurar Laborables
            </button>

            <!-- Paginador de Año -->
            <div style="display:flex;align-items:center;background:rgba(255,255,255,0.15);border-radius:14px;padding:4px;border:1px solid rgba(255,255,255,0.2);">
                <a href="?y=<?= $year - 1 ?>" style="padding:8px 14px;color:#fff;text-decoration:none;border-radius:10px;transition:background .2s;" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='transparent'"><i class="ti ti-chevron-left"></i></a>
                <div style="padding:0 20px;color:#fff;font-weight:800;font-size:1.2rem;letter-spacing:1px;"><?= $year ?></div>
                <a href="?y=<?= $year + 1 ?>" style="padding:8px 14px;color:#fff;text-decoration:none;border-radius:10px;transition:background .2s;" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='transparent'"><i class="ti ti-chevron-right"></i></a>
            </div>
        </div>
    </div>

    <!-- ══ Modal: Configurar Feriados Laborables ══════════════════════════ -->
    <div id="clModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:9000;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:680px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(0,0,0,.25);overflow:hidden;">

            <!-- Modal Header -->
            <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:24px 28px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <div>
                    <h2 id="clModalTitle" style="color:#fff;margin:0;font-size:1.2rem;font-weight:800;display:flex;align-items:center;gap:10px;">
                        <i class="ti ti-adjustments-horizontal"></i> Configurar Feriados Laborables
                    </h2>
                    <p style="color:#c4b5fd;margin:4px 0 0;font-size:0.82rem;font-weight:500;">
                        Indica cuáles feriados son días de trabajo en tu institución. Los <strong style="color:#a5f3fc;">laborables</strong> cuentan como días hábiles normales.
                    </p>
                </div>
                <button onclick="cerrarConfigLaborables()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:10px;width:36px;height:36px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <!-- Leyenda rápida -->
            <div style="padding:12px 28px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:20px;flex-shrink:0;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;color:#475569;">
                    <div style="width:12px;height:12px;border-radius:50%;background:#10b981;"></div> Laborable — se trabaja con normalidad
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;color:#475569;">
                    <div style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></div> No laborable — no se contabiliza en horas
                </div>
            </div>

            <!-- Modal Body -->
            <div id="clModalBody" style="overflow-y:auto;flex:1;padding:20px 28px;">
                <div style="text-align:center;padding:40px;color:#64748b;">
                    <i class="ti ti-loader-2" style="font-size:2rem;animation:spin 1s linear infinite;"></i>
                    <div style="margin-top:10px;font-size:0.9rem;">Cargando feriados...</div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:12px;flex-wrap:wrap;">
                <div id="clContadorInfo" style="font-size:0.8rem;color:#64748b;font-weight:600;"></div>
                <div style="display:flex;gap:10px;">
                    <button onclick="cerrarConfigLaborables()" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;padding:10px 18px;border-radius:10px;font-size:0.85rem;font-weight:600;cursor:pointer;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                        Cancelar
                    </button>
                    <button id="btnGuardarLaborables" onclick="guardarConfigLaborables()"
                        style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;padding:10px 22px;border:none;border-radius:10px;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 4px 12px rgba(79,70,229,0.3);"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                        <i class="ti ti-check"></i> Guardar configuración
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- ══════════════════════════════════════════════════════════════════ -->

    <!-- Estilos del Grid Almanaque -->
    <style>
        /* Ocultar footer para no romper el diseño */
        footer, .sgp-footer { display: none !important; }
        body { padding-bottom: 20px; }
        
        /* Banner Responsive */
        @media(max-width: 768px) {
            .almanac-banner { flex-direction: column !important; align-items: stretch !important; padding: 20px !important; }
            .almanac-banner-left { flex-direction: column !important; align-items: flex-start !important; }
            .txt-volver { display: none; }
        }

        .fer-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; }
        @media(max-width:1200px) { .fer-grid { grid-template-columns:repeat(3, 1fr); } }
        @media(max-width:992px) { .fer-grid { grid-template-columns:repeat(2, 1fr); } }
        @media(max-width:600px) { .fer-grid { grid-template-columns:1fr; } }
        
        /* Efecto premium en tarjetas de meses */
        @keyframes cascadeFadeUp {
            0% { opacity: 0; transform: translateY(25px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .as-mf-card { 
            background: #ffffff;
            background-clip: padding-box; 
            border: 2px solid #e2e8f0; /* Borde limpio en reposo */
            border-radius: 16px; 
            padding: 16px 18px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            position: relative;
            transition: transform .3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow .3s ease, border-color .3s ease;
            animation: cascadeFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            z-index: 1; 
        }

        /* Borde base invisible para la animación */
        .as-mf-card::before {
            content: '';
            position: absolute;
            inset: -2px; /* Alineado con el borde exterior */
            border-radius: 16px;
            z-index: -1;
            pointer-events: none;
        }

        /* Animación pulsante más gruesa */
        @keyframes pulseCardGlow {
            0% {
                box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.8), 0 0 0 2px rgba(37, 99, 235, 0.6);
            }
            70% {
                box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.8), 0 0 0 16px rgba(37, 99, 235, 0);
            }
            100% {
                box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.8), 0 0 0 2px rgba(37, 99, 235, 0);
            }
        }

        /* Efecto Elevación sin sobreescribir la animación de entrada */
        .as-mf-card:hover {
            transform: translateY(-6px);
            border-color: #2563eb; /* Borde original se ilumina */
            z-index: 5;
        }
        
        /* El pulso se activa en el pseudo-elemento */
        .as-mf-card:hover::before {
            animation: pulseCardGlow 1.5s infinite;
        }

        .as-mf-title { display:flex; align-items:center; justify-content:space-between; font-size:0.95rem; font-weight:800; color:#1e293b; margin-bottom:16px; }
        .as-mf-head { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-bottom:6px; }
        .as-mf-head div { text-align:center; font-size:.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; padding:4px 0; }
        .as-mf-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
        
        /* Celdas interactivas */
        .as-mf-cell {
            aspect-ratio:1; border-radius:8px;
            display:flex; align-items:center; justify-content:center;
            font-size:.78rem; font-weight:700; cursor:pointer;
            transition:transform .15s, box-shadow .15s, background .15s;
            position: relative;
        }
        .as-mf-cell:hover:not([data-e="fuera"]) { transform:scale(1.15); box-shadow:0 4px 10px rgba(0,0,0,0.1); z-index:2; }
        .as-mf-cell[data-e="fuera"] { background:transparent; color:transparent; pointer-events:none; }
        .as-mf-cell[data-e="vacio"] { background:#f1f5f9; color:#64748b; }
        .as-mf-cell[data-e="vacio"]:hover { background:#e2e8f0; color:#1e293b; }
        .as-mf-cell[data-e="feriado"]               { background:#f59e0b; color:#fff; box-shadow:inset 0 -2px 0 rgba(0,0,0,0.15); }
        .as-mf-cell[data-e="feriado"]:hover          { background:#d97706; }
        .as-mf-cell[data-e="feriado"][data-lab="1"]  { background:#10b981; }
        .as-mf-cell[data-e="feriado"][data-lab="1"]:hover { background:#059669; }
        .as-mf-cell.findesemana { opacity:0.6; }
        
        /* Día actual: Morado animado */
        .as-mf-cell.dia-actual {
            background: #7c3aed !important; /* Morado más intenso */
            color: #fff !important;
            font-weight: 900;
            border: none !important;
            animation: pulse-purple 2s infinite;
            z-index: 1;
        }
        @keyframes pulse-purple {
            0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(139, 92, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
        }
        
        /* Tooltip animado para Feriados */
        .as-mf-cell[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%) translateY(5px);
            background: #1e293b;
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s;
            font-weight: 600;
            z-index: 10;
        }
        .as-mf-cell[data-tooltip]::before {
            content: '';
            position: absolute;
            bottom: calc(100% + 3px);
            left: 50%;
            transform: translateX(-50%) translateY(5px);
            border-width: 5px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s;
            z-index: 10;
        }
        .as-mf-cell[data-tooltip]:hover::after,
        .as-mf-cell[data-tooltip]:hover::before {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>

    <!-- Grid de Meses -->
    <div class="fer-grid">
        <?php for($m = 1; $m <= 12; $m++): 
            $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $m, $year);
            $primerDia = date('N', strtotime("$year-$m-01")); // 1 (Lun) a 7 (Dom)
            
            // Contar feriados del mes
            $feriadosMes = 0;
            for($d = 1; $d <= $diasEnMes; $d++) {
                $fechaLoop = sprintf("%04d-%02d-%02d", $year, $m, $d);
                if(isset($feriados[$fechaLoop])) $feriadosMes++;
            }
        ?>
        <div class="as-mf-card" style="animation-delay: <?= $m * 0.05 ?>s;">
            <div class="as-mf-title">
                <span><?= $mesesNombres[$m] ?></span>
                <?php if($feriadosMes > 0): ?>
                <span style="background:#fef3c7;color:#d97706;padding:3px 8px;border-radius:12px;font-size:0.7rem;font-weight:800;"><?= $feriadosMes ?> feriado(s)</span>
                <?php endif; ?>
            </div>
            
            <div class="as-mf-head">
                <?php foreach($diasSemana as $dow): ?>
                <div><?= $dow ?></div>
                <?php endforeach; ?>
            </div>
            
            <div class="as-mf-grid">
                <?php 
                // Celdas vacías iniciales
                for($i = 1; $i < $primerDia; $i++): ?>
                    <div class="as-mf-cell" data-e="fuera"></div>
                <?php endfor; ?>
                
                <?php 
                // Días del mes
                for($d = 1; $d <= $diasEnMes; $d++): 
                    $fechaStr = sprintf("%04d-%02d-%02d", $year, $m, $d);
                    $diaSemana = date('N', strtotime($fechaStr));
                    $esFinDeSemana = ($diaSemana == 6 || $diaSemana == 7);
                    
                    $esFeriado = isset($feriados[$fechaStr]);
                    $fer = $esFeriado ? $feriados[$fechaStr] : null;
                    
                    $esHoy = ($fechaStr === date('Y-m-d'));
                    
                    $dataE      = $esFeriado ? "feriado" : "vacio";
                    $esLab      = $esFeriado ? (int)($fer->es_laborable ?? 0) : 0;
                    $claseExtra = $esFinDeSemana && !$esFeriado ? "findesemana" : "";
                    if ($esHoy) $claseExtra .= " dia-actual";

                    $tooltipText = '';
                    if ($esFeriado) {
                        $tooltipText = htmlspecialchars($fer->nombre) . ($esLab ? ' — Laborable' : ' — No laborable');
                    }
                    $tooltipAttr = $esFeriado ? 'data-tooltip="' . $tooltipText . '"' : '';
                ?>
                    <div class="as-mf-cell <?= $claseExtra ?>"
                         data-e="<?= $dataE ?>"
                         data-lab="<?= $esLab ?>"
                         <?= $tooltipAttr ?>
                         data-fecha="<?= $fechaStr ?>"
                         data-es-feriado="<?= $esFeriado ? '1' : '0' ?>"
                         data-id="<?= $esFeriado ? $fer->id : '0' ?>"
                         data-nombre="<?= htmlspecialchars($esFeriado ? $fer->nombre : '', ENT_QUOTES) ?>"
                         data-tipo="<?= htmlspecialchars($esFeriado ? $fer->tipo : 'Nacional', ENT_QUOTES) ?>"
                         data-es-laborable="<?= $esLab ?>"
                         onclick="abrirModalPorFecha(this)">
                        <?= $d ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    
    <!-- Leyenda -->
    <div style="margin-top:20px;display:flex;gap:16px;background:#fff;padding:12px 20px;border-radius:12px;border:1px solid #e2e8f0;width:fit-content;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:14px;height:14px;border-radius:4px;background:#f59e0b;"></div>
            <span style="font-size:0.8rem;font-weight:700;color:#64748b;">Feriado no laborable</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:14px;height:14px;border-radius:4px;background:#10b981;"></div>
            <span style="font-size:0.8rem;font-weight:700;color:#64748b;">Feriado laborable</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:14px;height:14px;border-radius:4px;background:#f1f5f9;"></div>
            <span style="font-size:0.8rem;font-weight:700;color:#64748b;">Día normal</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:14px;height:14px;border-radius:4px;background:#7c3aed;"></div>
            <span style="font-size:0.8rem;font-weight:700;color:#64748b;">Hoy</span>
        </div>
    </div>

</div>

<div id="modalFeriadoUnico123" class="sgp-modal-overlay" style="display:none;position:fixed;top:-20px;left:-20px;right:-20px;bottom:-20px;inset:-20px;width:calc(100% + 40px);height:calc(100% + 40px);background:rgba(15,23,42,0.8);backdrop-filter:blur(4px);z-index:99999999;align-items:center;justify-content:center;">
    <div class="sgp-modal-card" style="background:#fff;width:100%;max-width:480px;border-radius:24px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); font-family: 'Inter', sans-serif;">
        
        <!-- Header del Modal -->
        <div style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 24px 28px; display: flex; justify-content: space-between; align-items: flex-start; position: relative;">
            <div style="display: flex; gap: 16px; align-items: center;">
                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2);">
                    <i class="ti ti-calendar-event"></i>
                </div>
                <div>
                    <h3 id="mfTitulo" style="margin: 0; color: #fff; font-size: 1.25rem; font-weight: 800; letter-spacing: -0.5px;">Registrar Día Feriado</h3>
                    <p style="margin: 4px 0 0; color: #bfdbfe; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <i class="ti ti-calendar-check"></i> <span id="mfFechaDisplay">Cargando fecha...</span>
                    </p>
                </div>
            </div>
            <button onclick="cerrarModalFeriado()" style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.1); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <!-- Cuerpo del Modal -->
        <div style="padding: 28px;">
            <form id="formFeriadoAccion" action="<?= URLROOT ?>/configuracion" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>"><!-- [SGP-FIX-CSRF-FERIADOS] -->
                <input type="hidden" name="origen" value="almanaque">
                <input type="hidden" name="accion" id="mfAccion" value="agregar_feriado">
                <input type="hidden" name="id" id="mfId" value="">
                <input type="hidden" name="fecha" id="mfFecha" value="">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Nombre / Motivo del Feriado <span style="color:#ef4444">*</span></label>
                    <input type="text" name="nombre" id="mfNombre" required placeholder="Ej: Día de la Independencia"
                           style="width: 100%; padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 12px; font-size: 0.95rem; font-weight: 600; color: #1e293b; outline: none; transition: all 0.2s;"
                           onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 4px rgba(59,130,246,0.1)'" 
                           onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Tipo de Feriado <span style="color:#ef4444">*</span></label>
                    <select name="tipo" id="mfTipo"
                            style="width: 100%; padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 12px; font-size: 0.95rem; font-weight: 600; color: #1e293b; outline: none; transition: all 0.2s; background: #fff; cursor: pointer; appearance: auto;"
                            onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 4px rgba(59,130,246,0.1)'" 
                            onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                        <option value="Nacional">🟠 Nacional</option>
                        <option value="Regional">🟢 Regional</option>
                        <option value="Institucional">🔵 Institucional</option>
                    </select>
                </div>
                
                <!-- Toggle laborabilidad -->
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:0.75rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">¿Se trabaja este día?</label>
                    <label id="mfToggleLabel" style="display:flex;align-items:center;gap:14px;cursor:pointer;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 16px;transition:all .2s;">
                        <input type="hidden" name="es_laborable" id="mfEsLaborableHidden" value="0">
                        <div id="mfToggleTrack" style="width:44px;height:24px;border-radius:12px;background:#cbd5e1;position:relative;transition:background .2s;flex-shrink:0;">
                            <div id="mfToggleThumb" style="width:18px;height:18px;border-radius:50%;background:#fff;position:absolute;top:3px;left:3px;transition:left .2s;box-shadow:0 1px 4px rgba(0,0,0,0.2);"></div>
                        </div>
                        <div>
                            <div id="mfToggleTitle" style="font-size:0.9rem;font-weight:700;color:#1e293b;">No laborable</div>
                            <div id="mfToggleSub" style="font-size:0.75rem;color:#64748b;margin-top:1px;">El sistema omitirá este día en el conteo de asistencias</div>
                        </div>
                    </label>
                </div>

                <div id="mfInfoBox" style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:16px;display:flex;gap:12px;margin-bottom:24px;">
                    <i class="ti ti-alert-triangle" style="color:#d97706;font-size:1.25rem;flex-shrink:0;margin-top:2px;"></i>
                    <p id="mfInfoText" style="margin:0;font-size:0.82rem;color:#92400e;font-weight:500;line-height:1.5;">
                        El sistema <strong>omitirá</strong> este día en el auto-fill y las notificaciones avisarán que no hay actividad de asistencia.
                    </p>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="cerrarModalFeriado()" style="flex: 1; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 12px; background: #fff; color: #475569; font-weight: 800; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#fff'; this.style.borderColor='#cbd5e1';">
                        Cancelar
                    </button>
                    <button type="submit" id="mfBtnGuardar" style="flex: 2; padding: 12px; border: none; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 800; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(37,99,235,0.25); transition: all 0.2s;" onmouseover="this.style.background='#1d4ed8'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#2563eb'; this.style.transform='none'">
                        <i class="ti ti-check"></i> Registrar Feriado
                    </button>
                </div>
            </form>
            
            <form id="formFeriadoEliminar" action="<?= URLROOT ?>/configuracion" method="POST" style="display:none; margin-top: 16px;">
                <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>"><!-- [SGP-FIX-CSRF-FERIADOS] -->
                <input type="hidden" name="origen" value="almanaque">
                <input type="hidden" name="accion" value="eliminar_feriado">
                <input type="hidden" name="id" id="mfEliminarId" value="">
                <button type="submit" onclick="return confirm('¿Estás seguro de eliminar este feriado? Los pasantes podrían perder la justificación.')" style="width: 100%; padding: 12px; border: 1px solid #fecaca; border-radius: 12px; background: #fef2f2; color: #ef4444; font-weight: 800; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                    <i class="ti ti-trash"></i> Eliminar Feriado
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// ── Sincronizar Feriados (año de la página actual) ─────────
function sincronizarFeriados() {
    const anio = <?= $year ?>;
    const btn  = document.getElementById('btnSyncFeriados');
    const icon = document.getElementById('iconSyncFeriados');

    btn.disabled = true;
    icon.className = 'ti ti-loader-2';
    icon.style.animation = 'spin 1s linear infinite';

    fetch('<?= URLROOT ?>/configuracion/sincronizarFeriados', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'anio=' + anio + '&csrf_token=<?= Session::generateCsrfToken() ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const insertados = data.insertados ?? 0;
            const yaExistian = data.ya_existian ?? 0;
            const totalApi   = data.total_api   ?? 0;

            let html = '<div style="text-align:left;font-size:0.88rem;line-height:1.8;">';
            html += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;">'
                  + '<span style="color:#64748b;">Consultados en la API</span>'
                  + '<strong>' + totalApi + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;">'
                  + '<span style="color:#059669;">✅ Nuevos insertados</span>'
                  + '<strong style="color:#059669;">' + insertados + '</strong></div>';
            html += '<div style="display:flex;justify-content:space-between;padding:6px 0;">'
                  + '<span style="color:#94a3b8;">⏭ Ya existían (omitidos)</span>'
                  + '<strong style="color:#94a3b8;">' + yaExistian + '</strong></div>';
            html += '</div>';
            if (data.nota) {
                html += '<div style="margin-top:10px;font-size:0.78rem;background:#fef9c3;border-radius:8px;padding:8px 12px;color:#854d0e;text-align:left;">'
                      + '⚠️ ' + data.nota + '</div>';
            }

            Swal.fire({
                icon: insertados > 0 ? 'success' : 'info',
                title: insertados > 0 ? '¡Feriados Sincronizados!' : 'Todo al día',
                html: html,
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Ver Calendario',
                showDenyButton: insertados > 0,
                denyButtonText: '<i class="ti ti-adjustments-horizontal"></i> Configurar Laborables',
                denyButtonColor: '#4f46e5',
            }).then(r => {
                if (r.isDenied) {
                    // Marcar en sessionStorage para abrir el modal tras la recarga
                    sessionStorage.setItem('sgp_abrir_config_laborables', '1');
                    location.reload();
                } else if (r.isConfirmed && insertados > 0) {
                    location.reload();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error de Sincronización',
                text: data.message || 'No se pudo conectar con la API de feriados.',
                footer: '<small>Verifica que el equipo tenga conexión a internet.</small>',
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Sin conexión',
            text: 'No se pudo contactar la API. Verifica la conexión a internet.',
            confirmButtonColor: '#dc2626'
        });
    })
    .finally(() => {
        btn.disabled = false;
        icon.className = 'ti ti-refresh';
        icon.style.animation = '';
    });
}

// ── Estilos del spinner ──────────────────────────────────── [SGP-FIX-PJAX-REDECL]
var _spinStyle = document.createElement('style');
_spinStyle.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
document.head.appendChild(_spinStyle);

function mfSetToggle(val) {
    const on    = val === 1 || val === '1' || val === true;
    document.getElementById('mfEsLaborableHidden').value = on ? '1' : '0';
    document.getElementById('mfToggleTrack').style.background  = on ? '#10b981' : '#cbd5e1';
    document.getElementById('mfToggleThumb').style.left        = on ? '23px' : '3px';
    document.getElementById('mfToggleTitle').innerText         = on ? 'Laborable' : 'No laborable';
    document.getElementById('mfToggleSub').innerText           = on
        ? 'El sistema contará este día como hábil (asistencia normal)'
        : 'El sistema omitirá este día en el conteo de asistencias';
    const box  = document.getElementById('mfInfoBox');
    const txt  = document.getElementById('mfInfoText');
    if (on) {
        box.style.background = '#ecfdf5'; box.style.borderColor = '#6ee7b7';
        txt.style.color = '#065f46';
        txt.innerHTML = 'El sistema <strong>contará</strong> este día como hábil. Se generarán registros de asistencia normalmente.';
    } else {
        box.style.background = '#fef3c7'; box.style.borderColor = '#fde68a';
        txt.style.color = '#92400e';
        txt.innerHTML = 'El sistema <strong>omitirá</strong> este día en el auto-fill y las notificaciones avisarán que no hay actividad de asistencia.';
    }
}

document.getElementById('mfToggleLabel').addEventListener('click', function() {
    const cur = document.getElementById('mfEsLaborableHidden').value;
    mfSetToggle(cur === '0' ? 1 : 0);
});

window.abrirModalPorFecha = function(el) {
    if (el.getAttribute('data-e') === 'fuera') return;
    try {
        const fecha = el.getAttribute('data-fecha');
        if (!fecha) return;
        const esFeriado   = el.getAttribute('data-es-feriado') === '1';
        const id          = el.getAttribute('data-id');
        const nombre      = el.getAttribute('data-nombre');
        const tipo        = el.getAttribute('data-tipo');
        const esLaborable = el.getAttribute('data-es-laborable') || '0';
        abrirModalFeriado(fecha, esFeriado, id, nombre, tipo, esLaborable);
    } catch(err) {
        console.error("Excepción atrapada al procesar el clic:", err);
    }
};

window.abrirModalFeriado = function(fecha, esFeriado, id, nombre, tipo, esLaborable) {
    try {
        const modal = document.getElementById('modalFeriadoUnico123');
        if (!modal) return;

        const dParts = fecha.split('-');
        const fDate  = new Date(dParts[0], dParts[1]-1, dParts[2]);
        let fechaFormateada = fDate.toLocaleDateString('es-VE', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
        fechaFormateada = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);
        document.getElementById('mfFechaDisplay').innerText = fechaFormateada;

        document.getElementById('mfFecha').value  = fecha;
        document.getElementById('mfId').value     = id || '';
        document.getElementById('mfNombre').value = nombre || '';
        document.getElementById('mfTipo').value   = tipo || 'Nacional';
        mfSetToggle(esLaborable === '1' ? 1 : 0);

        const formEliminar = document.getElementById('formFeriadoEliminar');

        if (esFeriado) {
            document.getElementById('mfAccion').value = 'editar_feriado';
            document.getElementById('mfTitulo').innerText = 'Editar Feriado';
            document.getElementById('mfBtnGuardar').innerHTML = '<i class="ti ti-check"></i> Actualizar';
            document.getElementById('mfEliminarId').value = id;
            formEliminar.style.display = 'block';
        } else {
            document.getElementById('mfAccion').value = 'agregar_feriado';
            document.getElementById('mfTitulo').innerText = 'Registrar Feriado';
            document.getElementById('mfBtnGuardar').innerHTML = '<i class="ti ti-check"></i> Guardar Feriado';
            formEliminar.style.display = 'none';
        }
        
        if (modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
        
        modal.setAttribute("style", "display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100% !important; height: 100% !important; background: rgba(15,23,42,0.8) !important; backdrop-filter: blur(4px) !important; z-index: 2147483647 !important; align-items: center !important; justify-content: center !important; opacity: 1 !important; visibility: visible !important; pointer-events: auto !important;");
        
    } catch(err) {
        console.error("Excepción atrapada al configurar el modal:", err);
    }
}

function cerrarModalFeriado() {
    const modal = document.getElementById('modalFeriadoUnico123');
    if (modal) {
        modal.style.setProperty("display", "none", "important");
    }
}

// Cerrar clickeando overlay — [SGP-FIX-PJAX-REDECL]
var modalEl = document.getElementById('modalFeriadoUnico123');
if (modalEl) {
    modalEl.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalFeriado();
    });
} else {
    console.warn("No se pudo adjuntar el evento de cierre porque modalFeriado no existe.");
}

// ══════════════════════════════════════════════════════════════
// Configurar Feriados Laborables — Modal bulk
// ══════════════════════════════════════════════════════════════
// [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
var _MESES_CL = {
    '01':'Enero','02':'Febrero','03':'Marzo','04':'Abril','05':'Mayo','06':'Junio',
    '07':'Julio','08':'Agosto','09':'Septiembre','10':'Octubre','11':'Noviembre','12':'Diciembre'
};
var _ANIO_CL = <?= $year ?>;

function abrirConfigLaborables() {
    const overlay = document.getElementById('clModalOverlay');
    overlay.style.display = 'flex';
    document.getElementById('clModalBody').innerHTML =
        '<div style="text-align:center;padding:40px;color:#64748b;">'
        + '<i class="ti ti-loader-2" style="font-size:2rem;animation:spin 1s linear infinite;display:block;margin:0 auto 12px;"></i>'
        + 'Cargando feriados...</div>';
    document.getElementById('clContadorInfo').textContent = '';

    fetch('<?= URLROOT ?>/configuracion/getFeriadosParaConfig?anio=' + _ANIO_CL, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            document.getElementById('clModalBody').innerHTML =
                '<div style="color:#dc2626;text-align:center;padding:40px;">Error al cargar los feriados.</div>';
            return;
        }
        const feriados = data.feriados;
        if (!feriados.length) {
            document.getElementById('clModalBody').innerHTML =
                '<div style="text-align:center;padding:40px;color:#64748b;">'
                + '<i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>'
                + 'No hay feriados registrados para ' + _ANIO_CL + '.<br>'
                + '<small>Sincroniza primero con la API o agrega feriados manualmente.</small></div>';
            return;
        }

        // Agrupar por mes
        const grupos = {};
        feriados.forEach(f => {
            const mes = f.fecha.substring(5, 7);
            if (!grupos[mes]) grupos[mes] = [];
            grupos[mes].push(f);
        });

        let html = '';
        Object.keys(grupos).sort().forEach(mes => {
            html += '<div style="margin-bottom:20px;">'
                  + '<div style="font-weight:800;font-size:0.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:10px;display:flex;align-items:center;gap:8px;">'
                  + '<div style="height:1px;flex:1;background:#e2e8f0;"></div>'
                  + _MESES_CL[mes]
                  + '<div style="height:1px;flex:1;background:#e2e8f0;"></div></div>';

            grupos[mes].forEach(f => {
                const dia     = f.fecha.substring(8, 10);
                const esLab   = parseInt(f.es_laborable) === 1;
                const tipoBg  = f.tipo === 'Nacional' ? '#e0e7ff' : f.tipo === 'Regional' ? '#fef3c7' : '#f0fdf4';
                const tipoClr = f.tipo === 'Nacional' ? '#4338ca' : f.tipo === 'Regional' ? '#92400e' : '#166534';
                html +=
                '<div class="cl-feriado-row" data-id="' + f.id + '" data-laborable="' + (esLab ? 1 : 0) + '" '
                + 'style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:#f8fafc;margin-bottom:7px;border:1px solid #e2e8f0;transition:background .15s;">'

                + '<div style="min-width:34px;text-align:center;background:#0f172a;color:#fff;border-radius:8px;padding:5px 0;font-size:0.82rem;font-weight:800;flex-shrink:0;">' + dia + '</div>'

                + '<div style="flex:1;min-width:0;">'
                + '<div style="font-weight:700;font-size:0.88rem;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + f.nombre + '</div>'
                + '<span style="font-size:0.7rem;background:' + tipoBg + ';color:' + tipoClr + ';padding:2px 8px;border-radius:20px;font-weight:700;">' + f.tipo + '</span>'
                + '</div>'

                + '<div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">'
                + '<span class="cl-lab-label" style="font-size:0.75rem;font-weight:700;color:' + (esLab ? '#059669' : '#94a3b8') + ';white-space:nowrap;">' + (esLab ? 'Laborable' : 'No laborable') + '</span>'
                + '<div class="cl-toggle-track" onclick="clToggle(this)" title="Clic para cambiar" '
                + 'style="position:relative;width:44px;height:24px;border-radius:12px;background:' + (esLab ? '#10b981' : '#cbd5e1') + ';cursor:pointer;transition:background .2s;flex-shrink:0;">'
                + '<div class="cl-toggle-thumb" style="position:absolute;top:3px;left:' + (esLab ? '23px' : '3px') + ';width:18px;height:18px;border-radius:50%;background:white;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:left .2s;pointer-events:none;"></div>'
                + '</div></div>'
                + '</div>';
            });
            html += '</div>';
        });

        document.getElementById('clModalBody').innerHTML = html;
        clActualizarContador();
    })
    .catch(() => {
        document.getElementById('clModalBody').innerHTML =
            '<div style="color:#dc2626;text-align:center;padding:40px;">No se pudo conectar. Verifica la conexión.</div>';
    });
}

function cerrarConfigLaborables() {
    document.getElementById('clModalOverlay').style.display = 'none';
}

function clToggle(track) {
    const row   = track.closest('.cl-feriado-row');
    const thumb = track.querySelector('.cl-toggle-thumb');
    const label = row.querySelector('.cl-lab-label');
    const esLab = row.dataset.laborable === '1';
    const nuevo = esLab ? 0 : 1;

    row.dataset.laborable  = nuevo;
    track.style.background = nuevo ? '#10b981' : '#cbd5e1';
    thumb.style.left       = nuevo ? '23px' : '3px';
    label.style.color      = nuevo ? '#059669' : '#94a3b8';
    label.textContent      = nuevo ? 'Laborable' : 'No laborable';
    clActualizarContador();
}

function clActualizarContador() {
    const rows     = document.querySelectorAll('.cl-feriado-row');
    const laborables = [...rows].filter(r => r.dataset.laborable === '1').length;
    document.getElementById('clContadorInfo').textContent =
        rows.length + ' feriados — ' + laborables + ' laborable(s), ' + (rows.length - laborables) + ' no laborable(s)';
}

function guardarConfigLaborables() {
    const rows = document.querySelectorAll('.cl-feriado-row');
    const payload = [...rows].map(r => ({
        id:           parseInt(r.dataset.id),
        es_laborable: parseInt(r.dataset.laborable)
    }));

    const btn = document.getElementById('btnGuardarLaborables');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i> Guardando...';

    var csrfMetaLab = document.querySelector('meta[name="csrf-token"]');
    fetch('<?= URLROOT ?>/configuracion/actualizarLaborables', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfMetaLab ? csrfMetaLab.getAttribute('content') : ''
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        cerrarConfigLaborables();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Configuración guardada!',
                text: data.message,
                confirmButtonColor: '#7c3aed',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar.', confirmButtonColor: '#dc2626' });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar la configuración.', confirmButtonColor: '#dc2626' });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Guardar configuración';
    });
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarConfigLaborables();
});

// Cerrar clickeando el overlay
document.getElementById('clModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) cerrarConfigLaborables();
});

// Auto-abrir el modal si volvemos de una sincronización con "Configurar Laborables"
if (sessionStorage.getItem('sgp_abrir_config_laborables') === '1') {
    sessionStorage.removeItem('sgp_abrir_config_laborables');
    setTimeout(() => abrirConfigLaborables(), 400);
}
</script>

