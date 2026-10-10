/**
 * SGP — Notificaciones de escritorio (Web Notifications API)
 * Admin/SuperAdmin: asistencias nuevas + mediodía + feriados.
 * Pasante (rol 3): solo feriados próximos.
 * Se carga desde main_layout.php y corre en todas las páginas autenticadas.
 */
(function () {
    'use strict';

    var SGP_ROL = window.SGP_ROLE || 99;

    // ── Guardia: no corre si el navegador no soporta notificaciones ─────────
    if (!('Notification' in window)) return;

    var ICON         = URLROOT + '/img/notif-icon.png';
    var LS_HOLIDAY   = 'sgp_dn_holiday';
    var TODAY        = new Date().toISOString().slice(0, 10);

    // ── Solicitar permiso (solo si aún no fue decidido) ─────────────────────
    function pedirPermiso(cb) {
        if (Notification.permission === 'granted') { cb(); return; }
        if (Notification.permission === 'denied')  { return; }
        Notification.requestPermission().then(function (perm) {
            if (perm === 'granted') cb();
        });
    }

    // ── Mostrar notificación de escritorio ──────────────────────────────────
    function mostrar(titulo, cuerpo) {
        if (Notification.permission !== 'granted') return;
        var n = new Notification(titulo, { body: cuerpo, icon: ICON });
        setTimeout(function () { n.close(); }, 8000);
    }

    // ── Notificación de feriado próximo (compartida para todos los roles) ───
    function mostrarFeriado(feriado) {
        if (!feriado) return;
        if (localStorage.getItem(LS_HOLIDAY) === TODAY) return;

        var dias = parseInt(feriado.dias_restantes, 10);
        if (dias > 7) return;

        var texto = dias === 0
            ? 'Hoy es feriado: ' + feriado.nombre + '. No habrá registro de asistencia.'
            : 'En ' + dias + ' día' + (dias === 1 ? '' : 's') + ': ' + feriado.nombre +
              ' (' + formatFecha(feriado.fecha) + '). No habrá asistencia ese día.';

        mostrar('Próximo día feriado', texto);
        localStorage.setItem(LS_HOLIDAY, TODAY);
    }

    function formatFecha(f) {
        var p = f.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    // ════════════════════════════════════════════════════════════════════════
    // MODO PASANTE (rol 3) — solo notificaciones de feriado
    // ════════════════════════════════════════════════════════════════════════
    if (SGP_ROL === 3) {
        pedirPermiso(function () {
            fetch(URLROOT + '/notifications/getNextHoliday', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.success) mostrarFeriado(data.proximo_feriado);
            })
            .catch(function () { /* sin conexión — ignorar */ });
        });
        return; // Pasantes no ejecutan nada más
    }

    // ════════════════════════════════════════════════════════════════════════
    // MODO ADMIN / SUPERADMIN (rol 0 o 1)
    // ════════════════════════════════════════════════════════════════════════
    if (SGP_ROL > 1) return; // Tutores (rol 2) u otros no participan

    var POLL_INTERVAL   = 30000;
    var ENDPOINT        = URLROOT + '/notifications/getDesktopData';
    var LS_NOON_KEY     = 'sgp_dn_noon';
    var lastAsistenciaId = 0;
    var initialized      = false;
    var noonTimer        = null;
    var pollTimer        = null;

    function fetchDatos(cb) {
        var url = ENDPOINT + '?ultimo_id=' + lastAsistenciaId;
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) { if (data && data.success) cb(data); })
        .catch(function () { });
    }

    function iniciarPolling() {
        function tick() {
            fetchDatos(function (data) {
                if (!initialized) {
                    if (data.nuevas_asistencias && data.nuevas_asistencias.length > 0) {
                        lastAsistenciaId = data.nuevas_asistencias[data.nuevas_asistencias.length - 1].id;
                    }
                    initialized = true;

                    if (data.es_dia_habil) {
                        programarMediodia(data.pasantes_sin_asignar);
                        mostrarFeriado(data.proximo_feriado);
                    }
                    return;
                }

                if (!data.es_dia_habil) return;

                if (data.nuevas_asistencias && data.nuevas_asistencias.length > 0) {
                    data.nuevas_asistencias.forEach(function (a) {
                        mostrar('Asistencia registrada', a.nombre + ' — ' + a.hora);
                        if (a.id > lastAsistenciaId) lastAsistenciaId = a.id;
                    });
                }
            });
        }

        tick();
        pollTimer = setInterval(tick, POLL_INTERVAL);
    }

    function programarMediodia(sinAsignarActual) {
        if (localStorage.getItem(LS_NOON_KEY) === TODAY) return;

        var ahora    = new Date();
        var mediodia = new Date();
        mediodia.setHours(12, 0, 0, 0);
        var diff = mediodia - ahora;
        if (diff <= 0) return;

        noonTimer = setTimeout(function () {
            fetchDatos(function (data) {
                var n = data.pasantes_sin_asignar || 0;
                if (n > 0) {
                    mostrar('Pasantes sin asignar', 'Tienes ' + n + ' pasante' + (n === 1 ? '' : 's') + ' sin tutor asignado.');
                } else {
                    mostrar('Pasantes al día', 'Todos los pasantes tienen tutor asignado.');
                }
                localStorage.setItem(LS_NOON_KEY, TODAY);
            });
        }, diff);
    }

    pedirPermiso(function () {
        iniciarPolling();
    });

    window.addEventListener('beforeunload', function () {
        clearInterval(pollTimer);
        clearTimeout(noonTimer);
    });

}());
