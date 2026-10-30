/**
 * evaluaciones.js — Bento UI Seamless Slide
 * Lógica de transición, velocímetro en vivo y AJAX submit
 */

// [FIX-XS-1] Escape de HTML para datos de usuario insertados vía innerHTML
// (mismo patrón que notifications.js: textContent -> innerHTML escapado).
function _escHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

var EvalApp = { // [SGP-FIX-PJAX-REDECL] var: ahora execInlineScripts() sí re-ejecuta este script en cada visita por PJAX (ver sgp-pjax.js).

    currentPasante: null,

    // ── Transición: deslizar al formulario ────────────────────────────────
    abrirFormulario(p) {
        this.currentPasante = p;

        // Poblar header del formulario
        const ini = p.nombre.trim().split(/\s+/).map(w => w[0] || '').slice(0, 2).join('').toUpperCase();
        document.getElementById('fbAvatar').innerText = ini || '?';
        document.getElementById('fbNombre').innerText = p.nombre || '—';
        const metaInfo = p.tutor_nombre
            ? '<i class="ti ti-user-check"></i> ' + p.tutor_nombre
            : (p.depto || (p.institucion ? '<i class="ti ti-school"></i> ' + p.institucion : 'Sin información'));
        document.getElementById('fbMeta').innerHTML =
            '<i class="ti ti-id"></i> ' + (p.cedula || 'N/A') +
            ' &nbsp;·&nbsp; ' + metaInfo;

        // Poblar campo oculto
        document.getElementById('fPasanteId').value = p.id;
        let tutorInput = document.getElementById('fTutorId');
        if (tutorInput && p.tutor_id) {
            tutorInput.value = p.tutor_id;
        } else if (tutorInput) {
            tutorInput.value = '';
        }

        // Resetear form — fecha siempre es hoy, lapso viene del período del pasante
        document.getElementById('fFecha').value = new Date().toISOString().slice(0, 10);
        const _m = new Date().getMonth() + 1;
        const _lapsoAuto = new Date().getFullYear() + (_m <= 6 ? '-I' : '-II');
        document.getElementById('fLapso').value = (p.periodo_nombre && p.periodo_nombre.trim()) ? p.periodo_nombre.trim() : _lapsoAuto;
        // Mostrar los valores en los displays read-only
        const _dispFecha = document.getElementById('dispFecha');
        if (_dispFecha) _dispFecha.innerText = new Date().toLocaleDateString('es-VE', {day:'2-digit',month:'long',year:'numeric'});
        const _dispLapso = document.getElementById('dispLapso');
        if (_dispLapso) _dispLapso.innerText = document.getElementById('fLapso').value;
        document.getElementById('fObs').value   = '';
        
        let btnGlobal = document.getElementById('btnMarcarTodoGlobal');
        if (btnGlobal) {
            btnGlobal.dataset.marcado = '0';
            btnGlobal.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            btnGlobal.style.boxShadow = '0 4px 15px rgba(16,185,129,0.3)';
            if (btnGlobal.querySelector('span')) btnGlobal.querySelector('span').innerText = 'Excelente a Todo';
            if (btnGlobal.querySelector('i')) btnGlobal.querySelector('i').className = 'ti ti-stars';
            
            document.querySelectorAll('.cat-switch-input').forEach(chk => {
                chk.checked = false;
                let bg = chk.previousElementSibling;
                let knob = bg.querySelector('.cat-switch-knob');
                if (bg) bg.style.background = '#DDE2F0';
                if (knob) knob.style.transform = 'none';
            });
        }
        
        // Reiniciar evaluacion_id para indicar nueva
        const elId = document.getElementById('fEvaluacionId');
        if (elId) elId.value = '0';

        this._ocultarError();
        this.resetStars();
        this.updateGauge(0, 0);

        // Reset botón guardar
        const btn = document.getElementById('btnGuardar');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Guardar Evaluación';

        // Deslizar al Panel B
        document.getElementById('evalSlider').style.transform = 'translateX(-50%)';

        // Desktop: expandir contenedor para scroll natural de página (sin scroll interno)
        if (window.innerWidth > 767) {
            window.scrollTo(0, 0);
            const outer  = document.getElementById('evalOuter');
            const slider = document.getElementById('evalSlider');
            const vistaB = document.getElementById('vistaEvaluacion');
            if (outer)  { outer.style.height = 'auto'; }
            if (slider) { slider.style.height = 'auto'; slider.style.alignItems = 'flex-start'; }
            if (vistaB) { vistaB.style.overflowY = 'visible'; }
        }

        // Scroll al inicio del panel B
        setTimeout(() => {
            const vistaB = document.getElementById('vistaEvaluacion');
            if (vistaB) vistaB.scrollTop = 0;
        }, 480);
    },

    // ── Buscar pasante desde el botón "Nueva Evaluación" ─────────────────
    nuevaDesdeBoton() {
        if (typeof SGPModal !== 'undefined') {
            SGPModal.buscar({
                rol: 3,
                onSelect: (u) => {
                    this.abrirFormulario({
                        id:     u.id,
                        nombre: ((u.nombres || '') + ' ' + (u.apellidos || '')).trim(),
                        cedula: u.cedula      || '',
                        depto:  u.departamento || 'Sin asignar',
                        tutor_id: u.tutor_id || null
                    });
                }
            });
        }
    },

    // ── Transición: editar evaluación existente ──────────────────────────
    async editarEvaluacion(id) {
        try {
            const resp = await fetch(URLROOT + '/evaluaciones/obtenerDetalleAjax/' + id);
            const data = await resp.json();
            if (!data.success) {
                this._toast(data.message || 'Error al cargar evaluación', 'error');
                return;
            }
            
            const ev = data.evaluacion;
            const p = {
                id: ev.pasante_id,
                nombre: ev.pasante_nombre,
                cedula: ev.pasante_cedula,
                depto: ev.departamento || '',
                tutor_id: ev.tutor_id,
                tutor_nombre: ev.tutor_nombre || '',
                institucion: ev.institucion_nombre || ''
            };
            
            // Reutilizar la lógica de abrirFormulario para inicializar UI
            this.abrirFormulario(p);
            
            // Sobrescribir id y modo edición
            document.getElementById('fEvaluacionId').value = id;
            document.getElementById('fFecha').value = ev.fecha_evaluacion;
            const _dispFecha = document.getElementById('dispFecha');
            if (_dispFecha) {
                const parts = ev.fecha_evaluacion.split('-');
                _dispFecha.innerText = new Date(parts[0], parts[1]-1, parts[2]).toLocaleDateString('es-VE', {day:'2-digit',month:'long',year:'numeric'});
            }
            document.getElementById('fLapso').value = ev.lapso_academico || '';
            const _dispLapso = document.getElementById('dispLapso');
            if (_dispLapso) _dispLapso.innerText = ev.lapso_academico || '—';
            
            document.getElementById('fObs').value = ev.observaciones || '';
            
            // Llenar estrellas
            const criterios = {
                'criterio_iniciativa': ev.criterio_iniciativa,
                'criterio_interes': ev.criterio_interes,
                'criterio_conocimiento': ev.criterio_conocimiento,
                'criterio_analisis': ev.criterio_analisis,
                'criterio_comunicacion': ev.criterio_comunicacion,
                'criterio_aprendizaje': ev.criterio_aprendizaje,
                'criterio_companerismo': ev.criterio_companerismo,
                'criterio_cooperacion': ev.criterio_cooperacion,
                'criterio_puntualidad': ev.criterio_puntualidad,
                'criterio_presentacion': ev.criterio_presentacion,
                'criterio_desarrollo': ev.criterio_desarrollo,
                'criterio_analisis_res': ev.criterio_analisis_res,
                'criterio_conclusiones': ev.criterio_conclusiones,
                'criterio_recomendacion': ev.criterio_recomendacion
            };
            
            for (const [key, val] of Object.entries(criterios)) {
                if (val > 0) {
                    const group = document.querySelector(`.star-group[data-criterio="${key}"]`);
                    if (group) {
                        const stars = group.querySelectorAll('.star-btn');
                        if (stars[val - 1]) stars[val - 1].click();
                    }
                }
            }
            
            // Cambiar texto de botón
            const btn = document.getElementById('btnGuardar');
            if (btn) btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Evaluación';
            
        } catch (e) {
            this._toast('Error de conexión al cargar', 'error');
        }
    },

    // ── Transición: volver al dashboard ──────────────────────────────────
    volver() {
        const inputs  = Array.from(document.querySelectorAll('#formEvaluacion .star-input'));
        const hasData = inputs.some(i => parseInt(i.value) > 0);
        if (hasData && !confirm('¿Deseas volver? Los criterios calificados se perderán.')) return;
        document.getElementById('evalSlider').style.transform = 'translateX(0)';
    },

    // ── Star Ratings ──────────────────────────────────────────────────────
    initStars() {
        document.querySelectorAll('.star-group').forEach((group) => {
            const stars = Array.from(group.querySelectorAll('.star-btn'));
            const input = group.querySelector('.star-input');
            const card  = group.closest('.eval-criterio-tile');
            const color = card ? card.dataset.catColor : '#f59e0b';

            stars.forEach((star, idx) => {
                star.addEventListener('mouseenter', () => {
                    stars.forEach((s, i) => {
                        s.style.color     = i <= idx ? '#fbbf24' : '#e2e8f0';
                        s.style.transform = i === idx ? 'scale(1.3)' : 'scale(1)';
                    });
                });

                star.addEventListener('click', () => {
                    const val = idx + 1;
                    input.value = val;

                    // Highlight estrellas seleccionadas con color de categoría
                    stars.forEach((s, i) => {
                        s.style.color     = i < val ? color : '#e2e8f0';
                        s.style.transform = 'scale(1)';
                    });

                    // Marcar card como calificada: borde + checkmark badge
                    if (card) {
                        card.style.position    = 'relative';
                        card.style.borderColor = color;
                        card.style.background  = 'white';
                        let badge = card.querySelector('.check-badge');
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'check-badge';
                            badge.style.cssText = 'position:absolute;top:10px;right:10px;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:0.7rem;pointer-events:none;transition:background .3s;';
                            badge.innerHTML = '<i class="ti ti-check"></i>';
                            card.appendChild(badge);
                        }
                        badge.style.background = color;
                    }

                    // Badge de equivalencia ISP (columna PDF)
                    const EQUIV_ISP = {
                        1: { l:'Deficiente',     bg:'#fecaca', c:'#b91c1c' },
                        2: { l:'Bajo Promedio',  bg:'#fee2e2', c:'#dc2626' },
                        3: { l:'Promedio',       bg:'#fef3c7', c:'#b45309' },
                        4: { l:'Sobre Promedio', bg:'#d1fae5', c:'#059669' },
                        5: { l:'Excelente',      bg:'#dcfce7', c:'#15803d' },
                    };
                    const equivBadge = group.closest('.criterio-card, .eval-criterio-tile')?.querySelector('.equiv-badge-isp');
                    if (equivBadge && EQUIV_ISP[val]) {
                        const e = EQUIV_ISP[val];
                        equivBadge.textContent      = e.l;
                        equivBadge.style.background = e.bg;
                        equivBadge.style.color      = e.c;
                        equivBadge.style.display    = 'inline-block';
                    }

                    this._calcularYActualizar();
                });
            });

            group.addEventListener('mouseleave', () => {
                const val = parseInt(input.value) || 0;
                stars.forEach((s, i) => {
                    s.style.color     = i < val ? color : '#e2e8f0';
                    s.style.transform = 'scale(1)';
                });
            });
        });
    },

    // ── Novedad: Toggle Global de Estrellas ──
    toggleMarcarTodo(btn) {
        if (btn.dataset.marcado === '1') {
            document.querySelectorAll('.cat-switch-input').forEach(chk => {
                chk.checked = false;
                this.toggleCategoria(chk);
            });
            btn.dataset.marcado = '0';
            btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            btn.style.boxShadow = '0 4px 15px rgba(16,185,129,0.3)';
            if (btn.querySelector('span')) btn.querySelector('span').innerText = 'Excelente a Todo';
            if (btn.querySelector('i')) btn.querySelector('i').className = 'ti ti-stars';
        } else {
            document.querySelectorAll('.cat-switch-input').forEach(chk => {
                chk.checked = true;
                this.toggleCategoria(chk);
            });
            btn.dataset.marcado = '1';
            btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            btn.style.boxShadow = '0 4px 15px rgba(239, 68, 68, 0.3)';
            if (btn.querySelector('span')) btn.querySelector('span').innerText = 'Desmarcar Todo';
            if (btn.querySelector('i')) btn.querySelector('i').className = 'ti ti-eraser';
            /* En móvil: expandir todas las categorías al marcar todo */
            document.querySelectorAll('.eval-cat-wrapper .row.g-3').forEach(row => {
                row.style.display = '';
            });
            document.querySelectorAll('.eval-cat-wrapper .ti-chevron-down').forEach(chev => {
                chev.style.transform = 'rotate(0deg)';
            });
        }
    },

    toggleCategoria(checkbox) {
        let bg = checkbox.previousElementSibling;
        let knob = bg.querySelector('.cat-switch-knob');
        if (checkbox.checked) {
            bg.style.background = '#10b981';
            knob.style.transform = 'translateX(14px)';
        } else {
            bg.style.background = '#DDE2F0';
            knob.style.transform = 'none';
        }
        let container = checkbox.closest('.eval-cat-wrapper');
        if (container) {
            container.querySelectorAll('.eval-criterio-tile').forEach(card => {
                const btns = card.querySelectorAll('.star-btn');
                const input = card.querySelector('.star-input');
                if (checkbox.checked && btns.length >= 5) {
                    btns[4].click();
                } else if (!checkbox.checked) {
                    if (input) input.value = '0';
                    btns.forEach(s => {
                        s.style.color = '#e2e8f0';
                        s.style.transform = 'scale(1)';
                    });
                    card.style.borderColor = '#DDE2F0';
                    card.style.background = 'white';
                    const badge = card.querySelector('.check-badge');
                    if (badge) badge.remove();
                }
            });
            if (!checkbox.checked) {
                this._calcularYActualizar();
            }
        }
    },

    resetStars() {
        document.querySelectorAll('.star-group').forEach((group) => {
            group.querySelector('.star-input').value = '0';
            group.querySelectorAll('.star-btn').forEach(s => {
                s.style.color     = '#e2e8f0';
                s.style.transform = 'scale(1)';
            });
            // Ocultar badge de equivalencia ISP
            const equivBadge = group.closest('.criterio-card, .eval-criterio-tile')?.querySelector('.equiv-badge-isp');
            if (equivBadge) equivBadge.style.display = 'none';
        });
        document.querySelectorAll('.eval-criterio-tile').forEach(card => {
            card.style.borderColor = '#DDE2F0';
            card.style.background  = 'white';
            const badge = card.querySelector('.check-badge');
            if (badge) badge.remove();
        });
    },

    _calcularYActualizar() {
        const inputs = Array.from(document.querySelectorAll('#formEvaluacion .star-input'));
        const vals   = inputs.map(i => parseInt(i.value) || 0);
        const filled = vals.filter(v => v > 0).length;
        const suma   = vals.reduce((a, b) => a + b, 0);
        const total  = (typeof TOTAL_CRITERIOS !== 'undefined') ? TOTAL_CRITERIOS : 14;
        const avg    = filled > 0 ? suma / total : 0; // siempre sobre total

        this.updateGauge(avg, filled);
    },

    // ── Anillo de progreso en vivo ────────────────────────────────────────
    updateGauge(avg, filled) {
        const pct  = avg / 5;
        const _tot = (typeof TOTAL_CRITERIOS !== 'undefined') ? TOTAL_CRITERIOS : 14;

        const labels = [[4,'#10b981','Excelente'],[3,'#f59e0b','Bueno'],[2,'#f97316','Regular'],[0,'#ef4444','Deficiente']];
        const match  = filled > 0 ? labels.find(l => avg >= l[0]) : null;
        const color  = match ? match[1] : '#e2e8f0';
        const ltext  = match ? match[2] : 'Sin calificar';
        const lcolor = match ? match[1] : '#94a3b8';
        const display = filled > 0 ? avg.toFixed(2) : '—';

        // ── Desktop: gauge ring ──
        const arc = document.getElementById('gaugeArc');
        if (arc) {
            arc.setAttribute('stroke-dasharray', (pct * 100).toFixed(1) + ' 100');
            arc.setAttribute('stroke', color);
        }
        const valEl = document.getElementById('gaugeValue');
        const denomEl = document.getElementById('gaugeDenom');
        const lblEl   = document.getElementById('gaugeLabel');
        if (valEl)   { valEl.innerText = display; valEl.style.color = filled > 0 ? color : '#162660'; }
        if (denomEl) denomEl.style.display = filled > 0 ? 'inline' : 'none';
        if (lblEl)   { lblEl.innerText = ltext; lblEl.style.color = lcolor; }
        const countEl    = document.getElementById('gaugeCount');
        const progressEl = document.getElementById('gaugeProgress');
        if (countEl)    countEl.innerText = filled + ' / ' + _tot;
        if (progressEl) progressEl.style.width = ((filled / _tot) * 100).toFixed(0) + '%';

        // ── Mobile: mini donut ──
        const mArc  = document.getElementById('mobGaugeArc');
        const mVal  = document.getElementById('mobGaugeVal');
        const mDen  = document.getElementById('mobGaugeDenom');
        const mLbl  = document.getElementById('mobGaugeLabel');
        const mCnt  = document.getElementById('mobGaugeCount');
        const mBar  = document.getElementById('mobGaugeProgress');
        const barGrad = {'#10b981':'linear-gradient(90deg,#10b981,#34d399)','#f59e0b':'linear-gradient(90deg,#f59e0b,#fcd34d)','#f97316':'linear-gradient(90deg,#f97316,#fb923c)','#ef4444':'linear-gradient(90deg,#ef4444,#f87171)','#e2e8f0':'#e2e8f0'};
        if (mArc) { mArc.setAttribute('stroke-dasharray', (pct * 100).toFixed(1) + ' 100'); mArc.setAttribute('stroke', color); }
        if (mVal)  { mVal.innerText = display; mVal.style.color = filled > 0 ? color : '#162660'; }
        if (mDen)  mDen.style.display = filled > 0 ? 'inline' : 'none';
        if (mLbl)  { mLbl.innerText = ltext; mLbl.style.color = lcolor; mLbl.style.background = filled > 0 ? (color + '22') : '#f1f5f9'; }
        if (mCnt)  mCnt.innerText = filled + '/' + _tot;
        if (mBar)  { mBar.style.width = ((filled / _tot) * 100).toFixed(0) + '%'; mBar.style.background = barGrad[color] || color; }
    },

    // ── AJAX Submit ───────────────────────────────────────────────────────
    async guardar() {
        const form = document.getElementById('formEvaluacion');
        const btn  = document.getElementById('btnGuardar');

        // Validar tutor
        if (!form.querySelector('[name="tutor_id"]').value) {
            this._mostrarError('Selecciona un tutor evaluador.');
            return;
        }

        // Validar todos los criterios
        const inputs = Array.from(form.querySelectorAll('.star-input'));
        const sinCalificar = inputs.filter(i => !i.value || i.value === '0');
        if (sinCalificar.length > 0) {
            this._mostrarError('Debes calificar los ' + sinCalificar.length + ' criterio(s) restantes.');
            // Resaltar criterios vacíos
            sinCalificar.forEach(inp => {
                const card = document.querySelector('[data-criterio="' + inp.name + '"]')?.closest('.eval-criterio-tile');
                if (card) {
                    card.style.borderColor = '#fca5a5';
                    card.style.animation   = 'shake .3s ease';
                    setTimeout(() => { card.style.animation = ''; }, 400);
                }
            });
            // Scroll al primer criterio vacío
            const firstCard = sinCalificar[0];
            const grp = document.querySelector('[data-criterio="' + firstCard.name + '"]');
            if (grp) grp.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        this._ocultarError();
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader" style="animation:spin 1s linear infinite;display:inline-block;"></i> Guardando...';

        const fd = new FormData(form); // incluye _csrf del campo hidden en el form
        const isEdit = parseInt(fd.get('evaluacion_id') || '0') > 0;
        const endpoint = isEdit ? '/evaluaciones/actualizar' : '/evaluaciones/guardar';

        try {
            const resp = await fetch(URLROOT + endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await resp.json();

            if (data.success) {
                // Volver al panel A directo (sin Swal de confirmación)
                const slider = document.getElementById('evalSlider');
                if (slider) slider.style.transform = 'translateX(0)';
                const vistaEval = document.getElementById('vistaEvaluacion');
                if (vistaEval) vistaEval.classList.remove('ev-panel-visible');
                const saveBar = document.querySelector('.ev-save-bar');
                if (saveBar) saveBar.classList.remove('ev-save-active');
                document.body.style.overflow = '';
                this._restaurarPanelA();

                // Calcular promedio para mostrarlo en el modal
                const starInputs = Array.from(document.querySelectorAll('#formEvaluacion .star-input'));
                const vals = starInputs.map(i => parseFloat(i.value) || 0).filter(v => v > 0);
                const prom = vals.length ? (vals.reduce((a,b) => a+b, 0) / vals.length).toFixed(2) : '—';
                const promNum = parseFloat(prom);
                const promColor = promNum >= 4 ? '#10b981' : promNum >= 3 ? '#f59e0b' : '#ef4444';
                const promLabel = promNum >= 4 ? 'Excelente' : promNum >= 3 ? 'Bueno' : promNum >= 2 ? 'Regular' : 'Deficiente';
                const pasanteNom = (this.currentPasante && this.currentPasante.nombre) ? this.currentPasante.nombre : '';
                const tituloSwal = isEdit ? '¡Evaluación Actualizada!' : '¡Evaluación Registrada!';
                const msjSwal = isEdit 
                    ? 'Los cambios se han guardado correctamente en el historial.' 
                    : 'La evaluación quedó registrada correctamente<br>en el historial del pasante.';

                // SweetAlert2 Bento premium — éxito
                Swal.fire({
                    title: tituloSwal,
                    html: `
                        <div style="text-align:center;padding:4px 0 8px;">
                            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#dcfce7,#bbf7d0);margin-bottom:14px;">
                                <i class="ti ti-clipboard-check" style="font-size:2rem;color:#16a34a;"></i>
                            </div>
                            ${pasanteNom ? `<p style="font-weight:700;color:#0f172a;font-size:0.95rem;margin:0 0 12px;">${pasanteNom}</p>` : ''}
                            <div style="display:inline-flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:10px 20px;margin-bottom:4px;">
                                <span style="font-size:2rem;font-weight:900;color:${promColor};line-height:1;">${prom}</span>
                                <div style="text-align:left;">
                                    <div style="font-size:0.65rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Promedio Final</div>
                                    <div style="font-size:0.78rem;font-weight:800;color:${promColor};">${promLabel}</div>
                                </div>
                            </div>
                            <p style="color:#64748b;font-size:0.8rem;margin:12px 0 0;line-height:1.5;">${msjSwal}</p>
                        </div>`,
                    icon: false,
                    showConfirmButton: true,
                    confirmButtonText: '<i class="ti ti-arrow-right"></i> Ver Historial',
                    confirmButtonColor: '#1D4ED8',
                    showCancelButton: false,
                    customClass: {
                        popup:         'swal-eval-popup swal-eval-success',
                        confirmButton: 'swal-eval-btn-confirm',
                    },
                    didOpen: () => {
                        // Animación de entrada del ícono
                        const iconEl = Swal.getPopup().querySelector('.ti-clipboard-check');
                        if (iconEl) { iconEl.style.transition = 'transform .4s cubic-bezier(.34,1.56,.64,1)'; iconEl.style.transform = 'scale(0)'; setTimeout(() => { iconEl.style.transform = 'scale(1)'; }, 80); }
                    }
                }).then(() => {
                    window.location.reload();
                });
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-check"></i> Guardar Evaluación';
                this._mostrarError(data.message || 'Error al guardar.');
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check"></i> Guardar Evaluación';
            this._mostrarError('Error de conexión. Intenta de nuevo.');
        }
    },

    // ── Eliminar evaluación ───────────────────────────────────────────────
    async eliminarEvaluacion(id, nombrePasante) {
        const result = await Swal.fire({
            title: '¿Eliminar evaluación?',
            html: `<p style="color:#475569;font-size:0.9rem;margin:0;">Se eliminará definitivamente la evaluación de <strong>${nombrePasante || 'este pasante'}</strong>. Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            iconColor: '#ef4444',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
            cancelButtonText: '<i class="ti ti-x"></i> Cancelar',
            reverseButtons: true,
            customClass: {
                popup:         'swal-eval-popup',
                confirmButton: 'swal-eval-btn-confirm',
                cancelButton:  'swal-eval-btn-cancel',
            }
        });

        if (!result.isConfirmed) return;

        try {
            const fd = new FormData();
            fd.append('evaluacion_id', id);
            fd.append('_csrf', document.querySelector('input[name="_csrf"]')?.value || '');

            const resp = await fetch(URLROOT + '/evaluaciones/eliminar', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await resp.json();

            if (data.success) {
                this._toast('Evaluación eliminada correctamente', 'success');
                setTimeout(() => window.location.reload(), 900);
            } else {
                this._toast(data.message || 'Error al eliminar', 'error');
            }
        } catch (e) {
            this._toast('Error de conexión. Intenta de nuevo.', 'error');
        }
    },

    // ── Ajustar altura de #evalOuter al espacio real disponible ─────────
    _fitEvalOuter() {
        const outer = document.getElementById('evalOuter');
        if (!outer || window.innerWidth <= 767) return;
        const top = outer.getBoundingClientRect().top;
        outer.style.height = Math.max(window.innerHeight - top - 16, 300) + 'px';
    },

    // ── Restaurar Panel A (modo listas con scroll interno) ───────────────
    _restaurarPanelA() {
        if (window.innerWidth <= 767) return;
        const outer  = document.getElementById('evalOuter');
        const slider = document.getElementById('evalSlider');
        const vistaB = document.getElementById('vistaEvaluacion');
        if (outer)  { outer.style.height = ''; }
        if (slider) { slider.style.height = '100%'; slider.style.alignItems = ''; }
        if (vistaB) { vistaB.style.overflowY = ''; }
        this._fitEvalOuter();
    },

    // ── Helpers internos ──────────────────────────────────────────────────
    _mostrarError(msg) {
        const el  = document.getElementById('panelError');
        const txt = document.getElementById('panelErrorTxt');
        if (el && txt) {
            txt.innerText     = msg;
            el.style.display  = 'flex';
        }
    },

    _ocultarError() {
        const el = document.getElementById('panelError');
        if (el) el.style.display = 'none';
    },

    _toast(msg, type) {
        if (typeof SGPToast !== 'undefined') { SGPToast.show(msg, type); return; }
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:13px 18px;border-radius:12px;font-weight:600;font-size:0.88rem;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.15);';
        t.style.background = type === 'success' ? '#10b981' : '#ef4444';
        t.innerHTML = (type === 'success' ? '<i class="ti ti-check"></i> ' : '<i class="ti ti-alert-triangle"></i> ') + msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.transition = 'opacity .3s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 350); }, 3000);
    },

    // ── Init ──────────────────────────────────────────────────────────────
    init() {
        this.initStars();
        if (window.innerWidth > 767) {
            this._fitEvalOuter();
            window.addEventListener('resize', () => this._fitEvalOuter());
        }
    },
};

// ── Modal detalle (read-only) ─────────────────────────────────────────────────
function cerrarModalDetalle() {
    document.getElementById('modalDetalleEval').classList.remove('active');
    document.body.style.overflow = '';
}
function _sgpBindModalDetalleEval() { // [SGP-FIX-PJAX-DOMCONTENTLOADED]
    const m = document.getElementById('modalDetalleEval');
    if (m) m.addEventListener('click', function(e) { if (e.target === this) cerrarModalDetalle(); });
}
if (document.readyState !== 'loading') {
    _sgpBindModalDetalleEval();
} else {
    document.addEventListener('DOMContentLoaded', _sgpBindModalDetalleEval);
}

async function verEvaluacion(id) {
    const modal = document.getElementById('modalDetalleEval');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    const body      = document.getElementById('bodyDetalleEval');
    const subtitulo = document.getElementById('detalleEvalSubtitulo');
    body.innerHTML  = '<div style="text-align:center;padding:40px;"><i class="ti ti-loader sgp-spin" style="font-size:2rem;color:#1e3a8a;"></i></div>';

    try {
        const resp = await fetch(URLROOT + '/evaluaciones/obtenerDetalleAjax/' + id);
        const data = await resp.json();

        if (!data.success) {
            body.innerHTML = '<div style="text-align:center;padding:40px;color:#dc2626;">' + data.message + '</div>';
            return;
        }

        const ev = data.evaluacion;
        subtitulo.innerText = 'Pasante: ' + ev.pasante_nombre + ' · ' + ev.fecha_formateada;

        const cats = [
            { label: 'Actitudes y Comportamiento', color: '#3b82f6', items: [
                { label: 'Iniciativa',   icon: 'ti-bulb',  val: ev.criterio_iniciativa || 0 },
                { label: 'Interés',      icon: 'ti-heart', val: ev.criterio_interes    || 0 },
            ]},
            { label: 'Competencias Técnicas', color: '#059669', items: [
                { label: 'Conocimiento', icon: 'ti-book',         val: ev.criterio_conocimiento || 0 },
                { label: 'Análisis',     icon: 'ti-brain',        val: ev.criterio_analisis     || 0 },
                { label: 'Comunicación', icon: 'ti-message-dots', val: ev.criterio_comunicacion || 0 },
                { label: 'Aprendizaje',  icon: 'ti-school',       val: ev.criterio_aprendizaje  || 0 },
            ]},
            { label: 'Valores e Integridad', color: '#d97706', items: [
                { label: 'Compañerismo', icon: 'ti-users',     val: ev.criterio_companerismo || 0 },
                { label: 'Cooperación',  icon: 'ti-hand-stop', val: ev.criterio_cooperacion  || 0 },
            ]},
            { label: 'Disciplina y Organización', color: '#7c3aed', items: [
                { label: 'Puntualidad',  icon: 'ti-clock', val: ev.criterio_puntualidad  || 0 },
                { label: 'Presentación', icon: 'ti-shirt', val: ev.criterio_presentacion || 0 },
            ]},
            { label: 'Desempeño Global', color: '#1e3a8a', items: [
                { label: 'Desarrollo',             icon: 'ti-code',            val: ev.criterio_desarrollo    || 0 },
                { label: 'Análisis de Resultados', icon: 'ti-chart-bar',       val: ev.criterio_analisis_res  || 0 },
                { label: 'Conclusiones',           icon: 'ti-clipboard-check', val: ev.criterio_conclusiones  || 0 },
                { label: 'Recomendaciones',        icon: 'ti-star',            val: ev.criterio_recomendacion || 0 },
            ]},
        ];

        let html = '<div style="background:linear-gradient(135deg,#eff6ff,#f5f3ff);border:2px solid #c7d2fe;border-radius:16px;padding:18px;margin-bottom:18px;text-align:center;">' +
            '<p style="margin:0 0 4px;font-size:0.75rem;color:#64748b;font-weight:700;text-transform:uppercase;">PROMEDIO FINAL</p>' +
            '<span style="font-size:2.4rem;font-weight:900;color:#162660;">' + ev.promedio + '</span>' +
            '<span style="font-size:1rem;color:#94a3b8;font-weight:700;"> / 5</span>' +
            '</div>';

        cats.forEach(cat => {
            html += '<div style="border-left:3px solid ' + cat.color + ';padding-left:10px;margin:14px 0 8px;">' +
                '<span style="font-size:0.72rem;font-weight:800;color:' + cat.color + ';letter-spacing:.5px;text-transform:uppercase;">' + cat.label + '</span>' +
                '</div><div style="display:grid;gap:6px;">';
            cat.items.forEach(it => {
                html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:9px 13px;background:#f8fafc;border-radius:10px;border:1px solid #f1f5f9;">' +
                    '<div style="display:flex;align-items:center;gap:9px;">' +
                        '<i class="ti ' + it.icon + '" style="color:' + cat.color + ';font-size:0.9rem;"></i>' +
                        '<span style="font-size:0.83rem;font-weight:600;color:#334155;">' + it.label + '</span>' +
                    '</div>' +
                    '<div style="font-size:0.95rem;letter-spacing:1px;">' +
                        '<span style="color:' + cat.color + ';">' + '★'.repeat(it.val) + '</span>' +
                        '<span style="color:#e2e8f0;">' + '★'.repeat(5 - it.val) + '</span>' +
                    '</div>' +
                '</div>';
            });
            html += '</div>';
        });

        html += '<div style="margin-top:16px;padding:14px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;">' +
            '<label style="display:block;font-weight:800;color:#92400e;font-size:0.72rem;text-transform:uppercase;margin-bottom:7px;">Observaciones del Tutor</label>' +
            '<p style="margin:0;font-size:0.88rem;color:#78350f;line-height:1.5;">' + (ev.observaciones ? _escHtml(ev.observaciones) : 'Sin observaciones registradas.') + '</p>' +
            '</div>';

        body.innerHTML = html;

    } catch (e) {
        body.innerHTML = '<div style="text-align:center;padding:40px;color:#dc2626;">Error de conexión.</div>';
    }
}

// ── Keyframe para shake ───────────────────────────────────────────────────────
(function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%,100% { transform:translateX(0); }
            20% { transform:translateX(-5px); }
            40% { transform:translateX(5px); }
            60% { transform:translateX(-4px); }
            80% { transform:translateX(3px); }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
})();

// ── Init ──────────────────────────────────────────────────────────────────────
// [SGP-FIX-PJAX-DOMCONTENTLOADED] ejecución inmediata si el DOM ya está listo: este
// script se re-ejecuta en cada visita por PJAX (ver sgp-pjax.js), pero DOMContentLoaded
// solo se dispara una vez en toda la vida de la página — con el listener de antes,
// EvalApp.init() (que engancha el clic de las estrellas) nunca se repetía.
if (document.readyState !== 'loading') {
    EvalApp.init();
} else {
    document.addEventListener('DOMContentLoaded', function() {
        EvalApp.init();
    });
}
