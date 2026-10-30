<?php
// === ADAPTADOR DE COMPATIBILIDAD ===
$user_name = Session::get('user_name') ?? Session::get('nombres') ?? Session::get('nombre') ?? 'Usuario';

// Definir Rol
$rol_id = Session::get('role_id') ?? Session::get('rol_id') ?? 0;
$role = 'Invitado';

switch ($rol_id) {
    case 1: $role = 'Administrador'; break;
    case 2: $role = 'Tutor'; break;
    case 3: $role = 'Pasante'; break;
    default: 
        $role = Session::get('rol_nombre') ?? 'Invitado';
        break;
}
?>

<div class="dashboard-bento">
    
    <?php if ($role == 'Administrador'): ?>

    <!-- ===== BANNER BENTO SGP — Dashboard General ===== -->
    <style>
    @media (max-width: 991px) {
        .dashboard-banner {
            flex-direction: column !important;
            align-items: flex-start !important;
            padding: 24px 20px !important;
            gap: 20px !important;
        }
        .dashboard-banner > div:first-child, .dashboard-banner > div:nth-child(2) {
            display: none !important; /* Ocultar decoraciones en móvil */
        }
    }
    </style>
    <div class="dashboard-banner" style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:28px 36px;margin-bottom:4px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;">
        <!-- Círculos decorativos -->
        <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
        <div style="position:absolute;bottom:-40px;right:160px;width:130px;height:130px;background:rgba(255,255,255,0.04);border-radius:50%;pointer-events:none;"></div>
        <!-- Izquierda: Título -->
        <div style="display:flex;align-items:center;gap:14px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:14px;padding:12px;">
                <i class="ti ti-layout-dashboard" style="font-size:28px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.7rem;font-weight:700;margin:0;line-height:1.1;">Panel de Control</h1>
                <p style="color:rgba(255,255,255,0.75);margin:4px 0 0;font-size:0.85rem;">
                    Bienvenido, <strong><?= htmlspecialchars(explode(' ', $user_name)[0]) ?></strong> · <?= $role ?>
                </p>
            </div>
        </div>
        <!-- Derecha: Reloj en tiempo real -->
        <div style="z-index:1;">
            <div style="background:rgba(0,0,0,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:50px;padding:10px 22px;display:flex;align-items:center;gap:18px;color:white;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-calendar" style="color:rgba(255,255,255,0.8);font-size:1rem;"></i>
                    <span id="dash-date" style="font-size:0.82rem;font-weight:500;"></span>
                </div>
                <div style="width:1px;height:20px;background:rgba(255,255,255,0.2);"></div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-clock" style="color:rgba(255,255,255,0.8);font-size:1rem;"></i>
                    <span id="dash-time" style="font-size:1rem;font-weight:700;font-variant-numeric:tabular-nums;"></span>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function(){
            function tick(){
                var n = new Date();
                var d = n.toLocaleDateString('es-ES',{weekday:'long',day:'numeric',month:'long'});
                var t = n.toLocaleTimeString('es-ES',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true});
                var de = document.getElementById('dash-date'); if(de) de.textContent = d.charAt(0).toUpperCase()+d.slice(1);
                var te = document.getElementById('dash-time'); if(te) te.textContent = t.toUpperCase();
            }
            tick(); setInterval(tick,1000);
        })();
    </script>

    <!-- Fila 1: 4 KPIs -->


    <div class="card kpi-card slide-up" style="animation-delay: 0.1s;">
        <div class="kpi-header">
            <span class="kpi-label">Pasantes Activos</span>
            <div class="kpi-icon">
                <i class="ti ti-users"></i>
            </div>
        </div>
        <div class="kpi-value" data-kpi-value="<?= $totalActivos ?? 0 ?>">0</div>
        <div class="kpi-badge success">
            <i class="ti ti-users"></i> pasantes activos
        </div>
    </div>

    <div class="card kpi-card slide-up" style="animation-delay: 0.2s;">
        <div class="kpi-header">
            <span class="kpi-label">Tutores Activos</span>
            <div class="kpi-icon">
                <i class="ti ti-school"></i>
            </div>
        </div>
        <div class="kpi-value" data-kpi-value="<?= $totalTutores ?? 0 ?>">0</div>
    </div>

    <div class="card kpi-card slide-up" style="animation-delay: 0.3s;">
        <div class="kpi-header">
            <span class="kpi-label">Departamentos</span>
            <div class="kpi-icon">
                <i class="ti ti-building-hospital"></i>
            </div>
        </div>
        <div class="kpi-value" data-kpi-value="<?= $asistenciasHoy ?? 0 ?>">0</div>
    </div>

    <div class="card kpi-card slide-up" style="animation-delay: 0.4s;">
        <div class="kpi-header">
            <span class="kpi-label">Asignaciones pendientes</span>
            <div class="kpi-icon">
                <i class="ti ti-clipboard-check"></i>
            </div>
        </div>
        <div class="kpi-value" data-kpi-value="<?= $totalInstituciones ?? 0 ?>">0</div>
        <?php if (($totalInstituciones ?? 0) == 0): ?>
        <div class="kpi-badge warning">
            <i class="ti ti-alert-circle"></i> sin asignar
        </div>
        <?php endif; ?>
    </div>

    <!-- Fila 2: Gráfico Principal + Activity Feed -->
    <div class="card span-3 row-span-2 slide-up" style="animation-delay: 0.5s;">
        <div class="card-header">
            <h3>Asistencias por Mes</h3>
            <p>Asistencias mensuales de pasantes</p>
        </div>
        <div id="chart-asistencias-mes"></div>
    </div>

    <div class="card span-1 row-span-2 slide-up" style="animation-delay: 0.6s;">
        <h3 style="color: var(--deep-azure); font-size: 1.125rem; font-weight: 600; margin-bottom: 16px;">Actividad Reciente</h3>
        <div class="activity-list">
            <?php if (!empty($actividadReciente)): ?>
                <?php foreach ($actividadReciente as $act):
                    $nombres = trim(($act->nombres ?? '') . ' ' . ($act->apellidos ?? ''));
                    if (empty(trim($nombres))) $nombres = $act->cedula ?? 'Pasante';
                    $iniciales = strtoupper(mb_substr($act->nombres ?? 'P', 0, 1) . mb_substr($act->apellidos ?? '', 0, 1));
                    $hora = !empty($act->hora_registro) ? date('H:i', strtotime($act->hora_registro)) : date('H:i');
                    $badgeClass = ($act->estado ?? '') === 'Presente' ? 'success' : (($act->estado ?? '') === 'Justificado' ? 'warning' : 'info');
                    $badgeText  = $act->estado ?? 'Check';
                ?>
                <div class="activity-item">
                    <div class="avatar-circle"><?= htmlspecialchars($iniciales) ?></div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.875rem;"><?= htmlspecialchars($nombres) ?></div>
                        <div style="color: var(--text-muted); font-size: 0.75rem;">Registró asistencia — <?= htmlspecialchars($act->fecha ?? '') ?></div>
                        <div style="color: var(--text-muted); font-size: 0.7rem; margin-top: 2px;"><?= $hora ?></div>
                    </div>
                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeText) ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:24px; color:#94a3b8;">
                    <i class="ti ti-calendar-off" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                    Sin actividad reciente
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fila 3: Donut + Tabla -->
    <div class="card span-2 slide-up" style="animation-delay: 0.7s;">
        <h3 style="color: var(--deep-azure); font-size: 1.125rem; font-weight: 600; margin-bottom: 16px;">Distribución por Departamentos</h3>
        <div id="chart-departamentos"></div>
    </div>

    <div class="card span-2 slide-up" style="animation-delay: 0.8s;">
        <h3 style="color: var(--deep-azure); font-size: 1.125rem; font-weight: 600; margin-bottom: 16px;">Últimos Registros</h3>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Pasante</th>
                    <th>Cédula</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($actividadReciente)): ?>
                    <?php foreach ($actividadReciente as $act):
                        $nombres = trim(($act->nombres ?? '') . ' ' . ($act->apellidos ?? ''));
                        if (empty(trim($nombres))) $nombres = 'Pasante';
                        $iniciales = strtoupper(mb_substr($act->nombres ?? 'P', 0, 1) . mb_substr($act->apellidos ?? '', 0, 1));
                        $estadoBadge = ($act->estado ?? '') === 'Presente' ? 'success' : (($act->estado ?? '') === 'Justificado' ? 'warning' : 'info');
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 0.75rem;"><?= htmlspecialchars($iniciales) ?></div>
                                <span><?= htmlspecialchars($nombres) ?></span>
                            </div>
                        </td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($act->cedula ?? '—') ?></td>
                        <td><span class="badge <?= $estadoBadge ?>"><?= htmlspecialchars($act->estado ?? 'N/A') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center;padding:24px;color:#94a3b8;">Sin registros recientes</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<script src="<?= URLROOT ?>/js/apexcharts.min.js"></script>
