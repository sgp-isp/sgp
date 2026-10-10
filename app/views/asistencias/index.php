<?php
/**
 * Vista: Control de Asistencias
 * Diseño tomado del backup (20-02-2026) + datos reales del DB normalizado (3NF)
 *
 * Variables recibidas del AsistenciasController::index():
 *   $data['registrosHoy']   — array de objetos: id, hora_registro, estado, metodo,
 *                             pasante_id, cedula, nombres, apellidos, departamento_nombre
 *   $data['sinMarcar']      — pasantes activos que NO marcaron hoy
 *   $data['presentes']      — int
 *   $data['justificados']   — int
 *   $data['ausentes']       — int
 *   $data['totalActivos']   — int
 *   $data['pasantesActivos']— para el select del modal manual
 *   $data['hoy']            — string Y-m-d
 */

$registrosHoy    = $data['registrosHoy']    ?? $data['registrosLista'] ?? [];
$sinMarcar       = $data['sinMarcar']       ?? [];
$presentes       = $data['presentes']       ?? 0;
$justificados    = $data['justificados']    ?? 0;
$ausentes        = $data['ausentes']        ?? 0;
$totalActivos    = $data['totalActivos']    ?? 0;
$pasantesActivos = $data['pasantesActivos'] ?? [];
$hoy             = $data['hoy']             ?? date('Y-m-d');
$vistaActual     = $data['vistaActual']     ?? $data['vista'] ?? 'diaria';
$tituloRango     = $data['tituloRango']     ?? 'del día';

$estadoConfig = [
    'Presente'    => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'ti-check'],
    'Justificado' => ['bg' => '#dbeafe', 'color' => '#2563eb', 'icon' => 'ti-file-description'],
    'Ausente'     => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'ti-x'],
];
?>

<style>
/* Premium DataTables Overrides */
table.dataTable { border-collapse: collapse !important; border-spacing: 0; }
table.dataTable th, table.dataTable td {
    border-left: none !important;
    border-right: none !important;
    border-bottom: 1px solid #f0f2f5 !important;
    padding: 1rem 0.75rem !important;
    vertical-align: middle;
}
table.dataTable tbody tr:hover {
    background-color: #f8f9fc !important;
    transition: background-color 0.2s ease;
}

