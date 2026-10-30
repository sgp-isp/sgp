

<!-- ===== MODAL DE ASIGNACIÓN (Gold Standard) ===== -->
<div id="modalAsignacion" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <i class="ti ti-link"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalTitulo">Nueva Asignación</h2>
                    <p class="modal-subtitle" id="modalSubtitulo">Asignar pasante a un tutor y departamento</p>
                </div>
            </div>
            <button class="modal-close" onclick="cerrarModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="modal-body-scroll">
            <form id="formAsignacion" novalidate onsubmit="submitAsignacion(event)">
                <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
                <input type="hidden" id="modalPasanteId" name="pasante_id">

                <!-- Pasante (Buscador AJAX + Bento Box) -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                        <i class="ti ti-user-search" style="margin-right: 6px;"></i>Buscar Pasante (Sin Asignar) *
                    </label>
                    <div style="position: relative;" id="contenedorBuscadorPasante">
                        <i class="ti ti-search" style="position: absolute; left: 14px; top: 14px; color: #94a3b8; font-size: 1.1rem; pointer-events: none;"></i>
                        <input type="text" id="inputBuscarPasanteAJAX" class="input-modern" placeholder="Cédula o Apellidos..." autocomplete="off" style="padding-left: 42px;">
                        <!-- Lista de sugerencias AJAX -->
                        <div id="listaSugerenciasAjax" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #e2e8f0; border-radius:12px; margin-top:8px; box-shadow:0 10px 25px rgba(0,0,0,0.1); max-height:220px; overflow-y:auto; z-index:1000;"></div>
                    </div>

                    <!-- Bento Box ReadOnly (Oculto al inicio) -->
                    <div id="bentoPasanteSeleccionado" style="display: none; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 12px; transition: all 0.3s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div id="bentoAvatar" style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);"></div>
                                <div>
                                    <h4 id="bentoNombre" style="margin: 0; color: #1e293b; font-size: 1rem; font-weight: 800;">—</h4>
                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">C.I: <span id="bentoCedula" style="font-weight: 700; color: #475569;">—</span></div>
                                </div>
                            </div>
                            <button type="button" onclick="cancelarPasanteSeleccionado()" style="background: white; border: 1px solid #fee2e2; color: #ef4444; cursor: pointer; font-size: 0.75rem; font-weight: 600; padding: 6px 10px; display: flex; align-items: center; gap: 4px; border-radius: 8px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.05);" onmouseover="this.style.background='#fee2e2'; this.style.borderColor='#fca5a5';" onmouseout="this.style.background='white'; this.style.borderColor='#fee2e2';">
                                <i class="ti ti-exchange"></i> Cambiar
                            </button>
                        </div>
                        <div style="background: #eff6ff; padding: 12px 14px; border-radius: 8px; border: 1px dashed #bfdbfe;">
                            <div style="font-size: 0.65rem; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Institución de Procedencia</div>
                            <div id="bentoInstitucion" style="font-size: 0.85rem; color: #1e3a8a; font-weight: 700;">—</div>
                        </div>
                    </div>
                </div>

                <!-- Tutor -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                        <i class="ti ti-school" style="margin-right: 6px;"></i>Tutor Asignado *
                    </label>
                    <select name="tutor_id" id="selectTutor" required class="input-modern">
                        <option value="">Selecciona un tutor o administrador...</option>
                        <?php foreach ($tutores as $t): ?>
                        <option value="<?= (int)$t->id ?>">
                            [<?= htmlspecialchars($t->rol_nombre ?? 'Tutor') ?>]
                            <?= htmlspecialchars(($t->apellidos ?? '') . ', ' . ($t->nombres ?? '')) ?>
                            <?php if (!empty($t->departamento_nombre)): ?> — <?= htmlspecialchars($t->departamento_nombre) ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Departamento -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                        <i class="ti ti-building-community" style="margin-right: 6px;"></i>Departamento *
                    </label>
                    <select name="departamento_id" id="selectDepartamento" required class="input-modern">
                        <option value="">Selecciona un departamento...</option>
                        <?php foreach ($departamentos as $dept): ?>
                        <option value="<?= (int)$dept->id ?>"><?= htmlspecialchars($dept->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Periodo Académico -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                        <i class="ti ti-calendar-stats" style="margin-right: 6px;"></i>Periodo Académico *
                    </label>
                    <select name="periodo_id" id="selectPeriodo" required class="input-modern">
                        <option value="">Selecciona un periodo...</option>
                        <?php foreach ($periodos as $per):
                            // Horas meta estándar institucional (Regular: 1440h / Corto: 480h)
                            $pHorasMeta = ($per->tipo === 'Corto') ? 480 : 1440;
                        ?>
                        <option value="<?= (int)$per->id ?>"
                            <?= $per->estado === 'Activo' ? 'selected' : '' ?>
                            data-inicio="<?= htmlspecialchars($per->fecha_inicio ?? '') ?>"
                            data-fin="<?= htmlspecialchars($per->fecha_fin ?? '') ?>"
                            data-horas-meta="<?= $pHorasMeta ?>"
                            style="color: <?= $per->estado === 'Activo' ? '#16a34a' : ($per->estado === 'Cerrado' ? '#94a3b8' : '#d97706') ?>">
                            <?= htmlspecialchars($per->nombre) ?> (<?= htmlspecialchars($per->estado) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($periodos)): ?>
                    <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 10px; padding: 12px 14px; margin-top: 10px;">
                        <p style="margin: 0; color: #92400e; font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i class="ti ti-alert-triangle" style="font-size: 1.1rem; color: #d97706;"></i>
                            No hay períodos académicos activos o planificados disponibles.
                        </p>
                        <p style="margin: 4px 0 0; color: #b45309; font-size: 0.78rem;">
                            No es posible asignar un pasante sin vincularlo a un período regular o corto activo.
                        </p>
                        <a href="<?= URLROOT ?>/periodos" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; margin-top: 8px; font-size: 0.78rem; font-weight: 700; color: #2563eb; text-decoration: underline;">
                            <i class="ti ti-calendar-plus"></i> Ir a Períodos Académicos <i class="ti ti-external-link"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ===== CALCULADORA INTELIGENTE (Fusión Smart) ===== -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    
                    <!-- Horas Meta -->
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                            <i class="ti ti-target" style="margin-right: 6px;"></i>Horas Meta
                        </label>
                        <div class="horas-wrapper">
                            <input type="number" name="horas_meta" class="input-modern" id="inp-horas" value="1440" min="8" max="4000" oninput="recalcularConFeriados()">
                            <button type="button" class="btn-reset-horas" onclick="resetHoras()">
                                <i class="ti ti-refresh"></i> Recalcular por período
                            </button>
                        </div>
                    </div>

                    <!-- Jornada Diaria (Fija a 8h según instrucción) -->
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                            <i class="ti ti-clock" style="margin-right: 6px;"></i>Jornada Diaria
                        </label>
                        <div class="jornada-grid">
                            <label class="jornada-option selected" id="j-completa">
                                <input type="radio" name="jornada_h" value="8" checked>
                                <span class="jornada-icon">🕗</span>
                                <span class="jornada-label">Tiempo Completo</span>
                                <span class="jornada-sub">8 horas / día (Estándar Institucional)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Fecha de Inicio -->
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                            <i class="ti ti-calendar" style="margin-right: 6px;"></i>Fecha de Inicio *
                        </label>
                        <input type="date" name="fecha_inicio" id="inputFechaInicio" required class="input-modern" value="<?= date('Y-m-d') ?>" oninput="recalcular()">
                        <!-- Badge fecha personalizada -->
                        <div id="badgeFechaCustom" style="display:none; margin-top:8px; padding:7px 12px; background:linear-gradient(135deg,#fdf4ff,#ede9fe); border:1px solid #ddd6fe; border-radius:10px; font-size:0.78rem; color:#7c3aed; font-weight:600; align-items:center; gap:8px;">
                            <i class="ti ti-calendar-event"></i>
                            <span>Fecha personalizada — este pasante tendrá fechas distintas al período</span>
                            <button type="button" onclick="resetFechaAlPeriodo()" style="margin-left:auto;background:none;border:none;color:#7c3aed;cursor:pointer;font-size:0.75rem;padding:0;text-decoration:underline;white-space:nowrap;">Usar fecha del período</button>
                        </div>
                    </div>

                    <!-- TARJETA DE PROYECCIÓN INTELIGENTE -->
                    <div class="proyeccion-card" id="proy-card">
                        <div class="proy-title"><i class="ti ti-sparkles"></i> Proyección de Culminación</div>
                        <div class="proy-dato"><span>📋 Meta de horas</span><strong id="p-horas">—</strong></div>
                        <div class="proy-dato"><span>⏱️ Intensidad</span><strong>8 h / día</strong></div>
                        <div class="proy-dato"><span>📆 Días hábiles (L-V)</span><strong id="p-dias">—</strong></div>
                        <div class="proy-dato"><span>🗓️ Fecha de inicio</span><strong id="p-inicio">—</strong></div>
                        <div class="proy-highlight">🎓 Culminación estimada: <span id="p-fin">—</span></div>
                    </div>

                    <input type="hidden" name="fecha_fin" id="inputFechaFin">
                    <input type="hidden" name="hora_entrada" value="08:00:00">
                    <input type="hidden" name="hora_salida" value="16:00:00">
                </div>

                <!-- Auto-Rellenado (Pasantes Tardíos) -->
                <div style="background: #fdf4ff; border: 1px solid #fbcfe8; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="auto_rellenar" value="1" checked style="margin-top: 4px; width: 18px; height: 18px; accent-color: #d946ef;">
                        <div>
                            <span style="font-weight: 700; color: #86198f; font-size: 0.95rem; display: block; margin-bottom: 4px;">
                                <i class="ti ti-wand"></i> Botón Mágico: Auto-Rellenar Historial
                            </span>
                            <span style="color: #a21caf; font-size: 0.8rem; line-height: 1.4; display: block;">
                                Si la Fecha de Inicio es en el pasado, el sistema rellenará automáticamente las asistencias (L-V) desde la fecha de inicio hasta hoy, saltando los fines de semana.
                            </span>
                        </div>
                    </label>
                </div>

                <!-- Observaciones -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem;">
                        <i class="ti ti-notes" style="margin-right: 6px;"></i>Observaciones
                    </label>
                    <textarea name="observaciones" rows="3" class="input-modern" placeholder="Notas adicionales sobre la asignación..." style="resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="cerrarModal()" style="flex: 1; padding: 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: white; color: #64748b; font-weight: 600; cursor: pointer; font-size: 0.9rem; transition: all 0.2s;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardar"
                        style="flex: 2; padding: 14px; background: linear-gradient(135deg, #172554 0%, #1e3a8a 100%); border: none; border-radius: 12px; color: white; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.95rem; transition: all 0.2s;">
                        <i class="ti ti-check"></i> Confirmar Asignación
                    </button>
                </div>
                <div id="btnContenedorDescartarModal" style="text-align: center; margin-top: 14px; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
                    <button type="button" onclick="descartarDesdeModalAsignacion()" style="background: none; border: none; color: #ef4444; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; transition: background 0.15s;" onmouseover="this.style.background='#fef2f2';" onmouseout="this.style.background='none';">
                        <i class="ti ti-user-x"></i> Descartar solicitud de este pasante (No institucional)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL DETALLE DE ASIGNACIÓN ===== -->
<div id="modalDetalleAsignacion" class="modal">
    <div class="modal-content" style="max-width: 500px; border-radius: 20px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon" style="background: rgba(255,255,255,0.1); color: #fff;">
                    <i class="ti ti-id-badge"></i>
                </div>
                <div>
                    <h2 class="modal-title">Detalle de Asignación</h2>
                    <p class="modal-subtitle">Ficha Técnica Operativa</p>
                </div>
            </div>
            <button class="modal-close" onclick="cerrarModalDetalle()">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="modal-body-scroll" id="cuerpoDetalleAsignacion" style="position: relative; background: #fff;">
            <!-- Loading -->
            <div id="loadingDetalle" style="text-align: center; padding: 40px 0;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p style="color: #64748b; font-size: 0.9rem; margin-top: 12px; font-weight: 500;">Cargando expediente...</p>
            </div>
            
            <!-- Content -->
            <div id="contenidoDetalle" style="display: none;">
                <!-- Perfil -->
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div id="detAvatar" style="width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #3b82f6, #60a5fa); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                        —
                    </div>
                    <div>
                        <h3 id="detNombre" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #1e293b;">—</h3>
                        <p style="margin: 2px 0 0; font-size: 0.9rem; color: #64748b; font-weight: 600;">C.I: <span id="detCedula">—</span></p>
                    </div>
                    <div style="margin-left: auto;">
                        <span id="detEstadoBadge" style="padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">—</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Departamento</div>
                        <div id="detDepartamento" style="font-size: 0.9rem; font-weight: 700; color: #0f172a; margin-top: 6px;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tutor Asignado</div>
                        <div id="detTutor" style="font-size: 0.9rem; font-weight: 700; color: #0f172a; margin-top: 6px;">—</div>
                    </div>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 20px;" id="detProgresoContainer">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span id="detHorasEtiqueta" style="font-size: 0.85rem; font-weight: 700; color: #166534;"><i class="ti ti-chart-bar"></i> Progreso (Metas)</span>
                        <span id="detHorasLabel" style="font-size: 0.85rem; font-weight: 800; color: #15803d;">0 / 1440 hrs</span>
                    </div>
                    <div style="height: 10px; background: rgba(255,255,255,0.6); border-radius: 5px; overflow: hidden; margin-bottom: 8px;">
                        <div id="detBarraProgreso" style="height: 100%; width: 0%; background: #16a34a; transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 5px;"></div>
                    </div>
                    <p style="margin: 0; font-size: 0.75rem; color: #166534; text-align: right; font-weight: 600;" id="detPorcentajeLabel">0% Completado</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <span style="color: #64748b; font-weight: 600; font-size: 0.72rem; display: block; margin-bottom: 4px; text-transform: uppercase;">Fecha Inicio Pasantía</span>
                        <span id="detFechaInicio" style="color: #1e293b; font-weight: 700; font-size: 0.9rem;"><i class="ti ti-calendar" style="color: #3b82f6;"></i> —</span>
                    </div>
                    <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <span style="color: #64748b; font-weight: 600; font-size: 0.72rem; display: block; margin-bottom: 4px; text-transform: uppercase;">Vencimiento Est.</span>
                        <span id="detFechaFin" style="color: #1e293b; font-weight: 700; font-size: 0.9rem;"><i class="ti ti-calendar-event" style="color: #f59e0b;"></i> —</span>
                    </div>
                </div>

                <!-- Observaciones en detalle -->
                <div id="detObservacionesContainer" style="margin-top: 16px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 14px; display: none;">
                    <div style="font-size: 0.72rem; color: #92400e; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Observaciones</div>
                    <div id="detObservaciones" style="font-size: 0.85rem; color: #78350f; line-height: 1.5; font-style: italic;">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Total de períodos activos/planificados disponibles ──────
window.SGP_TOTAL_PERIODOS_DISPONIBLES = <?= count($periodos ?? []) ?>;

// ── Modal ──────────────────────────────────────────────────
window.abrirModalAsignacion = function() {
    if (window.SGP_TOTAL_PERIODOS_DISPONIBLES === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                iconColor: '#f59e0b',
                title: 'Sin Período Académico Disponible',
                html: `
                    <div style="text-align: left; padding: 4px 6px;">
                        <p style="color: #64748b; font-size: 0.92rem; margin: 0 0 12px; line-height: 1.5;">
                            No es posible crear una asignación en este momento porque <strong>todos los períodos académicos están cerrados</strong> o no existe ningún período en el sistema.
                        </p>
                        <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 12px 16px; color: #92400e; font-size: 0.84rem; display: flex; gap: 10px; align-items: flex-start;">
                            <i class="ti ti-info-circle" style="font-size: 1.2rem; color: #d97706; flex-shrink: 0; margin-top: 2px;"></i>
                            <div>
                                Para asignar departamento, tutor y registrar asistencias, primero debes <strong>activar o crear un período</strong> en el módulo de Períodos Académicos.
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-calendar-plus"></i> Ir a Períodos Académicos',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= URLROOT ?>/periodos';
                }
            });
        } else {
            alert('No hay períodos académicos activos o planificados disponibles. Por favor, crea o activa un período primero.');
        }
        return;
    }

    document.getElementById('formAsignacion').reset();
    document.getElementById('modalPasanteId').value = '';
    document.getElementById('modalTitulo').textContent = 'Nueva Asignación';
    document.getElementById('modalSubtitulo').textContent = 'Asignar pasante a un tutor y departamento';

    // Resetear Buscador y Bento Box
    document.getElementById('bentoPasanteSeleccionado').style.display = 'none';
    const inputBuscar = document.getElementById('inputBuscarPasanteAJAX');
    if (inputBuscar) inputBuscar.value = '';
    const contBuscar = document.getElementById('contenedorBuscadorPasante');
    if (contBuscar) contBuscar.style.display = 'block';
    const listaSug = document.getElementById('listaSugerenciasAjax');
    if (listaSug) listaSug.style.display = 'none';

    document.getElementById('modalAsignacion').classList.add('active');
    document.body.style.overflow = 'hidden';
    if (window.initBuscadorPasante) window.initBuscadorPasante();

    // Reinicializar plugins primero (antes de sobreescribir valores)
    if (window.SGPChoices) {
        window.SGPChoices.reinit('#selectTutor');
        window.SGPChoices.reinit('#selectDepartamento');
        window.SGPChoices.reinit('#selectPeriodo');
    }

    // Aplicar datos del período activo seleccionado (después de reinit plugins)
    const selPer = document.getElementById('selectPeriodo');
    const optPer = selPer ? selPer.options[selPer.selectedIndex] : null;
    if (optPer && optPer.value) {
        const fI = optPer.getAttribute('data-inicio');
        const fF = optPer.getAttribute('data-fin');
        if (fI && fF) {
            const horasMeta = parseInt(optPer.getAttribute('data-horas-meta')) || 1440;
            document.getElementById('inp-horas').value = horasMeta;
            document.getElementById('inputFechaInicio').value = fI.substring(0, 10);
        } else {
            document.getElementById('inputFechaInicio').value = new Date().toISOString().split('T')[0];
        }
    } else {
        document.getElementById('inputFechaInicio').value = new Date().toISOString().split('T')[0];
    }

    // Reinicializar Flatpickr después de establecer el valor de fecha
    if (window.SGPFlatpickr) {
        window.SGPFlatpickr.reinit('#inputFechaInicio');
    }

    // Disparar cálculo final
    recalcularConFeriados();
}

// ── Función auxiliar para normalizar fechas a YYYY-MM-DD ──────────────────
function _normalizarFecha(val) {
    if (!val) return '';
    val = String(val).trim();
    if (val.includes('/')) {
        const parts = val.split('/');
        if (parts.length === 3) {
            if (parts[2].length === 4) {
                return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
            } else if (parts[0].length === 4) {
                return `${parts[0]}-${parts[1].padStart(2, '0')}-${parts[2].padStart(2, '0')}`;
            }
        }
    }
    return val.substring(0, 10);
}

// ── Auto-calcular horas_meta y fecha_inicio al cambiar período ────────
document.getElementById('selectPeriodo').addEventListener('change', function() {
    const opt    = this.options[this.selectedIndex];
    const fInicio = opt.getAttribute('data-inicio');
    const fFin    = opt.getAttribute('data-fin');

    if (fInicio && fFin) {
        const horasMeta = parseInt(opt.getAttribute('data-horas-meta')) || 1440;
        document.getElementById('inp-horas').value = horasMeta;
        document.getElementById('inputFechaInicio').value = fInicio.substring(0, 10);
        if (document.getElementById('inputFechaInicio')._flatpickr) {
            document.getElementById('inputFechaInicio')._flatpickr.setDate(fInicio.substring(0, 10), true);
        }
    }
    // Ocultar badge al cambiar período (la fecha vuelve a ser la del período)
    const badge = document.getElementById('badgeFechaCustom');
    if (badge) badge.style.display = 'none';
    recalcularConFeriados();
});

// ── Lógica de Cálculo de 180 Días Hábiles (Calculadora Inteligente Fusionada) ───
window.resetHoras = function() {
    const selPer = document.getElementById('selectPeriodo');
    const opt    = selPer ? selPer.options[selPer.selectedIndex] : null;
    if (opt && opt.value) {
        const fI = opt.getAttribute('data-inicio');
        const fF = opt.getAttribute('data-fin');
        if (fI && fF) {
            const horasMeta = parseInt(opt.getAttribute('data-horas-meta')) || 1440;
            document.getElementById('inp-horas').value = horasMeta;
            document.getElementById('inputFechaInicio').value = fI.substring(0, 10);
            if (document.getElementById('inputFechaInicio')._flatpickr) {
                document.getElementById('inputFechaInicio')._flatpickr.setDate(fI.substring(0, 10), true);
            }
            recalcularConFeriados();
            return;
        }
    }
    document.getElementById('inp-horas').value = 1440;
    recalcularConFeriados();
}

window.recalcular = function() {
    const startInput = document.getElementById('inputFechaInicio');
    const endInput = document.getElementById('inputFechaFin');
    const horasInput = document.getElementById('inp-horas');
    const card = document.getElementById('proy-card');
    
    if (!startInput.value || !horasInput.value) {
        if (card) card.classList.remove('visible');
        return;
    }

    const selPer = document.getElementById('selectPeriodo');
    const optPer = selPer ? selPer.options[selPer.selectedIndex] : null;
    if (optPer && optPer.value) {
        const pInicio = _normalizarFecha(optPer.getAttribute('data-inicio'));
        const pFin    = _normalizarFecha(optPer.getAttribute('data-fin'));
        const fInicio = _normalizarFecha(startInput.value);
        if (pInicio && pFin && fInicio === pInicio) {
            if (endInput) endInput.value = pFin;
            const fmt = { day:'2-digit', month:'long', year:'numeric' };
            const startDate = new Date(pInicio + 'T12:00:00');
            const endDate   = new Date(pFin + 'T12:00:00');
            const horas = parseInt(horasInput.value) || 1440;
            const diasMeta = Math.ceil(horas / 8);
            if (document.getElementById('p-horas')) document.getElementById('p-horas').textContent  = horas.toLocaleString() + ' horas';
            if (document.getElementById('p-dias')) document.getElementById('p-dias').textContent   = diasMeta.toLocaleString() + ' días hábiles';
            if (document.getElementById('p-inicio')) document.getElementById('p-inicio').textContent = startDate.toLocaleDateString('es-VE', fmt);
            if (document.getElementById('p-fin')) document.getElementById('p-fin').textContent    = endDate.toLocaleDateString('es-VE', fmt);
            if (card) card.classList.add('visible');
            syncObservacionesAuto();
            return;
        }
    }

    const jornadaH = 8; // Fijo a 8h por instrucción
    const horas = parseInt(horasInput.value);
    const diasMeta = Math.ceil(horas / jornadaH);
    
    const fInicioNorm = _normalizarFecha(startInput.value);
    let startDate = new Date(fInicioNorm + 'T12:00:00'); // Usar mediodía para evitar problemas de zona horaria
    let businessDaysCount = 0;
    let currentDate = new Date(startDate);

    // Motor Matemático: Bucle para saltar Sábados y Domingos (sin postergar por feriados)
    while (businessDaysCount < diasMeta) {
        let dayOfWeek = currentDate.getDay(); // 0 es Domingo, 6 es Sábado
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            businessDaysCount++;
            if (businessDaysCount >= diasMeta) break;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
    
    const formattedDate = currentDate.toISOString().split('T')[0];
    if (endInput) endInput.value = formattedDate;

    // Actualizar Card de Proyección
    const fmt = { day:'2-digit', month:'long', year:'numeric' };
    document.getElementById('p-horas').textContent  = horas.toLocaleString() + ' horas';
    document.getElementById('p-dias').textContent   = diasMeta.toLocaleString() + ' días hábiles';
    document.getElementById('p-inicio').textContent = startDate.toLocaleDateString('es-VE', fmt);
    document.getElementById('p-fin').textContent    = currentDate.toLocaleDateString('es-VE', fmt);
    
    if (card) card.classList.add('visible');

    // Sincronizar observaciones automáticas (Botón Mágico)
    syncObservacionesAuto();
}

window.syncObservacionesAuto = function() {
    const chkAuto = document.querySelector('input[name="auto_rellenar"]');
    const fInicio = document.getElementById('inputFechaInicio').value;
    const txtObs  = document.querySelector('textarea[name="observaciones"]');
    if (!chkAuto || !fInicio || !txtObs) return;

    const hoy = new Date().toISOString().split('T')[0];
    const motivoInstitucional = "Ingreso tardío por trámites administrativos";

    if (chkAuto.checked && fInicio < hoy) {
        // Solo rellenar si está vacío o tiene el mismo motivo previo
        if (txtObs.value.trim() === '' || txtObs.value === motivoInstitucional) {
            txtObs.value = motivoInstitucional;
        }
    } else if (txtObs.value === motivoInstitucional) {
        // Limpiar si se desmarca o la fecha ya no es pasada y el texto no ha sido editado
        txtObs.value = '';
    }
}

// Escuchar cambios en el checkbox de auto-rellenado
document.querySelector('input[name="auto_rellenar"]')?.addEventListener('change', window.syncObservacionesAuto);

// ── Fecha personalizada: badge + recálculo con feriados desde servidor ──
// [SGP-FIX-PJAX-REDECL] var en vez de let: este script se re-inyecta sin
// reload completo (PJAX) y let/const de nivel superior revientan con
// "already declared" en la segunda visita a la página. var sí se puede
// re-declarar sin error.
var _periodoFechaInicio = null; // fecha_inicio del período seleccionado

function _getPeriodoFechaInicio() {
    const opt = document.getElementById('selectPeriodo')?.options[document.getElementById('selectPeriodo').selectedIndex];
    return _normalizarFecha(opt?.getAttribute('data-inicio')) || null;
}

function _actualizarBadgeFechaCustom() {
    const badge  = document.getElementById('badgeFechaCustom');
    const actual = _normalizarFecha(document.getElementById('inputFechaInicio')?.value);
    const base   = _getPeriodoFechaInicio();
    if (!badge) return;
    const esCustom = base && actual && actual !== base;
    badge.style.display = esCustom ? 'flex' : 'none';
}

window.resetFechaAlPeriodo = function() {
    const base = _getPeriodoFechaInicio();
    if (!base) return;
    const inp = document.getElementById('inputFechaInicio');
    if (inp) {
        inp.value = base;
        if (inp._flatpickr) {
            inp._flatpickr.setDate(base, true);
        }
    }
    _actualizarBadgeFechaCustom();
    recalcularConFeriados();
};

// Recalcular usando el endpoint del servidor (o la fecha fin oficial si coincide con el período)
window.recalcularConFeriados = async function() {
    const rawInicio = document.getElementById('inputFechaInicio')?.value;
    const inicio    = _normalizarFecha(rawInicio);
    const horas     = parseInt(document.getElementById('inp-horas')?.value) || 1440;
    if (!inicio) return;

    _actualizarBadgeFechaCustom();

    const selPer = document.getElementById('selectPeriodo');
    const optPer = selPer ? selPer.options[selPer.selectedIndex] : null;
    if (optPer && optPer.value) {
        const pInicio = _normalizarFecha(optPer.getAttribute('data-inicio'));
        const pFin    = _normalizarFecha(optPer.getAttribute('data-fin'));
        if (pInicio && pFin && inicio === pInicio) {
            document.getElementById('inputFechaFin').value = pFin;
            const fmt = { day:'2-digit', month:'long', year:'numeric' };
            const startDate = new Date(pInicio + 'T12:00:00');
            const endDate   = new Date(pFin + 'T12:00:00');
            const diasMeta  = Math.ceil(horas / 8);
            if (document.getElementById('p-horas')) document.getElementById('p-horas').textContent  = horas.toLocaleString() + ' horas';
            if (document.getElementById('p-dias')) document.getElementById('p-dias').textContent   = diasMeta.toLocaleString() + ' días hábiles';
            if (document.getElementById('p-inicio')) document.getElementById('p-inicio').textContent = startDate.toLocaleDateString('es-VE', fmt);
            if (document.getElementById('p-fin')) document.getElementById('p-fin').textContent    = endDate.toLocaleDateString('es-VE', fmt);
            document.getElementById('proy-card')?.classList.add('visible');
            syncObservacionesAuto();
            return;
        }
    }

    try {
        const r = await fetch(`<?= URLROOT ?>/periodos/calcularFechaFin?inicio=${inicio}&horas=${horas}`);
        const d = await r.json();
        if (d.success) {
            document.getElementById('inputFechaFin').value = d.fecha_fin;
            // Actualizar card de proyección
            const fmt = { day:'2-digit', month:'long', year:'numeric' };
            const startDate = new Date(inicio + 'T12:00:00');
            const endDate   = new Date(d.fecha_fin + 'T12:00:00');
            document.getElementById('p-horas')?.textContent  != null && (document.getElementById('p-horas').textContent  = horas.toLocaleString() + ' horas');
            document.getElementById('p-dias')?.textContent   != null && (document.getElementById('p-dias').textContent   = d.dias_lv.toLocaleString() + ' días hábiles');
            document.getElementById('p-inicio')?.textContent != null && (document.getElementById('p-inicio').textContent = startDate.toLocaleDateString('es-VE', fmt));
            document.getElementById('p-fin')?.textContent    != null && (document.getElementById('p-fin').textContent    = endDate.toLocaleDateString('es-VE', fmt));
            document.getElementById('proy-card')?.classList.add('visible');
            syncObservacionesAuto();
        }
    } catch (_) {
        recalcular(); // fallback al cálculo local
    }
};

// Escuchar cambios en la fecha de inicio
document.getElementById('inputFechaInicio')?.addEventListener('change', function() {
    _actualizarBadgeFechaCustom();
    recalcularConFeriados();
});

window.editarAsignacion = function(pasanteId, nombre, cedula, institucion = 'Registrada en sistema') {
    abrirModalAsignacion();
    document.getElementById('modalPasanteId').value = pasanteId;
    
    // Configurar Bento Box para Edición Manualmente
    document.getElementById('contenedorBuscadorPasante').style.display = 'none';
    document.getElementById('bentoNombre').innerText = nombre;
    document.getElementById('bentoCedula').innerText = cedula || 'N/A';
    document.getElementById('bentoAvatar').innerText = (nombre || 'P').substring(0,2).toUpperCase();
    document.getElementById('bentoInstitucion').innerText = institucion;
    document.getElementById('bentoPasanteSeleccionado').style.display = 'block';
    
    document.getElementById('modalTitulo').textContent = 'Editar Asignación';
    document.getElementById('modalSubtitulo').textContent = nombre;
}

window.cerrarModal = function() {
    document.getElementById('modalAsignacion').classList.remove('active');
    document.body.style.overflow = '';
}

window.descartarDesdeModalAsignacion = function() {
    const pasanteId = document.getElementById('modalPasanteId').value;
    const nombre = document.getElementById('bentoNombre').innerText;
    const cedula = document.getElementById('bentoCedula').innerText;
    if (!pasanteId) return;
    cerrarModal();
    if (typeof window.confirmarDescartarPasante === 'function') {
        window.confirmarDescartarPasante({
            getAttribute: function(attr) {
                if (attr === 'data-id') return pasanteId;
                if (attr === 'data-nombre') return nombre;
                if (attr === 'data-cedula') return cedula;
                return '';
            }
        });
    }
};

window.abrirAsignacionSegura = function(btn) {
    const id = btn.getAttribute('data-id');
    const nombre = btn.getAttribute('data-nombre');
    const cedula = btn.getAttribute('data-cedula');
    const institucion = btn.getAttribute('data-institucion');
    window.editarAsignacion(id, nombre, cedula, institucion);
};

window.confirmarDescartarPasante = function(btn) {
    const id = btn.getAttribute('data-id');
    const nombre = btn.getAttribute('data-nombre');
    const cedula = btn.getAttribute('data-cedula');

    Swal.fire({
        title: '¿Descartar este registro?',
        html: `
            <div style="text-align: left; font-size: 0.9rem; color: #334155;">
                <p style="margin-bottom: 8px;">Estás a punto de descartar la solicitud de pre-registro de:</p>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                    <div style="font-weight: 800; color: #1e293b; font-size: 1rem;">${nombre}</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">C.I: ${cedula}</div>
                </div>
                <label style="display: block; font-weight: 700; margin-bottom: 6px; font-size: 0.85rem; color: #1e293b;">
                    ¿Qué acción deseas tomar?
                </label>
                <div style="margin-bottom: 12px; font-size: 0.85rem;">
                    <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; cursor: pointer;">
                        <input type="radio" name="swal_descarte_accion" value="bloquear" checked id="radioBloquear" style="margin-top: 3px;">
                        <span><strong>Rechazar y Bloquear</strong> (Recomendado: No podrá iniciar sesión ni registrarse con esta cédula)</span>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                        <input type="radio" name="swal_descarte_accion" value="eliminar" id="radioEliminar" style="margin-top: 3px;">
                        <span><strong>Eliminar Permanentemente</strong> (Purga definitiva de la BD si era un registro spam o prueba)</span>
                    </label>
                </div>
                <div id="motivoBox" style="margin-top: 10px;">
                    <label style="display: block; font-weight: 700; margin-bottom: 4px; font-size: 0.82rem; color: #475569;">
                        Motivo del descarte:
                    </label>
                    <input type="text" id="swal_descarte_motivo" class="swal2-input" style="width: 100%; margin: 0; font-size: 0.85rem; box-sizing: border-box;" value="No figura en el listado oficial de pasantes de la institución">
                </div>
            </div>
        `,
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-check"></i> Proceder con el descarte',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const accion = document.querySelector('input[name="swal_descarte_accion"]:checked')?.value || 'bloquear';
            const motivo = document.getElementById('swal_descarte_motivo')?.value || '';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || (typeof _ADMIN_CSRF !== 'undefined' ? _ADMIN_CSRF : '');
            const formData = new FormData();
            formData.append('pasante_id', id);
            formData.append('accion', accion);
            formData.append('motivo', motivo);
            formData.append('csrf_token', csrfToken);

            return fetch(URLROOT + '/asignaciones/descartar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.message || 'Error al descartar registro');
                return res;
            })
            .catch(err => Swal.showValidationMessage(err.message));
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed && result.value?.success) {
            Swal.fire({
                icon: 'success',
                title: 'Registro Descartado',
                text: result.value.message,
                confirmButtonColor: '#2563eb'
            }).then(() => location.reload());
        }
    });
};

// Cerrar al hacer clic fuera
document.getElementById('modalAsignacion').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

// ── Enviar Formulario ──────────────────────────────────────
window.submitAsignacion = async function(e) {
    e.preventDefault();

    var btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader"></i> Guardando...';

    var fd = new FormData(document.getElementById('formAsignacion'));
    // Validación de pasante_id 
    if (!fd.get('pasante_id')) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Pasante Requerido', text: 'Por favor, busca y selecciona un pasante.', confirmButtonColor: '#162660' });
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Confirmar Asignación';
        return;
    }
    if (!fd.get('tutor_id')) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Tutor Requerido', text: 'Por favor, selecciona un tutor o administrador asignado.', confirmButtonColor: '#162660' });
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Confirmar Asignación';
        return;
    }
    if (!fd.get('departamento_id')) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Departamento Requerido', text: 'Por favor, selecciona un departamento de asignación.', confirmButtonColor: '#162660' });
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Confirmar Asignación';
        return;
    }
    if (!fd.get('periodo_id')) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Período Requerido', text: 'Por favor, selecciona un período académico.', confirmButtonColor: '#162660' });
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Confirmar Asignación';
        return;
    }

    try {
        var resp = await fetch('<?= URLROOT ?>/asignaciones/guardar', {
            method: 'POST',
            body: fd,
        });
        var json = await resp.json();

        if (json.success) {
            cerrarModal();
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Asignación Guardada!',
                    text: json.message || 'La asignación se registró correctamente.',
                    confirmButtonColor: '#162660',
                });
            }
            window.location.reload();
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message || 'No se pudo guardar.', confirmButtonColor: '#162660' });
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check"></i> Confirmar Asignación';
        }
    } catch (err) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Intenta de nuevo.', confirmButtonColor: '#162660' });
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Confirmar Asignación';
    }
}

// ── Finalizar ──────────────────────────────────────────────
window.finalizarAsignacion = function(pasanteId, nombre) {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        icon: 'warning',
        title: '¿Finalizar Pasantía?',
        html: '<p>Estás a punto de finalizar la asignación de <strong>' + nombre + '</strong>.</p>',
        showCancelButton: true,
        confirmButtonText: 'Sí, Finalizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7c3aed',
        reverseButtons: true,
    }).then(async function(result) {
        if (!result.isConfirmed) return;
        var fd = new FormData();
        fd.append('pasante_id', pasanteId);
        var csrfMetaFin = document.querySelector('meta[name="csrf-token"]');
        fd.append('_csrf', csrfMetaFin ? csrfMetaFin.getAttribute('content') : '');
        try {
            var resp = await fetch('<?= URLROOT ?>/asignaciones/finalizar', { method: 'POST', body: fd });
            var json = await resp.json();
            if (json.success) {
                await Swal.fire({ icon: 'success', title: '¡Finalizado!', text: json.message, confirmButtonColor: '#162660' });
                window.location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message, confirmButtonColor: '#162660' });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Intenta de nuevo.', confirmButtonColor: '#162660' });
        }
    });
}

// ── Buscador AJAX & Bento Box Logic ────────────────────────
window.buscarPasantesLista = function(query) {
    const listaSug = document.getElementById('listaSugerenciasAjax');
    if (!listaSug) return;

    const formData = new FormData();
    formData.append('query', query || '');
    const _esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    
    listaSug.innerHTML = '<div style="padding:14px; text-align:center; color:#64748b; font-size:0.85rem;"><i class="ti ti-loader" style="font-size:1.1rem; margin-right:6px;"></i> Cargando pasantes disponibles...</div>';
    listaSug.style.display = 'block';

    fetch('<?= URLROOT ?>/asignaciones/buscarPasanteAjax', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        listaSug.innerHTML = '';
        if (data && data.length > 0) {
            data.forEach(p => {
                const div = document.createElement('div');
                div.style.padding = '12px 16px';
                div.style.cursor = 'pointer';
                div.style.borderBottom = '1px solid #f1f5f9';
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.gap = '12px';
                div.addEventListener('mouseover', () => div.style.background = '#f8fafc');
                div.addEventListener('mouseout', () => div.style.background = 'white');

                const inis = (_esc(p.nombres.charAt(0)) + _esc(p.apellidos.charAt(0))).toUpperCase();
                div.innerHTML = `
                    <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #162660, #2563eb); display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:800; color:white; flex-shrink:0;">${inis}</div>
                    <div style="flex:1;">
                        <div style="font-size:0.9rem; font-weight:800; color:#1e293b;">${_esc(p.nombres)} ${_esc(p.apellidos)}</div>
                        <div style="font-size:0.75rem; font-weight:600; color:#64748b;">C.I: ${_esc(p.cedula)} · <span style="color:#2563eb;">${_esc(p.institucion_procedencia)}</span></div>
                    </div>
                    <div style="font-size:0.72rem; font-weight:700; color:#059669; background:#ecfdf5; padding:4px 8px; border-radius:6px; flex-shrink:0;">Sin Asignar</div>
                `;

                div.onclick = () => window.seleccionarPasante(p);
                listaSug.appendChild(div);
            });
        } else {
            listaSug.innerHTML = '<div style="padding:16px; text-align:center; color:#94a3b8; font-size:0.85rem; font-weight:600;"><i class="ti ti-user-x" style="font-size:1.4rem; display:block; margin-bottom:4px;"></i>No se encontraron pasantes pendientes por asignar</div>';
        }
        listaSug.style.display = 'block';
    })
    .catch(() => {
        listaSug.innerHTML = '<div style="padding:16px; text-align:center; color:#ef4444; font-size:0.85rem; font-weight:600;">Error al buscar pasantes</div>';
        listaSug.style.display = 'block';
    });
};

window.initBuscadorPasante = function() {
    const inputBuscar = document.getElementById('inputBuscarPasanteAJAX');
    const listaSug = document.getElementById('listaSugerenciasAjax');
    if (!inputBuscar || inputBuscar.dataset.bound === 'true') return;
    inputBuscar.dataset.bound = 'true';

    let timeoutId;
    inputBuscar.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const query = this.value.trim();
        timeoutId = setTimeout(() => {
            window.buscarPasantesLista(query);
        }, 150);
    });

    // Al hacer clic o focus en el buscador, listar los pasantes disponibles inmediatamente
    inputBuscar.addEventListener('focus', function() {
        window.buscarPasantesLista(this.value.trim());
    });
    inputBuscar.addEventListener('click', function() {
        window.buscarPasantesLista(this.value.trim());
    });
    
    document.addEventListener('click', (e) => {
        if (inputBuscar && listaSug && !inputBuscar.contains(e.target) && !listaSug.contains(e.target)) {
            listaSug.style.display = 'none';
        }
    });
};

// Inicializar de inmediato y en carga
window.initBuscadorPasante();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initBuscadorPasante);
}

window.seleccionarPasante = function(p) {
    document.getElementById('contenedorBuscadorPasante').style.display = 'none';
    document.getElementById('listaSugerenciasAjax').style.display = 'none';
    
    document.getElementById('modalPasanteId').value = p.pasante_id;
    
    document.getElementById('bentoNombre').innerText = p.nombres + ' ' + p.apellidos;
    document.getElementById('bentoCedula').innerText = p.cedula;
    document.getElementById('bentoAvatar').innerText = (p.nombres.charAt(0) + p.apellidos.charAt(0)).toUpperCase();
    document.getElementById('bentoInstitucion').innerText = p.institucion_procedencia || 'No especificada';
    
    document.getElementById('bentoPasanteSeleccionado').style.display = 'block';
}

window.cancelarPasanteSeleccionado = function() {
    document.getElementById('modalPasanteId').value = '';
    document.getElementById('bentoPasanteSeleccionado').style.display = 'none';
    document.getElementById('contenedorBuscadorPasante').style.display = 'block';
    const num = document.getElementById('inputBuscarPasanteAJAX');
    num.value = '';
    num.focus();
}

// ── Modal Detalles ──────────────────────────────────────
window.verDetalleAsignacion = async function(pasanteId) {
    const modal = document.getElementById('modalDetalleAsignacion');
    if (modal) modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    const loader = document.getElementById('loadingDetalle');
    if (loader) loader.style.display = 'block';
    
    const contenido = document.getElementById('contenidoDetalle');
    if (contenido) contenido.style.display = 'none';

    try {
        const formData = new FormData();
        formData.append('pasante_id', pasanteId);
        
        const resp = await fetch('<?= URLROOT ?>/asignaciones/getDetalleAjax', {
            method: 'POST',
            body: formData
        });
        
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        
        // Cargar datos (con optional chaining blindado)
        const nombresStr = data.nombres || '';
        const apeStr = data.apellidos ? ' ' + data.apellidos : '';
        if (document.getElementById('detNombre')) document.getElementById('detNombre').innerText = nombresStr + apeStr;
        if (document.getElementById('detCedula')) document.getElementById('detCedula').innerText = data.cedula || 'N/A';
        if (document.getElementById('detAvatar')) document.getElementById('detAvatar').innerText = (nombresStr.charAt(0) + (data.apellidos ? data.apellidos.charAt(0) : '')).toUpperCase();
        
        if (document.getElementById('detDepartamento')) document.getElementById('detDepartamento').innerText = data.departamento_nombre || 'No asignado';
        if (document.getElementById('detTutor')) document.getElementById('detTutor').innerText = data.tutor_nombres ? (data.tutor_nombres + ' ' + (data.tutor_apellidos||'')) : 'No asignado';
        
        if (document.getElementById('detFechaInicio'))            document.getElementById('detFechaInicio').innerHTML = `<i class="ti ti-calendar" style="color: #3b82f6;"></i> ${formatearFecha(data.fecha_inicio_pasantia)}`;
            if (document.getElementById('detFechaFin')) document.getElementById('detFechaFin').innerHTML    = `<i class="ti ti-calendar-event" style="color: #f59e0b;"></i> ${formatearFecha(data.fecha_fin_estimada)}`;

            // Observaciones
            const obsCont = document.getElementById('detObservacionesContainer');
            const obsText = document.getElementById('detObservaciones');
            if (obsCont && obsText) { // Added null checks for obsCont and obsText
                if (data.observaciones && data.observaciones.trim() !== '') {
                    obsText.textContent = data.observaciones;
                    obsCont.style.display = 'block';
                } else {
                    obsCont.style.display = 'none';
                }
            }
        
        // Progreso
        const hrAcum = parseInt(data.horas_acumuladas) || 0;
        const hrMeta = parseInt(data.horas_meta) || 1440;
        const pct = hrMeta > 0 ? Math.min(100, Math.round((hrAcum / hrMeta) * 100)) : 0;
        
        if (document.getElementById('detHorasLabel')) document.getElementById('detHorasLabel').innerText = `${hrAcum} / ${hrMeta} hrs`;
        
        // Colores de alerta
        let bColor = '#10b981';
        let bgCont = '#f0fdf4';
        let bBorder = '#bbf7d0';
        let bText = '#15803d';
        
        if(pct < 50) { bColor = '#ef4444'; bgCont = '#fef2f2'; bBorder = '#fecaca'; bText = '#b91c1c'; }
        else if (pct < 80) { bColor = '#f59e0b'; bgCont = '#fffbeb'; bBorder = '#fde68a'; bText = '#b45309'; }
        
        const progresoContainer = document.getElementById('detProgresoContainer');
        if (progresoContainer) {
            progresoContainer.style.background = bgCont;
            progresoContainer.style.borderColor = bBorder;
        }
        
        const badgeHoras = document.getElementById('detHorasLabel');
        if (badgeHoras) {
            badgeHoras.style.color = bText;
        }

        const etiquetaHoras = document.getElementById('detHorasEtiqueta');
        if (etiquetaHoras) {
            etiquetaHoras.style.color = bText;
        }
        
        const porcLabel = document.getElementById('detPorcentajeLabel');
        if (porcLabel) {
            porcLabel.style.color = bText;
            porcLabel.innerText = `${pct}% Completado`;
        }
        
        // Animacion del bar
        setTimeout(() => {
            const bar = document.getElementById('detBarraProgreso');
            if (bar) {
                bar.style.width = pct + '%';
                bar.style.background = bColor;
            }
        }, 150);
        
        // Badge
        const estadoCfg = {
            'Activo': {bg: '#dcfce7', c: '#16a34a', i: 'ti-check'},
            'Pendiente': {bg: '#fed7aa', c: '#ea580c', i: 'ti-hourglass'},
            'Sin Asignar': {bg: '#fef9c3', c: '#ca8a04', i: 'ti-clock'},
            'Finalizado': {bg: '#ede9fe', c: '#7c3aed', i: 'ti-award'}
        };
        const ec = estadoCfg[data.estado_pasantia] || estadoCfg['Sin Asignar'];
        const badge = document.getElementById('detEstadoBadge');
        if (badge) {
            badge.style.background = ec.bg;
            badge.style.color = ec.c;
            badge.innerHTML = `<i class="ti ${ec.i}"></i> ${data.estado_pasantia}`;
        }
        
        if (loader) loader.style.display = 'none';
        if (contenido) contenido.style.display = 'block';
    } catch(e) {
        console.error("Error en verDetalleAsignacion:", e);
        const errorBody = document.getElementById('cuerpoDetalleAsignacion');
        if (errorBody) {
             errorBody.innerHTML = `<div style="text-align:center; padding: 30px; color:#ef4444;"><i class="ti ti-alert-triangle" style="font-size:2rem;"></i><br>Error cargando detalles.</div>`;
        }
    }
}

// ── Helpers ────────────────────────────────────────────────
window.formatearFecha = function(fecha) {
    if (!fecha) return '—';
    const parts = fecha.split('-');
    if (parts.length !== 3) return fecha;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

window.cerrarModalDetalle = function() {
    document.getElementById('modalDetalleAsignacion').classList.remove('active');
    document.body.style.overflow = '';
}

// Cerrar al hacer clic fuera del detalle
document.getElementById('modalDetalleAsignacion').addEventListener('click', function(e) {
    if (e.target === this) window.cerrarModalDetalle();
});
</script>