<script>
// Configuración de gráficos con paleta institucional
(function initDashboardCharts() {
    if (typeof ApexCharts === 'undefined') {
        setTimeout(initDashboardCharts, 50);
        return;
    }
    
    // Gráfico de Barras - Nuevos pasantes por Mes (datos reales de la DB)
    const datosMensualesDB = <?= isset($datosMensuales) ? json_encode(array_values($datosMensuales)) : '[0,0,0,0,0,0,0,0,0,0,0,0]' ?>;
    const barChartOptions = {
        series: [{
            name: 'Pasantes',
            data: datosMensualesDB
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: { show: false },
            fontFamily: 'Plus Jakarta Sans, sans-serif'
        },
        colors: ['#162660'],  // Deep Azure
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '60%',
                distributed: false
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.4,
                gradientToColors: ['#3B82F6'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 0.8,
                stops: [0, 100]
            }
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: '#F1F5F9',
            strokeDashArray: 4,
            padding: { top: 0, right: 0, bottom: 0, left: 10 }
        },
        xaxis: {
            categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            labels: {
                style: {
                    colors: '#94A3B8',
                    fontSize: '12px'
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#94A3B8',
                    fontSize: '12px'
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function(val) {
                    return val + ' pasantes';
                }
            }
        }
    };
    
    const barChart = new ApexCharts(document.querySelector('#chart-pasantes-mes'), barChartOptions);
    barChart.render();
    
    // Gráfico Donut - Distribución por Departamentos
    const donutChartOptions = {
        series: [35, 25, 20, 12, 8],
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'Plus Jakarta Sans, sans-serif'
        },
        colors: ['#162660', '#3B82F6', '#60A5FA', '#93C5FD', '#F59E0B'],
        labels: ['Soporte Técnico', 'Enfermería', 'Administración', 'Laboratorio', 'Otros'],
        dataLabels: {
            enabled: true,
            style: {
                fontSize: '12px',
                fontWeight: 600
            }
        },
        legend: {
            position: 'bottom',
            fontSize: '13px',
            labels: {
                colors: '#475569'
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px',
                            color: '#162660'
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 700,
                            color: '#162660'
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '14px',
                            color: '#94A3B8',
                            formatter: function(w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function(val) {
                    return val + ' pasantes';
                }
            }
        }
    };
    
    const donutChart = new ApexCharts(document.querySelector('#chart-departamentos'), donutChartOptions);
    donutChart.render();
})();

// Función de logout (mantener compatibilidad)
function confirmLogout() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: "Estás a punto de salir del sistema",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#162660',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, salir'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = '<?= URLROOT ?>/auth/logout';
    });
}
</script>