/* ── Responsividad Banner Asistencias ── */
.asist-banner-actions { display: flex; gap: 12px; align-items: center; z-index: 1; }
@media (max-width: 768px) {
    .module-banner {
        flex-wrap: wrap !important;
        padding: 20px 18px !important;
        gap: 16px !important;
    }
    .module-banner > div:first-of-type { width: 100%; }
    .asist-banner-actions {
        width: 100%; flex-wrap: wrap; gap: 8px;
    }
    .asist-banner-actions button {
        flex: 1; min-width: 140px; justify-content: center;
    }
    /* Stack de dos columnas diarias */
    .asist-diaria-grid {
        grid-template-columns: 1fr !important;
    }
    /* KPIs 2x2 en tablet */
    .asist-kpi-grid-4 { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 480px) {
    .asist-kpi-grid-4 { grid-template-columns: 1fr !important; }
    .module-banner h1 { font-size: 1.3rem !important; }
}
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- ===== BANNER PREMIUM SGP ===== -->
    <div class="module-banner" style="
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        border-radius: 20px; padding: 32px 40px; margin-bottom: 28px;
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 16px;">

        <!-- Círculos decorativos -->
        <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
        <div style="position:absolute;bottom:-50px;right:150px;width:150px;height:150px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>

        <!-- Lado Izquierdo: Título y Selector -->
        <div style="z-index: 1;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:10px;">
                    <i class="ti ti-calendar-stats" style="font-size:28px;color:white;"></i>
                </div>
                <div>
                    <h1 style="color:white;font-size:1.8rem;font-weight:700;margin:0;">Control de Asistencias</h1>
                </div>
            </div>
            
            <!-- Selector de Vistas: Chips Individuales -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php
                $filtros = [
                    ['key' => 'diaria',  'label' => 'Diario'],
                    ['key' => 'semanal', 'label' => 'Semanal'],
                    ['key' => 'mensual', 'label' => 'Mensual'],
                    ['key' => 'anual',   'label' => 'Total'],
                ];
                foreach ($filtros as $f):
                    $isActive = ($vistaActual === $f['key']);
                    if ($isActive) {
                        $style = "background: rgba(255,255,255,0.30); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.4); border-radius: 50px; padding: 6px 16px; color: white; font-size: 0.85rem; font-weight: 600; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.2s;";
                    } else {
                        $style = "background: rgba(255,255,255,0.10); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; padding: 6px 16px; color: white; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: all 0.2s;";
                    }
                ?>
                <a href="<?= URLROOT ?>/asistencias?vista=<?= $f['key'] ?>" style="<?= $style ?>" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='<?= $isActive ? 'rgba(255,255,255,0.30)' : 'rgba(255,255,255,0.10)' ?>'">
                    <?= $f['label'] ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lado Derecho: Acciones -->
        <div class="asist-banner-actions">
            <button onclick="abrirModalConsulta()" style="background: rgba(255, 255, 255, 0.15); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px); transition: all 0.2s;" onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                <i class="ti ti-search" style="font-size: 1.1rem;"></i> Consulta Rápida
            </button>
            <button onclick="abrirModalManualNuevo()" style="background: white; color: #1e3a8a; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="ti ti-plus" style="font-size: 1.1rem;"></i> Registro Manual
            </button>
        </div>
    </div>

    <!-- ===== TARJETAS ESTADÍSTICAS (PREMIUM) ===== -->
    <?php if ($vistaActual === 'anual'): ?>
        <?php
            $historicoAnual = 0; $enCursoAnual = 0; $finalizadosAnual = 0;
            if(!empty($registrosLista)) {
                $pasantesAnual = [];
                foreach($registrosLista as $reg) {
                    $pid = $reg->pasante_id ?? 'P'.$reg->id;
                    if(!isset($pasantesAnual[$pid])) {
                        $pasantesAnual[$pid] = [
                            'presentes'       => 0,
                            'horas_meta'      => (int)($reg->horas_meta ?? 1440),
                            'estado_pasantia' => $reg->estado_pasantia ?? 'Sin Asignar',
                        ];
                    }
                    if(stripos($reg->estado, 'presente') !== false || stripos($reg->estado, 'justificado') !== false) {
                        $pasantesAnual[$pid]['presentes']++;
                    }
                }
                $historicoAnual = count($pasantesAnual);
                foreach($pasantesAnual as $pid => $data) {
                    if ($data['estado_pasantia'] === 'Finalizado') $finalizadosAnual++;
                    else $enCursoAnual++;
                }
            }
        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:20px;margin-bottom:32px;">
            <!-- Histórico Total (Azul) -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #3b82f6; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#3b82f6;margin:0;"><?= $historicoAnual ?></h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Pasantes Históricos</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">registrados en el año</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-users" style="font-size:24px;color:#3b82f6;"></i>
                    </div>
                </div>
            </div>
            <!-- Finalizados (Verde) -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #10b981; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#10b981;margin:0;"><?= $finalizadosAnual ?></h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Finalizados</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">cumplieron <?= number_format($horasMetaPeriodo ?? 1440, 0, ',', '.') ?> hrs</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-certificate" style="font-size:24px;color:#10b981;"></i>
                    </div>
                </div>
            </div>
            <!-- En Curso (Ámbar) -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #f59e0b; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#f59e0b;margin:0;"><?= $enCursoAnual ?></h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">En Curso</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">acumulando horas</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-progress-check" style="font-size:24px;color:#f59e0b;"></i>
                    </div>
                </div>
            </div>
            <!-- Retirados / Otros (Gris) -->
            <div class="stat-card opacity-75" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #64748b; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#64748b;margin:0;">0</h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Retirados</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">sin actividad reciente</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-user-off" style="font-size:24px;color:#64748b;"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="asist-kpi-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;">

            <!-- Total Activos -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #3b82f6; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#3b82f6;margin:0;" id="stat-total" data-kpi-value="<?= $totalActivos ?>">0</h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Activos</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">pasantes asignados</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-users" style="font-size:24px;color:#3b82f6;"></i>
                    </div>
                </div>
            </div>

            <!-- Presentes -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #10b981; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#10b981;margin:0;" id="stat-presentes" data-kpi-value="<?= $presentes ?>">0</h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Presentes</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">
                            <span style="color:#10b981;font-weight:600;"><i class="ti ti-arrow-up-right"></i> <?= $totalActivos > 0 ? round($presentes / $totalActivos * 100) : 0 ?>%</span> asistencia
                        </p>
                    </div>
                    <div style="background:linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-user-check" style="font-size:24px;color:#10b981;"></i>
                    </div>
                </div>
            </div>

            <!-- Justificados -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #f59e0b; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#f59e0b;margin:0;" id="stat-justificados" data-kpi-value="<?= $justificados ?>">0</h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Justificados</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">con permiso / récipe</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-clock-pause" style="font-size:24px;color:#f59e0b;"></i>
                    </div>
                </div>
            </div>

            <!-- Sin Marcar / Ausentes -->
            <div class="stat-card" style="background:white;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #ef4444; transition: all 0.3s; cursor: default;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 style="font-size:2.2rem;font-weight:800;color:#ef4444;margin:0;" id="stat-ausentes" data-kpi-value="<?= $ausentes ?>">0</h2>
                        <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Sin Marcar</p>
                        <p style="color:#94a3b8;font-size:0.75rem;margin:4px 0 0;">sin justificación</p>
                    </div>
                    <div style="background:linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                        <i class="ti ti-user-x" style="font-size:24px;color:#ef4444;"></i>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>

    <!-- ===== ENRUTADOR DE VISTAS (TABLAS) ===== -->
    <div class="tablas-container">
        <?php if ($vistaActual === 'diaria' || empty($vistaActual)): ?>
            <!-- ===== TABLA REGISTROS DEL DÍA + LISTA SIN MARCAR ===== -->
            <div class="asist-diaria-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

        <!-- Marcaron Hoy -->
        <div style="background: white; border-radius: 18px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); overflow: hidden; margin-bottom: 24px; border: 1px solid #e2e8f0; border-top: 4px solid #10b981; transition: all 0.3s ease;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3 style="font-size:1rem;font-weight:800;color:#1e293b;margin:0; display:flex; align-items:center; letter-spacing: 0.3px;">
                    <i class="ti ti-check" style="color: #10b981; margin-right: 8px; font-size: 1.3rem;"></i> Marcaron Hoy
                </h3>
                <span style="background:#ecfdf5;color:#059669;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700; border: 1px solid #d1fae5;">
                    <?= count($registrosHoy) ?> registros
                </span>
            </div>

            <?php if (empty($registrosHoy)): ?>
            <div style="padding:48px 24px;text-align:center;">
                <i class="ti ti-calendar-off" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
                <p style="color:#94a3b8;margin:0;font-size:0.95rem; font-weight:600;">Ningún pasante ha marcado aún hoy</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;max-height:380px;overflow-y:auto; scrollbar-width: thin;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position:sticky;top:0;z-index:10;">
                        <tr>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Pasante</th>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Hora</th>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Método</th>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Estado</th>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: center; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($registrosHoy as $r):
                        $cfg      = $estadoConfig[$r->estado] ?? $estadoConfig['Ausente'];
                        $nombre   = trim(($r->apellidos ?? '') . ', ' . ($r->nombres ?? ''));
                        $iniciales = strtoupper(substr($r->nombres ?? '?', 0, 1) . substr($r->apellidos ?? '', 0, 1));
                    ?>
                    <tr style="transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#162660,#2563eb);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:0.8rem;flex-shrink:0; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                                    <?= htmlspecialchars($iniciales) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:#1e293b;font-size:0.88rem;"><?= htmlspecialchars($nombre) ?></div>
                                    <div style="color:#64748b;font-size:0.75rem; font-weight: 500;"><?= htmlspecialchars($r->cedula ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle; font-weight:700;">
                            <i class="ti ti-clock" style="color: #94a3b8; margin-right: 4px;"></i> <?= ($r->hora_registro && $r->hora_registro !== '00:00:00') ? date('h:i A', strtotime($r->hora_registro)) : '—' ?>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle;">
                            <?php if ($r->metodo): ?>
                            <span style="background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:6px;font-size:0.75rem;font-weight:700; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="ti ti-device-desktop"></i> <?= htmlspecialchars($r->metodo) ?>
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle;">
                            <div style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <span style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:800;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="ti <?= $cfg['icon'] ?>"></i> <?= htmlspecialchars($r->estado) ?>
                                </span>
                                <?php if ($r->estado === 'Presente' && (int)($r->es_retardo ?? 0) === 1): ?>
                                <span style="background:#fff7ed;color:#ea580c;border:1px solid #fed7aa;padding:3px 8px;border-radius:20px;font-size:0.7rem;font-weight:800;display:inline-flex;align-items:center;gap:3px;">
                                    <i class="ti ti-clock-exclamation"></i> Retardo
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle; text-align:center;">
                            <button onclick="verDetalle(<?= (int)$r->id ?>,<?= htmlspecialchars(json_encode([
                                'nombre'     => $nombre,
                                'cedula'     => $r->cedula ?? '—',
                                'depto'      => $r->departamento_nombre ?? '—',
                                'hora'       => $r->hora_registro ? date('h:i A', strtotime($r->hora_registro)) : '—',
                                'metodo'     => $r->metodo ?? '—',
                                'estado'     => $r->estado,
                                'motivo'     => $r->motivo_justificacion ?? '',
                                'pasante_id' => (int)($r->pasante_id ?? 0)
                            ]), ENT_QUOTES) ?>)"
                                style="background: #f8fafc; color: #3b82f6; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';"
                                title="Ver detalle">
                                <i class="ti ti-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sin Marcar Hoy -->
        <div style="background: white; border-radius: 18px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); overflow: hidden; margin-bottom: 24px; border: 1px solid #e2e8f0; border-top: 4px solid #ef4444; transition: all 0.3s ease;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <h3 style="font-size:1rem;font-weight:800;color:#1e293b;margin:0; display:flex; align-items:center; letter-spacing: 0.3px;">
                        <i class="ti ti-alert-circle" style="color: #ef4444; margin-right: 8px; font-size: 1.3rem;"></i> Sin Marcar Hoy
                    </h3>
                    <span style="background:#fef2f2;color:#dc2626;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700; border: 1px solid #fecaca;">
                        <?= count($sinMarcar) ?> pasantes
                    </span>
                </div>
                <?php if (!empty($sinMarcar)): ?>
                <?php if (!$esHabilHoy): ?>
                <!-- Día no hábil: botón deshabilitado con tooltip -->
                <button disabled title="<?= htmlspecialchars($motivoNoHabilHoy) ?> — no se puede registrar asistencia"
                    style="background:#e2e8f0;color:#94a3b8;border:none;padding:8px 16px;border-radius:8px;font-size:0.8rem;font-weight:700;cursor:not-allowed;display:flex;align-items:center;gap:6px;">
                    <i class="ti ti-ban" style="font-size:1.1rem;"></i> Marcar todos (Presente)
                </button>
                <?php else: ?>
                <!-- Día hábil: botón activo -->
                <button onclick="marcarTodosDiaria('Presente')" style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(16,185,129,0.25); display: flex; align-items: center; gap: 6px;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -2px rgba(16,185,129,0.35)'" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(16,185,129,0.25)'" title="Registrar asistencia masiva para los que faltan">
                    <i class="ti ti-checks" style="font-size: 1.1rem;"></i> Marcar todos (Presente)
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!$esHabilHoy): ?>
            <!-- Banner día no hábil -->
            <div style="margin:0 24px 16px;padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;display:flex;align-items:center;gap:10px;">
                <i class="ti ti-calendar-off" style="font-size:1.3rem;color:#d97706;flex-shrink:0;"></i>
                <div>
                    <div style="font-weight:700;color:#92400e;font-size:0.85rem;"><?= htmlspecialchars($motivoNoHabilHoy) ?></div>
                    <div style="color:#a16207;font-size:0.78rem;margin-top:2px;">No se puede registrar asistencia en días no hábiles.</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($sinMarcar)): ?>
            <div style="padding:48px 24px;text-align:center;">
                <i class="ti ti-circle-check" style="font-size:48px;color:#10b981;display:block;margin-bottom:12px;"></i>
                <p style="color:#10b981;margin:0;font-size:0.95rem;font-weight:700;">¡Todos los pasantes marcaron hoy! 🎉</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;max-height:380px;overflow-y:auto; scrollbar-width: thin;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position:sticky;top:0;z-index:10;">
                        <tr>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Pasante</th>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Departamento</th>
                            <th style="text-transform: uppercase; font-size: 0.72rem; font-weight: 800; color: #64748b; padding: 14px 24px; text-align: center; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sinMarcar as $p):
                        $nombre   = trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''));
                        $iniciales = strtoupper(substr($p->nombres ?? '?', 0, 1) . substr($p->apellidos ?? '', 0, 1));
                    ?>
                    <tr style="transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#ef4444,#b91c1c);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:0.8rem;flex-shrink:0; box-shadow: 0 2px 4px rgba(239,68,68,0.2);">
                                    <?= htmlspecialchars($iniciales) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:#1e293b;font-size:0.88rem;"><?= htmlspecialchars($nombre) ?></div>
                                    <div style="color:#64748b;font-size:0.75rem; font-weight: 500;"><?= htmlspecialchars($p->cedula ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #475569; font-size: 0.85rem; vertical-align: middle; font-weight: 600;">
                            <i class="ti ti-building-community" style="color: #94a3b8; margin-right: 4px;"></i> <?= htmlspecialchars($p->departamento_nombre ?? 'Sin asignar') ?>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; vertical-align: middle; text-align:center;">
                        <?php if ($esHabilHoy): ?>
                            <button onclick="abrirModalManual(<?= (int)$p->id ?>, '<?= addslashes($nombre) ?>')"
                                style="background: #f8fafc; color: #3b82f6; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';"
                                title="Registrar manualmente">
                                <i class="ti ti-plus"></i> Registrar
                            </button>
                        <?php else: ?>
                            <button disabled title="<?= htmlspecialchars($motivoNoHabilHoy) ?>"
                                style="background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;padding:6px 14px;border-radius:8px;font-weight:700;font-size:0.8rem;cursor:not-allowed;display:inline-flex;align-items:center;gap:4px;">
                                <i class="ti ti-ban"></i> No disponible
                            </button>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        // Script agregado para procesar "Marcar todos" en la vista Diaria
        window.marcarTodosDiaria = async function(estado) {
            const pasantes = <?= json_encode(array_values(array_map(function($p){ return ['id'=>$p->id, 'nombre'=>trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''))]; }, $sinMarcar ?? []))) ?>;
            
            if (!pasantes || !pasantes.length) return;

            const { isConfirmed } = await Swal.fire({
                title: `¿Marcar ${pasantes.length} pasantes como Presente?`,
                text: 'Se registrará su asistencia como Presente para el día de hoy.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, marcar todos',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b'
            });

            if (!isConfirmed) return;

            Swal.fire({
                title: 'Procesando...',
                html: `Procesando <b id="swal-progreso-diaria">0</b> de ${pasantes.length} registros`,
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let completados = 0;
            let errores = 0;
            const fechaHoy = '<?= date('Y-m-d') ?>';

            async function processNext(i) {
                if (i >= pasantes.length) {
                    Swal.fire({
                        icon: errores > 0 ? 'warning' : 'success',
                        title: 'Proceso completado',
                        text: `${completados} marcados correctamente.`,
                        confirmButtonColor: '#2563eb'
                    }).then(() => {
                        window.location.reload();
                    });
                    return;
                }

                let p = pasantes[i];
                let fd = new FormData();
                fd.append('pasante_id', p.id);
                fd.append('fecha', fechaHoy);
                fd.append('estado', estado);
                fd.append('observacion', 'Marcado masivo (Diaria)');

                try {
                    let r = await fetch('<?= URLROOT ?>/asistencias/registro_manual', { method:'POST', body:fd });
                    let res = await r.json();
                    if (res.success) completados++;
                    else errores++;
                } catch(e) {
                    errores++;
                }

                let label = document.getElementById('swal-progreso-diaria');
                if (label) label.textContent = completados + errores;

                processNext(i + 1);
            }

            processNext(0);
        };
        </script>

            </div>
        <?php elseif ($vistaActual === 'semanal'): ?>
            <?php
            // ── Calcular métricas por departamento para barras de progreso ──
            $deptStats = [];
            foreach ($datosSemanales as $dep => $pArr) {
                $tp = 0; $ta = 0; $tj = 0;
                foreach ($pArr as $pid => $pd) {
                    foreach ($pd['dias'] as $d) {
                        if ($d === 'P') $tp++;
                        elseif ($d === 'A') $ta++;
                        elseif ($d === 'J') $tj++;
                    }
                }
                $tot = $tp + $ta + $tj;
                $pct = $tot > 0 ? round(($tp + $tj) / $tot * 100) : 0;
                $deptStats[$dep] = ['p'=>$tp,'a'=>$ta,'j'=>$tj,'pct'=>$pct,'total'=>count($pArr)];
            }
            ?>

            <!-- ══ CSS PREMIUM SEMANAL ══ -->
            <style>
            .sw-wrap { font-family: 'Plus Jakarta Sans', sans-serif; }
            .sw-wrap *, .sw-wrap *::before, .sw-wrap *::after { box-sizing: border-box; }

            /* ── TOOLBAR ── */
            .sw-toolbar {
                display: flex; align-items: center; justify-content: space-between;
                gap: 12px; flex-wrap: wrap; margin-bottom: 20px;
            }
            .sw-toolbar-left  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
            .sw-toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

            .sw-search { position: relative; display: flex; align-items: center; }
            .sw-search .ti { position: absolute; left: 12px; color: #94a3b8; font-size: 1rem; pointer-events: none; }
            .sw-search input {
                padding: 9px 14px 9px 36px;
                border: 1.5px solid #DDE2F0; border-radius: 50px;
                font-size: .83rem; font-family: 'Plus Jakarta Sans', sans-serif;
                color: #0D1424; background: white; outline: none; width: 220px;
                transition: border-color .18s, box-shadow .18s;
            }
            .sw-search input:focus {
                border-color: #2563EB;
                box-shadow: 0 0 0 3px rgba(37,99,235,.08);
            }

            /* ── CHIPS ── */
            .sw-chips { display: flex; gap: 6px; flex-wrap: wrap; }
            .sw-chip {
                padding: 7px 14px; border-radius: 50px;
                font-size: .76rem; font-weight: 600;
                border: 1.5px solid #DDE2F0; background: white;
                color: #7480A0; cursor: pointer;
                transition: all .18s; display: flex; align-items: center; gap: 5px;
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .sw-chip:hover { border-color: #C3CCDF; color: #0D1424; }
            .sw-chip.sc-all.active  { background: #0D1424;  color: white;    border-color: #0D1424; }
            .sw-chip.sc-ok.active   { background: #ECFDF5;  color: #059669;  border-color: #059669; }
            .sw-chip.sc-falta.active{ background: #FEF2F2;  color: #DC2626;  border-color: #DC2626; }
            .sw-chip-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

            /* ── LEYENDA ── */
            .sw-legend {
                display: flex; gap: 14px; align-items: center;
                font-size: .72rem; font-weight: 600; color: #7480A0; flex-wrap: wrap;
            }
            .sw-legend-item { display: flex; align-items: center; gap: 5px; }
            .sw-legend-dot  { width: 11px; height: 11px; border-radius: 3px; }

            /* ── NAV SEMANA ── */
            .sw-nav-pill {
                display: flex; align-items: center;
                background: white; border: 1.5px solid #DDE2F0;
                border-radius: 50px; padding: 3px 4px; gap: 2px;
                box-shadow: 0 1px 4px rgba(13,20,36,.05);
            }
            .sw-nav-btn {
                background: transparent; border: none; color: #7480A0;
                padding: 6px 12px; border-radius: 50px;
                font-size: .8rem; font-weight: 600;
                font-family: 'Plus Jakarta Sans', sans-serif;
                cursor: pointer; transition: background .15s;
                display: flex; align-items: center; gap: 3px;
            }
            .sw-nav-btn:hover { background: #F2F5FC; color: #0D1424; }
            .sw-nav-label {
                font-size: .83rem; font-weight: 700;
                color: #0D1424; padding: 0 12px; white-space: nowrap;
            }

            /* ── FLATPICKR WRAP ── */
            .sw-fp-wrap {
                display: flex; align-items: center;
                background: white; border: 1.5px solid #DDE2F0;
                border-radius: 50px; padding: 2px 14px;
                box-shadow: 0 1px 4px rgba(13,20,36,.04);
            }
            .sw-fp-wrap label {
                font-size: .8rem; font-weight: 700; color: #7480A0;
                padding-right: 10px; border-right: 1.5px solid #DDE2F0;
                margin: 0; display: flex; align-items: center; gap: 5px;
            }
            .sw-fp-wrap input {
                border: none; outline: none; padding: 6px 8px;
                color: #1D4ED8; font-weight: 700; font-size: .82rem;
                background: transparent; cursor: pointer; width: 130px;
                font-family: 'Plus Jakarta Sans', sans-serif;
            }

            /* ── DEPT CARD ── */
            .sw-dept-card {
                background: white; border: 1px solid #DDE2F0;
                border-radius: 20px;
                box-shadow: 0 2px 8px rgba(13,20,36,.05);
                overflow: hidden; margin-bottom: 16px;
                transition: box-shadow .22s cubic-bezier(.16,1,.3,1);
            }
            .sw-dept-card:hover { box-shadow: 0 8px 24px rgba(13,20,36,.09); }
            .sw-dept-head {
                padding: 14px 22px; border-bottom: 1px solid #EDF0F8;
                background: #F8FAFD;
                display: flex; align-items: center; justify-content: space-between; gap: 14px;
            }
            .sw-dept-title {
                font-size: .9rem; font-weight: 700; color: #0D1424;
                display: flex; align-items: center; gap: 10px;
            }
            .sw-dept-icon {
                width: 32px; height: 32px; border-radius: 9px;
                display: flex; align-items: center; justify-content: center;
                font-size: 1rem; flex-shrink: 0;
            }
            .sw-dept-meta { display: flex; align-items: center; gap: 12px; }
            .sw-pbar-wrap { display: flex; align-items: center; gap: 8px; }
            .sw-pbar-track { width: 80px; height: 5px; background: #EDF0F8; border-radius: 99px; overflow: hidden; }
            .sw-pbar-fill  { height: 100%; border-radius: 99px; transition: width 1s cubic-bezier(.16,1,.3,1); }
            .sw-pbar-lbl   { font-family: 'JetBrains Mono', monospace; font-size: .72rem; font-weight: 700; color: #7480A0; }
            .sw-badge      { font-size: .69rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; padding: 4px 11px; border-radius: 50px; background: #EDF0F8; color: #7480A0; }

            /* ── TABLE ── */
            .sw-table-wrap { overflow-x: auto; }
            .sw-table { width: 100%; border-collapse: collapse; min-width: 580px; }
            .sw-table thead th {
                padding: 10px 16px; font-size: .67rem; font-weight: 700;
                text-transform: uppercase; letter-spacing: .7px;
                color: #7480A0; text-align: left; background: #F8FAFD;
                border-bottom: 1px solid #DDE2F0; white-space: nowrap;
            }
            .sw-table thead th.sw-th-day { text-align: center; width: 68px; }
            .sw-table thead th.sw-th-act { text-align: right; width: 110px; }
            .sw-table tbody td {
                padding: 0 16px; height: 58px;
                border-bottom: 1px solid #EDF0F8; vertical-align: middle;
            }
            .sw-table tbody tr { transition: background .14s; }
            .sw-table tbody tr:hover td { background: #F2F5FC; }
            .sw-table tbody tr:last-child td { border-bottom: none; }

            /* ── PERSONA ── */
            .sw-person { display: flex; align-items: center; gap: 10px; }
            .sw-av {
                width: 36px; height: 36px; border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                font-size: .74rem; font-weight: 700; color: white;
                flex-shrink: 0; letter-spacing: .3px;
            }
            .sw-pname { font-size: .85rem; font-weight: 600; color: #0D1424; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
            .sw-pdept { font-size: .7rem; color: #7480A0; }

            /* ── HEATMAP ── */
            .sw-day-td { text-align: center; }
            .sw-heat {
                display: inline-flex; align-items: center; justify-content: center;
                width: 38px; height: 38px; border-radius: 9px;
                font-size: .74rem; font-weight: 700;
                font-family: 'JetBrains Mono', monospace;
                transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s;
                cursor: default; position: relative; z-index: 1;
            }
            .sw-heat:hover { transform: scale(1.2); z-index: 5; }
            .sw-h-P { background:#D1FAE5; color:#065F46; box-shadow: 0 0 0 1.5px rgba(5,150,105,.2); }
            .sw-h-P:hover { box-shadow: 0 5px 14px rgba(5,150,105,.3); }
            .sw-h-A { background:#FEE2E2; color:#991B1B; box-shadow: 0 0 0 1.5px rgba(220,38,38,.18); }
            .sw-h-A:hover { box-shadow: 0 5px 14px rgba(220,38,38,.28); }
            .sw-h-J { background:#FEF3C7; color:#92400E; box-shadow: 0 0 0 1.5px rgba(217,119,6,.18); }
            .sw-h-J:hover { box-shadow: 0 5px 14px rgba(217,119,6,.28); }
            .sw-h-N { background: #EDF0F8; color: #B4BDD4; }

            /* ── BOTÓN VER ── */
            .sw-btn-ver {
                background: #EDF0F8; border: 1.5px solid #DDE2F0;
                color: #2563EB; padding: 7px 15px; border-radius: 9px;
                font-size: .76rem; font-weight: 600;
                font-family: 'Plus Jakarta Sans', sans-serif;
                cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
                transition: all .18s cubic-bezier(.16,1,.3,1);
            }
            .sw-btn-ver:hover {
                background: #DBEAFE; border-color: #2563EB;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(37,99,235,.15);
            }

            /* ── EMPTY STATE ── */
            .sw-empty {
                text-align: center; padding: 48px 24px;
                background: white; border-radius: 20px; border: 1px solid #DDE2F0;
            }

            /* ── ANIMATIONS ── */
            @keyframes sw-reveal {
                from { opacity:0; transform: translateY(12px); }
                to   { opacity:1; transform: translateY(0); }
            }
            .sw-rv { opacity:0; animation: sw-reveal .4s cubic-bezier(.16,1,.3,1) forwards; }
            .sw-d1{animation-delay:.04s}.sw-d2{animation-delay:.08s}.sw-d3{animation-delay:.12s}
            .sw-d4{animation-delay:.16s}.sw-d5{animation-delay:.20s}.sw-d6{animation-delay:.24s}
            .sw-d7{animation-delay:.28s}.sw-d8{animation-delay:.32s}
            .sw-collapsed { display: none !important; }

            /* ── Flatpickr fixes ── */
            .flatpickr-monthDropdown-months {
                display: inline-block !important; visibility: visible !important;
                width: auto !important; appearance: menulist !important;
                -moz-appearance: menulist !important; -webkit-appearance: menulist !important;
                background: transparent !important; border: none !important;
                color: inherit !important; height: auto !important;
                padding: 0 !important; margin: 0 !important; font-weight: bold;
            }
            .flatpickr-current-month { display: flex !important; justify-content: center !important; align-items: center !important; }
            .flatpickr-calendar { z-index: 1055 !important; }
            .flatpickr-day.inRange,.flatpickr-day.prevMonthDay.inRange,.flatpickr-day.nextMonthDay.inRange,
            .flatpickr-day.today.inRange,.flatpickr-day:hover,.flatpickr-day.prevMonthDay:hover,
            .flatpickr-day.nextMonthDay:hover,.flatpickr-day:focus,.flatpickr-day.prevMonthDay:focus,
            .flatpickr-day.nextMonthDay:focus { background:#DBEAFE !important; border-color:#DBEAFE !important; }
            .flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange,
            .flatpickr-day.selected.inRange,.flatpickr-day.startRange.inRange,.flatpickr-day.endRange.inRange,
            .flatpickr-day.selected:focus,.flatpickr-day.startRange:focus,.flatpickr-day.endRange:focus,
            .flatpickr-day.selected:hover,.flatpickr-day.startRange:hover,.flatpickr-day.endRange:hover,
            .flatpickr-day.selected.prevMonthDay,.flatpickr-day.startRange.prevMonthDay,
            .flatpickr-day.endRange.prevMonthDay,.flatpickr-day.selected.nextMonthDay,
            .flatpickr-day.startRange.nextMonthDay,.flatpickr-day.endRange.nextMonthDay
            { background:#1D4ED8 !important; color:#fff !important; border-color:#1D4ED8 !important; }

            /* ═══════════════════════════════════════════════════════════
               VISTA SEMANAL — ADAPTACIÓN MÓVIL (≤ 640px)
               Estrategia: tabla → tarjeta apilada, toolbar → columna.
               CSS-only, sin cambios en PHP/HTML.
            ═══════════════════════════════════════════════════════════ */
            @media (max-width: 640px) {

                /* ── Toolbar ─────────────────────────────────────────── */
                .sw-toolbar {
                    flex-direction: column !important;
                    gap: 10px !important;
                    padding: 14px 16px !important;
                }
                .sw-toolbar-left,
                .sw-toolbar-right {
                    width: 100% !important;
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 10px !important;
                }
                /* Buscador ancho completo */
                .sw-search { width: 100% !important; }
                .sw-search input { width: 100% !important; box-sizing: border-box !important; }

                /* Chips — tres botones en fila equitativa */
                .sw-chips {
                    display: flex !important;
                    width: 100% !important;
                    gap: 6px !important;
                }
                .sw-chip {
                    flex: 1 !important;
                    justify-content: center !important;
                    text-align: center !important;
                    padding: 6px 4px !important;
                    font-size: .72rem !important;
                }

                /* Leyenda — centrada en dos filas si no cabe */
                .sw-legend {
                    justify-content: center !important;
                    flex-wrap: wrap !important;
                    gap: 6px 12px !important;
                    padding: 8px 12px !important;
                    background: #f8fafc !important;
                    border-radius: 10px !important;
                    border: 1px solid #e2e8f0 !important;
                }

                /* Selector de semana (Flatpickr) — ancho completo */
                .sw-fp-wrap { width: 100% !important; box-sizing: border-box !important; }
                .sw-fp-wrap input { width: 100% !important; min-width: 0 !important; }

                /* Navegación de semana — ocupa ancho completo */
                .sw-nav-pill {
                    width: 100% !important;
                    justify-content: space-between !important;
                    padding: 4px 6px !important;
                }
                .sw-nav-label {
                    font-size: .82rem !important;
                    text-align: center !important;
                    flex: 1 !important;
                }

                /* ── Cabecera de departamento ────────────────────────── */
                .sw-dept-head {
                    padding: 12px 14px !important;
                    flex-wrap: wrap !important;
                    gap: 8px !important;
                }
                .sw-dept-title { font-size: .82rem !important; }
                .sw-dept-meta {
                    flex-wrap: wrap !important;
                    gap: 6px 10px !important;
                    width: 100% !important;
                }
                .sw-pbar-track { width: 56px !important; }
                .sw-badge { font-size: .65rem !important; }

                /* ── Tabla → tarjeta apilada ─────────────────────────── */
                /* Eliminar scroll horizontal */
                .sw-table-wrap { overflow-x: hidden !important; }

                /* Colapsar estructura de tabla a bloques */
                .sw-table          { display: block !important; min-width: 0 !important; width: 100% !important; }
                .sw-table thead    { display: none !important; }
                .sw-table tbody    { display: block !important; }

                /* Cada fila: bloque con botón absoluto arriba-derecha */
                .sw-prow {
                    display: block !important;
                    position: relative !important;
                    padding: 10px 14px 10px 14px !important;
                    border-bottom: 1px solid #f1f5f9 !important;
                }
                .sw-prow:last-child { border-bottom: none !important; }

                /* Celda del pasante (nombre + avatar): línea superior */
                .sw-prow td:first-child {
                    display: block !important;
                    padding: 0 72px 8px 0 !important; /* espacio para el botón absoluto */
                    min-width: 0 !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }

                /* Celdas de días: inline-block formando una fila */
                .sw-prow .sw-day-td {
                    display: inline-block !important;
                    padding: 0 2px !important;
                    width: auto !important;
                    vertical-align: middle !important;
                }

                /* Botón "Ver": posición absoluta arriba-derecha de la fila */
                .sw-prow td:last-child {
                    display: block !important;
                    position: absolute !important;
                    top: 10px !important;
                    right: 14px !important;
                    padding: 0 !important;
                    width: auto !important;
                }

                /* Ajustar tamaño de celdas de días para que los 5 quepan */
                .sw-heat {
                    width: 36px !important;
                    height: 36px !important;
                    font-size: .7rem !important;
                    border-radius: 8px !important;
                }

                /* Avatar y texto más compactos */
                .sw-av {
                    width: 30px !important;
                    height: 30px !important;
                    min-width: 30px !important;
                    font-size: .72rem !important;
                    border-radius: 8px !important;
                }
                .sw-pname {
                    font-size: .8rem !important;
                    max-width: 200px !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                }
                .sw-pdept { font-size: .65rem !important; }

                /* Botón Ver — más compacto */
                .sw-btn-ver {
                    font-size: .72rem !important;
                    padding: 5px 10px !important;
                    border-radius: 8px !important;
                    white-space: nowrap !important;
                }
            }
            </style>

            <div class="sw-wrap">

            <!-- ── TOOLBAR ── -->
            <div class="sw-toolbar sw-rv sw-d1">
                <div class="sw-toolbar-left">
                    <div class="sw-search">
                        <i class="ti ti-search"></i>
                        <input type="text" id="buscadorSemanal" placeholder="Buscar pasante…" autocomplete="off">
                    </div>
                    <div class="sw-chips">
                        <button class="sw-chip sc-all active" onclick="swSetChip('all',this)">Todos</button>
                        <button class="sw-chip sc-ok"         onclick="swSetChip('ok',this)">
                            <span class="sw-chip-dot" style="background:#059669"></span>Sin faltas
                        </button>
                        <button class="sw-chip sc-falta"      onclick="swSetChip('falta',this)">
                            <span class="sw-chip-dot" style="background:#DC2626"></span>Con faltas
                        </button>
                    </div>
                    <div class="sw-legend">
                        <div class="sw-legend-item">
                            <div class="sw-legend-dot" style="background:#D1FAE5;border:1.5px solid rgba(5,150,105,.3);"></div>P · Presente
                        </div>
                        <div class="sw-legend-item">
                            <div class="sw-legend-dot" style="background:#FEE2E2;border:1.5px solid rgba(220,38,38,.2);"></div>A · Ausente
                        </div>
                        <div class="sw-legend-item">
                            <div class="sw-legend-dot" style="background:#FEF3C7;border:1.5px solid rgba(217,119,6,.2);"></div>J · Justificado
                        </div>
                    </div>
                </div>
                <div class="sw-toolbar-right">
                    <div class="sw-fp-wrap">
                        <label for="filtro_semana"><i class="ti ti-calendar-time"></i> Semana</label>
                        <input type="text" id="filtro_semana" class="form-control" placeholder="Seleccionar…">
                    </div>
                    <div class="sw-nav-pill">
                        <button class="sw-nav-btn nav-semana-btn" data-url="<?= $navSemana['ant_url'] ?>">
                            <i class="ti ti-chevron-left"></i> Ant.
                        </button>
                        <span id="label-semana-nav" class="sw-nav-label"><?= $navSemana['texto'] ?></span>
                        <button class="sw-nav-btn nav-semana-btn" data-url="<?= $navSemana['sig_url'] ?>">
                            Sig. <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── FLATPICKR + AJAX PJAX ── -->
            <script src="<?= URLROOT ?>/assets/libs/flatpickr/plugins/weekSelect.js"></script>
            <script>
                function cargarSemanaAjax(url) {
                    const contenedor = document.getElementById('contenedor-tarjetas-semanales');
                    if(contenedor) contenedor.style.opacity = '0.4';
                    fetch(url)
                        .then(res => res.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const nuevoCont = doc.getElementById('contenedor-tarjetas-semanales');
                            if (nuevoCont && contenedor) {
                                contenedor.innerHTML = nuevoCont.innerHTML;
                                contenedor.style.opacity = '1';
                                swIniciarFiltros();
                            }
                            const nuevoLabel = doc.getElementById('label-semana-nav');
                            const actualLabel = document.getElementById('label-semana-nav');
                            if (nuevoLabel && actualLabel) actualLabel.innerText = nuevoLabel.innerText;
                            const nuevosBtn = doc.querySelectorAll('.nav-semana-btn');
                            const actualesBtn = document.querySelectorAll('.nav-semana-btn');
                            if (nuevosBtn.length === 2 && actualesBtn.length === 2) {
                                actualesBtn[0].dataset.url = nuevosBtn[0].dataset.url;
                                actualesBtn[1].dataset.url = nuevosBtn[1].dataset.url;
                            }
                            window.history.pushState({ path: url }, '', url);
                        })
                        .catch(err => { console.error('Error AJAX PJAX:', err); window.location.href = url; });
                }

                var swActiveChip = 'all'; // [SGP-FIX-PJAX-REDECL]
                function swSetChip(tipo, btn) {
                    swActiveChip = tipo;
                    document.querySelectorAll('.sw-chip').forEach(c => c.classList.remove('active'));
                    btn.classList.add('active');
                    swFiltrar();
                }
                function swFiltrar() {
                    const q = (document.getElementById('buscadorSemanal')?.value || '').toLowerCase().trim();
                    document.querySelectorAll('.sw-prow').forEach(row => {
                        const nombre = row.dataset.nombre || '';
                        const faltas = row.dataset.faltas === '1';
                        const mQ = !q || nombre.includes(q);
                        const mC = swActiveChip === 'all'
                            || (swActiveChip === 'ok'    && !faltas)
                            || (swActiveChip === 'falta' &&  faltas);
                        row.style.display = (mQ && mC) ? '' : 'none';
                    });
                }
                function swIniciarFiltros() {
                    const inp = document.getElementById('buscadorSemanal');
                    if (inp) { inp.removeEventListener('input', swFiltrar); inp.addEventListener('input', swFiltrar); }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    swIniciarFiltros();
                    const defaultAnio   = <?= $paramsUrl['anio']   ?? date('Y') ?>;
                    const defaultSemana = <?= $paramsUrl['semana'] ?? date('W') ?>;
                    if (typeof flatpickr !== 'undefined' && typeof weekSelect !== 'undefined') {
                        flatpickr('#filtro_semana', {
                            locale: 'es', weekNumbers: true,
                            plugins: [new weekSelect({})],
                            onReady: function(selectedDates, dateStr, instance) {
                                instance.input.value = "Semana " + defaultSemana + ", " + defaultAnio;
                                setTimeout(() => {
                                    const monthSelect = instance.monthElements[0];
                                    if (monthSelect) {
                                        if (window.jQuery && $(monthSelect).data('select2')) $(monthSelect).select2('destroy');
                                        if (window.jQuery) {
                                            $(monthSelect).css({'display':'inline-block','visibility':'visible'}).removeClass('select2-hidden-accessible');
                                        } else {
                                            monthSelect.style.display = 'inline-block';
                                            monthSelect.style.visibility = 'visible';
                                            monthSelect.classList.remove('select2-hidden-accessible');
                                        }
                                        const choicesContainer = monthSelect.closest('.choices');
                                        if (choicesContainer) {
                                            choicesContainer.parentNode.insertBefore(monthSelect, choicesContainer);
                                            choicesContainer.remove();
                                            monthSelect.style.display = 'inline-block';
                                            monthSelect.classList.remove('choices__input', 'is-hidden');
                                        }
                                    }
                                }, 150);
                            },
                            onChange: function(selectedDates, dateStr, instance) {
                                if (selectedDates.length > 0) {
                                    const date = selectedDates[0];
                                    const dt = new Date(date.valueOf());
                                    const dayNr = (date.getDay() + 6) % 7;
                                    dt.setDate(dt.getDate() - dayNr + 3);
                                    const firstThursday = dt.valueOf();
                                    dt.setMonth(0, 1);
                                    if (dt.getDay() !== 4) dt.setMonth(0, 1 + ((4 - dt.getDay()) + 7) % 7);
                                    const targetYear = new Date(firstThursday).getFullYear();
                                    const targetWeek = 1 + Math.ceil((firstThursday - dt) / 604800000);
                                    instance.input.value = "Semana " + targetWeek + ", " + targetYear;
                                    cargarSemanaAjax("<?= URLROOT ?>/asistencias?vista=semanal&semana=" + targetWeek + "&anio=" + targetYear);
                                }
                            }
                        });
                    }
                    document.addEventListener('click', function(e) {
                        const btn = e.target.closest('.nav-semana-btn');
                        if (btn) {
                            e.preventDefault();
                            const targetUrl = btn.dataset.url;
                            try {
                                const urlObj = new URL(targetUrl, window.location.origin);
                                const s = urlObj.searchParams.get('semana');
                                const a = urlObj.searchParams.get('anio');
                                const fpInput = document.getElementById('filtro_semana');
                                if (fpInput && s && a) fpInput.value = "Semana " + s + ", " + a;
                            } catch(e) {}
                            cargarSemanaAjax(targetUrl);
                        }
                    });
                });
            </script>

            <!-- ── CONTENEDOR PJAX (actualizado por AJAX) ── -->
            <div id="contenedor-tarjetas-semanales" style="transition: opacity 0.3s ease;">
                <?php if(empty($datosSemanales)): ?>
                    <div class="sw-empty">
                        <i class="ti ti-calendar-x" style="font-size:3rem;color:#B4BDD4;display:block;margin-bottom:12px;"></i>
                        <p style="color:#7480A0;font-weight:600;margin:0;">No hay pasantes activos para mostrar en esta semana.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $coloresDep  = ['#1D4ED8','#7C3AED','#059669','#0891B2','#D97706','#BE185D','#DC2626'];
                    $swDiIdx     = 0;
                    $swDelay     = 3;
                    foreach ($datosSemanales as $depto => $pasantes):
                        $st       = $deptStats[$depto] ?? ['pct'=>0];
                        $pct      = $st['pct'];
                        $barClr   = $pct >= 80 ? '#059669' : ($pct >= 60 ? '#D97706' : '#DC2626');
                        $dClr     = $coloresDep[$swDiIdx % count($coloresDep)];
                        $swDiIdx++; $swDelay++;
                    ?>
                    <div class="sw-dept-card sw-rv sw-d<?= min($swDelay, 8) ?>">
                        <div class="sw-dept-head" style="cursor: pointer;" onclick="this.nextElementSibling.classList.toggle('sw-collapsed'); this.querySelector('.chevron-toggle').style.transform = this.nextElementSibling.classList.contains('sw-collapsed') ? 'rotate(-90deg)' : 'rotate(0deg)'">
                            <div class="sw-dept-title">
                                <div class="sw-dept-icon" style="background:<?= $dClr ?>18;">
                                    <i class="ti ti-building-community" style="color:<?= $dClr ?>;font-size:1rem;"></i>
                                </div>
                                <?= htmlspecialchars($depto) ?>
                            </div>
                            <div class="sw-dept-meta">
                                <div class="sw-pbar-wrap">
                                    <div class="sw-pbar-track">
                                        <div class="sw-pbar-fill" style="width:<?= $pct ?>%;background:<?= $barClr ?>;"></div>
                                    </div>
                                    <span class="sw-pbar-lbl"><?= $pct ?>%</span>
                                </div>
                                <span class="sw-badge"><?= count($pasantes) ?> pasantes</span>
                                <i class="ti ti-chevron-down chevron-toggle" style="transition: transform 0.3s; font-size:1.2rem; color:#7480A0;"></i>
                            </div>
                        </div>
                        <div class="sw-table-wrap">
                            <table class="sw-table">
                                <thead>
                                    <tr>
                                        <th>Pasante</th>
                                        <th class="sw-th-day">Lun</th>
                                        <th class="sw-th-day">Mar</th>
                                        <th class="sw-th-day">Mié</th>
                                        <th class="sw-th-day">Jue</th>
                                        <th class="sw-th-day">Vie</th>
                                        <th class="sw-th-act">Resumen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pasantes as $id => $pData):
                                    $nombre   = htmlspecialchars($pData['nombre']);
                                    $nomLow   = strtolower($pData['nombre']);
                                    $partes   = explode(',', $pData['nombre']);
                                    $ap1      = trim(explode(' ', $partes[0] ?? 'A')[0]);
                                    $nom1     = trim(explode(' ', $partes[1] ?? 'A')[0]);
                                    $initials = strtoupper(substr($nom1,0,1) . substr($ap1,0,1));
                                    if (strlen(trim($initials)) < 2) $initials = strtoupper(substr($pData['nombre'],0,2));
                                    $tFaltas  = 0;
                                    foreach ($pData['dias'] as $dl) { if ($dl === 'A') $tFaltas++; }
                                ?>
                                <tr class="sw-prow"
                                    data-nombre="<?= $nomLow ?>"
                                    data-faltas="<?= $tFaltas > 0 ? '1' : '0' ?>">
                                    <td style="min-width:180px;max-width:240px;padding:0 16px;">
                                        <div class="sw-person">
                                            <div class="sw-av" style="background:<?= $dClr ?>"><?= $initials ?></div>
                                            <div>
                                                <div class="sw-pname"><?= $nombre ?></div>
                                                <div class="sw-pdept"><?= htmlspecialchars($depto) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <?php for ($i = 1; $i <= 5; $i++):
                                        $letra  = $pData['dias'][$i] ?? '-';
                                        $hCls   = in_array($letra, ['P','A','J']) ? "sw-h-{$letra}" : 'sw-h-N';
                                        $hTxt   = in_array($letra, ['P','A','J']) ? $letra : '·';
                                        $motivo = trim($pData['motivos'][$i] ?? '');
                                        $hTitle = match($letra) {
                                            'P' => 'Presente',
                                            'A' => 'Ausente',
                                            'J' => 'Justificado' . ($motivo !== '' ? ': ' . $motivo : ''),
                                            default => 'Sin datos'
                                        };
                                    ?>
                                    <td class="sw-day-td">
                                        <span class="sw-heat <?= $hCls ?>"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="<?= htmlspecialchars($hTitle) ?>"><?= $hTxt ?></span>
                                    </td>
                                    <?php endfor; ?>
                                    <td style="padding:0 16px;text-align:right;">
                                        <button type="button"
                                            class="sw-btn-ver btn-resumen"
                                            data-id="<?= htmlspecialchars($id) ?>"
                                            data-nombre="<?= htmlspecialchars($pData['nombre']) ?>"
                                            data-inicio="<?= htmlspecialchars($pData['fecha_inicio'] ?? '') ?>"
                                            data-fin="<?= htmlspecialchars($pData['fecha_fin'] ?? '') ?>"
                                            onclick="abrirAlmanaque(this)">
                                            <i class="ti ti-calendar-stats"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            </div><!-- /.sw-wrap -->

        <?php elseif ($vistaActual === 'mensual'): ?>
            <?php
            // ── Variables de la vista mensual (todo viene del controlador) ──
            $calPorDia     = $data['calPorDia']     ?? ['Todos' => []];
            $feriadosDia   = $data['feriadosDia']   ?? [];
            $pctPorPasante = $data['pctPorPasante'] ?? [];
            $ausenciasMes  = $data['ausenciasMes']  ?? [];
            $urlAnt        = $data['urlAnt']        ?? '#';
            $urlSig        = $data['urlSig']        ?? '#';
            $mesActual     = (int)($paramsUrl['mes']  ?? date('n'));
            $anioActual    = (int)($paramsUrl['anio'] ?? date('Y'));
            $numDays       = cal_days_in_month(CAL_GREGORIAN, $mesActual, $anioActual);
            $primerDow     = (int)(new DateTime(sprintf('%04d-%02d-01', $anioActual, $mesActual)))->format('N'); // 1=Lun
            $hoyDia        = (date('n') == $mesActual && date('Y') == $anioActual) ? (int)date('j') : -1;
            $deptosDisp    = array_keys($calPorDia); // incluye 'Todos'

            // Ranking riesgo vs top
            $pasantesRiesgo = array_filter($pctPorPasante, fn($p) => $p['pct'] < 75);
            $pasantesTop    = array_slice($pctPorPasante, 0, 5);

            // Nombres de días y meses
            $diasNombreCorto = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            $mesesNombresEs  = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',
                                6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',
                                10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];

            // Serializar datos para JS — con protección ante json_encode() false
            // (ocurre cuando la BD devuelve strings con encoding inválido/latin-1)
            $jFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

            // Limpiar calPorDia de caracteres inválidos para JSON
            $calPorDiaClean = json_decode(
                json_encode($calPorDia, JSON_HEX_TAG | JSON_HEX_AMP),
                true
            ) ?? [];

            $mesDataJS = json_encode([
                'anio'     => $anioActual,
                'mes'      => $mesActual,
                'numDays'  => $numDays,
                'primerDow'=> $primerDow,
                'hoyDia'   => $hoyDia,
                'feriados' => $feriadosDia,
                'deptos'   => $calPorDiaClean,
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            if ($mesDataJS === false) {
                $mesDataJS = json_encode([
                    'anio'=>$anioActual,'mes'=>$mesActual,'numDays'=>$numDays,
                    'primerDow'=>$primerDow,'hoyDia'=>$hoyDia,'feriados'=>[],'deptos'=>[]
                ]);
            }

            $ausenciasJS = json_encode(array_map(fn($r) => [
                'id'         => (int)($r->id ?? 0),
                'pasante_id' => (int)($r->pasante_id ?? 0),
                'nombre'     => mb_convert_encoding(trim(($r->apellidos ?? '') . ', ' . ($r->nombres ?? '')), 'UTF-8', 'UTF-8'),
                'depto'      => mb_convert_encoding($r->departamento_nombre ?? '', 'UTF-8', 'UTF-8'),
                'fecha'      => $r->fecha ?? '',
                'auto_fill'  => (int)($r->es_auto_fill ?? 0),
                'metodo'     => $r->metodo ?? '',
            ], $ausenciasMes), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            if ($ausenciasJS === false) { $ausenciasJS = '[]'; }
            // ── Alias para la vista ──
            $resumenDeptosJS = $data['resumenDeptosJS'] ?? [];
            if (!function_exists('getFriendlyName')) {
                function getFriendlyName($str, $variant = 'card') {
                    $u = mb_strtoupper(trim($str), 'UTF-8');
                    // Mapeo específico según el contexto
                    if ($variant === 'cal') {
                        if (str_contains($u, 'ATENCION AL USUARIO')) return 'Atención U.';
                        if (str_contains($u, 'SOPORTE T')) return 'Soporte T.';
                        if (str_contains($u, 'REDES')) return 'Redes T.';
                        if (str_contains($u, 'REPARACIONES')) return 'Reparaciones E.';
                    } else {
                        // Variante 'card' (Tarjetas)
                        if (str_contains($u, 'REDES')) return 'Redes T.';
                        if (str_contains($u, 'REPARACIONES')) return 'Reparaciones E.';
                        // Los demás retornan el nombre completo (o casi)
                        if (str_contains($u, 'ATENCION AL USUARIO')) return 'Atención al Usuario';
                        if (str_contains($u, 'SOPORTE T')) return 'Soporte Técnico';
                    }
                    
                    // Fallback a acrónimo si es muy largo y no está en el mapa
                    if (mb_strlen($str, 'UTF-8') > 15) {
                        $words = explode(' ', str_replace([' y ', ' DE ', ' LA '], [' ', ' ', ' '], mb_strtoupper($str, 'UTF-8')));
                        $acr = '';
                        foreach ($words as $w) {
                            $w = trim($w);
                            if (mb_strlen($w, 'UTF-8') >= 2) $acr .= mb_substr($w, 0, 1, 'UTF-8');
                        }
                        return $acr ?: $str;
                    }
                    return $str;
                }
            }
            ?>
<style>
/* ══ MENSUAL BENTO v3 ══════════════════════════════════════════════ */
.men-card { background:#fff; border-radius:var(--alm-radius,18px); box-shadow:var(--alm-shadow,0 2px 16px rgba(15,23,42,.07)); border:1px solid var(--alm-border,#e2e8f0); padding:18px 20px; display:flex; flex-direction:column; }
.men-card-title { font-size:.76rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.6px; display:flex; align-items:center; gap:6px; margin-bottom:12px; }
/* Grids principales */
.men-bento-top { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:14px; margin-bottom:14px; }
.men-bento-bottom { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; margin-bottom:14px; }
@media(max-width:1100px){ .men-bento-top { grid-template-columns:1fr 1fr; } .men-bento-bottom { grid-template-columns:1fr; } }
@media(max-width:700px)  { .men-bento-top { grid-template-columns:1fr; } }
/* Nav mes — igual que alm-mm-mobile-controls */
.men-nav { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; padding:0 2px; }
.men-nav-btn { background:transparent; border:1px solid #e2e8f0; border-radius:10px; padding:6px 14px; font-weight:700; color:#475569; font-size:.82rem; text-decoration:none; display:inline-flex; align-items:center; transition:all .12s; }
.men-nav-btn:hover { background:#f1f5f9; border-color:#cbd5e1; }
.men-nav-title { font-size:.95rem; font-weight:800; color:#1e3a8a; text-transform:uppercase; letter-spacing:.5px; }
/* Pills dept */
.men-dept-pills { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:10px; }
.men-dept-pill { padding:3px 11px; border-radius:20px; font-size:.68rem; font-weight:700; cursor:pointer; border:2px solid #e2e8f0; background:#f8fafc; color:#475569; transition:all .12s; user-select:none; }
.men-dept-pill.active { background:#2563eb; border-color:#2563eb; color:#fff; }
.men-dept-pill:hover:not(.active){ border-color:#2563eb; color:#2563eb; }
/* Filtros mini (riesgo/top) */
.men-filter-pills { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:10px; }
.men-filter-pill { padding:2px 9px; border-radius:20px; font-size:.63rem; font-weight:700; cursor:pointer; border:1.5px solid #e2e8f0; background:#f8fafc; color:#64748b; transition:all .1s; user-select:none; }
.men-filter-pill.active { background:#1e293b; border-color:#1e293b; color:#fff; }
/* Calendario 5 col — idéntico a .alm-mm-days-* */
.men-cal-header { display:grid; grid-template-columns:repeat(5,1fr); gap:4px; margin-bottom:8px; }
.men-cal-dow { text-align:center; font-size:.7rem; font-weight:700; color:#94a3b8; padding:3px 0; }
.men-cal-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:4px; }
/* Celdas — colores sólidos con texto blanco, igual que .alm-cell */
.men-cell { aspect-ratio:1; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; cursor:default; transition:transform .1s; }
.men-cell:not(.is-empty):not(.future):hover { transform:scale(1.15); z-index:10; box-shadow:0 2px 6px rgba(0,0,0,.15); cursor:pointer; }
.men-cell.is-empty  { background:transparent; color:transparent; cursor:default; }
.men-cell.future    { background:#ffffff; color:#cbd5e1; font-weight:600; border:1px solid #e2e8f0; }
.men-cell.feriado   { background:#f59e0b; color:#fff; }
.men-cell.verde     { background:#16a34a; color:#fff; }
.men-cell.amarillo  { background:#d97706; color:#fff; }
.men-cell.rojo      { background:#dc2626; color:#fff; }
.men-cell.sin-dato  { background:#e2e8f0; color:#64748b; }
.men-cell.hoy       { outline:2px solid #2563eb; outline-offset:2px; }
/* Leyenda — igual que .alm-legend */
.men-legend { display:flex; align-items:center; flex-wrap:wrap; gap:12px; margin-top:18px; font-size:.75rem; color:#64748b; }
.men-legend-item { display:flex; align-items:center; gap:5px; font-weight:600; }
.men-legend-dot { width:12px; height:12px; border-radius:3px; flex-shrink:0; }
/* Col2 depto bars */
.men-depto-bar  { height:5px; border-radius:20px; background:#e2e8f0; overflow:hidden; margin:2px 0 1px; }
.men-depto-fill { height:100%; border-radius:20px; transition:width .8s ease; }
/* Col3 radar */
.men-pasante-row { display:flex; align-items:center; gap:7px; padding:5px 0; border-bottom:1px solid #f1f5f9; }
.men-pasante-row:last-child { border-bottom:none; }
.men-av { width:28px; height:28px; border-radius:7px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:.63rem; font-weight:800; color:#fff; }
.men-pct-badge { padding:2px 7px; border-radius:20px; font-size:.63rem; font-weight:800; margin-left:auto; flex-shrink:0; }
.men-radar-scroll { flex:1; min-height:0; max-height:380px; overflow-y:auto; scrollbar-width:thin; padding-right:4px; margin-top:4px; }
/* Botones de paginación miniatura para cards */
.men-card-nav { display:flex; align-items:center; gap:4px; }
.men-card-btn { width:24px; height:24px; border-radius:6px; display:flex; align-items:center; justify-content:center; background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; cursor:pointer; transition:all .2s; font-size:.8rem; }
.men-card-btn:hover:not(:disabled) { background:#e2e8f0; color:#1e293b; }
.men-card-btn:disabled { opacity:.4; cursor:not-allowed; }
/* S4 */
.men-aus-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.men-aus-table th { padding:6px 10px; font-size:.62rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid #f1f5f9; text-align:left; white-space:nowrap; }
.men-aus-table td { padding:6px 10px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
.men-aus-table tr:hover td { background:#f8fafc; }
.men-aus-table tr.procesada td { opacity:.35; }
.men-aus-table tr.pag-hidden { display:none; }
/* Acciones bulk siempre visibles */
.men-bulk-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:10px 14px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:10px; }
.men-bulk-btn { border:none; padding:5px 14px; border-radius:8px; font-weight:700; font-size:.72rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:opacity .15s; }
.men-bulk-btn:disabled { opacity:.4; cursor:default; }
/* Paginador */
.men-pager { display:flex; align-items:center; gap:7px; justify-content:flex-end; margin-top:8px; font-size:.72rem; color:#64748b; font-weight:600; }
.men-pager button { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:7px; padding:3px 10px; cursor:pointer; font-weight:700; font-size:.72rem; color:#475569; display:inline-flex; align-items:center; gap:3px; transition:background .1s; }
.men-pager button:hover:not([disabled]){ background:#e2e8f0; }
.men-pager button[disabled]{ opacity:.35; cursor:default; }
/* Tooltip */
#menTooltip { position:fixed; z-index:9999; pointer-events:none; background:#1e293b; color:#f8fafc; border-radius:9px; padding:7px 12px; font-size:.74rem; line-height:1.5; box-shadow:0 8px 24px rgba(0,0,0,.3); opacity:0; transition:opacity .1s; max-width:200px; white-space:pre-line; }
#menTooltip.show { opacity:1; }
@keyframes men-reveal { from{opacity:0;transform:translateY(5px)} to{opacity:1;transform:translateY(0)} }
.men-reveal { animation:men-reveal .28s ease forwards; }
</style>

<div id="menBentoWrapper">
<div id="menTooltip"></div>

<!-- ══ BENTO ══ -->
<!-- BENTO TOP ROW -->
<div class="men-bento-top">

    <!-- Card 1: Calendario 5-col — estilo almanaque exacto -->
    <div class="men-card" style="padding:24px 28px;">
        <!-- Título grupo + días laborables (encabezado del wrapper almanaque) -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
            <div style="font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                <i class="ti ti-calendar-stats" style="color:#2563eb;font-size:1.1rem;"></i>
                Calendario de Asistencia
                <?php
                $diasLaborablesDelMes = 0;
                for ($dd = 1; $dd <= $numDays; $dd++) {
                    $dow = (int)(new DateTime(sprintf('%04d-%02d-%02d',$anioActual,$mesActual,$dd)))->format('N');
                    if ($dow <= 5 && !isset($feriadosDia[$dd])) $diasLaborablesDelMes++;
                }
                ?>
                <span style="font-size:.75rem;color:#94a3b8;font-weight:500;"><?= $diasLaborablesDelMes ?> días laborables</span>
            </div>
        </div>
        <!-- Nav mes — idéntico a alm-mm-mobile-controls -->
        <div class="men-nav">
            <button onclick="cargarMesAjax('<?= $urlAnt ?>')" class="men-nav-btn" style="background:#f8fafc;" title="Mes anterior">
                <i class="ti ti-chevron-left"></i>
            </button>
            <span class="men-nav-title" id="menNavLabel"><?= $mesesNombresEs[$mesActual] ?> <?= $anioActual ?></span>
            <button onclick="cargarMesAjax('<?= $urlSig ?>')" class="men-nav-btn" style="background:#f8fafc;" title="Mes siguiente">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>
        <!-- Pills depto debajo del nav -->
        <div class="men-dept-pills">
            <?php foreach ($deptosDisp as $dept): ?>
            <button class="men-dept-pill <?= $dept === 'Todos' ? 'active' : '' ?>" title="<?= htmlspecialchars($dept) ?>"
                    onclick="menFiltrarDepto(this, <?= htmlspecialchars(json_encode($dept), ENT_QUOTES, 'UTF-8') ?>)">
                <?= htmlspecialchars(getFriendlyName($dept, 'cal')) ?>
            </button>
            <?php endforeach; ?>
        </div>
        <!-- Días L M M J V — igual que alm-mm-days-header -->
        <div class="men-cal-header">
            <div class="men-cal-dow">L</div>
            <div class="men-cal-dow">M</div>
            <div class="men-cal-dow">M</div>
            <div class="men-cal-dow">J</div>
            <div class="men-cal-dow">V</div>
        </div>
        <div class="men-cal-grid" id="menCalGrid"></div>
        <!-- Leyenda — igual que .alm-legend -->
        <div class="men-legend">
            <span style="color:#94a3b8;">Leyenda:</span>
            <div class="men-legend-item"><div class="men-legend-dot" style="background:#16a34a;"></div>&ge;85%</div>
            <div class="men-legend-item"><div class="men-legend-dot" style="background:#d97706;"></div>60&ndash;84%</div>
            <div class="men-legend-item"><div class="men-legend-dot" style="background:#dc2626;"></div>&lt;60%</div>
            <div class="men-legend-item"><div class="men-legend-dot" style="background:#f59e0b;"></div>Feriado</div>
            <div class="men-legend-item"><div class="men-legend-dot" style="background:#e2e8f0;"></div>Sin registro</div>
        </div>
    </div>

    <!-- Card 2: Donut radial + Depto breakdown -->
        <?php
        $hi = count($pctPorPasante) > 0
            ? (int)round(array_sum(array_column($pctPorPasante, 'pct')) / count($pctPorPasante))
            : 0;
        $hiColor = $hi >= 85 ? '#16a34a' : ($hi >= 60 ? '#d97706' : '#dc2626');
        $hiLabel = $hi >= 85 ? 'Excelente' : ($hi >= 60 ? 'Regular' : 'Cr&iacute;tico');
        // Datos para donut por depto
        $coloresDepto = ['#2563eb','#059669','#d97706','#7c3aed','#0284c7','#dc2626','#0891b2','#0f766e'];
        $deptoDonut = [];
        $ciD = 0;
        // Inyectar todos los departamentos activos para garantizar que aparezcan los ghost rings
        $todosDeptos = $data['todosDeptos'] ?? [];
        foreach ($todosDeptos as $td) {
            if (!isset($resumenDeptosJS[$td])) {
                $resumenDeptosJS[$td] = ['p'=>0, 'f'=>0, 'j'=>0, 't'=>0];
            }
        }

        if (!empty($resumenDeptosJS)) {
            foreach ($resumenDeptosJS as $dep => $d) {
                $pp_p = (int)($d['p'] ?? 0);
                $pp_a = (int)($d['f'] ?? $d['a'] ?? 0);
                $pp_j = (int)($d['j'] ?? 0);
                $tot  = $pp_p + $pp_a + $pp_j;
                $pp   = $tot > 0 ? round(($pp_p + $pp_j) / $tot * 100) : 0;
                $deptoDonut[] = ['label' => $dep, 'pct' => $pp, 'color' => $coloresDepto[$ciD % count($coloresDepto)], 'p' => $pp_p, 'j' => $pp_j, 'a' => $pp_a];
                $ciD++;
            }
        }
        $globalP = array_sum(array_column($deptoDonut, 'p'));
        $globalJ = array_sum(array_column($deptoDonut, 'j'));
        $globalA = array_sum(array_column($deptoDonut, 'a'));
        $donutJS = json_encode($deptoDonut, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
        if ($donutJS === false) { $donutJS = '[]'; }
        ?>
        <div class="men-card" style="display:flex;flex-direction:column;flex:1;">
            <!-- Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <div class="men-card-title" style="margin-bottom:0;">
                    <i class="ti ti-chart-donut" style="color:<?= $hiColor ?>;"></i> Asistencia global
                </div>
                <span id="menDonutGlobalLabel" style="background:<?= $hiColor ?>18;color:<?= $hiColor ?>;font-size:.65rem;font-weight:800;padding:2px 10px;border-radius:20px;flex-shrink:0;"><?= $hiLabel ?></span>
            </div>
            <!-- Filtros -->
            <div class="men-filter-pills" id="menDonutPills" style="margin-bottom:10px;">
                <button class="men-filter-pill active" onclick="menFiltrarDonut('Todos', this)" title="Todos">Todos</button>
                <?php foreach ($deptosDisp as $dept): if($dept === 'Todos') continue; ?>
                <button class="men-filter-pill" onclick="menFiltrarDonut(<?= htmlspecialchars(json_encode($dept), ENT_QUOTES, 'UTF-8') ?>, this)" title="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars(getFriendlyName($dept, 'cal')) ?></button>
                <?php endforeach; ?>
            </div>
            <!-- Donut -->
            <div style="display:flex;justify-content:center;">
                <div id="radialAppleWatch"></div>
            </div>
            <!-- Leyenda -->
            <div class="men-radar-scroll" id="menDonutLey" style="margin-top:6px;"></div>
        </div>


        <!-- MAYOR RETARDO -->
        <div class="men-card" style="flex:1; display:flex; flex-direction:column;">
            <div class="men-card-title" style="margin-bottom:6px;">
                <i class="ti ti-clock-stop" style="color:#f59e0b;"></i> Retardos del mes
            </div>
            <div class="men-filter-pills" id="menRetardoPills" style="margin-bottom:6px;">
                <button class="men-filter-pill active" data-dep="Todos" title="Todos" onclick="menFiltrarRetardos(this)">Todos</button>
                <?php foreach (array_unique(array_column($pctPorPasante, 'depto')) as $dp): ?>
                <button class="men-filter-pill" data-dep="<?= htmlspecialchars($dp) ?>" title="<?= htmlspecialchars($dp) ?>" onclick="menFiltrarRetardos(this)"><?= htmlspecialchars(getFriendlyName($dp, 'card')) ?></button>
                <?php endforeach; ?>
            </div>
            <div id="menRetardoList" style="flex:1;overflow-y:auto;scrollbar-width:thin;padding-right:4px;">
                <?php if (empty($pasantesRetardos)): ?>
                <div style="text-align:center;padding:32px 8px;color:#94a3b8;">
                    <i class="ti ti-circle-check" style="font-size:1.8rem;display:block;margin-bottom:6px;color:#16a34a;opacity:.5;"></i>
                    <p style="margin:0;font-size:.72rem;font-weight:600;">Sin retardos registrados</p>
                </div>
                <?php else: foreach ($pasantesRetardos as $idx => $p): ?>
                <div class="men-pasante-row men-ret-row" data-dep="<?= htmlspecialchars($p['depto']) ?>" style="padding:8px 0;">
                    <div class="men-av" style="background:#f59e0b;"><?= htmlspecialchars($p['iniciales']) ?></div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:.73rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div style="font-size:.59rem;color:#94a3b8;"><?= htmlspecialchars($p['depto']) ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <button class="men-card-btn" style="width:28px;height:28px;background:#fff7ed;color:#f59e0b;border-color:#ffedd5;"
                                onclick="menVerDetalleRetardos(<?= (int)($p['id'] ?? 0) ?>,'<?= addslashes($p['nombre']) ?>','<?= addslashes($p['iniciales']) ?>','<?= addslashes($p['depto']) ?>',<?= (int)$p['retardos'] ?>)"
                                title="Ver historial">
                            <i class="ti ti-eye"></i>
                        </button>
                        <span class="men-pct-badge" style="background:#fff7ed;color:#9a3412;"><?= $p['retardos'] ?> ret.</span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
</div><!-- /men-bento-top -->

<!-- BENTO BOTTOM ROW -->
<div class="men-bento-bottom">
        <!-- POR DEPARTAMENTO -->
        <div class="men-card" style="flex:1;">
            <div class="men-card-title"><i class="ti ti-building" style="color:#0891b2;"></i> Por departamento</div>
            <p style="font-size:.62rem;color:#94a3b8;margin-top:-6px;margin-bottom:12px;">Promedio de asistencia grupal por equipo.</p>
            <div style="margin-top:8px;">
            <?php
            $sgpGradients = [
                ['bar'=>'linear-gradient(90deg,#3b82f6,#8b5cf6)','muted'=>'linear-gradient(90deg,rgba(59,130,246,.2),rgba(139,92,246,.15))','color'=>'#2563eb'],
                ['bar'=>'linear-gradient(90deg,#10b981,#34d399)','muted'=>'linear-gradient(90deg,rgba(16,185,129,.2),rgba(52,211,153,.15))','color'=>'#059669'],
                ['bar'=>'linear-gradient(90deg,#f59e0b,#fbbf24)','muted'=>'linear-gradient(90deg,rgba(245,158,11,.2),rgba(251,191,36,.15))','color'=>'#d97706'],
                ['bar'=>'linear-gradient(90deg,#ec4899,#f43f5e)','muted'=>'linear-gradient(90deg,rgba(236,72,153,.2),rgba(244,63,94,.15))','color'=>'#db2777'],
                ['bar'=>'linear-gradient(90deg,#6366f1,#a855f7)','muted'=>'linear-gradient(90deg,rgba(99,102,241,.2),rgba(168,85,247,.15))','color'=>'#4f46e5'],
                ['bar'=>'linear-gradient(90deg,#06b6d4,#3b82f6)','muted'=>'linear-gradient(90deg,rgba(6,182,212,.2),rgba(59,130,246,.15))','color'=>'#0891b2'],
            ];
            $deptSorted = $deptoDonut;
            usort($deptSorted, fn($a,$b) => $b['pct'] - $a['pct']);
            if (empty($deptSorted)): ?>
                <p style="text-align:center;color:#94a3b8;font-size:.75rem;padding:28px 0;">Sin datos este mes</p>
            <?php else: ?>
            <style>
                @keyframes sgpFillBar{from{width:0}}
                @keyframes sgpTipIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
                .sgp-dept-bar{animation:sgpFillBar 1s cubic-bezier(.22,1,.36,1) forwards;transition:filter .25s,transform .25s}
                .sgp-dept-row{
                    position:relative;padding:10px 12px;border-radius:10px;cursor:default;
                    border:1.5px solid #e2e8f0;
                    transition:background .2s,transform .2s cubic-bezier(.34,1.56,.64,1),box-shadow .2s,border-color .2s;
                }
                .sgp-dept-row:hover{
                    background:#f0f9ff;transform:translateY(-2px);
                    box-shadow:0 4px 14px rgba(0,0,0,.07);
                    border-color:var(--dept-color);
                }
                .sgp-dept-row:hover .sgp-dept-pct{transform:scale(1.18);filter:brightness(1.1);}
                .sgp-dept-row:hover .sgp-dept-bar{filter:brightness(1.12) saturate(1.2);transform:scaleY(1.25);transform-origin:center;}
                .sgp-dept-row:hover .sgp-dept-track{box-shadow:inset 0 1px 2px rgba(0,0,0,.05),0 0 0 2px rgba(99,102,241,.12);}
                .sgp-dept-row:hover .sgp-dept-name{color:var(--dept-color) !important;}
                .sgp-dept-pct{transition:transform .2s cubic-bezier(.34,1.56,.64,1),filter .2s;display:inline-block}
                .sgp-dept-track{transition:box-shadow .2s;}
                /* Tooltip */
                .sgp-dept-tip{
                    display:none;position:absolute;bottom:calc(100% + 10px);left:50%;
                    transform:translateX(-50%);
                    background:#fff;color:#1e293b;border-radius:12px;
                    padding:11px 15px;white-space:nowrap;z-index:99;
                    box-shadow:0 8px 28px rgba(0,0,0,.12);
                    border:1px solid #e2e8f0;
                    pointer-events:none;min-width:170px;
                }
                .sgp-dept-tip::before{
                    content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);
                    border:7px solid transparent;border-top-color:#e2e8f0;
                }
                .sgp-dept-tip::after{
                    content:'';position:absolute;top:calc(100% - 1px);left:50%;transform:translateX(-50%);
                    border:7px solid transparent;border-top-color:#fff;
                }
                .sgp-dept-row:hover .sgp-dept-tip{
                    display:block;animation:sgpTipIn .18s ease forwards;
                }
            </style>
            <?php foreach ($deptSorted as $ci => $dd):
                $g  = $sgpGradients[$ci % count($sgpGradients)];
                $has = ($dd['p'] + $dd['j'] + $dd['a']) > 0;
                $tot = $dd['p'] + $dd['j'] + $dd['a'];
            ?>
            <div class="sgp-dept-row" style="margin-bottom:8px;--dept-color:<?= $g['color'] ?>;">
                <!-- Tooltip -->
                <div class="sgp-dept-tip">
                    <div style="font-size:.72rem;font-weight:800;color:<?= $g['color'] ?>;margin-bottom:6px;border-bottom:1px solid #f1f5f9;padding-bottom:5px;"><?= htmlspecialchars($dd['label']) ?></div>
                    <?php if ($has): ?>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;justify-content:space-between;gap:16px;font-size:.68rem;">
                            <span style="color:#16a34a;font-weight:600;">✔ Presentes</span>
                            <span style="font-weight:800;color:#1e293b;"><?= $dd['p'] ?> <span style="color:#94a3b8;font-weight:500;">(<?= $tot>0?round($dd['p']/$tot*100):0 ?>%)</span></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:16px;font-size:.68rem;">
                            <span style="color:#dc2626;font-weight:600;">✖ Ausentes</span>
                            <span style="font-weight:800;color:#1e293b;"><?= $dd['a'] ?> <span style="color:#94a3b8;font-weight:500;">(<?= $tot>0?round($dd['a']/$tot*100):0 ?>%)</span></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:16px;font-size:.68rem;">
                            <span style="color:#2563eb;font-weight:600;">📄 Justificados</span>
                            <span style="font-weight:800;color:#1e293b;"><?= $dd['j'] ?> <span style="color:#94a3b8;font-weight:500;">(<?= $tot>0?round($dd['j']/$tot*100):0 ?>%)</span></span>
                        </div>
                        <div style="margin-top:4px;padding-top:5px;border-top:1px solid #f1f5f9;font-size:.68rem;display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:#64748b;font-weight:600;">Asistencia efectiva</span>
                            <span style="font-weight:900;color:<?= $g['color'] ?>;font-size:.8rem;"><?= $dd['pct'] ?>%</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <span style="font-size:.68rem;color:#94a3b8;">Sin registros este mes</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                    <span class="sgp-dept-name" style="font-size:.72rem;font-weight:700;color:#1e293b;transition:color .2s;<?= $has ? '' : 'opacity:.5;' ?>"><?= htmlspecialchars($dd['label']) ?></span>
                    <?php if ($has): ?>
                    <span class="sgp-dept-pct" style="font-size:.78rem;font-weight:800;color:<?= $g['color'] ?>;"><?= $dd['pct'] ?>%</span>
                    <?php else: ?>
                    <span style="font-size:.62rem;font-weight:700;color:<?= $g['color'] ?>;background:<?= $g['color'] ?>18;padding:1px 8px;border-radius:20px;">Sin datos</span>
                    <?php endif; ?>
                </div>
                <div class="sgp-dept-track" style="height:10px;border-radius:999px;background:#f1f5f9;overflow:hidden;box-shadow:inset 0 1px 2px rgba(0,0,0,.05);">
                    <?php if ($has): ?>
                    <div class="sgp-dept-bar" style="height:100%;border-radius:999px;background:<?= $g['bar'] ?>;width:<?= $dd['pct'] ?>%;"></div>
                    <?php else: ?>
                    <div style="height:100%;border-radius:999px;background:<?= $g['muted'] ?>;width:50%;"></div>
                    <?php endif; ?>
                </div>
                <?php if ($has): ?>
                <div style="display:flex;gap:8px;margin-top:4px;font-size:.6rem;font-weight:700;color:#94a3b8;">
                    <span style="color:#16a34a;"><?= $dd['p'] ?> Pres</span>
                    <span>·</span>
                    <span style="color:#dc2626;"><?= $dd['a'] ?> Aus</span>
                    <span>·</span>
                    <span style="color:#2563eb;"><?= $dd['j'] ?> Just</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>

        <!-- MEJOR ASISTENCIA -->
        <div class="men-card" style="flex:1; display:flex; flex-direction:column;">
            <div class="men-card-title" style="margin-bottom:6px; justify-content:space-between;">
                <span><i class="ti ti-trophy" style="color:#d97706;"></i> Mejor asistencia</span>
                <div class="men-card-nav">
                    <button class="men-card-btn" onclick="menPaginarTop(-1)" id="btnTopAnt" title="Anterior"><i class="ti ti-chevron-left"></i></button>
                    <button class="men-card-btn" onclick="menPaginarTop(1)" id="btnTopSig" title="Siguiente"><i class="ti ti-chevron-right"></i></button>
                </div>
            </div>
            <div class="men-filter-pills" id="menTopPills">
                <button class="men-filter-pill active" data-dep="Todos" title="Todos" onclick="menFiltrarTop(this)">Todos</button>
                <?php foreach (array_unique(array_column($pctPorPasante, 'depto')) as $dp): ?>
                <button class="men-filter-pill" data-dep="<?= htmlspecialchars($dp) ?>" title="<?= htmlspecialchars($dp) ?>" onclick="menFiltrarTop(this)"><?= htmlspecialchars(getFriendlyName($dp, 'card')) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="men-radar-scroll" id="menTopList">
                <?php
                if (empty($pctPorPasante)): ?>
                <div style="text-align:center;padding:16px 8px;color:#94a3b8;">
                    <i class="ti ti-ghost" style="font-size:1.4rem;display:block;margin-bottom:4px;opacity:.3;"></i>
                    <p style="margin:0;font-size:.72rem;">Sin registros</p>
                </div>
                <?php else:
                foreach ($pctPorPasante as $i5 => $p):
                    // Determinar color según porcentaje
                    $c5 = $p['pct'] >= 85 ? '#16a34a' : ($p['pct'] >= 60 ? '#d97706' : '#dc2626');
                ?>
                <div class="men-pasante-row men-top-row men-reveal" data-dep="<?= htmlspecialchars($p['depto']) ?>" data-idx="<?= $i5 ?>" style="display:<?= $i5 < 5 ? 'flex' : 'none' ?>;">
                    <div style="width:15px;font-size:.58rem;font-weight:900;color:#94a3b8;text-align:center;flex-shrink:0;"><?= $i5 + 1 ?></div>
                    <div class="men-av" style="background:<?= $c5 ?>;"><?= htmlspecialchars($p['iniciales']) ?></div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:.73rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div style="font-size:.59rem;color:#94a3b8;"><?= htmlspecialchars($p['depto']) ?></div>
                    </div>
                    <span class="men-pct-badge" style="background:<?= $c5 ?>15;color:<?= $c5 ?>;"><?= $p['pct'] ?>%</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
</div><!-- /men-bento-bottom -->

<!-- ══ S4: Inasistencias paginadas ══ -->
<div class="men-card" id="panelInasistencias">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;background:#f8fafc;padding:12px 16px;border-radius:12px;border:1px solid #e2e8f0;">
        <div class="men-card-title" style="margin-bottom:0;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-calendar-x" style="color:#dc2626;"></i>
            Inasistencias del mes
            <span id="menAusBadge" style="background:#fee2e2;color:#dc2626;font-size:.58rem;padding:2px 8px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;"><?= count($ausenciasMes) ?></span>
        </div>
        
        <!-- Controles Masivos Integrados -->
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:#475569;font-weight:700;cursor:pointer;user-select:none;background:white;padding:6px 12px;border-radius:8px;border:1px solid #cbd5e1;transition:all .2s;" onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#cbd5e1'">
                <input type="checkbox" id="menChkTodos" onchange="menSelTodos(this)" style="width:16px;height:16px;accent-color:#2563eb;cursor:pointer;"> 
                <span>Seleccionar todos</span>
            </label>

            <div class="men-bulk-actions" style="display:flex;align-items:center;gap:8px;opacity:0.5;pointer-events:none;transition:all .3s;" id="menBulkContainer">
                <span style="font-size:.7rem;color:#64748b;font-weight:700;background:#e2e8f0;padding:4px 10px;border-radius:20px;"><span id="menBulkCount">0</span> selec.</span>
                <button class="men-bulk-btn" id="menBtnPresente" onclick="window.menMarcarMasivoConConfirmacion('Presente')"
                    style="background:#16a34a;color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 2px 4px rgba(22,163,74,.2);transition:all .2s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 6px rgba(22,163,74,.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(22,163,74,.2)'">
                    <i class="ti ti-user-check" style="font-size:1rem;"></i> Marcar Presente
                </button>
                <button class="men-bulk-btn" id="menBtnJustificado" onclick="window.menMarcarMasivoConConfirmacion('Justificado')"
                    style="background:#2563eb;color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 2px 4px rgba(37,99,235,.2);transition:all .2s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 6px rgba(37,99,235,.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(37,99,235,.2)'">
                    <i class="ti ti-file-check" style="font-size:1rem;"></i> Marcar Justificado
                </button>
            </div>
        </div>
    </div>
    <?php if (empty($ausenciasMes)): ?>
    <div style="text-align:center;padding:28px 20px;color:#94a3b8;">
        <i class="ti ti-circle-check" style="font-size:2.2rem;display:block;margin-bottom:8px;color:#16a34a;opacity:.4;"></i>
        <p style="margin:0;font-weight:600;font-size:.84rem;">Sin inasistencias en <?= $mesesNombresEs[$mesActual] ?></p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="men-aus-table" id="menAusTable">
        <thead><tr>
            <th style="width:28px;"></th>
            <th>Pasante</th><th>Departamento</th><th>Fecha</th><th>Origen</th>
            <th style="text-align:center;">Acciones</th>
        </tr></thead>
        <tbody>
        <?php
        $diasEs2  = [1=>'Lun',2=>'Mar',3=>'Mie',4=>'Jue',5=>'Vie',6=>'Sab',7=>'Dom'];
        $mesesEs2 = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun',
                     '07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
        foreach ($ausenciasMes as $reg):
            $nombre  = trim(($reg->apellidos ?? '') . ', ' . ($reg->nombres ?? ''));
            $ini2    = strtoupper(substr($reg->nombres ?? 'P',0,1) . substr($reg->apellidos ?? 'A',0,1));
            $fechaS  = $reg->fecha ?? '';
            $dowN    = (int)(new DateTime($fechaS))->format('N');
            $diaN    = (int)date('j', strtotime($fechaS));
            $mesN    = date('m', strtotime($fechaS));
            $fechaFmt= ($diasEs2[$dowN] ?? '') . ' ' . $diaN . ' ' . ($mesesEs2[$mesN] ?? '');
            $isAuto  = (int)($reg->es_auto_fill ?? 0);
            $rowId   = 'men-aus-' . (int)($reg->id ?? 0);
        ?>
        <tr id="<?= $rowId ?>" class="men-aus-tr" data-pid="<?= (int)($reg->pasante_id ?? 0) ?>" data-fecha="<?= htmlspecialchars($fechaS) ?>">
            <td><input type="checkbox" class="men-aus-chk" onchange="menActualizarBulk()"></td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:26px;height:26px;border-radius:6px;background:linear-gradient(135deg,#dc2626,#f87171);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.62rem;font-weight:800;flex-shrink:0;"><?= htmlspecialchars($ini2) ?></div>
                    <div>
                        <div style="font-weight:700;color:#1e293b;font-size:.76rem;white-space:nowrap;"><?= htmlspecialchars($nombre) ?></div>
                        <div style="font-size:.61rem;color:#94a3b8;"><?= htmlspecialchars($reg->cedula ?? '') ?></div>
                    </div>
                </div>
            </td>
            <td style="color:#64748b;font-size:.73rem;white-space:nowrap;"><?= htmlspecialchars($reg->departamento_nombre ?? '') ?></td>
            <td><span style="font-weight:700;color:#1e293b;font-size:.73rem;"><?= $fechaFmt ?></span></td>
            <td>
                <?php if ($isAuto): ?>
                <span style="background:#f0f9ff;color:#0369a1;font-size:.62rem;font-weight:800;padding:2px 7px;border-radius:20px;display:inline-flex;align-items:center;gap:2px;"><i class="ti ti-robot"></i> Auto</span>
                <?php else: ?>
                <span style="background:#fef3c7;color:#92400e;font-size:.62rem;font-weight:800;padding:2px 7px;border-radius:20px;display:inline-flex;align-items:center;gap:2px;"><i class="ti ti-pencil"></i> Manual</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center;white-space:nowrap;">
                <button onclick="window.menMarcarUnoConConfirmacion(this,'Presente')" data-pid="<?= (int)($reg->pasante_id ?? 0) ?>" data-fecha="<?= htmlspecialchars($fechaS) ?>"
                    style="background:#dcfce7;color:#15803d;border:none;padding:3px 9px;border-radius:6px;font-size:.65rem;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:3px;margin-right:3px;transition:all .2s;" onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                    <i class="ti ti-user-check"></i> Presente
                </button>
                <button onclick="window.menMarcarUnoConConfirmacion(this,'Justificado')" data-pid="<?= (int)($reg->pasante_id ?? 0) ?>" data-fecha="<?= htmlspecialchars($fechaS) ?>"
                    style="background:#dbeafe;color:#1d4ed8;border:none;padding:3px 9px;border-radius:6px;font-size:.65rem;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:3px;transition:all .2s;" onmouseover="this.style.background='#bfdbfe'" onmouseout="this.style.background='#dbeafe'">
                    <i class="ti ti-file-check"></i> Justificado
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class="men-pager" id="menPager">
        <span id="menPagInfo" style="flex:1;font-size:.7rem;color:#94a3b8;"></span>
        <button id="menPagPrev" onclick="menCambiarPag(-1)" disabled><i class="ti ti-chevron-left"></i> Ant.</button>
        <button id="menPagNext" onclick="menCambiarPag(1)">Sig. <i class="ti ti-chevron-right"></i></button>
    </div>
    <?php endif; ?>
</div>

<script>
/* ══════════════════════════════════════════════════════
   AJAX PJAX — Navegación mensual sin recarga de página
   ═══════════════════════════════════════════════════ */
window.cargarMesAjax = function(url) {
    if (!url || url === '#') return;

    // Indicador visual
    var calGrid = document.getElementById('menCalGrid');
    var label   = document.getElementById('menNavLabel');
    if (calGrid) calGrid.style.opacity = '0.3';
    if (label)   label.style.opacity   = '0.5';

    fetch(url)
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function(html) {
            var parser      = new DOMParser();
            var doc         = parser.parseFromString(html, 'text/html');
            var newWrapper  = doc.getElementById('menBentoWrapper');
            var curWrapper  = document.getElementById('menBentoWrapper');

            if (!newWrapper || !curWrapper) {
                window.location.href = url;
                return;
            }

            // Clonar el nuevo contenido SIN los <script> para insertar primero el HTML
            var clone = newWrapper.cloneNode(true);
            var scripts = Array.from(clone.querySelectorAll('script'));
            scripts.forEach(function(s) { s.parentNode.removeChild(s); });

            curWrapper.innerHTML = clone.innerHTML;

            // Actualizar URL
            window.history.pushState({ path: url }, '', url);

            // Re-ejecutar scripts del bloque mensual (IIFE + lógica de calendario)
            var srcScripts = newWrapper.querySelectorAll('script');
            srcScripts.forEach(function(s) {
                var ns = document.createElement('script');
                ns.textContent = s.textContent;
                document.body.appendChild(ns);
            });
        })
        .catch(function(err) {
            console.error('[SGP-AJAX] Error cargando mes:', err);
            window.location.href = url;
        });
};

(function(){
'use strict';
const MES    = <?= $mesDataJS ?? '{}' ?>;
const DONUT       = <?= !empty($donutJS) ? $donutJS : '[]' ?>;
const GLOBAL_DONUT = {p: <?= (int)($globalP ?? 0) ?>, j: <?= (int)($globalJ ?? 0) ?>, a: <?= (int)($globalA ?? 0) ?>};
const DEPTOS_DATA = <?= json_encode($resumenDeptosJS ?? []) ?>;
const DEPTOS_COLORS = <?= json_encode($coloresDepto ?? []) ?>;
const URL_RM = '<?= URLROOT ?>/asistencias/registro_manual';
const grid   = document.getElementById('menCalGrid');
const tip    = document.getElementById('menTooltip');

/* ── Donut Chart de Asistencia Global ── */
(function buildApexDonut() {
    if (typeof ApexCharts === 'undefined') { setTimeout(buildApexDonut, 100); return; }
    var cont = document.getElementById('radialAppleWatch');
    if (!cont) return;

    function calcPct(p, j, a) {
        var tot = p + j + a;
        return tot > 0 ? Math.round((p + j) / tot * 100) : 0;
    }
    function getHiColor(pct) {
        return pct >= 85 ? '#16a34a' : (pct >= 60 ? '#d97706' : '#dc2626');
    }
    function buildOptions(p, j, a, centerLabel, centerColor) {
        var pct = calcPct(p, j, a);
        return {
            series: [p, j, a],
            chart: { type: 'donut', height: 200, toolbar: { show: false }, animations: { enabled: true, speed: 500 } },
            labels: ['Presente', 'Justificado', 'Ausente'],
            colors: ['#16a34a', '#2563eb', '#dc2626'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name:  { show: true, fontSize: '11px', color: '#94a3b8', fontWeight: 700, offsetY: 18 },
                            value: { show: true, fontSize: '26px', fontWeight: 900, color: centerColor, offsetY: -12,
                                     formatter: function() { return pct + '%'; } },
                            total: { show: true, label: centerLabel, color: '#94a3b8', fontSize: '11px', fontWeight: 700,
                                     formatter: function() { return pct + '%'; } }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: { show: false },
            stroke: { width: 2, colors: ['#fff'] },
            tooltip: { enabled: true, y: { formatter: function(val) { return val + ' registros'; } } }
        };
    }
    function buildDeptoLey() {
        var ley = document.getElementById('menDonutLey');
        if (!ley) return;
        ley.innerHTML = '';
        DONUT.forEach(function(d) {
            var has      = (d.p + d.j + d.a) > 0;
            var initial  = d.label.charAt(0).toUpperCase();
            var pctColor = d.pct >= 85 ? '#16a34a' : (d.pct >= 60 ? '#d97706' : '#dc2626');
            var pctBg    = d.pct >= 85 ? '#dcfce7' : (d.pct >= 60 ? '#fef3c7' : '#fee2e2');
            var row = document.createElement('div');
            row.className = 'men-pasante-row';
            row.innerHTML =
                '<div class="men-av" style="background:'+d.color+';flex-shrink:0;">'+initial+'</div>'
              + '<div style="min-width:0;flex:1;">'
              +   '<div style="font-size:.72rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+d.label+'</div>'
              +   '<div style="font-size:.58rem;color:#94a3b8;">'+(has ? d.p+' Pres · '+d.a+' Aus · '+d.j+' Just' : 'Sin registros')+'</div>'
              + '</div>'
              + (has
                  ? '<span class="men-pct-badge" style="background:'+pctBg+';color:'+pctColor+';">'+d.pct+'%</span>'
                  : '<span class="men-pct-badge" style="background:#f1f5f9;color:#94a3b8;">—</span>');
            ley.appendChild(row);
        });
    }

    var hiColor = '<?= $hiColor ?>';
    window.radialDonutChart = new ApexCharts(cont, buildOptions(GLOBAL_DONUT.p, GLOBAL_DONUT.j, GLOBAL_DONUT.a, 'GLOBAL', hiColor));
    window.radialDonutChart.render();
    buildDeptoLey();

    window._donutBuildOptions  = buildOptions;
    window._donutCalcPct       = calcPct;
    window._donutGetHiColor    = getHiColor;
    window._donutBuildDeptoLey = buildDeptoLey;
})();

window.menFiltrarDonut = function(dep, btn) {
    document.querySelectorAll('#menDonutPills .men-filter-pill').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    if (!window.radialDonutChart || !window._donutBuildOptions) return;

    var lbl = document.getElementById('menDonutGlobalLabel');

    if (dep === 'Todos') {
        var pct    = window._donutCalcPct(GLOBAL_DONUT.p, GLOBAL_DONUT.j, GLOBAL_DONUT.a);
        var hColor = window._donutGetHiColor(pct);
        var hLabel = pct >= 85 ? 'Excelente' : (pct >= 60 ? 'Regular' : 'Crítico');
        window.radialDonutChart.updateOptions(
            window._donutBuildOptions(GLOBAL_DONUT.p, GLOBAL_DONUT.j, GLOBAL_DONUT.a, 'GLOBAL', hColor)
        );
        if (lbl) { lbl.textContent = hLabel; lbl.style.background = hColor+'18'; lbl.style.color = hColor; }
        window._donutBuildDeptoLey();
    } else {
        var d = DONUT.find(function(x) { return x.label === dep; });
        if (!d) return;
        var shortLabel = d.label.length > 14 ? d.label.substring(0, 11) + '…' : d.label;
        var hLColor    = window._donutGetHiColor(d.pct);
        var hLabel     = d.pct >= 85 ? 'Excelente' : (d.pct >= 60 ? 'Regular' : 'Crítico');
        window.radialDonutChart.updateOptions(
            window._donutBuildOptions(d.p, d.j, d.a, shortLabel, d.color)
        );
        if (lbl) { lbl.textContent = hLabel; lbl.style.background = hLColor+'18'; lbl.style.color = hLColor; }
        var ley = document.getElementById('menDonutLey');
        if (ley) {
            ley.innerHTML = '';
            var total = d.p + d.j + d.a;
            [{label:'Presente', val:d.p, color:'#16a34a', icon:'ti-user-check'},
             {label:'Justificado', val:d.j, color:'#2563eb', icon:'ti-file-check'},
             {label:'Ausente', val:d.a, color:'#dc2626', icon:'ti-x'}]
            .forEach(function(s) {
                var pct = total > 0 ? Math.round(s.val / total * 100) : 0;
                var row = document.createElement('div');
                row.className = 'men-pasante-row';
                row.innerHTML =
                    '<div class="men-av" style="background:'+s.color+';flex-shrink:0;"><i class="ti '+s.icon+'" style="font-size:.7rem;"></i></div>'
                  + '<div style="min-width:0;flex:1;">'
                  +   '<div style="font-size:.72rem;font-weight:700;color:#1e293b;">'+s.label+'</div>'
                  +   '<div style="font-size:.58rem;color:#94a3b8;">'+s.val+' registros este mes</div>'
                  + '</div>'
                  + '<span class="men-pct-badge" style="background:'+s.color+'18;color:'+s.color+';">'+pct+'%</span>';
                ley.appendChild(row);
            });
        }
    }
};


/* ── Calendario 5 columnas (solo L-V) ── */
function getColor(d, dep) {
    if (MES.feriados[d] !== undefined) return 'feriado';
    var data = (MES.deptos[dep] || {})[d];
    if (!data) return null;
    var tot = data.p + data.a + data.j;
    if (tot === 0) return null;
    var pct = (data.p + data.j) / tot * 100;
    return pct >= 85 ? 'verde' : (pct >= 60 ? 'amarillo' : 'rojo');
}

function renderCal(dep) {
    grid.innerHTML = '';
    // Calcular el DOW del primer día (1=Lun..5=Vie, 6=Sab,7=Dom)
    // En 5 columnas: el offset es (DOW-1) para L-V, ignorar si cae sab/dom
    var primerDowLV = MES.primerDow <= 5 ? MES.primerDow - 1 : 0;
    for (var i = 0; i < primerDowLV; i++) {
        var b = document.createElement('div'); b.className = 'men-cell is-empty'; grid.appendChild(b);
    }
    for (var d = 1; d <= MES.numDays; d++) {
        var dt  = new Date(MES.anio, MES.mes - 1, d);
        var dow = dt.getDay() === 0 ? 7 : dt.getDay(); // 1=Lun..7=Dom
        if (dow >= 6) continue; // Saltar sáb y dom
        var cell = document.createElement('div');
        cell.className = 'men-cell';
        cell.textContent = d;
        if (MES.hoyDia > 0 && d > MES.hoyDia) {
            cell.classList.add('future');
        } else {
            var color = getColor(d, dep);
            cell.classList.add(color || 'sin-dato');
            if (color) {
                (function(dia){
                    cell.addEventListener('mouseenter', function(e){
                        var fer  = MES.feriados[dia];
                        var dat  = (MES.deptos[dep] || {})[dia];
                        var txt  = '';
                        if (fer) { txt = 'Feriado: ' + fer; }
                        else if (dat) {
                            var tot = dat.p + dat.a + dat.j;
                            var pct = tot > 0 ? Math.round((dat.p + dat.j) / tot * 100) : 0;
                            txt = pct + '% asistencia\n' + dat.p + ' pres. · ' + dat.a + ' aus. · ' + dat.j + ' just.';
                        }
                        tip.textContent = txt; tip.classList.add('show'); posT(e);
                    });
                    cell.addEventListener('mousemove', function(e){ posT(e); });
                    cell.addEventListener('mouseleave', function(){ tip.classList.remove('show'); });
                })(d);
            }
        }
        if (d === MES.hoyDia) cell.classList.add('hoy');
        grid.appendChild(cell);
    }
}

function posT(e) {
    var x = e.clientX + 14, y = e.clientY - 10;
    tip.style.left = (x + tip.offsetWidth > window.innerWidth ? x - tip.offsetWidth - 28 : x) + 'px';
    tip.style.top  = y + 'px';
}

window.menFiltrarDepto = function(btn, dep) {
    document.querySelectorAll('.men-dept-pill').forEach(function(p){ p.classList.remove('active'); });
    btn.classList.add('active');
    renderCal(dep);
};
renderCal('Todos');

/* ── Filtros En riesgo ── */
function menFiltrarLista(listId, dep) {
    var rows = document.querySelectorAll('#' + listId + ' .men-pasante-row');
    var count = 0;
    rows.forEach(function(r){
        var show = dep === 'Todos' || r.dataset.dep === dep;
        r.style.display = show ? '' : 'none';
        if (show) count++;
    });
    return count;
}

/* ── Filtros para Tarjetas (Retardos y Top) ── */
let currentTopPage = 0;
const PAGE_SIZE = 5;

window.menFiltrarRetardos = function(btn) {
    document.querySelectorAll('#menRetardoPills .men-filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const dep = btn.dataset.dep;
    document.querySelectorAll('#menRetardoList .men-ret-row').forEach(r => {
        r.style.display = (dep === 'Todos' || r.dataset.dep === dep) ? 'flex' : 'none';
    });
};

window.actualizarVistaRetardos = function() {
    const pillEl = document.querySelector('#menRetardoPills .men-filter-pill.active');
    if (!pillEl) return;
    const dep = pillEl.dataset.dep;
    document.querySelectorAll('#menRetardoList .men-ret-row').forEach(r => {
        r.style.display = (dep === 'Todos' || r.dataset.dep === dep) ? 'flex' : 'none';
    });
};

window.menFiltrarTop = function(btn) {
    document.querySelectorAll('#menTopPills .men-filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    currentTopPage = 0;
    actualizarVistaTop();
};

window.menPaginarTop = function(dir) {
    currentTopPage += dir;
    actualizarVistaTop();
};

window.actualizarVistaTop = function() {
    const pillEl = document.querySelector('#menTopPills .men-filter-pill.active');
    if (!pillEl) return; // Guard: solo en vista mensual
    const dep = pillEl.dataset.dep;
    const allRows = document.querySelectorAll('.men-top-row');
    const filteredRows = Array.from(allRows).filter(r => dep === 'Todos' || r.dataset.dep === dep);
    const totalFiltered = filteredRows.length;

    if (currentTopPage < 0) currentTopPage = 0;
    const maxPage = Math.ceil(totalFiltered / PAGE_SIZE) - 1;
    if (currentTopPage > maxPage && maxPage >= 0) currentTopPage = maxPage;

    allRows.forEach(r => r.style.display = 'none');
    filteredRows.slice(currentTopPage * PAGE_SIZE, (currentTopPage + 1) * PAGE_SIZE).forEach((r, idx) => {
        r.style.display = 'flex';
        const rankEl = r.querySelector('[style*="width:15px"]');
        if (rankEl) rankEl.textContent = (currentTopPage * PAGE_SIZE) + idx + 1;
    });

    const btnAnt = document.getElementById('btnTopAnt');
    const btnSig = document.getElementById('btnTopSig');
    if (btnAnt) btnAnt.disabled = currentTopPage <= 0;
    if (btnSig) btnSig.disabled = currentTopPage >= maxPage || totalFiltered === 0;
};

window.menVerDetalleRetardos = function(pid, nombre, iniciales, depto, totalRetardos) {
    const mesAnio  = '<?= $anioActual ?>-<?= sprintf("%02d", $mesActual) ?>';
    const mesLabel = '<?= $mesesNombresEs[$mesActual] ?? '' ?> <?= $anioActual ?>';
    iniciales      = iniciales || nombre.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    depto          = depto     || '';
    totalRetardos  = totalRetardos || 0;

    // --- Loading state premium ---
    Swal.fire({
        html: `
        <div style="padding:8px 0;">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem;flex-shrink:0;box-shadow:0 4px 12px rgba(245,158,11,.35);">${iniciales}</div>
                <div style="text-align:left;">
                    <div style="font-size:.95rem;font-weight:800;color:#1e293b;">${nombre}</div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">${depto}</div>
                </div>
            </div>
            <div style="text-align:center;padding:24px 0;color:#94a3b8;"><div class="swal2-loading" style="margin:0 auto;"></div><p style="margin-top:10px;font-size:.8rem;">Cargando historial...</p></div>
        </div>`,
        showConfirmButton: false,
        allowOutsideClick: false,
        width: '480px',
        padding: '24px',
        customClass: { popup: 'rounded-4' }
    });

    fetch('<?= URLROOT ?>/asistencias/obtenerResumenMensual', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `pasante_id=${pid}&mes_anio=${mesAnio}`
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) throw new Error(res.message);

        const retardos = [];
        for (const fecha in res.datos) {
            if (res.datos[fecha].es_retardo === 1) {
                retardos.push({ fecha, ...res.datos[fecha] });
            }
        }

        if (retardos.length === 0) {
            Swal.fire({
                html: `
                <div style="padding:8px 0;">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
                        <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem;flex-shrink:0;box-shadow:0 4px 12px rgba(245,158,11,.35);">${iniciales}</div>
                        <div style="text-align:left;">
                            <div style="font-size:.95rem;font-weight:800;color:#1e293b;">${nombre}</div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">${depto}</div>
                        </div>
                    </div>
                    <div style="text-align:center;padding:20px;">
                        <i class="ti ti-circle-check" style="font-size:2.5rem;color:#10b981;display:block;margin-bottom:8px;"></i>
                        <p style="color:#475569;font-size:.85rem;font-weight:600;margin:0;">Sin retardos confirmados este mes.</p>
                    </div>
                </div>`,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#1e293b',
                width: '440px',
                padding: '24px',
                customClass: { popup: 'rounded-4' }
            });
            return;
        }

        // --- Construir filas de la tabla ---
        let filas = '';
        retardos.forEach(r => {
            const dt      = new Date(r.fecha + 'T00:00:00');
            const dateStr = dt.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
            const hora    = r.hora_registro ? r.hora_registro.substring(0, 5) : '--:--';
            const isKiosko = (r.metodo || '').toLowerCase().includes('kiosk') || (r.metodo || '').toLowerCase().includes('kiosco');
            const metodoBadge = isKiosko
                ? `<span style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;color:#2563eb;padding:2px 8px;border-radius:20px;font-size:.67rem;font-weight:700;"><i class="ti ti-device-imac"></i> Kiosco</span>`
                : `<span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:20px;font-size:.67rem;font-weight:700;"><i class="ti ti-pencil"></i> Manual</span>`;

            filas += `
                <tr style="border-bottom:1px solid #f1f5f9;transition:background .12s;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'">
                    <td style="padding:10px 12px;font-size:.78rem;font-weight:700;color:#1e293b;white-space:nowrap;">${dateStr}</td>
                    <td style="padding:10px 12px;">
                        <span style="background:linear-gradient(135deg,#fff7ed,#ffedd5);color:#c2410c;font-weight:800;font-size:.78rem;padding:3px 10px;border-radius:8px;display:inline-block;font-family:monospace;letter-spacing:.5px;">${hora}</span>
                    </td>
                    <td style="padding:10px 12px;">${metodoBadge}</td>
                </tr>`;
        });

        const html = `
        <div style="padding:4px 0;">

            <!-- Identidad del pasante -->
            <div style="display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;margin-bottom:14px;">
                <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem;flex-shrink:0;box-shadow:0 4px 12px rgba(245,158,11,.35);">${iniciales}</div>
                <div style="text-align:left;flex:1;min-width:0;">
                    <div style="font-size:.95rem;font-weight:800;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${nombre}</div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">${depto}</div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:center;gap:2px;flex-shrink:0;">
                    <span style="font-size:1.4rem;font-weight:900;color:#f59e0b;line-height:1;">${retardos.length}</span>
                    <span style="font-size:.6rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">retardo${retardos.length !== 1 ? 's' : ''}</span>
                </div>
            </div>

            <!-- Mes label -->
            <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">
                <i class="ti ti-calendar" style="margin-right:4px;"></i>${mesLabel}
            </div>

            <!-- Tabla -->
            <div style="max-height:280px;overflow-y:auto;border-radius:10px;border:1px solid #f1f5f9;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#fafafa;position:sticky;top:0;">
                            <th style="padding:8px 12px;font-size:.63rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;text-align:left;border-bottom:1px solid #f1f5f9;">Fecha</th>
                            <th style="padding:8px 12px;font-size:.63rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;text-align:left;border-bottom:1px solid #f1f5f9;">Hora</th>
                            <th style="padding:8px 12px;font-size:.63rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;text-align:left;border-bottom:1px solid #f1f5f9;">Método</th>
                        </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                </table>
            </div>
        </div>`;

        Swal.fire({
            html: html,
            confirmButtonText: '<i class="ti ti-x" style="margin-right:4px;"></i> Cerrar',
            confirmButtonColor: '#1e293b',
            width: '480px',
            padding: '24px 28px',
            customClass: { popup: 'rounded-4', confirmButton: 'rounded-3' },
            showClass: { popup: 'animate__animated animate__fadeInDown animate__faster' }
        });
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'No se pudo cargar el detalle de retardos.',
            confirmButtonColor: '#1e293b'
        });
    });
};

// Inicializar badges
(function(){
    var rn = document.querySelectorAll('#menRiesgoList .men-pasante-row').length;
    var rb = document.getElementById('menRiesgoBadge');
    if (rb) rb.textContent = rn + ' pasante' + (rn !== 1 ? 's' : '');
    var tn = document.querySelectorAll('#menTopList .men-pasante-row').length;
    var tb = document.getElementById('menTopBadge');
    if (tb) tb.textContent = 'top ' + tn;
})();

/* ── Paginación S4 ── */
var PAG_SIZE = 8, menPage = 0, menTrs = [];
(function initPag(){
    var t = document.getElementById('menAusTable');
    if (!t) return;
    menTrs = Array.from(t.querySelectorAll('tbody tr.men-aus-tr'));
    menRenderPag();
})();

function menRenderPag() {
    var active = menTrs.filter(function(r){ return !r.classList.contains('procesada'); });
    var total  = active.length;
    var pages  = Math.max(1, Math.ceil(total / PAG_SIZE));
    if (menPage >= pages) menPage = pages - 1;
    menTrs.forEach(function(r){ r.classList.add('pag-hidden'); });
    active.slice(menPage * PAG_SIZE, menPage * PAG_SIZE + PAG_SIZE).forEach(function(r){ r.classList.remove('pag-hidden'); });
    var info = document.getElementById('menPagInfo');
    if (info) info.textContent = 'Pág. ' + (menPage + 1) + ' / ' + pages + ' — ' + total + ' pendientes';
    var prev = document.getElementById('menPagPrev');
    var next = document.getElementById('menPagNext');
    if (prev) prev.disabled = menPage === 0;
    if (next) next.disabled = menPage >= pages - 1;
}
window.menCambiarPag = function(dir) { menPage += dir; menRenderPag(); };

/* ── Bulk (botones siempre visibles) ── */
window.menActualizarBulk = function() {
    var sel   = document.querySelectorAll('.men-aus-chk:checked').length;
    document.getElementById('menBulkCount').textContent = sel;
    var hasAny = sel > 0;
    
    var container = document.getElementById('menBulkContainer');
    if (container) {
        if (hasAny) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        } else {
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';
        }
    }

    var bp = document.getElementById('menBtnPresente');
    var bj = document.getElementById('menBtnJustificado');
    if (bp) bp.disabled = !hasAny;
    if (bj) bj.disabled = !hasAny;
    var total = document.querySelectorAll('.men-aus-chk:not([disabled])').length;
    document.getElementById('menChkTodos').checked = sel === total && total > 0;
};

window.menSelTodos = function(chk) {
    document.querySelectorAll('.men-aus-chk:not([disabled])').forEach(function(c){ c.checked = chk.checked; });
    menActualizarBulk();
};

function menPost(pid, fecha, estado) {
    var fd = new FormData();
    fd.append('pasante_id', pid); fd.append('fecha', fecha); fd.append('estado', estado);
    return fetch(URL_RM, { method:'POST', body:fd }).then(function(r){ return r.json(); });
}

function marcarFila(rowId, estado) {
    var tr = document.getElementById(rowId);
    if (!tr) return;
    tr.classList.add('procesada');
    var chk = tr.querySelector('.men-aus-chk');
    if (chk) { chk.checked = false; chk.disabled = true; }
    var color = estado === 'Presente' ? '#16a34a' : '#2563eb';
    var bg    = estado === 'Presente' ? '#dcfce7' : '#dbeafe';
    var ico   = estado === 'Presente' ? 'ti-check' : 'ti-file-check';
    tr.querySelector('td:last-child').innerHTML = '<span style="background:' + bg + ';color:' + color + ';font-size:.65rem;font-weight:800;padding:3px 9px;border-radius:6px;display:inline-flex;align-items:center;gap:3px;"><i class="ti ' + ico + '"></i> ' + estado + '</span>';
    var badge = document.getElementById('menAusBadge');
    if (badge) badge.textContent = document.querySelectorAll('#menAusTable tbody tr.men-aus-tr:not(.procesada)').length;
    menRenderPag();
}

window.menMarcarUnoConConfirmacion = async function(btn, estado) {
    let motivo = '';
    
    if (estado === 'Justificado') {
        const { value: text, isConfirmed } = await Swal.fire({
            title: '<i class="ti ti-file-check" style="color:#2563eb;font-size:2rem;margin-bottom:8px;display:block;"></i> Justificar Inasistencia',
            text: 'Ingrese el motivo de la justificación:',
            input: 'textarea',
            inputPlaceholder: 'Ej. Reposo médico, permiso especial...',
            inputAttributes: {
                'aria-label': 'Motivo de la justificación',
                'maxlength': '255',
                'style': 'height: 100px; resize: none;'
            },
            showCancelButton: true,
            confirmButtonText: 'Justificar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            inputValidator: (value) => {
                if (!value || value.trim().length < 5) {
                    return 'Debe ingresar un motivo válido (mínimo 5 caracteres)';
                }
            }
        });
        if (!isConfirmed) return;
        motivo = text.trim();
    } else {
        const { isConfirmed } = await Swal.fire({
            title: '¿Marcar como Presente?',
            text: 'Esta acción registrará la asistencia manual para este día.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar presente',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b'
        });
        if (!isConfirmed) return;
    }

    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>...';
    var tr = btn.closest('tr');
    
    var fd = new FormData();
    fd.append('pasante_id', btn.dataset.pid); 
    fd.append('fecha', btn.dataset.fecha); 
    fd.append('estado', estado);
    if (motivo) fd.append('observacion', motivo);

    fetch(URL_RM, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) { 
            marcarFila(tr.id, estado); 
            menActualizarBulk(); 
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Registro actualizado correctamente.',
                timer: 1500,
                showConfirmButton: false
            });
        }
        else { 
            btn.disabled = false; 
            btn.innerHTML = estado === 'Presente' ? '<i class="ti ti-user-check"></i> Presente' : '<i class="ti ti-file-check"></i> Justificado'; 
            Swal.fire('Error', res.message || 'Error al guardar.', 'error'); 
        }
    })
    .catch(err => {
        btn.disabled = false; 
        btn.innerHTML = estado === 'Presente' ? '<i class="ti ti-user-check"></i> Presente' : '<i class="ti ti-file-check"></i> Justificado'; 
        Swal.fire('Error', 'Error de conexión', 'error');
    });
};

window.menMarcarMasivoConConfirmacion = async function(estado) {
    var chks = Array.from(document.querySelectorAll('.men-aus-chk:checked'));
    if (!chks.length) return;
    
    let motivo = '';

    if (estado === 'Justificado') {
        const { value: text, isConfirmed } = await Swal.fire({
            title: `<i class="ti ti-file-check" style="color:#2563eb;font-size:2rem;margin-bottom:8px;display:block;"></i> Justificar Masivamente`,
            html: `Se justificarán <b>${chks.length}</b> inasistencias.<br>Ingrese el motivo general:`,
            input: 'textarea',
            inputPlaceholder: 'Ej. Permiso colectivo, reposo...',
            inputAttributes: {
                'aria-label': 'Motivo de la justificación',
                'maxlength': '255',
                'style': 'height: 100px; resize: none;'
            },
            showCancelButton: true,
            confirmButtonText: 'Justificar Todos',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            inputValidator: (value) => {
                if (!value || value.trim().length < 5) {
                    return 'Debe ingresar un motivo válido (mínimo 5 caracteres)';
                }
            }
        });
        if (!isConfirmed) return;
        motivo = text.trim();
    } else {
        const { isConfirmed } = await Swal.fire({
            title: `¿Marcar ${chks.length} registros como Presente?`,
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar todos',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b'
        });
        if (!isConfirmed) return;
    }

    Swal.fire({
        title: 'Procesando...',
        html: `Procesando <b id="swal-progreso">0</b> de ${chks.length} registros`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    let completados = 0;
    let errores = 0;

    async function next(i) {
        if (i >= chks.length) { 
            menActualizarBulk(); 
            Swal.fire({
                icon: errores > 0 ? 'warning' : 'success',
                title: 'Proceso completado',
                text: `${completados} exitosos, ${errores} errores.`,
                confirmButtonColor: '#2563eb'
            });
            return; 
        }
        var tr = chks[i].closest('tr');
        if (tr.classList.contains('procesada')) { next(i+1); return; }
        
        var fd = new FormData();
        fd.append('pasante_id', tr.dataset.pid); 
        fd.append('fecha', tr.dataset.fecha); 
        fd.append('estado', estado);
        if (motivo) fd.append('observacion', motivo);

        try {
            let r = await fetch(URL_RM, { method:'POST', body:fd });
            let res = await r.json();
            if (res.success) {
                marcarFila(tr.id, estado);
                completados++;
            } else {
                errores++;
            }
        } catch(e) {
            errores++;
        }
        
        let pro = document.getElementById('swal-progreso');
        if (pro) pro.textContent = completados + errores;
        
        next(i+1);
    }
    next(0);
};
})();
</script>
</div><!-- /#menBentoWrapper -->



        <?php elseif ($vistaActual === 'anual'): ?>
            <?php 
            /**
             * VISTA TOTAL (ANUAL) - REDISEÑO PREMIUM
             * Mantiene Banner/KPIs estándar pero eleva el Muro de Pasantes.
             */
            ?>
            <!-- Fuentes Premium -->
            <!-- Fuentes locales -->
            <link rel="stylesheet" href="<?= URLROOT ?>/css/fonts.css">
            
            <style>
            :root {
                /* Premium Tokens (Adaptados del template) */
                --p-surface:    #FFFFFF;
                --p-surface-2:  #F8FAFD;
                --p-border:     #DDE2F0;
                --p-ink:        #0D1424;
                --p-ink-2:      #3A4768;
                --p-ink-3:      #7480A0;
                --p-blue:       #1D4ED8;
                --p-blue-dim:   rgba(29, 78, 216, 0.07);
                --p-green:      #059669;
                --p-green-dim:  rgba(5, 150, 105, 0.08);
                --p-radius:     20px;
                --p-shadow:     0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
                --p-font:       'Geist', sans-serif;
            }

            /* Contenedor Muro */
            .muro-premium { font-family: var(--p-font); }
            
            /* Card Estilo Apple/Premium */
            .p-card {
                background: white;
                border: 1px solid var(--p-border);
                border-radius: var(--p-radius);
                padding: 24px;
                transition: box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s;
                position: relative;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                box-shadow: var(--p-shadow);
            }
            .muro-item:hover .p-card {
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
                border-color: var(--p-blue);
            }

            /* ── Flip 3D ── */
            .flip-wrapper {
                perspective: 1200px;
                width: 100%;
            }
            .flip-inner {
                width: 100%;
                display: grid;
                transform-style: preserve-3d;
                transition: transform 0.65s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .flip-inner.is-flipped {
                transform: rotateY(180deg);
            }
            .flip-front, .flip-back {
                grid-area: 1 / 1;
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
                min-width: 0;
            }
            .flip-back {
                transform: rotateY(180deg);
                border-radius: var(--p-radius);
                overflow: hidden;
                cursor: pointer;
            }

            /* Toda la card frontal es cliceable */
            .flip-front.p-card { cursor: pointer; }

            /* Separador + pill "Ver carnet" */
            .flip-hint-sep {
                width: 100%;
                margin-top: auto;
                padding-top: 14px;
                border-top: 1px solid var(--p-border);
                display: flex;
                justify-content: center;
            }
            .flip-hint {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                font-weight: 700;
                color: var(--p-blue);
                background: var(--p-blue-dim);
                border: 1.5px solid rgba(29,78,216,0.18);
                padding: 5px 16px;
                border-radius: 50px;
                letter-spacing: 0.3px;
                transition: all 0.22s;
            }
            .flip-front.p-card:hover .flip-hint {
                background: var(--p-blue);
                color: white;
                box-shadow: 0 4px 14px rgba(29,78,216,0.28);
            }

            /* ── Carnet (reverso) ── */
            .p-carnet {
                background: linear-gradient(160deg, #0a0f1e 0%, #0f1f4a 55%, #1D4ED8 100%);
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 18px 16px 16px;
                position: relative;
                gap: 0;
            }
            .p-carnet::before {
                content: '';
                position: absolute;
                top: -50px; right: -50px;
                width: 200px; height: 200px;
                border-radius: 50%;
                background: rgba(29,78,216,0.2);
                pointer-events: none;
            }
            .p-carnet::after {
                content: '';
                position: absolute;
                bottom: -35px; left: -35px;
                width: 160px; height: 160px;
                border-radius: 50%;
                background: rgba(255,255,255,0.04);
                pointer-events: none;
            }
            .pc-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                margin-bottom: 12px;
                position: relative; z-index: 1;
            }
            .pc-logo {
                height: 26px; width: auto;
                filter: brightness(0) invert(1);
                opacity: 0.85;
            }
            .pc-sys-label {
                font-size: 0.58rem;
                font-weight: 700;
                color: rgba(255,255,255,0.45);
                letter-spacing: 1px;
                text-transform: uppercase;
            }
            .pc-photo-wrap { margin-bottom: 8px; position: relative; z-index: 1; }
            .pc-photo-img {
                width: 72px; height: 72px;
                border-radius: 50%;
                border: 3px solid rgba(255,255,255,0.3);
                object-fit: cover;
                box-shadow: 0 8px 20px rgba(0,0,0,0.45);
            }
            .pc-photo-init {
                width: 72px; height: 72px;
                border-radius: 50%;
                border: 3px solid rgba(255,255,255,0.25);
                background: linear-gradient(135deg, #1D4ED8, #7C3AED);
                color: white;
                font-size: 1.5rem;
                font-weight: 800;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 8px 20px rgba(0,0,0,0.45);
            }
            .pc-fullname {
                font-size: 0.9rem;
                font-weight: 800;
                color: white;
                text-align: center;
                line-height: 1.2;
                margin-bottom: 5px;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                position: relative; z-index: 1;
            }
            .pc-role-badge {
                font-size: 0.58rem;
                font-weight: 800;
                letter-spacing: 2px;
                color: rgba(255,255,255,0.5);
                background: rgba(255,255,255,0.08);
                border: 1px solid rgba(255,255,255,0.12);
                border-radius: 20px;
                padding: 2px 10px;
                margin-bottom: 10px;
                position: relative; z-index: 1;
            }
            .pc-data {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 4px;
                margin-bottom: 12px;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.09);
                border-radius: 10px;
                padding: 8px 10px;
                position: relative; z-index: 1;
            }
            .pc-row { display: flex; align-items: flex-start; gap: 7px; min-width: 0; }
            .pc-icon { color: rgba(255,255,255,0.4); font-size: 0.8rem; flex-shrink: 0; margin-top: 1px; }
            .pc-val {
                font-size: 0.7rem;
                color: rgba(255,255,255,0.82);
                font-weight: 600;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                min-width: 0;
            }
            .pc-actions {
                display: flex;
                gap: 7px;
                width: 100%;
                position: relative; z-index: 1;
            }
            .pc-btn {
                flex: 1; height: 34px;
                border-radius: 9px;
                border: none;
                font-size: 0.75rem;
                font-weight: 700;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                transition: all 0.2s;
            }
            .pc-btn-pdf {
                background: rgba(220,38,38,0.15);
                color: #fca5a5;
                border: 1px solid rgba(220,38,38,0.3);
            }
            .pc-btn-pdf:hover { background: #dc2626; color: white; }
            .pc-btn-xls {
                background: rgba(21,128,61,0.15);
                color: #86efac;
                border: 1px solid rgba(21,128,61,0.3);
            }
            .pc-btn-xls:hover { background: #15803d; color: white; }

            /* Botón cerrar carnet */
            .pc-close-btn {
                width: 22px; height: 22px;
                border-radius: 50%;
                border: 1px solid rgba(255,255,255,0.2);
                background: rgba(255,255,255,0.1);
                color: rgba(255,255,255,0.6);
                font-size: 0.7rem;
                cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                transition: all 0.2s;
                flex-shrink: 0;
            }
            .pc-close-btn:hover { background: rgba(255,255,255,0.25); color: white; }

            /* Status Badge */
            .p-badge {
                padding: 6px 14px;
                border-radius: 50px;
                font-size: 0.75rem;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                letter-spacing: 0.3px;
            }
            .p-badge-active { background: var(--p-blue-dim); color: var(--p-blue); }
            .p-badge-final  { background: var(--p-green-dim); color: var(--p-green); }

            /* Radial Gauge Premium */
            .p-gauge-wrap { position: relative; width: 130px; height: 130px; margin: 15px 0; }
            .p-gauge-svg { transform: rotate(-90deg); }
            .p-gauge-track { fill: none; stroke: #F1F5F9; stroke-width: 10; }
            .p-gauge-fill {
                fill: none;
                stroke-width: 10;
                stroke-linecap: round;
                stroke-dasharray: 339.292;
                transition: stroke-dashoffset 1.5s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .p-gauge-info {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .p-gauge-val { font-family: 'JetBrains Mono', monospace; font-size: 1.4rem; font-weight: 800; color: var(--p-ink); line-height: 1; }
            .p-gauge-lbl { font-size: 0.7rem; color: var(--p-ink-3); font-weight: 600; margin-top: 2px; }

            /* Animación Stagger */
            @keyframes p-reveal {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .stagger-enter {
                opacity: 0;
                animation: p-reveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }

            /* Botones Soft/Ghost de Auditoría */
            .btn-p-soft {
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
                border-width: 1.5px !important;
                font-weight: 700 !important;
                background: transparent !important;
            }
            .btn-soft-pdf { color: #dc2626 !important; border-color: #dc2626 !important; }
            .btn-soft-pdf:hover { background: #dc2626 !important; color: white !important; box-shadow: 0 8px 15px rgba(220, 38, 38, 0.25); }
            
            .btn-soft-excel { color: #15803d !important; border-color: #15803d !important; }
            .btn-soft-excel:hover { background: #15803d !important; color: white !important; box-shadow: 0 8px 15px rgba(21, 128, 61, 0.25); }

            /* Modal Preview PDF Premium */
            .p-modal-overlay {
                position: fixed; inset: 0;
                background: rgba(13, 20, 36, 0.8); backdrop-filter: blur(10px);
                z-index: 9999; display: none; align-items: center; justify-content: center;
                padding: 40px; transition: all 0.3s ease;
            }
            .p-modal-content {
                background: #F8FAFD; width: 100%; max-width: 1100px; height: 90vh;
                border-radius: 28px; display: flex; flex-direction: column; overflow: hidden;
                box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.3);
                transform: scale(0.95); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .p-modal-overlay.active { display: flex; }
            .p-modal-overlay.active .p-modal-content { transform: scale(1); }
            
            .p-modal-header {
                padding: 20px 32px; background: white; border-bottom: 1px solid #EDF2F7;
                display: flex; justify-content: space-between; align-items: center;
            }
            .p-modal-body { flex: 1; position: relative; background: #525659; }
            .p-modal-iframe { width: 100%; height: 100%; border: none; }
            
            .p-modal-close {
                width: 40px; height: 40px; border-radius: 12px; border: none;
                background: #F1F5F9; color: #475569; cursor: pointer;
                display: flex; align-items: center; justify-content: center; transition: all 0.2s;
            }
            .p-modal-close:hover { background: #dc2626; color: white; transform: rotate(90deg); }

            /* Grid Custom */
            .p-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 24px;
            }
            </style>

            <?php 
            // VISTA TOTAL (ANUAL): Muro de Graduados (Card Grid)
            $resumenTotal = [];
            if(!empty($registrosLista)) {
                foreach($registrosLista as $reg) {
                    $pid = $reg->pasante_id ?? 'P'.$reg->id;
                    if(!isset($resumenTotal[$pid])) {
                        $resumenTotal[$pid] = [
                            'nombre'         => trim(($reg->apellidos ?? '') . ', ' . ($reg->nombres ?? '')),
                            'nombres'        => $reg->nombres ?? '',
                            'apellidos'      => $reg->apellidos ?? '',
                            'cedula'         => $reg->cedula ?? '',
                            'departamento'   => $reg->departamento_nombre ?? 'General',
                            'avatar'         => $reg->avatar ?? 'default.png',
                            'institucion'    => $reg->institucion_nombre ?? '',
                            'periodo'        => $reg->periodo_nombre ?? '',
                            'estado_cuenta'  => $reg->estado_cuenta ?? 'activo',
                            'estado_pasantia'=> $reg->estado_pasantia ?? 'Sin Asignar',
                            'presentes'      => 0,
                            'faltas'         => 0,
                            'horas_meta'     => (int)($reg->horas_meta ?? $horasMetaPeriodo ?? 1440)
                        ];
                    }
                    $estado = strtolower($reg->estado ?? '');
                    if(strpos($estado, 'presente') !== false || strpos($estado, 'justificado') !== false) {
                        $resumenTotal[$pid]['presentes']++;
                    } elseif(strpos($estado, 'ausente') !== false) {
                        $resumenTotal[$pid]['faltas']++;
                    }
                }
            }
            
            // Navegación de Período Académico
            $periodosFiltro = $periodosFiltro ?? [];
            $periodoSeleccionado = $periodoSeleccionado ?? null;
            $periodoIdSel = $periodoIdSel ?? 0;
            
            $idxActual = -1;
            foreach ($periodosFiltro as $i => $p) {
                if ($p->id == $periodoIdSel) { $idxActual = $i; break; }
            }
            
            $btnAnt = '<span class="sw-nav-btn" style="opacity:0.3; cursor:not-allowed;"><i class="ti ti-chevron-left"></i></span>';
            $btnSig = '<span class="sw-nav-btn" style="opacity:0.3; cursor:not-allowed;"><i class="ti ti-chevron-right"></i></span>';
            
            if ($idxActual !== -1) {
                $tituloVista = $periodoSeleccionado->nombre;
                // Anterior = más viejo (índice mayor, pues están DESC)
                if (isset($periodosFiltro[$idxActual + 1])) {
                    $pAnt = $periodosFiltro[$idxActual + 1];
                    $btnAnt = '<a href="'.URLROOT.'/asistencias?vista=anual&periodo_id='.$pAnt->id.'" class="sw-nav-btn" title="'.htmlspecialchars($pAnt->nombre).'"><i class="ti ti-chevron-left"></i></a>';
                }
                // Siguiente = más nuevo (índice menor)
                if (isset($periodosFiltro[$idxActual - 1])) {
                    $pSig = $periodosFiltro[$idxActual - 1];
                    $btnSig = '<a href="'.URLROOT.'/asistencias?vista=anual&periodo_id='.$pSig->id.'" class="sw-nav-btn" title="'.htmlspecialchars($pSig->nombre).'"><i class="ti ti-chevron-right"></i></a>';
                }
            } else {
                $tituloVista = !empty($periodosFiltro) ? $periodosFiltro[0]->nombre : "Sin Períodos";
            }
            ?>
            <div class="muro-premium">
                <!-- Header / Navigation -->
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <h3 style="margin: 0 0 12px 0; color: var(--p-ink); font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; gap: 10px;">
                            <i class="ti ti-chart-dots" style="color: var(--p-blue); font-size: 1.6rem;"></i> 
                            Histórico Total <span style="color: var(--p-ink-4); font-weight: 300;">/</span> <?= htmlspecialchars($tituloVista) ?>
                        </h3>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <!-- Navegador de Período -->
                            <div class="sw-nav-pill">
                                <?= $btnAnt ?>
                                <span class="sw-nav-label"><?= htmlspecialchars($tituloVista) ?></span>
                                <?= $btnSig ?>
                            </div>
                            
                            <!-- Filtros por Chips -->
                            <div id="muroChipRow" style="display:inline-flex;align-items:center;gap:4px;
                                background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:30px;
                                padding:4px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                                <button id="chip-all" onclick="filterMuro('all',this)"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;
                                           border-radius:24px;border:none;cursor:pointer;font-size:.78rem;
                                           font-weight:700;font-family:inherit;transition:all .2s;
                                           background:linear-gradient(135deg,#2563eb,#3b82f6);
                                           color:white;box-shadow:0 2px 8px rgba(37,99,235,.35);">
                                    <i class="ti ti-layout-grid" style="font-size:.85rem;"></i> Todos
                                </button>
                                <button id="chip-activo" onclick="filterMuro('Activo',this)"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;
                                           border-radius:24px;border:none;cursor:pointer;font-size:.78rem;
                                           font-weight:700;font-family:inherit;transition:all .2s;
                                           background:transparent;color:#475569;">
                                    <i class="ti ti-activity" style="font-size:.85rem;"></i> Activos
                                </button>
                                <button id="chip-finalizado" onclick="filterMuro('Finalizado',this)"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;
                                           border-radius:24px;border:none;cursor:pointer;font-size:.78rem;
                                           font-weight:700;font-family:inherit;transition:all .2s;
                                           background:transparent;color:#475569;">
                                    <i class="ti ti-certificate" style="font-size:.85rem;"></i> Finalizados
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <button class="btn btn-p-soft btn-soft-pdf" onclick="openPreview('<?= URLROOT ?>/reportes/pdfNomina?download=0')" style="height: 42px; padding: 0 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="ti ti-file-type-pdf" style="font-size: 1.1rem;"></i> PDF Global
                        </button>
                        <button class="btn btn-p-soft btn-soft-excel" onclick="window.location.href='<?= URLROOT ?>/reportes/excelAnual'" style="height: 42px; padding: 0 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="ti ti-file-type-xls" style="font-size: 1.1rem;"></i> Excel Anual
                        </button>
                        <div style="position: relative; width: 300px;">
                            <i class="ti ti-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--p-blue); font-size: 1.2rem; z-index: 2;"></i>
                            <input type="text" id="buscadorTablaTotal" oninput="searchMuro()" placeholder="Buscar por nombre o CI..." 
                                   style="width: 100%; height: 42px; padding: 0 16px 0 48px; border: 1.5px solid var(--p-border); border-radius: 50px; background: white; color: var(--p-ink); font-weight: 600; font-size: 0.88rem; outline: none; transition: all 0.2s; box-shadow: var(--p-shadow);"
                                   onfocus="this.style.borderColor='var(--p-blue)'; this.style.boxShadow='0 0 0 4px var(--p-blue-dim)'" 
                                   onblur="this.style.borderColor='var(--p-border)'; this.style.boxShadow='var(--p-shadow)'">
                        </div>
                    </div>
                </div>
            
                <div class="p-grid" id="muroContainer">
                    <div id="muroNoResults" class="col-12 d-none stagger-enter"
                         style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;
                                justify-content:center;text-align:center;padding:72px 30px;
                                background:white;border-radius:22px;
                                border:2px dashed #e2e8f0;gap:16px;
                                margin:8px 0;">
                        <div style="width:90px;height:90px;border-radius:50%;
                                    background:linear-gradient(135deg,#dbeafe,#eff6ff);
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:2.8rem;color:#3b82f6;">
                            <i class="ti ti-filter-off"></i>
                        </div>
                        <h3 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;">
                            Sin resultados para este filtro
                        </h3>
                        <p style="color:#64748b;max-width:400px;line-height:1.7;margin:0;font-size:.9rem;">
                            No hay pasantes que coincidan con el filtro seleccionado.<br>
                            Prueba con <strong>Activos</strong>, <strong>Finalizados</strong> o regresa a <strong style="color:#2563eb;">Todos</strong>.
                        </p>
                        <button onclick="filterMuro('all', document.getElementById('chip-all'))"
                                style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;
                                       border-radius:50px;border:none;cursor:pointer;font-size:.85rem;
                                       font-weight:700;font-family:inherit;
                                       background:linear-gradient(135deg,#2563eb,#3b82f6);
                                       color:white;box-shadow:0 4px 14px rgba(37,99,235,.35);
                                       transition:all .2s;"
                                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(37,99,235,.4)'"
                                onmouseout="this.style.transform='';this.style.boxShadow='0 4px 14px rgba(37,99,235,.35)'">
                            <i class="ti ti-layout-grid"></i> Ver todos los pasantes
                        </button>
                    </div>

                    <?php if(empty($resumenTotal)): ?>
                        <div class="col-12 stagger-enter"
                             style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;
                                    justify-content:center;text-align:center;padding:72px 30px;
                                    background:white;border-radius:22px;
                                    border:2px dashed #e2e8f0;gap:16px;
                                    margin:8px 0;">
                            <div style="width:90px;height:90px;border-radius:50%;
                                        background:linear-gradient(135deg,#f1f5f9,#e2e8f0);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:2.8rem;color:#94a3b8;">
                                <i class="ti ti-calendar-x"></i>
                            </div>
                            <h3 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;">
                                Sin historial en este período
                            </h3>
                            <p style="color:#64748b;max-width:420px;line-height:1.7;margin:0;font-size:.9rem;">
                                No hay registros de asistencia para
                                <strong style="color:#334155;"><?= htmlspecialchars($tituloVista) ?></strong>.<br>
                                Usa el selector de período o cambia a otra vista.
                            </p>
                            <div style="display:flex;align-items:center;gap:8px;
                                        background:#f8fafc;border:1px solid #e2e8f0;
                                        border-radius:50px;padding:10px 22px;
                                        color:#64748b;font-weight:600;font-size:.83rem;">
                                <i class="ti ti-arrows-left-right" style="color:#94a3b8;"></i>
                                Navega con las flechas del período
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $muroDelay=0; foreach($resumenTotal as $pid => $rt):
                            $partsN = explode(',', $rt['nombre'] ?? '');
                            $apellido = trim($partsN[0] ?? 'A');
                            $nombreP  = trim($partsN[1] ?? 'A');
                            $iniciales = strtoupper(substr($nombreP, 0, 1) . substr($apellido, 0, 1));
                            if(strlen(trim($iniciales)) < 2) $iniciales = strtoupper(substr($rt['nombre'] ?? 'AA', 0, 2));

                            $horasMeta       = $rt['horas_meta'] ?? $horasMetaPeriodo ?? 1440;
                            $horasAcumuladas = min(($rt['presentes'] + ($rt['faltas'] ?? 0) + ($rt['justificados'] ?? 0)) * 8, $horasMeta);
                            $pctHoras        = $horasMeta > 0 ? min(100, round(($horasAcumuladas / $horasMeta) * 100)) : 0;

                            // [SGP-FIX-ANUAL-FINALIZADO-VS-INACTIVO] Antes "Inactivo"
                            // (estado_cuenta, si el usuario puede o no iniciar sesión)
                            // se evaluaba PRIMERO que "Finalizado" (estado_pasantia, si
                            // ya completó sus horas). Como es normal desactivar la cuenta
                            // de un pasante justo cuando termina su pasantía, casi todos
                            // los que llegaban a 100% quedaban etiquetados "Inactivo" en
                            // vez de "Finalizado" — por eso el chip "Finalizados" salía
                            // vacío aunque el KPI de arriba (que sí cuenta por horas,
                            // sin mirar estado_cuenta) mostrara varios. Ahora "Finalizado"
                            // tiene prioridad: si completó sus horas, se etiqueta así sin
                            // importar si la cuenta ya está desactivada. "Inactivo" queda
                            // para cuentas desactivadas que NO llegaron a completar (retiro,
                            // suspensión, etc.).
                            $esInactivo   = ($rt['estado_cuenta'] === 'inactivo');
                            $isFinalizado = ($rt['estado_pasantia'] === 'Finalizado');

                            if ($isFinalizado) {
                                $estadoBadge  = 'Finalizado';
                                $badgeIcon    = 'ti-circle-check';
                                $gaugeColor   = '#0ea5e9';
                                $statusBg     = 'rgba(14,165,233,0.1)';
                                $statusBorder = 'rgba(14,165,233,0.2)';
                            } elseif ($esInactivo) {
                                $estadoBadge  = 'Inactivo';
                                $badgeIcon    = 'ti-user-off';
                                $gaugeColor   = '#94a3b8';
                                $statusBg     = 'rgba(148,163,184,0.1)';
                                $statusBorder = 'rgba(148,163,184,0.2)';
                            } else {
                                $estadoBadge  = 'Activo';
                                $badgeIcon    = 'ti-circle-dot';
                                $gaugeColor   = '#059669';
                                $statusBg     = 'rgba(5,150,105,0.1)';
                                $statusBorder = 'rgba(5,150,105,0.2)';
                            }
                            $searchData = strtolower($rt['nombre'] . ' ' . $rt['cedula'] . ' ' . $estadoBadge);

                            // Avatar
                            $hasAvatar  = !empty($rt['avatar']) && $rt['avatar'] !== 'default.png';
                            $avatarUrl  = $hasAvatar ? (URLROOT . '/img/avatars/' . htmlspecialchars($rt['avatar'], ENT_QUOTES)) : '';

                            // Nombre para el carnet: "Nombres Apellidos"
                            $nombreReal = trim(($rt['nombres'] ?? '') . ' ' . ($rt['apellidos'] ?? ''));
                            if(empty($nombreReal)) $nombreReal = $rt['nombre'];

                            $institucion = htmlspecialchars($rt['institucion'] ?: '—', ENT_QUOTES);
                            $periodo     = htmlspecialchars($rt['periodo']     ?: '—', ENT_QUOTES);
                        ?>
                        <div class="muro-item stagger-enter"
                             data-search="<?= htmlspecialchars($searchData . ' ' . strtolower($rt['departamento']), ENT_QUOTES) ?>"
                             data-estado="<?= $estadoBadge ?>"
                             style="animation-delay: <?= $muroDelay ?>ms;">
                            <?php $muroDelay += 30; ?>
                            <div class="flip-wrapper">
                                <div class="flip-inner">

                                    <!-- ═══ FRENTE ═══ -->
                                    <div class="flip-front p-card">
                                        <!-- Estado / Faltas -->
                                        <div style="width:100%;display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
                                            <span class="p-badge" style="background:<?= $statusBg ?>;color:<?= $gaugeColor ?>;border:1px solid <?= $statusBorder ?>;">
                                                <i class="ti <?= $badgeIcon ?>"></i> <?= strtoupper($estadoBadge) ?>
                                            </span>
                                            <?php if($rt['faltas'] > 0): ?>
                                            <span class="p-badge" style="background:rgba(220,38,38,0.08);color:#dc2626;">
                                                <i class="ti ti-alert-triangle"></i> <?= $rt['faltas'] ?> faltas
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Persona -->
                                        <h5 class="p-name" style="font-weight:800;font-size:1.15rem;color:var(--p-ink);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;" title="<?= htmlspecialchars($rt['nombre'], ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($rt['nombre']) ?>
                                        </h5>
                                        <div class="p-dept" style="font-size:0.75rem;font-weight:700;color:var(--p-blue);letter-spacing:0.5px;text-transform:uppercase;margin-top:4px;">
                                            <?= htmlspecialchars($rt['departamento']) ?>
                                        </div>
                                        <div class="p-ci" style="font-size:0.8rem;color:var(--p-ink-3);font-family:'JetBrains Mono',monospace;margin-top:2px;">
                                            V-<?= htmlspecialchars($rt['cedula']) ?>
                                        </div>

                                        <!-- Gauge radial -->
                                        <div class="p-gauge-wrap">
                                            <svg class="p-gauge-svg" width="130" height="130" viewBox="0 0 130 130">
                                                <circle class="p-gauge-track" cx="65" cy="65" r="54"/>
                                                <circle class="p-gauge-fill" cx="65" cy="65" r="54"
                                                        stroke="<?= $gaugeColor ?>"
                                                        stroke-dashoffset="<?= 339.292 - (339.292 * $pctHoras) / 100 ?>"/>
                                            </svg>
                                            <div class="p-gauge-info">
                                                <span class="p-gauge-val"><?= number_format($horasAcumuladas, 0, ',', '.') ?></span>
                                                <span class="p-gauge-lbl">/ <?= number_format($horasMeta, 0, ',', '.') ?> hrs</span>
                                            </div>
                                        </div>

                                        <div class="p-pct" style="font-size:0.9rem;font-weight:800;color:<?= $gaugeColor ?>;margin-bottom:8px;">
                                            <?= $pctHoras ?>% COMPLETADO
                                        </div>

                                        <!-- Pill "Ver carnet" -->
                                        <div class="flip-hint-sep">
                                            <div class="flip-hint">
                                                <i class="ti ti-id-badge-2"></i> Ver carnet
                                            </div>
                                        </div>
                                    </div><!-- /.flip-front -->

                                    <!-- ═══ REVERSO: Carnet ═══ -->
                                    <div class="flip-back p-carnet">
                                        <!-- Cabecera -->
                                        <div class="pc-header">
                                            <img src="<?= URLROOT ?>/img/logo.png" class="pc-logo" alt="SGP">
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <span class="pc-sys-label">SGP · Pasante</span>
                                                <button class="pc-close-btn" onclick="event.stopPropagation();this.closest('.flip-inner').classList.remove('is-flipped')" title="Volver">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Foto -->
                                        <div class="pc-photo-wrap">
                                            <?php if($hasAvatar): ?>
                                                <img src="<?= $avatarUrl ?>" class="pc-photo-img" alt="Foto">
                                            <?php else: ?>
                                                <div class="pc-photo-init"><?= $iniciales ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Nombre y rol -->
                                        <div class="pc-fullname" title="<?= htmlspecialchars($nombreReal, ENT_QUOTES) ?>"><?= htmlspecialchars($nombreReal) ?></div>
                                        <div class="pc-role-badge">PASANTE</div>

                                        <!-- Datos del carnet -->
                                        <div class="pc-data">
                                            <div class="pc-row">
                                                <i class="ti ti-id pc-icon"></i>
                                                <span class="pc-val">V-<?= htmlspecialchars($rt['cedula']) ?></span>
                                            </div>
                                            <div class="pc-row">
                                                <i class="ti ti-building pc-icon"></i>
                                                <span class="pc-val" title="<?= $institucion ?>"><?= $institucion ?></span>
                                            </div>
                                            <div class="pc-row">
                                                <i class="ti ti-briefcase pc-icon"></i>
                                                <span class="pc-val"><?= htmlspecialchars($rt['departamento']) ?></span>
                                            </div>
                                            <div class="pc-row">
                                                <i class="ti ti-calendar pc-icon"></i>
                                                <span class="pc-val"><?= $periodo ?></span>
                                            </div>
                                        </div>

                                        <!-- Acciones -->
                                        <div class="pc-actions">
                                            <button onclick="event.stopPropagation();window.open('<?= URLROOT ?>/reportes/pdfIndividual?id=<?= $pid ?>&download=0','_blank')" class="pc-btn pc-btn-pdf">
                                                <i class="ti ti-file-type-pdf"></i> PDF
                                            </button>
                                            <button onclick="event.stopPropagation();window.location.href='<?= URLROOT ?>/reportes/exportarExcel?id=<?= $pid ?>'" class="pc-btn pc-btn-xls">
                                                <i class="ti ti-file-type-xls"></i> Excel
                                            </button>
                                        </div>
                                    </div><!-- /.flip-back -->

                                </div><!-- /.flip-inner -->
                            </div><!-- /.flip-wrapper -->
                        </div><!-- /.muro-item -->
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div><!-- /.muro-premium -->

            <!-- Modal de Previsualización PDF Premium -->
            <div id="pdfModal" class="p-modal-overlay">
                <div class="p-modal-content">
                    <div class="p-modal-header">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:#dc262611; color:#dc2626; display:flex; align-items:center; justify-content:center;">
                                <i class="ti ti-file-type-pdf" style="font-size:20px;"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; color:var(--p-ink); font-size:1.1rem;">Previsualización de Documento</h4>
                                <span style="font-size:0.75rem; color:var(--p-ink-3); font-weight:600;">SGP — Sistema de Gestión de Pasantes</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:12px;">
                            <button id="btnDescargarActual" class="btn" style="height:40px; padding:0 16px; border-radius:12px; background:#15803d; color:white; border:none; font-weight:700; display:flex; align-items:center; gap:8px; font-size:0.85rem;">
                                <i class="ti ti-download"></i> Descargar Ahora
                            </button>
                            <button class="p-modal-close" onclick="closePreview()">
                                <i class="ti ti-x" style="font-size:20px;"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-modal-body">
                        <iframe id="pdfFrame" class="p-modal-iframe" src="about:blank"></iframe>
                    </div>
                </div>
            </div>

            <style>
                /* Shared CSS from Weekly View needed for Annual nav pills if not present */
                .sw-nav-pill { display: flex; align-items: center; background: white; border: 1.5px solid #DDE2F0; border-radius: 50px; padding: 3px 4px; gap: 2px; box-shadow: 0 1px 4px rgba(13,20,36,.05); }
                .sw-nav-btn { background: transparent; border: none; color: #7480A0; padding: 6px 12px; border-radius: 50px; font-size: .8rem; font-weight: 600; cursor: pointer; transition: background .15s; display: flex; align-items: center; gap: 3px; }
                .sw-nav-btn:hover { background: #F2F5FC; color: #0D1424; }
                .sw-nav-label { font-size: .83rem; font-weight: 700; color: #0D1424; padding: 0 12px; white-space: nowrap; }
            </style>

            <script>
            // [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
            var muroActiveChip = 'all';
            var CHIP_STYLES = {
                all:        { bg:'linear-gradient(135deg,#2563eb,#3b82f6)', color:'white', shadow:'0 2px 8px rgba(37,99,235,.35)' },
                Activo:     { bg:'linear-gradient(135deg,#059669,#10b981)', color:'white', shadow:'0 2px 8px rgba(5,150,105,.35)'  },
                Finalizado: { bg:'linear-gradient(135deg,#7c3aed,#a78bfa)', color:'white', shadow:'0 2px 8px rgba(124,58,237,.35)' },
            };
            var CHIP_IDS = { all:'chip-all', Activo:'chip-activo', Finalizado:'chip-finalizado' };

            function applyChipStyles(activeType) {
                ['all','Activo','Finalizado'].forEach(t => {
                    const el = document.getElementById(CHIP_IDS[t]);
                    if (!el) return;
                    if (t === activeType) {
                        const s = CHIP_STYLES[t];
                        el.style.background  = s.bg;
                        el.style.color       = s.color;
                        el.style.boxShadow   = s.shadow;
                    } else {
                        el.style.background = 'transparent';
                        el.style.color      = '#475569';
                        el.style.boxShadow  = 'none';
                    }
                });
            }

            function filterMuro(type, btn) {
                // Toggle: clic en chip ya activo (no "all") → vuelve a "todos"
                if (muroActiveChip === type && type !== 'all') {
                    muroActiveChip = 'all';
                } else {
                    muroActiveChip = type;
                }
                applyChipStyles(muroActiveChip);
                searchMuro();
            }

            function searchMuro() {
                const q = document.getElementById('buscadorTablaTotal').value.toLowerCase();
                const items = document.querySelectorAll('.muro-item');
                let found = 0;

                items.forEach(item => {
                    const searchData = item.getAttribute('data-search').toLowerCase();
                    const estado = item.getAttribute('data-estado').toLowerCase();
                    
                    const matchQ = !q || searchData.includes(q);
                    const matchChip = muroActiveChip === 'all' || estado === muroActiveChip.toLowerCase();

                    if (matchQ && matchChip) {
                        item.classList.remove('d-none');
                        found++;
                    } else {
                        item.classList.add('d-none');
                    }
                });

                const noRes = document.getElementById('muroNoResults');
                if (found === 0) noRes.classList.remove('d-none');
                else noRes.classList.add('d-none');
            }

            // Click en cualquier parte del frente → voltear al carnet
            document.querySelectorAll('.flip-front').forEach(front => {
                front.addEventListener('click', function() {
                    this.closest('.flip-inner').classList.add('is-flipped');
                });
            });
            // Click en el reverso (fuera de los botones de acción) → volver
            document.querySelectorAll('.flip-back').forEach(back => {
                back.addEventListener('click', function() {
                    this.closest('.flip-inner').classList.remove('is-flipped');
                });
            });

            function doExportAnual() {
                let csv = 'Nombre,Cédula,Departamento,Horas Acumuladas,Meta,%,Estado\n';
                const items = document.querySelectorAll('.muro-item:not(.d-none)');
                items.forEach(item => {
                    const name = item.querySelector('.p-name').innerText.trim();
                    const ci = item.querySelector('.p-ci').innerText.trim().replace('V-', '');
                    const dept = item.querySelector('.p-dept').innerText.trim();
                    const accumulated = item.querySelector('.p-gauge-val').innerText.trim().replace(/\./g, '');
                    const meta = item.querySelector('.p-gauge-lbl').innerText.replace('/ ', '').replace(' hrs', '').trim().replace(/\./g, '');
                    const pct = item.querySelector('.p-pct').innerText.replace('% COMPLETADO', '').trim();
                    const state = item.getAttribute('data-estado').toUpperCase();
                    csv += `"${name}","${ci}","${dept}",${accumulated},${meta},${pct}%,${state}\n`;
                });
                if(items.length === 0) {
                    alert('No hay datos para exportar');
                    return;
                }
                const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte_anual_pasantias_${new Date().getFullYear()}.csv`;
                a.click();
            }

            // Controlador de Previsualización PDF
            function openPreview(url) {
                const modal = document.getElementById('pdfModal');
                const iframe = document.getElementById('pdfFrame');
                const btnDownload = document.getElementById('btnDescargarActual');
                
                iframe.src = url;
                // El link de descarga es el mismo pero con download=1
                const downloadUrl = url.replace('download=0', 'download=1');
                btnDownload.onclick = () => window.location.href = downloadUrl;
                
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Evitar scroll de fondo
            }

            function closePreview() {
                const modal = document.getElementById('pdfModal');
                const iframe = document.getElementById('pdfFrame');
                
                modal.classList.remove('active');
                iframe.src = 'about:blank';
                document.body.style.overflow = 'auto';
            }

            // Cerrar al hacer clic fuera del contenido
            document.getElementById('pdfModal').addEventListener('click', (e) => {
                if (e.target.id === 'pdfModal') closePreview();
            });
            </script>
        <?php endif; ?>
    </div>
</div>

<!-- ===== ESTILOS DEL MODAL PREMIUM ===== -->
<style>
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,0.7); backdrop-filter: blur(6px);
    z-index: 9999; align-items: center; justify-content: center;
    animation: fadeIn 0.2s ease;
}
.modal-overlay.active { display: flex; }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes slideUp { from { transform:translateY(24px);opacity:0; } to { transform:translateY(0);opacity:1; } }

.modal-box {
    background: white; border-radius: 24px; width: 90%; max-width: 660px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 32px 80px rgba(15,23,42,0.3); animation: slideUp 0.3s ease;
}

.modal-head {
    background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%); padding: 28px 32px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; color: white;
}
.modal-head h2 { font-size:1.3rem; font-weight:700; margin:0; color:white !important; }
.modal-head p  { font-size:0.85rem; margin:4px 0 0; color:rgba(255,255,255,0.8) !important; }
.modal-head * { color: white !important; }

.btn-close-modal {
    background: rgba(255,255,255,0.2); border: none; color: white !important; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; flex-shrink: 0;
}
.btn-close-modal:hover { background: rgba(255,255,255,0.35); }
.btn-close-modal i { color: white !important; }

.modal-body {
    padding: 28px 32px; overflow-y: auto; flex: 1; min-height: 320px;
}

.form-group { margin-bottom: 20px; }
.form-label {
    display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;
}
.form-input {
    width:100%; padding:12px 16px; border:2px solid #e5e7eb; border-radius:12px; font-size:0.95rem; color:#1e293b; transition: border-color 0.2s, box-shadow 0.2s; box-sizing:border-box; background:#fafafa;
}
.form-input:focus {
    outline:none; border-color:#2563eb; box-shadow:0 0 0 4px rgba(79,70,229,0.1); background:white;
}

.btn-cancel {
    padding: 12px; border: 2px solid #e2e8f0; border-radius: 14px; background: #f1f5f9; color: #475569; font-weight: 700; cursor: pointer; font-size: 0.85rem; font-family: inherit; transition: background 0.2s;
}
.btn-cancel:hover { background: #e2e8f0; }

.btn-save {
    padding: 12px; background: linear-gradient(135deg, #172554, #2563eb); color: white; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: transform 0.2s, box-shadow 0.2s;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4); }
</style>

<!-- ===== MODAL REGISTRO MANUAL ===== -->
<div id="modal-manual" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <h2><i class="ti ti-calendar-plus" style="margin-right:8px"></i>Registro Manual</h2>
                <p>Justificar ausencia o registrar asistencia</p>
            </div>
            <button class="btn-close-modal" onclick="cerrarModal()"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
            <form id="form-manual" onsubmit="enviarManual(event)">
                <input type="hidden" name="fecha" value="<?= $hoy ?>">

                <!-- Custom Combo-Box HTML para Pasante -->
                <div class="form-group" style="position: relative;">
                    <label class="form-label" style="display:flex; align-items:center; gap:6px;"><i class="ti ti-user" style="font-size:1.1rem;"></i> Pasante *</label>
                    
                    <!-- Input visible de búsqueda -->
                    <div style="position: relative;">
                        <i class="ti ti-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; color: #94a3b8; pointer-events: none;"></i>
                        <input type="text" class="form-input" id="buscadorPasante" placeholder="Buscar por nombre..." style="padding-left: 44px; padding-right: 40px;" autocomplete="off">
                        
                        <!-- Botón para limpiar selección -->
                        <button type="button" id="btnLimpiarPasante" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: none; background: #e2e8f0; border: none; width: 24px; height: 24px; border-radius: 50%; color: #64748b; cursor: pointer; align-items: center; justify-content: center; transition: all 0.2s;">
                            <i class="ti ti-x" style="font-size: 0.8rem;"></i>
                        </button>
                    </div>

                    <!-- Input oculto para el formulario real -->
                    <input type="hidden" name="pasante_id" id="manual-pasante-id" required>

                    <!-- Dropdown de resultados (oculto por defecto) -->
                    <div id="resultadosPasante" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 250px; overflow-y: auto; z-index: 100;">
                        <!-- Resultados inyectados por JS -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex; align-items:center; gap:6px;"><i class="ti ti-activity" style="font-size:1.1rem;"></i> Estado *</label>
                    <select id="manual-estado" name="estado" required class="form-input" style="width: 100%; cursor: pointer;" onchange="toggleMotivo(this.value)">
                        <option value="Presente">✅ Presente</option>
                        <option value="Ausente">❌ Ausente</option>
                        <option value="Justificado">📄 Justificado</option>
                    </select>
                </div>

                <div class="form-group" id="div-motivo" style="display:none;">
                    <label class="form-label" style="display:flex; align-items:center; gap:6px;"><i class="ti ti-file-description" style="font-size:1.1rem;"></i> Motivo de justificación *</label>
                    <textarea id="manual-motivo" name="motivo_justificacion" rows="3" class="form-input" placeholder="Describe el motivo de la justificación..." style="width:100%; resize:vertical;"></textarea>
                </div>

                <!-- ═══ UPLOAD DE EVIDENCIA (récipe médico / PDF) ═══ -->
                <div class="form-group" id="div-evidencia" style="display:none;">
                    <label class="form-label" style="display:flex; align-items:center; gap:6px;">
                        <i class="ti ti-paperclip" style="font-size:1.1rem;"></i>
                        Evidencia / Récipe médico
                        <span style="font-size:.72rem; font-weight:500; color:#94a3b8; text-transform:none; letter-spacing:0;">(opcional)</span>
                    </label>
                    <!-- Drop Zone -->
                    <div id="dropzone-evidencia"
                         onclick="document.getElementById('input-evidencia').click()"
                         ondragover="event.preventDefault(); this.classList.add('dz-hover')"
                         ondragleave="this.classList.remove('dz-hover')"
                         ondrop="handleDrop(event)"
                         style="border: 2px dashed #bfdbfe; border-radius: 14px; padding: 20px 16px; text-align: center; cursor: pointer;
                                background: #f0f9ff; transition: all 0.2s; position: relative;">
                        <i class="ti ti-cloud-upload" style="font-size: 2rem; color: #93c5fd; display: block; margin-bottom: 6px;"></i>
                        <p style="margin:0; font-size:.82rem; color:#64748b; font-weight:600;">Arrastra aquí o <span style="color:#2563eb;">selecciona un archivo</span></p>
                        <p style="margin:4px 0 0; font-size:.72rem; color:#94a3b8;">JPG, PNG, PDF · Máx. 5 MB</p>
                        <!-- Preview -->
                        <div id="dz-preview" style="display:none; margin-top:10px; align-items:center; gap:10px; background:white;
                                                    border-radius:10px; padding:8px 12px; border:1px solid #bfdbfe;">
                            <i id="dz-file-icon" class="ti ti-file-check" style="font-size:1.4rem; color:#2563eb;"></i>
                            <div style="text-align:left; flex:1; min-width:0;">
                                <div id="dz-file-name" style="font-size:.8rem; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
                                <div id="dz-file-size" style="font-size:.68rem; color:#94a3b8;"></div>
                            </div>
                            <button type="button" onclick="clearEvidencia(event)" style="background:#fee2e2; border:none; width:24px; height:24px; border-radius:50%; color:#dc2626; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="ti ti-x" style="font-size:.72rem;"></i>
                            </button>
                        </div>
                    </div>
                    <input type="file" id="input-evidencia" name="evidencia" accept="image/*,.pdf"
                           style="display:none;" onchange="showEvidenciaPreview(this.files[0])">
                </div>

                <style>
                #dropzone-evidencia.dz-hover {
                    border-color: #2563eb;
                    background: #dbeafe;
                    transform: scale(1.01);
                }
                </style>

                <div style="display:flex; gap:12px; margin-top:24px;">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()" style="flex:1;">Cancelar</button>
                    <button type="submit" class="btn-save" id="btn-submit-manual" style="flex:1;"><i class="ti ti-device-floppy"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL DETALLE DE ASISTENCIA (Premium SGP) ===== -->
<div id="modal-detalle" class="sgp-modal-overlay" onclick="if(event.target===this)cerrarModalDetalle()">
    <div class="sgp-modal" style="max-width:480px;">
        <div class="sgp-modal-header">
            <h3><i class="ti ti-info-circle" style="margin-right:8px"></i>Detalle de Asistencia</h3>
            <p>Información completa del registro</p>
            <button class="sgp-modal-close" onclick="cerrarModalDetalle()"><i class="ti ti-x"></i></button>
        </div>
        <div class="sgp-modal-body" style="padding: 24px;">
            <div style="text-align:center; margin-bottom:20px;">
                <div id="detalle-avatar" style="width:72px; height:72px; background:linear-gradient(135deg,#162660,#2563eb); border-radius:18px; color:white; font-size:1.6rem; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; box-shadow:0 8px 20px rgba(37,99,235,0.25);"></div>
                <h4 id="detalle-nombre" style="margin:0; font-size:1.2rem; color:#1e293b; font-weight:700;"></h4>
                <p id="detalle-cedula" style="margin:4px 0 0; color:#64748b; font-size:0.9rem;"></p>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <span style="display:block; font-size:0.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:4px;">Departamento</span>
                    <strong id="detalle-depto" style="color:#1e293b; font-size:0.85rem;"></strong>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <span style="display:block; font-size:0.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:4px;">Hora de Registro</span>
                    <strong id="detalle-hora" style="color:#1e293b; font-size:0.85rem;"></strong>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <span style="display:block; font-size:0.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:4px;">Método</span>
                    <strong id="detalle-metodo" style="color:#1e293b; font-size:0.85rem;"></strong>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <span style="display:block; font-size:0.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:4px;">Estado</span>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <strong id="detalle-estado" style="font-size:0.85rem;"></strong>
                        <button id="btnEditarEstado" onclick="abrirEditarEstado()" title="Editar estado" style="background:#eff6ff; border:none; border-radius:8px; width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; color:#2563eb; transition:background 0.2s; flex-shrink:0;">
                            <i class="ti ti-pencil" style="font-size:0.85rem;"></i>
                        </button>
                    </div>
                    <!-- Mini-form edición de estado -->
                    <div id="panelEditarEstado" style="display:none; margin-top:10px; background:#f0f9ff; border:1px solid #bfdbfe; border-radius:10px; padding:10px;">
                        <select id="selectNuevoEstado" class="no-choices" style="width:100%; padding:7px 10px; border:1.5px solid #bfdbfe; border-radius:8px; font-size:0.85rem; font-weight:600; color:#1e293b; margin-bottom:8px; outline:none;" onchange="toggleMotivoDetalle(this.value)">
                            <option value="Presente">✅ Presente</option>
                            <option value="Ausente">❌ Ausente</option>
                            <option value="Justificado">📄 Justificado</option>
                        </select>
                        <textarea id="motivoNuevoEstado" placeholder="Motivo de justificación..." rows="2" style="display:none; width:100%; padding:7px 10px; border:1.5px solid #bfdbfe; border-radius:8px; font-size:0.85rem; color:#1e293b; resize:none; box-sizing:border-box; margin-bottom:8px; outline:none;"></textarea>
                        <div style="display:flex; gap:6px;">
                            <button onclick="guardarNuevoEstado()" style="flex:1; padding:7px; background:linear-gradient(135deg,#172554,#2563eb); color:white; border:none; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer;"><i class="ti ti-device-floppy"></i> Guardar</button>
                            <button onclick="cerrarEditarEstado()" style="flex:1; padding:7px; background:#f1f5f9; color:#64748b; border:none; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer;">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="detalle-motivo-container" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:16px; margin-bottom:20px;">
                <span style="display:block; font-size:0.75rem; font-weight:700; color:#d97706; text-transform:uppercase; margin-bottom:6px;"><i class="ti ti-file-description"></i> Motivo de justificación</span>
                <p id="detalle-motivo" style="margin:0; color:#92400e; font-size:0.9rem; line-height:1.5;"></p>
            </div>
        </div>
        <div style="padding:0 24px 24px; display:flex; gap:12px;">
             <a id="btnModalAlmanaque" href="#" style="width:100%; padding:14px; background:#f3e8ff; color:#7c3aed; font-weight:700; border:none; border-radius:12px; cursor:pointer; font-size:0.95rem; font-family:inherit; transition:background 0.2s; text-decoration:none; display:none; align-items:center; justify-content:center; gap:8px;">
                 <i class="ti ti-calendar-stats"></i> Almanaque
             </a>
             <button onclick="cerrarModalDetalle()" style="width:100%; padding:14px; background:#f1f5f9; color:#475569; font-weight:700; border:none; border-radius:12px; cursor:pointer; font-size:0.95rem; font-family:inherit; transition:background 0.2s;">Cerrar Detalle</button>
        </div>
    </div>
</div>

<!-- Script editor de estado en modal detalle -->
<script>
// [SGP-FIX-PJAX-REDECL] var en vez de let: este script se re-inyecta por
// PJAX sin reload completo; let/const de nivel superior revientan con
// "already declared" en la segunda visita a esta página.
var _detalleRegistroId = null;
var _detallePasanteId  = null;
var _detalleFecha      = null;

function abrirEditarEstado() {
    const panel = document.getElementById('panelEditarEstado');
    const sel   = document.getElementById('selectNuevoEstado');
    const estadoActual = document.getElementById('detalle-estado').dataset.rawEstado || 'Presente';
    sel.value = estadoActual;
    toggleMotivoDetalle(estadoActual);
    panel.style.display = 'block';
}
function cerrarEditarEstado() {
    document.getElementById('panelEditarEstado').style.display = 'none';
    document.getElementById('motivoNuevoEstado').value = '';
}
function toggleMotivoDetalle(val) {
    const t = document.getElementById('motivoNuevoEstado');
    t.style.display = val === 'Justificado' ? 'block' : 'none';
    if (val !== 'Justificado') t.value = '';
}
async function guardarNuevoEstado() {
    const nuevoEstado = document.getElementById('selectNuevoEstado').value;
    const motivo      = document.getElementById('motivoNuevoEstado').value.trim();
    if (nuevoEstado === 'Justificado' && !motivo) { alert('El motivo de justificación es obligatorio.'); return; }
    const fd = new FormData();
    fd.append('pasante_id', _detallePasanteId);
    fd.append('fecha',      _detalleFecha);
    fd.append('estado',     nuevoEstado);
    fd.append('motivo_justificacion', motivo);
    if (_detalleRegistroId) fd.append('registro_id', _detalleRegistroId);
    const btn = document.querySelector('#panelEditarEstado button');
    btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader-2"></i> Guardando...';
    try {
        const res  = await fetch(`${URLROOT}/asistencias/registro_manual`, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
        const data = await res.json();
        if (data.success) {
            cerrarModalDetalle();
            Swal.fire({
                icon: 'success',
                title: 'Estado actualizado',
                text: 'El registro fue actualizado correctamente.',
                confirmButtonColor: '#162660',
                timer: 1800,
                showConfirmButton: false
            }).then(() => location.reload());
        } else { alert('❌ ' + (data.message || 'Error al guardar.')); }
    } catch(e) { alert('Error de red. Intenta de nuevo.'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar'; }
}
</script>

<!-- ===== ESTILOS (herencia diseño backup) ===== -->

<style>
.btn-banner-primary {
    background: white;
    color: #162660;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.btn-banner-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

.btn-banner-secondary {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1.5px solid rgba(255,255,255,0.3);
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.btn-banner-secondary:hover { background: rgba(255,255,255,0.25); }

/* ── Filter Pills (Inside Banner Glass) ── */
.pill-filter {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 10px;
    font-size: 0.82rem; font-weight: 600;
    color: rgba(255,255,255,0.75);
    background: transparent;
    border: none;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    white-space: nowrap;
}
.pill-filter:hover {
    color: white;
    background: rgba(255,255,255,0.12);
}
.pill-active {
    background: white !important;
    color: #172554 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    font-weight: 700;
}
.pill-active:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>

<!-- ===== ESTILOS EXCLUSIVOS: MODAL CONSULTA RÁPIDA ===== -->
<style>
    /* ── Filtros del motor frontend ── */
    .sgp-filtro-select {
        padding: 6px 10px; border: 1.5px solid #e2e8f0; border-radius: 8px;
        font-size: 0.78rem; font-weight: 600; color: #475569; background: white;
        outline: none; cursor: pointer; transition: border-color 0.2s;
        width: 100%;
    }
    .sgp-filtro-select:focus { border-color: #3b82f6; }

    /* ── Overrides para que el modal de auditoría tenga altura correcta ── */
    #modalConsultaRapida .sgp-modal {
        max-width: 580px;
        height: auto;
    }
    #modalConsultaRapida .sgp-modal-body {
        overflow-y: visible;
        max-height: unset;
    }

    /* ── Smart Scroll Moderno ── */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.6);
    }

    /* ── Cápsula de perfil ── */
    .aud-profile-capsule {
        display: flex;
        align-items: center;
        gap: 14px;
        background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        border: 1px solid #dbeafe;
        border-radius: 18px;
        padding: 14px 16px;
        margin-bottom: 16px;
    }
    .aud-avatar {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.3rem;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(16,185,129,0.35);
    }

    /* ── KPI cards ── */
    .aud-kpi-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-bottom: 14px;
    }
    .aud-kpi-card {
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        border: 1px solid transparent;
    }
    .aud-kpi-card .kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
    .aud-kpi-card .kpi-lbl { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; opacity: 0.75; }
    .aud-kpi-presente  { background: #ecfdf5; border-color: #a7f3d0; }
    .aud-kpi-ausente   { background: #fef2f2; border-color: #fecaca; }
    .aud-kpi-justif    { background: #fffbeb; border-color: #fde68a; }

    /* ── Panel radial + KPIs horizontales ── */
    .aud-stats-row {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 10px;
        margin-bottom: 14px;
        align-items: center;
    }
    .aud-radial-box {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    /* ── Timeline item ── */
    .aud-timeline-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 10px;
        margin-bottom: 6px;
        transition: box-shadow 0.2s;
    }
    .aud-timeline-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .aud-timeline-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-right: 10px;
    }

    /* ── Botones del footer ── */
    .aud-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
        text-decoration: none;
    }
    .aud-btn-primary {
        background: linear-gradient(135deg, #172554, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    .aud-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(37,99,235,0.4); }
    .aud-btn-ghost {
        background: white;
        color: #2563eb;
        border-color: #bfdbfe;
    }
    .aud-btn-ghost:hover { background: #eff6ff; }
    .aud-btn-close {
        background: #f1f5f9;
        color: #64748b;
        border-color: #e2e8f0;
    }
    .aud-btn-close:hover { background: #e2e8f0; }

    /* ── Responsividad Móvil (< 480px) ── */
    @media (max-width: 480px) {
        .aud-profile-capsule {
            flex-direction: column;
            text-align: center;
        }
        .aud-kpi-grid {
            grid-template-columns: 1fr;
        }
        .aud-stats-row {
            grid-template-columns: 1fr;
            justify-items: center;
        }
        .aud-kpi-card {
            flex-direction: row !important;
            justify-content: space-between !important;
            width: 100%;
        }
    }
</style>

<!-- ===== MODAL: AUDITORÍA / CONSULTA RÁPIDA DE PASANTE ===== -->
<div id="modalConsultaRapida" class="sgp-modal-overlay" onclick="if(event.target===this)cerrarModalConsulta()">
    <div id="modalConsultaContent" class="sgp-modal" style="transition: all 0.3s ease-in-out;">

        <!-- ── CABECERA AZUL ── -->
        <div class="sgp-modal-header">
            <div style="display:flex; align-items:center; gap:12px; position: relative; z-index: 1;">
                <div style="background:rgba(255,255,255,0.18); border-radius:10px; padding:8px; display:flex;">
                    <i class="ti ti-user-search" style="font-size:1.5rem; color:white;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.15rem; color:#fff;">Consulta Rápida</h3>
                    <p style="margin:3px 0 0; font-size:0.78rem; color:rgba(255,255,255,0.7);">Buscar por nombre o cédula del pasante</p>
                </div>
            </div>
            <button class="sgp-modal-close" onclick="cerrarModalConsulta()">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- ── CUERPO EXPANDIBLE ── -->
        <div class="sgp-modal-body" style="padding: 24px; overflow-y: auto !important; max-height: 60vh !important;">
            
            <!-- BUSCADOR -->
            <div class="sgp-search-wrapper" style="margin-bottom: 8px;">
                <i class="ti ti-search sgp-search-icon"></i>
                <input
                    type="text"
                    id="inputBuscarPasanteAJAX"
                    class="sgp-search-input"
                    placeholder="Buscar por nombre o cédula..."
                    autocomplete="off">
                <div id="sgpAudLoading" class="sgp-search-loading"></div>
            </div>
            
            <!-- Lista de sugerencias -->
            <div id="listaSugerencias" class="sgp-results-list" style="display:none; transition: all 0.3s ease-in-out;">
            </div>

            <!-- Estado cero (Minimalista) -->
            <div id="estadoCeroBusqueda" style="padding: 20px 0; color: #94a3b8; text-align: center;">
                <i class="ti ti-users-group" style="font-size: 2.2rem; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                <p style="font-size: 0.85rem; margin: 0; font-weight: 500;">Escriba al menos 2 caracteres para buscar</p>
            </div>

            <!-- Zona de resultados (oculta hasta búsqueda) -->
            <div id="zonaResultadosPasante" style="display:none;">

                <!-- CÁPSULA DE PERFIL -->
                <div class="aud-profile-capsule">
                    <div id="avatarPasante" class="aud-avatar" style="background:#10b981;">P</div>
                    <div style="flex:1; min-width:0;">
                        <h4 id="nombrePasante" style="margin:0; font-size:1rem; color:#0f172a; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</h4>
                        <p id="deptoPasante" style="margin:3px 0 0; font-size:0.78rem; color:#64748b; display:flex; align-items:center; gap:4px;">
                            <i class="ti ti-building"></i> —
                        </p>
                    </div>
                    <div style="background:#eff6ff; color:#2563eb; padding:4px 12px; border-radius:20px; font-size:0.7rem; font-weight:800; white-space:nowrap; text-transform:uppercase; letter-spacing:0.5px;">
                        Pasante
                    </div>
                </div>

                <!-- FICHA DE DATOS RÁPIDOS -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:12px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px;">
                        <div style="font-size:0.63rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;"><i class="ti ti-id-badge"></i> Cédula</div>
                        <div id="fichaCedula" style="font-size:0.82rem; font-weight:700; color:#0f172a; font-family:'JetBrains Mono',monospace;">—</div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px;">
                        <div style="font-size:0.63rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;"><i class="ti ti-phone"></i> Teléfono</div>
                        <div id="fichaTelefono" style="font-size:0.82rem; font-weight:700; color:#0f172a;">—</div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px; grid-column:span 2;">
                        <div style="font-size:0.63rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;"><i class="ti ti-school"></i> Institución de Procedencia</div>
                        <div id="fichaInstitucion" style="font-size:0.82rem; font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">—</div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px; grid-column:span 2;">
                        <div style="font-size:0.63rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;"><i class="ti ti-user-check"></i> Tutor Asignado</div>
                        <div id="fichaTutor" style="font-size:0.82rem; font-weight:700; color:#0f172a;">—</div>
                    </div>
                </div>

                <!-- FILA: GRÁFICO RADIAL + KPIs -->
                <div class="aud-stats-row">
                    <!-- Gráfico Radial de Progreso -->
                    <div class="aud-radial-box">
                        <div id="graficoRadialTiempo" style="width:100px; height:100px;"></div>
                        <span style="font-size:0.63rem; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-top:-8px;">Pasantía</span>
                    </div>

                    <!-- KPIs verticales -->
                    <div class="aud-kpi-grid" style="grid-template-columns:1fr;">
                        <!-- Presente -->
                        <div class="aud-kpi-card aud-kpi-presente" style="flex-direction:row; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="ti ti-circle-check" style="color:#059669; font-size:1.2rem;"></i>
                                <span class="kpi-lbl" style="color:#059669;">Presentes</span>
                            </div>
                            <span id="kpiPresentes" class="kpi-val" style="color:#059669;">0</span>
                        </div>
                        <!-- Ausente -->
                        <div class="aud-kpi-card aud-kpi-ausente" style="flex-direction:row; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="ti ti-circle-minus" style="color:#dc2626; font-size:1.2rem;"></i>
                                <span class="kpi-lbl" style="color:#dc2626;">Ausentes</span>
                            </div>
                            <span id="kpiAusentes" class="kpi-val" style="color:#dc2626;">0</span>
                        </div>
                        <!-- Justificado -->
                        <div class="aud-kpi-card aud-kpi-justif" style="flex-direction:row; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="ti ti-file-certificate" style="color:#d97706; font-size:1.2rem;"></i>
                                <span class="kpi-lbl" style="color:#d97706;">Justificados</span>
                            </div>
                            <span id="kpiJustificados" class="kpi-val" style="color:#d97706;">0</span>
                        </div>
                    </div>
                </div>

                <!-- FILTROS DEL HISTORIAL -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px 14px; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-size:0.72rem; font-weight:800; color:#475569; display:flex; align-items:center; gap:5px;">
                            <i class="ti ti-adjustments-horizontal"></i> FILTRAR HISTORIAL
                        </span>
                        <button onclick="limpiarFiltrosAuditoria()" style="background:none; border:none; color:#3b82f6; font-size:0.72rem; font-weight:700; cursor:pointer; padding:0;">
                            <i class="ti ti-x" style="font-size:0.65rem;"></i> Limpiar
                        </button>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:6px;">
                        <select id="filtroMesAuditoria"      class="sgp-filtro-select" onchange="ejecutarFiltrosAuditoria()">
                            <option value="">Mes</option>
                            <option value="01">Ene</option><option value="02">Feb</option><option value="03">Mar</option>
                            <option value="04">Abr</option><option value="05">May</option><option value="06">Jun</option>
                            <option value="07">Jul</option><option value="08">Ago</option><option value="09">Sep</option>
                            <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dic</option>
                        </select>
                        <select id="filtroEstadoAuditoria"   class="sgp-filtro-select" onchange="ejecutarFiltrosAuditoria()">
                            <option value="">Estado</option>
                            <option value="presente">Presente</option>
                            <option value="ausente">Ausente</option>
                            <option value="justificado">Justificado</option>
                        </select>
                        <select id="filtroMetodoAuditoria"   class="sgp-filtro-select" onchange="ejecutarFiltrosAuditoria()">
                            <option value="">Método</option>
                            <option value="Manual">Manual</option>
                            <option value="Escritorio">PC</option>
                        </select>
                    </div>
                </div>

                <!-- TIMELINE -->
                <div style="font-size:0.72rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <i class="ti ti-history"></i> Registros
                </div>
                <div id="timelineAsistencias" style="max-height: 160px !important; overflow-y: auto !important; padding-right: 10px; display: block;">
                    <!-- Cargado por JS -->
                </div>

            </div><!-- /zonaResultadosPasante -->
        </div><!-- /sgp-modal-body -->

        <!-- ── FOOTER CON ACCIONES ── -->
        <div id="audFooterAcciones" style="padding:14px 24px; border-top:1px solid #f1f5f9; background:#f8fafc;
                    display:none; align-items:center; justify-content:center; flex-shrink:0;">
            <!-- Botones contextuales (ocultos hasta que se seleccione pasante) -->
            <div style="display:flex; gap:8px; width: 100%;">
                <a href="#" id="btnAudAlmanaque" class="aud-btn" style="display:none; flex: 1; justify-content: center; background:#f3e8ff; color:#7c3aed; border:1px solid #e9d5ff; font-weight: 700; text-decoration: none;">
                    <i class="ti ti-calendar-stats"></i> Almanaque
                </a>
                <a href="#" id="btnExportarExcel" target="_self" class="aud-btn aud-btn-ghost" style="display:none; flex: 1; justify-content: center;">
                    <i class="ti ti-file-spreadsheet"></i> Exportar Excel
                </a>
                <button id="btnExportarPDF" class="aud-btn aud-btn-primary" style="display:none; flex: 1; justify-content: center;">
                    <i class="ti ti-file-type-pdf"></i> Generar PDF
                </button>
            </div>
        </div>

    </div><!-- /sgp-modal -->
</div><!-- /sgp-modal-overlay -->

<!-- ===== JAVASCRIPT ===== -->
<script>
function abrirModalConsulta() {
    const modal = document.getElementById('modalConsultaRapida');
    if (!modal) {
        console.error('Error: No se encontró el modal de auditoría.');
        return;
    }
    
    // Resetear UI
    const input = document.getElementById('inputBuscarPasanteAJAX');
    const sugerencias = document.getElementById('listaSugerencias');
    const estadoCero = document.getElementById('estadoCeroBusqueda');
    const zonaResultados = document.getElementById('zonaResultadosPasante');
    const footer = document.getElementById('audFooterAcciones');
    
    if (input) input.value = '';
    if (sugerencias) sugerencias.style.display = 'none';
    if (estadoCero) estadoCero.style.display = 'block';
    if (zonaResultados) zonaResultados.style.display = 'none';
    if (footer) footer.style.display = 'none';

    modal.classList.add('active');
    if (input) setTimeout(() => input.focus(), 300);
}

function cerrarModalConsulta() {
    const modal = document.getElementById('modalConsultaRapida');
    if (!modal) return;
    modal.classList.remove('active');
    setTimeout(() => {
        // Reset the form
        const inputBuscar = document.getElementById('inputBuscarPasanteAJAX');
        if (inputBuscar) { inputBuscar.value = ''; inputBuscar.placeholder = 'Buscar por nombre o cédula...'; }
        const zonaRes = document.getElementById('zonaResultadosPasante');
        if (zonaRes) zonaRes.style.display = 'none';
        const listaSug = document.getElementById('listaSugerencias');
        if (listaSug) listaSug.style.display = 'none';
        const estadoCero = document.getElementById('estadoCeroBusqueda');
        if (estadoCero) estadoCero.style.display = 'block';
        const btnPerfil = document.getElementById('btnIrPerfil');
        if (btnPerfil) btnPerfil.style.display = 'none';
        const btnPdf = document.getElementById('btnExportarPDF');
        if (btnPdf) btnPdf.style.display = 'none';
    }, 350);
}
/* ── Datos de pasantes (para el select del modal "Nuevo" registro) ── */
var pasantesActivos = <?= json_encode(array_map(fn($p) => [
    'id'     => $p->id,
    'nombre' => mb_convert_encoding(trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')), 'UTF-8', 'UTF-8'),
], $pasantesActivos), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]' ?>;

/* ── Abrir modal pre-seleccionado (desde lista "Sin Marcar") ─────── */
function abrirModalManual(pasanteId, nombre) {
    const inputVisible = document.getElementById('buscadorPasante');
    const inputOculto = document.getElementById('manual-pasante-id');
    const btnLimpiar = document.getElementById('btnLimpiarPasante');

    if (inputOculto) inputOculto.value = pasanteId;
    if (inputVisible) {
        inputVisible.value = nombre;
        inputVisible.readOnly = true; 
        inputVisible.style.background = '#f1f5f9'; 
    }
    if (btnLimpiar) btnLimpiar.style.display = 'none';

    const estadoEl = document.getElementById('manual-estado');
    if (estadoEl) {
        estadoEl.value = 'Presente';
        toggleMotivo('Presente');
    }

    const modal = document.getElementById('modal-manual');
    if (modal) modal.style.display = 'flex';
}

/* ── Abrir modal vacío (botón "Registro Manual" del banner) ─────── */
function abrirModalManualNuevo() {
    if (pasantesActivos.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin pasantes activos', text: 'No hay pasantes con pasantía activa para registrar.', confirmButtonColor: '#162660' });
        return;
    }

    const inputVisible = document.getElementById('buscadorPasante');
    const inputOculto = document.getElementById('manual-pasante-id');
    const btnLimpiar = document.getElementById('btnLimpiarPasante');

    if (inputOculto) inputOculto.value = '';
    if (inputVisible) {
        inputVisible.value = '';
        inputVisible.readOnly = false;
        inputVisible.style.background = '#fff';
    }
    if (btnLimpiar) btnLimpiar.style.display = 'none';

    const estadoEl = document.getElementById('manual-estado');
    if (estadoEl) {
        estadoEl.value = 'Presente';
        toggleMotivo('Presente');
    }

    const modal = document.getElementById('modal-manual');
    if (modal) modal.style.display = 'flex';
}

/* ── Cerrar modal ─────────────────────────────────────────── */
function cerrarModal() {
    document.getElementById('modal-manual').style.display = 'none';
    document.getElementById('form-manual').reset();
    document.getElementById('div-motivo').style.display = 'none';
    document.getElementById('div-evidencia').style.display = 'none';
    clearEvidencia();
    
    // Restaurar los inputs
    const inputVisible = document.getElementById('buscadorPasante');
    if (inputVisible) {
        inputVisible.readOnly = false;
        inputVisible.style.background = '#fff';
    }
}

/* ── Toggle campo motivo + evidencia ─────────────────────────────── */
function toggleMotivo(estado) {
    const isJust = estado === 'Justificado';
    document.getElementById('div-motivo').style.display    = isJust ? 'block' : 'none';
    document.getElementById('div-evidencia').style.display = isJust ? 'block' : 'none';
    document.getElementById('manual-motivo').required      = isJust;
}

/* ── Helpers para el dropzone de evidencia ────────────────────────── */
function showEvidenciaPreview(file) {
    if (!file) return;
    const preview = document.getElementById('dz-preview');
    const icon    = document.getElementById('dz-file-icon');
    const name    = document.getElementById('dz-file-name');
    const size    = document.getElementById('dz-file-size');
    const isPdf   = file.type === 'application/pdf';
    icon.className = isPdf ? 'ti ti-file-type-pdf' : 'ti ti-photo';
    icon.style.color = isPdf ? '#dc2626' : '#2563eb';
    name.textContent = file.name;
    size.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    preview.style.display = 'flex';
    document.getElementById('dropzone-evidencia').style.borderColor = '#2563eb';
    document.getElementById('dropzone-evidencia').style.background  = '#eff6ff';
}

function clearEvidencia(e) {
    if (e) e.stopPropagation();
    document.getElementById('input-evidencia').value = '';
    const preview = document.getElementById('dz-preview');
    if (preview) preview.style.display = 'none';
    const dz = document.getElementById('dropzone-evidencia');
    if (dz) { dz.style.borderColor = '#bfdbfe'; dz.style.background = '#f0f9ff'; }
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('dropzone-evidencia').classList.remove('dz-hover');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
    if (!allowed.includes(file.type)) {
        Swal.fire({ icon: 'error', title: 'Formato no soportado', text: 'Use JPG, PNG o PDF.', confirmButtonColor: '#162660' });
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        Swal.fire({ icon: 'error', title: 'Archivo muy grande', text: 'El archivo supera el límite de 5 MB.', confirmButtonColor: '#162660' });
        return;
    }
    // Asignar al input
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('input-evidencia').files = dt.files;
    showEvidenciaPreview(file);
}

/* ── Detección de feriado en el campo de fecha del modal ─────────── */
(function initFeriadoCheck() {
    // Escucha el campo de fecha del modal manual (puede llamarse 'manual-fecha' o 'fecha')
    const campoFecha = document.getElementById('manual-fecha') || document.querySelector('#form-manual input[type="date"]');
    if (!campoFecha) return;

    campoFecha.addEventListener('change', async function() {
        const fecha = this.value;
        if (!fecha) return;

        // Quitar aviso previo
        const prevAlert = document.getElementById('feriado-alerta-modal');
        if (prevAlert) prevAlert.remove();

        try {
            const r = await fetch(URLROOT + '/kiosco/esFeriado?fecha=' + fecha);
            const d = await r.json();
            if (d.is_feriado) {
                // Insertar pequeño aviso inline debajo del campo de fecha
                const aviso = document.createElement('div');
                aviso.id = 'feriado-alerta-modal';
                aviso.style.cssText = 'margin-top:6px;padding:8px 12px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;font-size:.82rem;font-weight:600;color:#92400e;display:flex;align-items:center;gap:6px;';
                aviso.innerHTML = '<i class="ti ti-calendar-event"></i> Feriado: <strong>' + d.nombre + '</strong> — Se registrará como Justificado.';
                campoFecha.parentNode.appendChild(aviso);
            }
        } catch(e) { /* silencioso */ }
    });
})();

/* ── Submit del form lateral ——————————————————————————————————————— */
function enviarManual(e) {
    e.preventDefault();
    const form = e.target;
    const fd   = new FormData(form);
    const btn  = document.getElementById('btn-submit-manual');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader ti-spin"></i> Guardando...'; }

    fetch('<?= URLROOT ?>/asistencias/registro_manual', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Bloque de horas (si el backend lo devuelve)
                let horasHtml = '';
                if (data.horas) {
                    const pct = data.horas.porcentaje;
                    const color = pct >= 100 ? '#16a34a' : pct >= 75 ? '#2563eb' : '#d97706';
                    horasHtml = `<div style="margin-top:12px;background:#f1f5f9;border-radius:8px;padding:10px 14px;font-size:.85rem;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span style="color:#475569;">Horas acumuladas</span>
                            <strong style="color:${color};">${data.horas.horas_acumuladas}h / ${data.horas.horas_meta}h (${pct}%)</strong>
                        </div>
                        <div style="background:#e2e8f0;border-radius:4px;height:6px;">
                            <div style="background:${color};width:${Math.min(pct,100)}%;height:100%;border-radius:4px;transition:width .4s;"></div>
                        </div>
                    </div>`;
                }
                // Si es feriado, mostrar aviso adicional antes de recargar
                if (data.feriado_advertencia && data.feriado_nombre) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        html: (data.message || 'Asistencia registrada correctamente.') +
                              '<br><span style="background:#fef3c7;padding:6px 12px;border-radius:8px;font-size:.85rem;font-weight:600;color:#92400e;">' +
                              '📅 Feriado: <strong>' + data.feriado_nombre + '</strong></span>' + horasHtml,
                        confirmButtonColor: '#162660'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'success', title: '¡Registrado!', html: (data.message || 'Asistencia registrada correctamente.') + horasHtml, confirmButtonColor: '#162660' })
                        .then(() => location.reload());
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo registrar.', confirmButtonColor: '#162660' });
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar'; }
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor.', confirmButtonColor: '#162660' });
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar'; }
        });
}

/* ── Envío AJAX compartido ───────────────────────────────────────── */
function enviarRegistroManual(pasanteId, estado, motivo) {
    const btn = document.getElementById('btn-submit-manual');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader ti-spin"></i> Guardando...'; }

    const fd = new FormData();
    fd.append('pasante_id', pasanteId);
    fd.append('fecha', '<?= $hoy ?>');
    fd.append('estado', estado);
    fd.append('motivo_justificacion', motivo);

    fetch('<?= URLROOT ?>/asistencias/registro_manual', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let horasHtml = '';
                if (data.horas) {
                    const pct = data.horas.porcentaje;
                    const color = pct >= 100 ? '#16a34a' : pct >= 75 ? '#2563eb' : '#d97706';
                    horasHtml = `<div style="margin-top:12px;background:#f1f5f9;border-radius:8px;padding:10px 14px;font-size:.85rem;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span style="color:#475569;">Horas acumuladas</span>
                            <strong style="color:${color};">${data.horas.horas_acumuladas}h / ${data.horas.horas_meta}h (${pct}%)</strong>
                        </div>
                        <div style="background:#e2e8f0;border-radius:4px;height:6px;">
                            <div style="background:${color};width:${Math.min(pct,100)}%;height:100%;border-radius:4px;"></div>
                        </div>
                    </div>`;
                }
                Swal.fire({ icon: 'success', title: '¡Registrado!', html: (data.message || 'Asistencia registrada correctamente.') + horasHtml, confirmButtonColor: '#162660' })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo registrar.', confirmButtonColor: '#162660' });
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar'; }
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor.', confirmButtonColor: '#162660' });
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar'; }
        });
}

/* ── Modal de detalle ─────────────────────────────────────────────── */
function verDetalle(id, info) {
    // Guardar variables globales para la edición de estado
    _detalleRegistroId = id;
    _detallePasanteId  = info.pasante_id;
    _detalleFecha      = '<?= $hoy ?>';

    // Iniciales para el avatar
    const parts = info.nombre.split(',').map(s => s.trim());
    let iniciales = '?';
    if(parts.length === 2 && parts[0].length > 0 && parts[1].length > 0) {
        iniciales = (parts[1].charAt(0) + parts[0].charAt(0)).toUpperCase();
    } else if (info.nombre.length > 0) {
        iniciales = info.nombre.substring(0, 2).toUpperCase();
    }

    // Llenar datos
    document.getElementById('detalle-avatar').textContent = iniciales;
    document.getElementById('detalle-nombre').textContent = info.nombre;
    document.getElementById('detalle-cedula').textContent = 'C.I. ' + info.cedula;
    document.getElementById('detalle-depto').textContent = info.depto;
    document.getElementById('detalle-hora').textContent = info.hora;
    document.getElementById('detalle-metodo').textContent = info.metodo;
    
    // Configurar color del estado
    const estadoEl = document.getElementById('detalle-estado');
    estadoEl.textContent = info.estado;
    estadoEl.dataset.rawEstado = info.estado;
    if(info.estado === 'Presente') estadoEl.style.color = '#10b981';
    else if(info.estado === 'Justificado') estadoEl.style.color = '#2563eb';
    else estadoEl.style.color = '#ef4444';

    // Motivo si existe
    const motivoContainer = document.getElementById('detalle-motivo-container');
    if (info.motivo) {
        document.getElementById('detalle-motivo').textContent = info.motivo;
        motivoContainer.style.display = 'block';
    } else {
        motivoContainer.style.display = 'none';
    }

    // Botón Almanaque
    const btnAlmanaque = document.getElementById('btnModalAlmanaque');
    if (btnAlmanaque) {
        if (info.pasante_id > 0) {
            btnAlmanaque.href = '<?= URLROOT ?>/asistencias/almanaque/' + info.pasante_id;
            btnAlmanaque.style.display = 'flex';
        } else {
            btnAlmanaque.style.display = 'none';
        }
    }

    // Mostrar modal
    document.getElementById('modal-detalle').classList.add('active');
}

function cerrarModalDetalle() {
    document.getElementById('modal-detalle').classList.remove('active');
}

/* ── Exportar CSV ─────────────────────────────────────────────────── */
function exportarCSV() {
    let csv = 'Pasante,Cédula,Departamento,Hora,Método,Estado\n';
    const dataRows = <?php 
        $rows = [];
        foreach($registrosHoy as $r) {
            $rows[] = [
                trim(($r->apellidos ?? '') . ', ' . ($r->nombres ?? '')),
                $r->cedula ?? '',
                $r->departamento_nombre ?? '',
                $r->hora_registro ? date('h:i A', strtotime($r->hora_registro)) : '—',
                $r->metodo ?? '—',
                $r->estado ?? '—'
            ];
        }
        $encodedRows = json_encode(array_map(function($row) {
            return array_map(fn($v) => mb_convert_encoding((string)$v, 'UTF-8', 'UTF-8'), $row);
        }, $rows), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        echo $encodedRows ?: '[]';
    ?>;
    dataRows.forEach(r => {
        const escaped = r.map(val => val.replace(/"/g, '""'));
        csv += `"${escaped[0]}","${escaped[1]}","${escaped[2]}","${escaped[3]}","${escaped[4]}","${escaped[5]}"\n`;
    });

    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = 'asistencias_<?= $hoy ?>.csv';
    a.click();
    URL.revokeObjectURL(url);

    NotificationService.success('Archivo CSV descargado correctamente');
}

/* ── Cerrar modal si clic fuera ───────────────────────────────────── */
document.getElementById('modal-manual').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
/* ── Lógica del Combo-Box de Pasantes (Buscador en Tiempo Real) ── */
document.addEventListener('DOMContentLoaded', () => {
    const inputBuscador = document.getElementById('buscadorPasante');
    const inputOculto = document.getElementById('manual-pasante-id');
    const resultadosDiv = document.getElementById('resultadosPasante');
    const btnLimpiar = document.getElementById('btnLimpiarPasante');

    if (inputBuscador && resultadosDiv) {
        // Filtrar al escribir
        inputBuscador.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            resultadosDiv.innerHTML = ''; // Limpiar resultados
            
            // Si el input está vacío, ocultar dropdown y limpiar selección
            if (query === '') {
                resultadosDiv.style.display = 'none';
                btnLimpiar.style.display = 'none';
                inputOculto.value = '';
                return;
            }

            // Filtrar pasantes activos
            const filtrados = pasantesActivos.filter(p => p.nombre.toLowerCase().includes(query));

            if (filtrados.length === 0) {
                resultadosDiv.innerHTML = `
                <div style="padding: 24px; text-align: center;">
                    <i class="ti ti-ghost" style="font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                    <p style="margin: 0; color: #64748b; font-size: 0.9rem; font-weight: 500;">
                        No se encontró ningún pasante activo llamado <strong style="color: #1e293b;">"${query}"</strong>.
                    </p>
                    <p style="margin: 6px 0 0; color: #94a3b8; font-size: 0.8rem;">
                        <strong style="color: #64748b;">Verifica si el nombre está bien escrito</strong> o si el pasante ya fue asignado.
                    </p>
                </div>`;
            } else {
                // Renderizar opciones
                filtrados.forEach(p => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding: 12px 16px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #1e293b; font-weight: 500; display: flex; align-items: center; gap: 8px;';
                    item.innerHTML = `<div style="width: 28px; height: 28px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">${p.nombre.substring(0,2).toUpperCase()}</div> ${p.nombre}`;
                    
                    // Hover effect via JS events since inline hover is tricky
                    item.addEventListener('mouseenter', () => item.style.background = '#f8fafc');
                    item.addEventListener('mouseleave', () => item.style.background = 'white');

                    // Al hacer clic en un resultado
                    item.addEventListener('click', () => {
                        inputBuscador.value = p.nombre;
                        inputOculto.value = p.id;
                        resultadosDiv.style.display = 'none';
                        btnLimpiar.style.display = 'flex';
                    });
                    resultadosDiv.appendChild(item);
                });
            }
            resultadosDiv.style.display = 'block';
        });

        // Limpiar selección
        btnLimpiar.addEventListener('click', () => {
            inputBuscador.value = '';
            inputOculto.value = '';
            btnLimpiar.style.display = 'none';
            resultadosDiv.style.display = 'none';
            inputBuscador.focus();
        });

        // Ocultar dropdown si se hace clic afuera
        document.addEventListener('click', (e) => {
            if (!inputBuscador.contains(e.target) && !resultadosDiv.contains(e.target)) {
                resultadosDiv.style.display = 'none';
            }
        });

        // Mostrar todo al enfocar el input vacío (opcional, para ver la lista inicial)
        inputBuscador.addEventListener('focus', function() {
            if (this.value === '') {
                // Trigger a search with empty string to show all initially, or leave it empty until typing. Let's force an empty search.
                this.dispatchEvent(new Event('input'));
            } else {
               resultadosDiv.style.display = 'block';
            }
        });
    }
});
</script>

<!-- ===== DEPENDENCIAS DATATABLES Y ESTILO PREMIUM VENTO BOX ===== -->
<link rel="stylesheet" href="<?= URLROOT ?>/assets/libs/datatables/jquery.dataTables.min.css">
<script src="<?= URLROOT ?>/assets/libs/datatables/jquery.dataTables.min.js"></script>
<!-- Estilos DataTables Premium (Globalizados en datatables-sgp.css) -->

<script>
    $(document).ready(function() {
        if ($('#tablaHistorial').length) {
            // Inicializar la tabla
            var table = $('#tablaHistorial').DataTable({
                language: { url: '<?= URLROOT ?>/assets/libs/datatables/es-ES.json' },
                pageLength: 10,
                order: [[0, 'desc']],
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                responsive: true
            });
            
            // Inicializar Flatpickr en español y en modo rango
            if (typeof flatpickr !== 'undefined') {
                const fp = flatpickr("#filtroFechaRango", {
                    mode: "range",
                    dateFormat: "d/m/Y",
                    locale: "es",
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            $('#btnLimpiarFecha').show();
                        } else {
                            $('#btnLimpiarFecha').hide();
                        }
                        table.draw(); // Forzar a DataTables a redibujar aplicando el filtro
                    }
                });
                
                // Botón para limpiar el filtro de fechas
                $('#btnLimpiarFecha').on('click', function() {
                    fp.clear();
                    $(this).hide();
                    table.draw();
                });
            }
            
            // Extensión del buscador de DataTables para evaluar las fechas
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var fpInput = document.querySelector("#filtroFechaRango");
                    if (!fpInput || !fpInput._flatpickr || fpInput._flatpickr.selectedDates.length === 0) {
                        return true; // Si no hay fechas seleccionadas, mostrar todo
                    }
                    
                    var min = fpInput._flatpickr.selectedDates[0];
                    min.setHours(0, 0, 0, 0);
                    
                    var max = null;
                    if (fpInput._flatpickr.selectedDates.length > 1) {
                        max = fpInput._flatpickr.selectedDates[1];
                        max.setHours(23, 59, 59, 999);
                    } else {
                        // Si solo seleccionó un día, el máximo es ese mismo día
                        max = new Date(min.getTime());
                        max.setHours(23, 59, 59, 999);
                    }
                    
                    // Extraer la fecha de la primera columna (Ej: "28/02/2026")
                    var fechaTablaTexto = data[0].trim().substring(0, 10);
                    var partes = fechaTablaTexto.split('/');
                    if (partes.length === 3) {
                        // Convertir a objeto Date (Año, Mes - 1, Día)
                        var fechaTabla = new Date(partes[2], partes[1] - 1, partes[0]);
                        
                        // Lógica de evaluación
                        if (fechaTabla >= min && fechaTabla <= max) {
                            return true;
                        }
                        return false;
                    }
                    return true;
                }
            );
        }
    });
</script>

<!-- ===== APEXCHARTS PARA GRÁFICO RADIAL (Local) ===== -->
<script src="<?= URLROOT ?>/assets/libs/apexcharts/apexcharts.min.js"></script>

<script>
// ==========================================
// MOTOR DE CONSULTA RÁPIDA (AJAX + ApexCharts)
// IIFE: se ejecuta inmediatamente al inyectarse (funciona en carga normal y tras PJAX)
// ==========================================
(function() {
    const inputBuscar = document.getElementById('inputBuscarPasanteAJAX');
    const listaSugerencias = document.getElementById('listaSugerencias');
    const zonaResultados = document.getElementById('zonaResultadosPasante');
    let chartTiempo = null;

    if (!inputBuscar) return; // Si no existe el modal, salir

    // ==========================================
    // 1. BUSCADOR EN VIVO (Con Debounce)
    // ==========================================
    let timeoutId;
    inputBuscar.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const query = this.value.trim();
        const estadoCero = document.getElementById('estadoCeroBusqueda');
        const zonaResultados = document.getElementById('zonaResultadosPasante');
        
        if (query.length < 2) {
            listaSugerencias.style.display = 'none';
            if(estadoCero) estadoCero.style.display = 'block';
            if(zonaResultados) zonaResultados.style.display = 'none';
            return;
        }

        if(estadoCero) estadoCero.style.display = 'none';
        if(zonaResultados) zonaResultados.style.display = 'none';

        const loadingIcon = document.getElementById('sgpAudLoading');
        if(loadingIcon) loadingIcon.classList.add('active');

        timeoutId = setTimeout(() => {
            const formData = new FormData();
            formData.append('query', query);

            fetch('<?= URLROOT ?>/asistencias/buscarPasanteAjax', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(loadingIcon) loadingIcon.classList.remove('active');
                listaSugerencias.innerHTML = '';
                
                if (data.length > 0) {
                    data.forEach(pasante => {
                        const div = document.createElement('div');
                        div.className = 'sgp-result-item';

                        // Extraer iniciales reales (Ej: JG)
                        const iniciales = (pasante.nombres.charAt(0) + pasante.apellidos.charAt(0)).toUpperCase();
                        const instText  = pasante.institucion_nombre || pasante.departamento_nombre || '—';
                        const periodoBadge = pasante.periodo_nombre
                            ? `<span style="background:#f3e8ff;color:#7e22ce;font-size:0.6rem;padding:2px 6px;border-radius:4px;font-weight:700;margin-right:4px;border:1px solid #e9d5ff;">${pasante.periodo_nombre}</span>`
                            : '';

                        div.innerHTML = `
                            <div class="sgp-result-avatar" style="background:#10b981;">${iniciales}</div>
                            <div class="sgp-result-info">
                                <div class="sgp-result-name">${pasante.nombres.toLowerCase()} ${pasante.apellidos.toLowerCase()}</div>
                                <div class="sgp-result-meta"><i class="ti ti-id" style="font-size:0.65rem;"></i> ${pasante.cedula} · <i class="ti ti-school" style="font-size:0.65rem; margin-left:3px;"></i> ${instText}</div>
                            </div>
                            <div style="display:flex;align-items:center;margin-left:auto;">${periodoBadge}<span class="sgp-result-badge sgp-badge-pasante" style="background:#d1fae5;color:#059669;font-size:0.6rem;">Pasante</span></div>
                        `;
                        
                        div.onclick = () => cargarAuditoriaPasante(pasante.pasante_id, pasante);
                        listaSugerencias.appendChild(div);
                    });
                    listaSugerencias.style.display = 'block';
                } else {
                    listaSugerencias.innerHTML = `
                        <div class="sgp-empty-state" style="padding: 16px; text-align: center; color: #64748b;">
                            <i class="ti ti-user-off" style="font-size: 1.5rem; opacity: 0.5;"></i>
                            <p style="margin: 4px 0 0; font-size: 0.85rem;">No se encontraron resultados</p>
                        </div>
                    `;
                    listaSugerencias.style.display = 'block';
                }
            })
            .catch(error => {
                if(loadingIcon) loadingIcon.classList.remove('active');
                console.error("Error buscando pasante:", error);
            });
        }, 300);


    });



    // ==========================================
    // 2. EXTRAER Y PINTAR AUDITORÍA
    // ==========================================
    let currentHistorial = []; // Store for frontend filtering

    function cargarAuditoriaPasante(idPasante, datosBasicos) {
        listaSugerencias.style.display = 'none';
        inputBuscar.value = ''; 
        inputBuscar.placeholder = 'Extrayendo expediente...';
        
        const formData = new FormData();
        formData.append('id_pasante', idPasante);

        fetch('<?= URLROOT ?>/asistencias/obtenerDatosAuditoriaAjax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            inputBuscar.placeholder = 'Buscar por nombre o cédula...';
            
            if (data.status === 'success') {
                const perfil = data.perfil;
                currentHistorial = data.historial; // Guardar para filtros

                // Ocultar estado cero y mostrar resultados
                document.getElementById('estadoCeroBusqueda').style.display = 'none';
                document.getElementById('zonaResultadosPasante').style.display = 'block';
                document.getElementById('audFooterAcciones').style.display = 'flex';

                // A. Llenar Perfil Compacto
                const iniciales = (perfil.nombres.charAt(0) + perfil.apellidos.charAt(0)).toUpperCase();
                const avatar = document.getElementById('avatarPasante');
                avatar.innerText = iniciales;
                avatar.style.background = '#10b981'; // Color por defecto pasante

                document.getElementById('nombrePasante').innerText = `${perfil.nombres} ${perfil.apellidos}`;
                document.getElementById('deptoPasante').innerHTML = `<i class="ti ti-building"></i> ${perfil.departamento_nombre}`;

                // B. Llenar Ficha de Datos Rápidos
                const esc = t => String(t || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                document.getElementById('fichaCedula').textContent   = perfil.cedula      || '—';
                document.getElementById('fichaTelefono').textContent = perfil.telefono    || 'Sin registrar';
                let instHtml = esc(perfil.institucion || 'Sin especificar');
                if (perfil.representante_nombre) {
                    instHtml += ` <span style="color:#64748b; font-size:0.75rem; font-weight:500;">· Rep: ${esc(perfil.representante_nombre)}</span>`;
                }
                if (perfil.periodo_nombre) {
                    instHtml += ` <span style="background:#f3e8ff;color:#7e22ce;font-size:0.68rem;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;">${esc(perfil.periodo_nombre)}</span>`;
                }
                document.getElementById('fichaInstitucion').innerHTML = instHtml;
                const tutorNombre = (perfil.tutor_nombre || '').trim();
                document.getElementById('fichaTutor').textContent = tutorNombre || 'Sin asignar';
                
                // Botones Footer
                const btnAlmanaque = document.getElementById('btnAudAlmanaque');
                btnAlmanaque.href = '<?= URLROOT ?>/asistencias/almanaque/' + perfil.pasante_id;
                btnAlmanaque.style.display = 'flex';

                const btnExcel = document.getElementById('btnExportarExcel');
                btnExcel.href = '<?= URLROOT ?>/reportes/excel/' + perfil.pasante_id; 
                btnExcel.style.display = 'flex';
                
                const btnPdf = document.getElementById('btnExportarPDF');
                btnPdf.onclick = () => window.open('<?= URLROOT ?>/reportes/pdfKardex?id=' + perfil.pasante_id, '_blank');
                btnPdf.style.display = 'flex';

                // B. Renderizar Timeline con Filtros Iniciales
                renderizarTimeline(currentHistorial);

                // C. ✅ FASE 2: Renderizar Gauge con progreso de calendario (L-V)
                renderizarGraficoTiempo(data.calendario, data.pro_rata.horas_meta);
            }
        });
    }

    // ==========================================
    // 3. MOTOR DE FILTROS FRONTEND
    // ==========================================
    window.ejecutarFiltrosAuditoria = function() {
        const mes = document.getElementById('filtroMesAuditoria').value;
        const estado = document.getElementById('filtroEstadoAuditoria').value;
        const metodo = document.getElementById('filtroMetodoAuditoria').value;

        const filtrados = currentHistorial.filter(reg => {
            const matchMes = !mes || reg.fecha.split('-')[1] === mes;
            const matchEstado = !estado || reg.estado.toLowerCase().includes(estado.toLowerCase());
            const matchMetodo = !metodo || reg.metodo.toLowerCase().includes(metodo.toLowerCase());
            return matchMes && matchEstado && matchMetodo;
        });

        renderizarTimeline(filtrados);
    };

    window.limpiarFiltrosAuditoria = function() {
        document.getElementById('filtroMesAuditoria').value = '';
        document.getElementById('filtroEstadoAuditoria').value = '';
        document.getElementById('filtroMetodoAuditoria').value = '';
        renderizarTimeline(currentHistorial);
    };

    function renderizarTimeline(datos) {
        const container = document.getElementById('timelineAsistencias');
        let countP = 0, countA = 0, countJ = 0;
        let html = '';

        if (datos.length > 0) {
            datos.forEach(reg => {
                const estadoL = reg.estado.toLowerCase();
                let icon = 'ti-check', iconColor = '#059669', iconBg = '#ecfdf5', badgeBg = '#d1fae5', badgeColor = '#059669';
                let label = 'Presente';

                if (estadoL.includes('presente')) {
                    countP++;
                } else if (estadoL.includes('ausente')) {
                    countA++;
                    icon = 'ti-x'; iconColor = '#dc2626'; iconBg = '#fef2f2'; badgeBg = '#fee2e2'; badgeColor = '#dc2626';
                    label = 'Ausente';
                } else {
                    countJ++;
                    icon = 'ti-file-certificate'; iconColor = '#d97706'; iconBg = '#fffbeb'; badgeBg = '#fef3c7'; badgeColor = '#d97706';
                    label = 'Justificado';
                }

                const fechaFormat = reg.fecha.split('-').reverse().join('/');
                let hora = '—';
                if (reg.hora_registro) {
                    const [h, m] = reg.hora_registro.split(':');
                    const numH = parseInt(h, 10);
                    const ampm = numH >= 12 ? 'PM' : 'AM';
                    const h12 = numH % 12 || 12;
                    hora = `${h12.toString().padStart(2, '0')}:${m} ${ampm}`;
                }
                const metodo = reg.metodo || '—';

                html += `
                    <div class="aud-timeline-item">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="aud-timeline-icon" style="background:${iconBg}; color:${iconColor};">
                                <i class="ti ${icon}" style="font-size:0.9rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700; color:#1e293b; font-size:0.83rem;">${fechaFormat}</div>
                                <div style="font-size:0.68rem; color:#94a3b8; margin-top:1px;">
                                    <i class="ti ti-clock"></i> ${hora}
                                    <span style="margin:0 5px; opacity:0.4;">|</span>
                                    <i class="ti ti-device-desktop"></i> ${metodo}
                                </div>
                            </div>
                        </div>
                        <span style="background:${badgeBg}; color:${badgeColor}; padding:3px 10px; border-radius:20px; font-size:0.63rem; font-weight:800; text-transform:uppercase; letter-spacing:0.4px; white-space:nowrap;">${label}</span>
                    </div>
                `;
            });
        } else {
            html = `
                <div style="text-align:center; padding:28px 10px; color:#94a3b8;">
                    <i class="ti ti-mood-empty" style="font-size:2rem; opacity:0.35; display:block; margin-bottom:8px;"></i>
                    <p style="font-size:0.8rem; margin:0; font-weight:600;">Sin resultados con estos filtros</p>
                </div>
            `;
        }

        container.innerHTML = html;
        document.getElementById('kpiPresentes').innerText = countP;
        document.getElementById('kpiAusentes').innerText = countA;
        document.getElementById('kpiJustificados').innerText = countJ;
    }

    // ==========================================
    // 4. MOTOR DEL GRÁFICO RADIAL — CALENDARIO
    //    Fuente de verdad: objeto calendario { porcentaje_calendario, horas_calendario, ... }
    // ==========================================
    function renderizarGraficoTiempo(calendario, horasMeta = 1440) {
        if (chartTiempo) { chartTiempo.destroy(); }

        const container = document.querySelector('#graficoRadialTiempo');
        if (!container) return;

        // Fallback seguro
        const porcentaje  = calendario ? Math.round(calendario.porcentaje_calendario) : 0;
        const hCalendario = calendario ? calendario.horas_calendario : 0;
        const dTranscurridos = calendario ? calendario.dias_habiles_transcurridos : 0;
        const dRestantes     = calendario ? calendario.dias_habiles_restantes : 0;

        // Color dinámico: verde OK, amarillo en progreso, rojo bajo
        let color = '#10b981'; // verde
        if (porcentaje < 20) color = '#ef4444';      // rojo
        else if (porcentaje < 60) color = '#f59e0b'; // amarillo

        const options = {
            series: [porcentaje],
            chart: { height: 120, type: 'radialBar', sparkline: { enabled: true } },
            plotOptions: {
                radialBar: {
                    hollow: { size: '55%' },
                    track: { background: '#f1f5f9' },
                    dataLabels: {
                        name: { show: false },
                        value: {
                            show: true,
                            fontSize: '0.85rem',
                            fontWeight: 800,
                            color: '#1e293b',
                            offsetY: 6,
                            formatter: function(val) { return val + '%'; }
                        }
                    }
                }
            },
            colors: [color],
            stroke: { lineCap: 'round' },
            tooltip: {
                enabled: true,
                theme: 'light',
                y: { 
                    formatter: function() {
                        return `${hCalendario}h / ${horasMeta}h`;
                    },
                    title: {
                        formatter: function() { return ''; }
                    }
                },
                x: { show: false },
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    return `
                        <div style="padding: 12px; background: #ffffff; color: #334155; border-radius: 12px; font-size: 0.8rem; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">
                            <div style="font-weight:800; margin-bottom: 8px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Progreso Calendario L-V</div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px; gap: 20px;">
                                <span style="color:#64748b; font-weight:600;">Días Transcurridos:</span>
                                <strong style="color:#0f172a;">${dTranscurridos}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px; gap: 20px;">
                                <span style="color:#64748b; font-weight:600;">Días Restantes:</span>
                                <strong style="color:#0f172a;">${dRestantes}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                                <span style="color:#2563eb; font-weight:700;">Horas Esperadas:</span>
                                <strong style="color:#1e293b; font-weight:800;">${hCalendario}h / ${horasMeta}h</strong>
                            </div>
                        </div>
                    `;
                }
            }
        };

        chartTiempo = new ApexCharts(container, options);
        chartTiempo.render();
    }
})();
</script>

<!-- MODAL ALMANAQUE INTELIGENTE (Heatmap) -->
<div class="modal fade" id="modalResumenInteligente" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%); border-radius: 20px 20px 0 0; padding: 24px 32px;">
                <div>
                    <h5 class="modal-title text-white" style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="ti ti-calendar-stats me-2"></i> <span id="nombrePasanteHeatmap">Pasante</span>
                    </h5>
                    <div class="text-light" style="font-size: 0.8rem; font-weight: 500; opacity: 0.9;">Heatmap de Asistencia</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.8rem; box-shadow: none;"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-4 mt-2">
                    <button type="button" id="btn-prev-month" class="btn btn-sm btn-outline-secondary" onclick="cambiarMesAlmanaque(-1)"><i class="ti ti-chevron-left"></i></button>
                    <!-- no-choices: los selects del heatmap se controlan via JS puro,
                         sin Choices.js, para poder reconstruir sus opciones dinámicamente -->
                    <select id="heatmap-mes" class="no-choices" style="width:148px;" onchange="cargarAlmanaqueActual()">
                        <option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option><option value="04">Abril</option><option value="05">Mayo</option><option value="06">Junio</option><option value="07">Julio</option><option value="08">Agosto</option><option value="09">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                    </select>
                    <select id="heatmap-anio" class="no-choices" style="width:82px;" onchange="_hmFiltrarMeses(); cargarAlmanaqueActual()">
                        <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                    </select>
                    <button type="button" id="btn-next-month" class="btn btn-sm btn-outline-secondary" onclick="cambiarMesAlmanaque(1)"><i class="ti ti-chevron-right"></i></button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-bottom: 8px; text-align: center; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">
                    <div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div><div>D</div>
                </div>
                <!-- CSS GRID (7x6 máximo para cubrir un mes) -->
                <div id="grid-almanaque" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px;">
                    <!-- Se llena vía JS -->
                </div>

                <!-- KPI de retardos del mes -->
                <div id="kpi-almanaque" style="margin-top: 16px; display: none; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 10px 16px; font-size: 0.78rem; color: #92400e; font-weight: 600; display: flex; gap: 16px; flex-wrap: wrap; align-items: center; justify-content: center;">
                    <span><i class="ti ti-check" style="color:#10b981;"></i> <span id="kpi-presentes">0</span> Presentes</span>
                    <span><i class="ti ti-clock-exclamation" style="color:#f97316;"></i> <span id="kpi-retardos">0</span> Retardos</span>
                    <span><i class="ti ti-x" style="color:#ef4444;"></i> <span id="kpi-ausentes">0</span> Ausentes</span>
                    <span><i class="ti ti-file-description" style="color:#2563eb;"></i> <span id="kpi-justificados">0</span> Justificados</span>
                </div>

                <!-- Leyenda interactiva -->
                <div style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; font-size: 0.72rem; color: #64748b; font-weight: 600;">
                    <div style="display:flex; align-items:center; gap:5px;"><span style="width:11px; height:11px; border-radius:3px; background:#10b981;"></span> Presente</div>
                    <div style="display:flex; align-items:center; gap:5px;"><span style="width:11px; height:11px; border-radius:3px; background:#f97316;"></span> Retardo</div>
                    <div style="display:flex; align-items:center; gap:5px;"><span style="width:11px; height:11px; border-radius:3px; background:#ef4444;"></span> Ausente</div>
                    <div style="display:flex; align-items:center; gap:5px;"><span style="width:11px; height:11px; border-radius:3px; background:#3b82f6;"></span> Justificado</div>
                    <div style="display:flex; align-items:center; gap:5px;"><span style="width:11px; height:11px; border-radius:3px; background:#a855f7;"></span> Feriado</div>
                    <div style="display:flex; align-items:center; gap:5px;"><span style="width:11px; height:11px; border-radius:3px; background:#f1f5f9; border:1px solid #e2e8f0;"></span> Sin Marcar</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes hm-pulse { 0%,100%{opacity:.4} 50%{opacity:1} }
.hm-skel { background:#e2e8f0; border-radius:4px; aspect-ratio:1/1; animation:hm-pulse 1.2s ease-in-out infinite; }

/* Selects de mes/año del heatmap — estilo acorde al header azul del modal */
#heatmap-mes, #heatmap-anio {
    background: #eff6ff;
    border: 1.5px solid #93c5fd;
    color: #1e3a8a;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    padding: .3rem .5rem;
    text-align: center;
    text-align-last: center;
    transition: border-color .18s, background .18s, box-shadow .18s;
    /* override Bootstrap form-select */
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231e3a8a' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .5rem center;
    padding-right: 1.6rem;
}
#heatmap-mes:hover, #heatmap-anio:hover {
    background-color: #dbeafe;
    border-color: #3b82f6;
}
#heatmap-mes:focus, #heatmap-anio:focus {
    outline: none;
    background-color: #dbeafe;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.18);
}
/* Botones de flecha del heatmap — mismo tono azul */
#btn-prev-month, #btn-next-month {
    border-color: #93c5fd;
    color: #1e3a8a;
    border-radius: 8px;
    width: 32px; height: 32px;
    padding: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .18s, border-color .18s;
}
#btn-prev-month:hover, #btn-next-month:hover {
    background: #dbeafe;
    border-color: #3b82f6;
    color: #1d4ed8;
}
#btn-prev-month:disabled, #btn-next-month:disabled {
    border-color: #e2e8f0;
    color: #cbd5e1;
    background: transparent;
}

/* Tooltip personalizado del heatmap */
#hm-tooltip {
    position:fixed; z-index:9999; pointer-events:none;
    background:#1e293b; color:#fff; border-radius:10px;
    padding:8px 12px; font-size:.75rem; line-height:1.6;
    box-shadow:0 8px 24px rgba(0,0,0,.25); min-width:150px;
    opacity:0; transition:opacity .15s;
}
#hm-tooltip.visible { opacity:1; }
</style>
<div id="hm-tooltip"></div>

<script>
    var currentAlmanaquePasanteId = null;
    var _hmPeriodoInicio = null; // 'YYYY-MM-DD' — límite inferior del período
    var _hmPeriodoFin    = null; // 'YYYY-MM-DD' — límite superior del período

    /* ── Abrir modal ──────────────────────────────────────────────── */
    function abrirAlmanaque(btn) {
        currentAlmanaquePasanteId = btn.getAttribute('data-id');
        document.getElementById('nombrePasanteHeatmap').innerText = btn.getAttribute('data-nombre');

        // Guardar límites del período para filtrar navegación
        _hmPeriodoInicio = btn.getAttribute('data-inicio') || null;
        _hmPeriodoFin    = btn.getAttribute('data-fin')    || null;

        // Mes inicial: el que corresponde a la semana/fecha actual de la vista
        let targetMesText = "<?= str_pad($paramsUrl['anio'] ?? date('Y'), 4, '0', STR_PAD_LEFT) ?>-<?= str_pad(($paramsUrl['semana'] ? date('m', strtotime($paramsUrl['anio'].'W'.str_pad($paramsUrl['semana'], 2, '0', STR_PAD_LEFT))) : date('m')), 2, '0', STR_PAD_LEFT) ?>";
        const [targetAnio, targetMesNum] = targetMesText.split('-');

        document.getElementById('heatmap-mes').value  = targetMesNum;
        document.getElementById('heatmap-anio').value = targetAnio;

        // Filtrar años y meses según el período del pasante
        _hmFiltrarOpciones();

        // Actualizar estado de los botones de flecha según límites del período
        _hmActualizarBotones();

        const modal = new bootstrap.Modal(document.getElementById('modalResumenInteligente'));
        modal.show();
        cargarAlmanaqueActual();
    }

    /* ── Navegar mes con flechas ──────────────────────────────────── */
    function cambiarMesAlmanaque(delta) {
        const selMes  = document.getElementById('heatmap-mes');
        const selAnio = document.getElementById('heatmap-anio');
        const d = new Date(selAnio.value + '-' + selMes.value + '-01T00:00:00');
        d.setMonth(d.getMonth() + delta);

        const newMes  = String(d.getMonth() + 1).padStart(2, '0');
        const newAnio = String(d.getFullYear());

        // Respetar límites del período si están disponibles
        if (_hmPeriodoInicio && _hmPeriodoFin) {
            const propuesto = newAnio + '-' + newMes;
            const limInf = _hmPeriodoInicio.substring(0, 7); // 'YYYY-MM'
            const limSup = _hmPeriodoFin.substring(0, 7);
            if (propuesto < limInf || propuesto > limSup) return;
        }

        selMes.value  = newMes;
        selAnio.value = newAnio;
        _hmActualizarBotones();
        cargarAlmanaqueActual();
    }

    /* Deshabilitar flechas en los bordes del período */
    function _hmActualizarBotones() {
        if (!_hmPeriodoInicio || !_hmPeriodoFin) return;
        const mes  = document.getElementById('heatmap-mes').value;
        const anio = document.getElementById('heatmap-anio').value;
        const actual = anio + '-' + mes;
        const limInf = _hmPeriodoInicio.substring(0, 7);
        const limSup = _hmPeriodoFin.substring(0, 7);
        document.getElementById('btn-prev-month').disabled = (actual <= limInf);
        document.getElementById('btn-next-month').disabled = (actual >= limSup);
    }

    // [SGP-FIX-PJAX-REDECL] var en vez de const: mismo motivo (PJAX re-inyecta
    // este script sin reload completo).
    var _HM_MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    /* ── Reconstruye el select de años con solo los del período ──── */
    function _hmFiltrarOpciones() {
        if (!_hmPeriodoInicio || !_hmPeriodoFin) return;

        const iniAnio  = parseInt(_hmPeriodoInicio.substring(0, 4));
        const finAnio  = parseInt(_hmPeriodoFin.substring(0, 4));
        const selAnio  = document.getElementById('heatmap-anio');
        const prevAnio = parseInt(selAnio.value) || iniAnio;
        const anioOk   = Math.max(iniAnio, Math.min(finAnio, prevAnio));

        selAnio.innerHTML = '';
        for (let y = iniAnio; y <= finAnio; y++) {
            const opt = document.createElement('option');
            opt.value = String(y);
            opt.textContent = String(y);
            if (y === anioOk) opt.selected = true;
            selAnio.appendChild(opt);
        }

        _hmFiltrarMeses();
    }

    /* ── Reconstruye el select de meses válidos para el año elegido ─ */
    function _hmFiltrarMeses() {
        if (!_hmPeriodoInicio || !_hmPeriodoFin) return;

        const iniAnio = parseInt(_hmPeriodoInicio.substring(0, 4));
        const finAnio = parseInt(_hmPeriodoFin.substring(0, 4));
        const iniMes  = parseInt(_hmPeriodoInicio.substring(5, 7));
        const finMes  = parseInt(_hmPeriodoFin.substring(5, 7));
        const anio    = parseInt(document.getElementById('heatmap-anio').value);
        const selMes  = document.getElementById('heatmap-mes');
        const prevMes = parseInt(selMes.value) || iniMes;

        let mesMin = 1, mesMax = 12;
        if (anio === iniAnio && anio === finAnio) { mesMin = iniMes; mesMax = finMes; }
        else if (anio === iniAnio)                { mesMin = iniMes; }
        else if (anio === finAnio)                { mesMax = finMes; }

        const mesOk = Math.max(mesMin, Math.min(mesMax, prevMes));

        selMes.innerHTML = '';
        for (let m = mesMin; m <= mesMax; m++) {
            const opt = document.createElement('option');
            opt.value = String(m).padStart(2, '0');
            opt.textContent = _HM_MESES[m - 1];
            if (m === mesOk) opt.selected = true;
            selMes.appendChild(opt);
        }

        _hmActualizarBotones();
    }

    /* ── Cargar datos del mes via AJAX ────────────────────────────── */
    function cargarAlmanaqueActual() {
        if (!currentAlmanaquePasanteId) return;

        const selMes  = document.getElementById('heatmap-mes').value;
        const selAnio = document.getElementById('heatmap-anio').value;
        const mesAnio = selAnio + '-' + selMes;
        const grid    = document.getElementById('grid-almanaque');

        // Skeleton loader — 37 celdas (máximo que puede ocupar un mes en 7 cols)
        let skeletonHtml = '';
        for (let i = 0; i < 37; i++) skeletonHtml += `<div class="hm-skel" style="animation-delay:${(i * 0.03).toFixed(2)}s"></div>`;
        grid.innerHTML = skeletonHtml;
        document.getElementById('kpi-almanaque').style.display = 'none';

        const formData = new FormData();
        formData.append('pasante_id', currentAlmanaquePasanteId);
        formData.append('mes_anio', mesAnio);

        fetch('<?= URLROOT ?>/asistencias/obtenerResumenMensual', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                renderizarGridAlmanaque(mesAnio, res.datos, res.feriados || {});
                const kpi = res.kpi || {};
                document.getElementById('kpi-presentes').textContent   = kpi.presentes    || 0;
                document.getElementById('kpi-retardos').textContent     = kpi.retardos     || 0;
                document.getElementById('kpi-ausentes').textContent     = kpi.ausentes     || 0;
                document.getElementById('kpi-justificados').textContent = kpi.justificados || 0;
                document.getElementById('kpi-almanaque').style.display  = 'flex';
            } else {
                grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:#ef4444;padding:20px;font-size:.85rem;">Error al cargar datos.</div>`;
            }
        })
        .catch(() => {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:#ef4444;padding:20px;font-size:.85rem;">Error de red.</div>`;
        });
    }

    /* ── Tooltip personalizado ────────────────────────────────────── */
    const _hmTip = document.getElementById('hm-tooltip');
    function _hmShowTip(e, html) {
        _hmTip.innerHTML = html;
        _hmTip.classList.add('visible');
        _hmMoveTip(e);
    }
    function _hmMoveTip(e) {
        const x = e.clientX + 14, y = e.clientY - 10;
        const tipW = _hmTip.offsetWidth, tipH = _hmTip.offsetHeight;
        _hmTip.style.left = (x + tipW > window.innerWidth  ? x - tipW - 28 : x) + 'px';
        _hmTip.style.top  = (y + tipH > window.innerHeight ? y - tipH      : y) + 'px';
    }
    function _hmHideTip() { _hmTip.classList.remove('visible'); }

    /* ── Renderizar el grid del heatmap ───────────────────────────── */
    function renderizarGridAlmanaque(mesAnio, asistenciasData, feriadosData) {
        const grid = document.getElementById('grid-almanaque');
        const [year, month] = mesAnio.split('-').map(Number);
        const diasEnMes = new Date(year, month, 0).getDate();

        // Ajuste de inicio: Lunes = 0, Domingo = 6
        let primerDiaSemana = new Date(year, month - 1, 1).getDay();
        primerDiaSemana = primerDiaSemana === 0 ? 6 : primerDiaSemana - 1;

        let html = '';
        const cells = []; // para bindear eventos después

        for (let i = 0; i < primerDiaSemana; i++) {
            html += `<div style="aspect-ratio:1/1;"></div>`;
        }

        const hoy = new Date().toISOString().split('T')[0];

        for (let d = 1; d <= diasEnMes; d++) {
            const dateStr  = `${year}-${String(month).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const registro = asistenciasData[dateStr] || null;
            const esFeriado = feriadosData[dateStr] != null;

            let bg = '#f8fafc', border = '1px solid #e2e8f0', color = '#94a3b8', badge = '';
            let tipHtml = `<div style="font-weight:700;margin-bottom:4px;">${dateStr}</div>`;

            if (esFeriado) {
                bg = '#a855f7'; border = 'none'; color = '#fff';
                badge = `<span style="position:absolute;top:2px;right:2px;font-size:.55rem;">🗓</span>`;
                tipHtml += `<div>🗓 Feriado</div><div style="font-size:.7rem;color:#c4b5fd;">${feriadosData[dateStr]}</div>`;
            } else if (registro) {
                const est        = (registro.estado || '').toLowerCase();
                const esRetardo  = registro.es_retardo  == 1;
                const esAuto     = registro.es_auto_fill == 1;
                const horaStr    = registro.hora ? registro.hora.substring(0,5) : null;
                const origenStr  = esAuto ? '🤖 Automático' : '👤 Manual';

                if (est.includes('presente')) {
                    if (esRetardo) {
                        bg = '#f97316'; border = 'none'; color = '#fff';
                        badge = `<span style="position:absolute;top:2px;right:2px;font-size:.55rem;">⏰</span>`;
                        tipHtml += `<div>⏰ Presente con Retardo</div>`;
                    } else {
                        bg = '#10b981'; border = 'none'; color = '#fff';
                        tipHtml += `<div>✅ Presente</div>`;
                    }
                } else if (est.includes('ausente')) {
                    bg = esAuto ? '#fca5a5' : '#ef4444'; border = 'none'; color = esAuto ? '#b91c1c' : '#fff';
                    tipHtml += `<div>❌ Ausente</div>`;
                } else if (est.includes('justificado')) {
                    bg = '#3b82f6'; border = 'none'; color = '#fff';
                    tipHtml += `<div>📄 Justificado</div>`;
                }

                if (horaStr) tipHtml += `<div style="font-size:.7rem;color:#94a3b8;margin-top:2px;">🕐 ${horaStr} · ${origenStr}</div>`;
                else         tipHtml += `<div style="font-size:.7rem;color:#94a3b8;margin-top:2px;">${origenStr}</div>`;
            } else {
                tipHtml += `<div style="color:#94a3b8;">Sin marcar</div>`;
            }

            const todayShadow = dateStr === hoy ? 'box-shadow:inset 0 0 0 2px #1e3a8a;' : '';
            const cellIdx = primerDiaSemana + d - 1;
            cells.push({ idx: cellIdx, tipHtml });

            html += `<div data-cell="${cellIdx}" style="position:relative;background:${bg};border:${border};border-radius:4px;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:${color};cursor:pointer;transition:transform .15s;${todayShadow}" onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
                ${badge}${d}
            </div>`;
        }

        grid.innerHTML = html;

        // Bindear tooltip a cada celda con datos
        cells.forEach(({ idx, tipHtml }) => {
            const el = grid.querySelector(`[data-cell="${idx}"]`);
            if (!el) return;
            el.addEventListener('mouseenter', e => _hmShowTip(e, tipHtml));
            el.addEventListener('mousemove',  e => _hmMoveTip(e));
            el.addEventListener('mouseleave', _hmHideTip);
        });
    }
</script>

<!-- ===== FASE 5: ELITE SAAS UI/UX ENHANCEMENTS ===== -->
<style>
/* Animaciones en Cascada (Skeleton Load Feeling) */
@keyframes staggerFadeInUp {
    0% { opacity: 0; transform: translateY(20px); filter: blur(5px); }
    100% { opacity: 1; transform: translateY(0); filter: blur(0); }
}

.stagger-enter {
    opacity: 0;
    animation: staggerFadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Glassmorphism en Sticky Header y Cajas (Estética Backdrop Blur) */
.glass-header {
    backdrop-filter: blur(12px) !important;
    background: rgba(248, 250, 252, 0.85) !important;
}
.glass-input {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.glass-input:focus {
    background: rgba(255, 255, 255, 0.95) !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
}
.btn-clean-input {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; z-index: 2; transition: color 0.2s;
}
.btn-clean-input:hover { color: #ef4444; }

/* Flat & Crisp UI: The Death of the Generic Drop Shadow */
.crisp-card {
    border: 1px solid #f1f5f9 !important;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.02) !important;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.bento-card:hover, .muro-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06) !important;
    border-color: rgba(59, 130, 246, 0.1) !important;
}
.group-hover-bg:hover { background: white !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.crisp-border { border: 1px solid rgba(15, 23, 42, 0.04); }

/* Status Rings (El estilo Instagram Stories) */
.status-ring {
    position: relative;
    z-index: 10;
}
.status-ring::after {
    content: '';
    position: absolute;
    top: -4px; left: -4px; right: -4px; bottom: -4px;
    border-radius: 50%;
    border: 2px solid transparent;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: -1;
}
.status-ring.ring-success::after { border-color: rgba(16, 185, 129, 0.3); }
.status-ring.ring-danger::after  { border-color: rgba(239, 68, 68, 0.4); }
.status-ring.ring-primary::after { border-color: rgba(59, 130, 246, 0.3); }
.bento-card:hover .status-ring::after { transform: scale(1.1); }

/* Radial Progress / Gráficas Gauge (CSS Puro Apple-Style) */
.radial-gauge {
    transition: --pct 1.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.muro-card .radial-gauge { margin: 0 auto; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.03); }

/* Custom Properties Required for native CSS animation of Conic-gradients in modern Webkit */
@property --pct {
  syntax: '<percentage>';
  inherits: false;
  initial-value: 0%;
}

/* Sparklines Visuales / Tendencias Flexbox */
.sparkline-bar {
    opacity: 0.6;
    transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    transform-origin: bottom;
}
.group-hover-bg:hover .sparkline-bar { opacity: 1; transform: scaleY(1.3); }

/* Buttons & Hero Interactions */
.btn-elite {
    border-radius: 12px !important; font-weight: 600 !important; padding: 10px !important; font-size: 0.85rem !important; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.btn-elite:hover, .btn-elite-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08) !important; }
.btn-icon-hover i { transition: transform 0.2s; }
.btn-icon-hover:hover i { transform: scale(1.2); color: #0f172a; }

/* Empty States Beautiful */
.empty-state {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.empty-state:hover { transform: translateY(-2px); opacity: 1 !important; }

/* ===== COHERENCIA VISUAL INSTITUCIONAL SGP ===== */
/* Tokens de la app: navy #1e2b58, accent #F59E0B, bg #F0F5FA */

.sgp-action-bar { transition: box-shadow 0.3s ease; }
.sgp-action-bar:hover { box-shadow: 0 12px 32px rgba(30,43,88,0.08) !important; }

/* Bento cards: alineadas con el card system de la app */
.bento-card {
    border: 1px solid rgba(30,43,88,0.06) !important;
    box-shadow: 0 4px 16px rgba(30,43,88,0.04) !important;
    transition: transform 0.25s cubic-bezier(0.4,0,0.2,1),
                box-shadow 0.25s cubic-bezier(0.4,0,0.2,1),
                border-color 0.2s ease !important;
    min-height: 200px;
}
.bento-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 20px 40px rgba(30,43,88,0.1), 0 4px 12px rgba(30,43,88,0.04) !important;
    border-color: rgba(30,43,88,0.15) !important;
}
/* Accent line al hover (igual que .kpi-card de modern-theme) */
.bento-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #1e2b58 0%, #F59E0B 100%);
    border-radius: 24px 24px 0 0;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.bento-card { position: relative; overflow: hidden; }
.bento-card:hover::before { opacity: 1; }

/* Tabla Maestra: hover de fila con token institucional */
#tablaMaestra tbody tr.sgp-tabla-fila {
    transition: background 0.12s ease;
}
#tablaMaestra tbody tr.sgp-tabla-fila:hover {
    background: rgba(30,43,88,0.025) !important;
}
#tablaMaestra thead tr th.sgp-th:hover {
    background: rgba(30,43,88,0.04) !important;
}

/* Selector de mes: usa el accent dorado al hover */
.sgp-mes-nav a:hover { color: #1e2b58 !important; }

/* Chips de filtros activos: azul navy institucional */
#activeFiltersBar span {
    background: rgba(30,43,88,0.06) !important;
    color: #1e2b58 !important;
    border-color: rgba(30,43,88,0.12) !important;
}

/* Keyframe para el toast de exportación */
@keyframes sgpFadeIn {
    from { opacity: 0; transform: translateX(20px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* ===== FIX #4: CSS Design Tokens — Color System Unificado ===== */
:root {
    /* Success (verde único) */
    --sgp-success:        #10b981;
    --sgp-success-bg:     #f0fdf4;
    --sgp-success-border: #d1fae5;
    --sgp-success-text:   #166534;
    /* Danger */
    --sgp-danger:         #ef4444;
    --sgp-danger-bg:      #fef2f2;
    --sgp-danger-border:  #fee2e2;
    --sgp-danger-text:    #991b1b;
    /* Warning */
    --sgp-warning:        #f59e0b;
    --sgp-warning-bg:     #fffbeb;
    --sgp-warning-border: #fef3c7;
    --sgp-warning-text:   #92400e;
    /* Neutrales */
    --sgp-border:         #f1f5f9;
    --sgp-surface:        #f8fafc;
    --sgp-text-primary:   #0f172a;
    --sgp-text-secondary: #475569;
    --sgp-text-muted:     #94a3b8;
    /* Tipografía (escala de 4) */
    --sgp-t-heading:  1.05rem;
    --sgp-t-body:     0.875rem;
    --sgp-t-small:    0.75rem;
    --sgp-t-micro:    0.7rem;
}

/* ===== FIX #5: Grid Responsivo con clamp() ===== */
.bento-grid {
    display: grid !important;
    gap: 20px !important;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)) !important;
    align-items: start !important;
}

/* ===== Hover Premium Bento Card (Linear / Vercel style) ===== */
.bento-card {
    min-height: 200px;
    transition: transform 0.25s cubic-bezier(0.16,1,0.3,1),
                box-shadow 0.25s cubic-bezier(0.16,1,0.3,1),
                border-color 0.25s ease !important;
}
.bento-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 20px 48px rgba(15,23,42,0.09), 0 4px 12px rgba(15,23,42,0.04) !important;
    border-color: rgba(59,130,246,0.18) !important;
}

/* ===== Tipografía coherente en el Bento ===== */
.bento-card h4 { font-size: var(--sgp-t-heading); }
.bento-card .text-muted { font-size: var(--sgp-t-small); }
.bento-card span[style*="0.7rem"] { font-size: var(--sgp-t-micro) !important; }

/* ===== Mini-bars limpias ===== */
.sgp-bar {
    border-radius: 10px;
    transform-origin: bottom;
}
.sgp-sparkline:hover .sgp-bar {
    opacity: 1 !important;
    filter: brightness(1.1);
}
</style>

<!-- ===== FASE 5: LOGICA SAAS SUPERIOR (NATIVE JS) ===== -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Motor de Búsqueda Elite (Vanilla JS) sin librerías pesadas
    function setupLiveSearch(inputID, containerID, itemClass, noResultsID) {
        const input = document.getElementById(inputID);
        const container = document.getElementById(containerID);
        const noResults = document.getElementById(noResultsID);
        if(!input || !container) return;
        
        const items = container.querySelectorAll(itemClass);
        input.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            let count = 0;
            items.forEach(item => {
                const searchData = item.getAttribute('data-search') || '';
                if(searchData.includes(term)) {
                    item.style.display = 'block';
                    // Re-trigger visual feedback skeleton
                    item.classList.remove('stagger-enter');
                    void item.offsetWidth; 
                    if(term !== '') {
                        item.classList.add('stagger-enter');
                        item.style.animationDelay = '0ms';
                    }
                    count++;
                } else {
                    item.style.display = 'none';
                }
            });
            if(count === 0 && term !== '') noResults.classList.remove('d-none');
            else noResults.classList.add('d-none');
        });
    }

    setupLiveSearch('buscadorBento', 'bentoContainer', '.bento-item', 'bentoNoResults');
    setupLiveSearch('buscadorTablaTotal', 'muroContainer', '.muro-item', 'muroNoResults');

    // 2. Intersection Observer para Gauges (Efecto Satisfactorio de Llenado Mágico)
    const gaugeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Dispara el llenado desde CSS al cruzar el target
                const targetPct = entry.target.style.getPropertyValue('--target-pct');
                if(targetPct) {
                    // Timeout sutil para dar ese efecto visual de "delay" natural de Apple
                    setTimeout(() => {
                        entry.target.style.setProperty('--pct', targetPct);
                        entry.target.classList.add('gauge-animated');
                    }, 150);
                }
                gaugeObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 }); // Se dispara al ver el 10% del gauge

    document.querySelectorAll('.observer-animate').forEach(el => gaugeObserver.observe(el));
    
    // 3. Render ApexCharts Donut in Mensual View if container exists
    const donutContainer = document.querySelector('#healthDonutChart');
    if (donutContainer) {
        var options = {
            series: [<?= $totalAsistencias ?? 0 ?>, <?= $totalFaltasGlobal ?? 0 ?>, <?= $totalJustificadosGlobal ?? 0 ?>],
            labels: ['Presentes', 'Faltas', 'Justificados'],
            colors: ['#10b981', '#ef4444', '#f59e0b'],
            chart: {
                type: 'donut',
                height: 120, // Adjusted to fit Hero Box
                width: 120,
                sparkline: { enabled: true } // Creates a clean gauge-like donut without paddings
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: { show: false }
                    }
                }
            },
            dataLabels: { enabled: false },
            tooltip: {
                enabled: true,
                theme: 'light',
                y: { formatter: function(val) { return val + " Registros" } }
            },
            stroke: { width: 0 }
        };
        
        var chart = new ApexCharts(donutContainer, options);
        chart.render();
    }

    // ===== SGP: Filtros del Bento Box =====
    function applyBentoFilters() {
        const q     = (document.getElementById('bentoBuscador')?.value  || '').toLowerCase().trim();
        const depto = (document.getElementById('filtroDepto')?.value    || '').toLowerCase().trim();
        const est   = (document.getElementById('filtroEstado')?.value   || '').toLowerCase().trim();
        let visible = 0;

        document.querySelectorAll('#bentoContainer .bento-item').forEach(item => {
            const search    = (item.getAttribute('data-search')       || '').toLowerCase();
            const iDepto    = (item.getAttribute('data-depto')       || '').toLowerCase();
            const iFaltas   = item.getAttribute('data-tiene-faltas') === 'true';

            const mQ     = !q     || search.includes(q);
            const mDepto = !depto || iDepto === depto;
            const mEst   = !est   || (est === 'faltas' && iFaltas) || (est === 'perfectos' && !iFaltas);

            const show = mQ && mDepto && mEst;
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const noR = document.getElementById('bentoNoResults');
        if (noR) noR.classList.toggle('d-none', visible > 0 || (!q && !depto && !est));

        // Chips de filtros activos
        const bar = document.getElementById('activeFiltersBar');
        if (!bar) return;
        bar.innerHTML = '';
        const chip = (icon, txt, clearId) =>
            `<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:50px;background:#eff6ff;color:#1d4ed8;font-size:0.78rem;font-weight:600;border:1px solid #bfdbfe;">
                <i class="ti ${icon}"></i>${txt}
                <span onclick="sgpClearFilter('${clearId}')" style="cursor:pointer;opacity:0.5;font-size:0.85rem;">✕</span>
            </span>`;
        if (q)     bar.innerHTML += chip('ti-search',   `"${q}"`,                          'bentoBuscador');
        if (depto) bar.innerHTML += chip('ti-building', depto,                             'filtroDepto');
        if (est)   bar.innerHTML += chip('ti-filter',   est==='faltas'?'Con Faltas':'Sin Faltas','filtroEstado');
    }

    ['bentoBuscador','filtroDepto','filtroEstado'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener(id === 'bentoBuscador' ? 'input' : 'change', applyBentoFilters);
    });

    // ===== BARRAS CHART: Índice de Salud Global (Estilo Mini-Bars CSS) =====
    (function() {
        const areaEl = document.getElementById('areaHealthChart');
        if (!areaEl || typeof ApexCharts === 'undefined') return;

        const semaCartas   = <?= $chartSemanasJson ?>;
        const semaPcts     = <?= $chartPctsJson ?>;
        const healthTarget = <?= (int)$healthIndex ?>;

        const areaChart = new ApexCharts(areaEl, {
            chart: {
                type: 'bar',
                height: 168,
                sparkline: { enabled: false },
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 900 },
                background: 'transparent',
            },
            series: [{ name: 'Asistencia', data: semaPcts }],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '25%', // Ajusta el grosor de las barras
                    dataLabels: { position: 'top' },
                }
            },
            xaxis: {
                categories: semaCartas,
                labels: { style: { colors: '#94a3b8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } },
                axisBorder: { show: false },
                axisTicks:  { show: false },
            },
            yaxis: {
                min: 0, max: 100,
                labels: { formatter: v => v + '%', style: { colors: '#94a3b8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } },
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4,
                padding: { left: 4, right: 4, top: 0, bottom: 0 }
            },
            fill: {
                type: 'solid',
                colors: ['#3b82f6'] // Color sólido azul eléctrico
            },
            tooltip: {
                theme: 'light',
                y: { formatter: val => val !== null ? val + '%' : 'Sin datos' },
                style: { fontSize: '12px', fontFamily: 'Inter, sans-serif' }
            },
            dataLabels: { 
                enabled: true,
                formatter: function (val) { return val + "%"; },
                offsetY: -20,
                style: { fontSize: '10px', colors: ["#64748b"] }
            },
        });
        areaChart.render();

        // Contador animado del HealthIndex
        const counterEl = document.getElementById('healthCounterDisplay');
        if (counterEl) {
            let current = 0;
            const step  = Math.ceil(healthTarget / 40);
            const timer = setInterval(() => {
                current = Math.min(current + step, healthTarget);
                counterEl.textContent = current + '%';
                if (current >= healthTarget) clearInterval(timer);
            }, 30);
        }
    })();

    // ===== SGP: Mini-bars (IntersectionObserver) =====
    const sgpSparkObs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.querySelectorAll('.sgp-bar').forEach((bar, idx) => {
                const h = bar.getAttribute('data-h') || 50;
                setTimeout(() => { bar.style.height = h + '%'; }, idx * 55);
            });
            sgpSparkObs.unobserve(entry.target);
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.sgp-sparkline').forEach(el => sgpSparkObs.observe(el));

    // ===== SGP: Chevron collapse =====
    document.querySelectorAll('.sgp-collapse-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const chev = this.querySelector('.sgp-chevron');
            if (!chev) return;
            const open = this.getAttribute('aria-expanded') === 'true';
            chev.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
        });
    });

    // ===== TABLA MAESTRA: Sorting por columna =====
    let sgpSortCol = -1, sgpSortAsc = true;

    document.querySelectorAll('#tablaMaestra .sgp-th').forEach(th => {
        th.addEventListener('click', function() {
            const col = parseInt(this.getAttribute('data-col'));
            if (sgpSortCol === col) { sgpSortAsc = !sgpSortAsc; }
            else { sgpSortCol = col; sgpSortAsc = true; }

            // Actualizar flechas en headers
            document.querySelectorAll('#tablaMaestra .sgp-th').forEach(h => {
                const ico = h.querySelector('i');
                if (ico) ico.className = 'ti ti-selector text-muted';
                h.style.color = '#374151';
            });
            const ico = this.querySelector('i');
            if (ico) ico.className = sgpSortAsc ? 'ti ti-sort-ascending text-primary' : 'ti ti-sort-descending text-primary';
            this.style.color = '#3b82f6';

            const tbody = document.getElementById('tablaMaestraBody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('.sgp-tabla-fila'));

            rows.sort((a, b) => {
                const tdA = a.querySelectorAll('td')[col];
                const tdB = b.querySelectorAll('td')[col];
                // Columnas numéricas (2,3,4,5): usar data-val
                if ([2,3,4,5].includes(col)) {
                    const va = parseFloat(tdA?.getAttribute('data-val') ?? 0);
                    const vb = parseFloat(tdB?.getAttribute('data-val') ?? 0);
                    return sgpSortAsc ? va - vb : vb - va;
                }
                // Columnas texto (0,1)
                const ta = tdA?.textContent?.trim().toLowerCase() ?? '';
                const tb = tdB?.textContent?.trim().toLowerCase() ?? '';
                return sgpSortAsc ? ta.localeCompare(tb, 'es') : tb.localeCompare(ta, 'es');
            });

            rows.forEach(r => tbody.appendChild(r));
        });

        // Hover visual
        th.addEventListener('mouseover', () => { th.style.background = '#f1f5f9'; });
        th.addEventListener('mouseout',  () => { th.style.background = ''; });
    });

    // ===== TABLA MAESTRA: Búsqueda Live =====
    const tablaBuscador = document.getElementById('tablaMaestaBuscador');
    if (tablaBuscador) {
        tablaBuscador.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('#tablaMaestraBody .sgp-tabla-fila').forEach(row => {
                const s = (row.getAttribute('data-search') || '').toLowerCase();
                const show = !q || s.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const cnt = document.getElementById('tablaMaestraCount');
            if (cnt) cnt.textContent = visible + ' registro' + (visible !== 1 ? 's' : '') + (q ? ' encontrados' : '');
        });
    }

    // ===== TABLA MAESTRA: Progress bars animadas (IntersectionObserver) =====
    const sgpProgressObs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.querySelectorAll('.sgp-progress-bar').forEach(bar => {
                const w = bar.getAttribute('data-w') || 0;
                bar.style.width = w + '%';
            });
            sgpProgressObs.unobserve(entry.target);
        });
    }, { threshold: 0.1 });

    const tablaEl = document.getElementById('tablaMaestra');
    if (tablaEl) sgpProgressObs.observe(tablaEl);

});



// ===== SGP: Helpers globales =====
function sgpClearFilter(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = '';
    el.dispatchEvent(new Event(el.tagName === 'SELECT' ? 'change' : 'input'));
}

function sgpToggleMore(btn, deptId) {
    const card = btn.closest('.sgp-bc');
    if (!card) return;
    const hiddenRows = card.querySelectorAll('.sgp-bc-hidden-row');
    const isExpanded = btn.getAttribute('data-expanded') === 'true';

    hiddenRows.forEach(r => {
        r.style.display = isExpanded ? 'none' : 'grid';
    });
    
    btn.setAttribute('data-expanded', !isExpanded);
    const count = hiddenRows.length;
    btn.innerHTML = !isExpanded 
        ? `Ver menos <i class="ti ti-chevron-up" style="margin-left:4px;"></i>` 
        : `Ver ${count} pasante${count > 1 ? 's' : ''} más <i class="ti ti-chevron-down" style="transition:transform 0.3s;margin-left:4px;"></i>`;
}

function exportarBentoCSV() {
    let csv = 'Departamento,Pasante,Presentes,Faltas,Justificados\n';
    document.querySelectorAll('#bentoContainer .bento-item').forEach(item => {
        if (item.style.display === 'none') return;
        const dep = item.querySelector('h4')?.textContent?.trim() || '';
        item.querySelectorAll('[title*="pres"]').forEach(row => {
            const nom = row.querySelector('span')?.textContent?.trim() || '';
            const t   = row.getAttribute('title') || '';
            const [p,f,j] = [
                (t.match(/(\d+) pres/) || [,0])[1],
                (t.match(/(\d+) faltas/) || [,0])[1],
                (t.match(/(\d+) just/)  || [,0])[1]
            ];
            csv += `"${dep}","${nom}",${p},${f},${j}\n`;
        });
    });
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8;'})),
        download: `asistencias_${new Date().toISOString().slice(0,7)}.csv`
    });
    a.click();
}

function exportarDeptoCSV(depto) {
    let csv = 'Departamento,Pasante,Presentes,Faltas,Justificados\n';
    document.querySelectorAll('#bentoContainer .bento-item').forEach(item => {
        if ((item.getAttribute('data-depto') || '').toLowerCase() !== depto.toLowerCase()) return;
        item.querySelectorAll('[title*="pres"]').forEach(row => {
            const nom = row.querySelector('span')?.textContent?.trim() || '';
            const t   = row.getAttribute('title') || '';
            const [p,f,j] = [
                (t.match(/(\d+) pres/) || [,0])[1],
                (t.match(/(\d+) faltas/) || [,0])[1],
                (t.match(/(\d+) just/)  || [,0])[1]
            ];
            csv += `"${depto}","${nom}",${p},${f},${j}\n`;
        });
    });
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8;'})),
        download: `${depto.replace(/\s+/g,'_')}_${new Date().toISOString().slice(0,7)}.csv`
    });
    a.click();
}

function exportarTablaMaestraCSV() {
    let csv = 'Departamento,Pasante,C.I.,Presentes,Faltas,Justificados,% Asistencia,Estado\n';
    let count = 0;
    document.querySelectorAll('#tablaMaestraBody .sgp-tabla-fila').forEach(row => {
        if (row.style.display === 'none') return;
        const tds  = row.querySelectorAll('td');
        // td[0] = Pasante (nombre + CI), td[1] = Depto, td[2]=Pres, td[3]=Faltas, td[4]=Just, td[5]=%, td[6]=Estado
        const nombre = tds[0]?.querySelector('.fw-semibold')?.textContent?.trim() ?? '';
        const ci     = tds[0]?.querySelector('[style*="0.72rem"]')?.textContent?.replace('C.I.','').trim() ?? '';
        const depto  = tds[1]?.textContent?.trim() ?? '';
        const pres   = tds[2]?.getAttribute('data-val') ?? '';
        const falt   = tds[3]?.getAttribute('data-val') ?? '';
        const just   = tds[4]?.getAttribute('data-val') ?? '';
        const pct    = tds[5]?.getAttribute('data-val') ?? '';
        const estado = tds[6]?.textContent?.trim() ?? '';
        csv += `"${depto}","${nombre}","${ci}",${pres},${falt},${just},${pct}%,"${estado}"\n`;
        count++;
    });
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'})),
        download: `tabla_maestra_${new Date().toISOString().slice(0,7)}.csv`
    });
    a.click();
    // Toast de confirmación
    const toast = Object.assign(document.createElement('div'), {
        innerHTML: `<i class="ti ti-check me-2"></i>CSV exportado — ${count} registros`,
        style: 'position:fixed;bottom:24px;right:24px;z-index:9999;background:#0f172a;color:white;padding:12px 20px;border-radius:12px;font-size:0.85rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.2);display:flex;align-items:center;gap:4px;animation:sgpFadeIn 0.3s ease;'
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function sgpAlertarDepto(depto, faltas) {
    const msg = `El departamento de ${depto} presenta ${faltas} faltas recientes. ¿Desea enviar una notificación directa al coordinador?`;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Atención Requerida',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#f1f5f9',
            cancelButtonText: '<span style="color:#475569">Cancelar</span>',
            confirmButtonText: '<i class="ti ti-send me-1"></i> Sí, enviar alerta',
            customClass: { confirmButton: 'btn fw-bold', cancelButton: 'btn fw-bold' }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: '¡Alerta Enviada!', text: 'El coordinador ha sido notificado exitosamente.', icon: 'success', confirmButtonColor: '#10b981' });
            }
        });
    } else {
        if(confirm(msg)) alert('Alerta enviada exitosamente al coordinador.');
    }
}

// Inicializar badges e interfaces (guard: solo si estamos en vista mensual)
document.addEventListener("DOMContentLoaded", function() {
    if (typeof window.actualizarVistaRetardos === 'function') window.actualizarVistaRetardos();
    if (typeof window.actualizarVistaTop      === 'function') window.actualizarVistaTop();

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        if (typeof bootstrap !== 'undefined') return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

