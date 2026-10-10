<!-- JavaScript Assets (Before closing body tag) -->
<script src="<?= URLROOT ?>/js/sweetalert2.min.js"></script>
<script src="<?= URLROOT ?>/js/notyf.min.js"></script>
<script src="<?= URLROOT ?>/js/notification-service.js"></script>
<script src="<?= URLROOT ?>/js/notifications.js"></script>

<!-- Footer Institucional sobre fondo oscuro -->
<footer class="auth-institutional-footer">
    <p class="copyright" style="display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap;">
        <span>Copyleft <?= date('Y') ?> Instituto de Salud Pública. Algunos derechos reservados.</span>
        <button type="button" onclick="abrirAyudaLogin()" data-tooltip="Guía de uso del sistema" class="sgp-help-btn">
            <i class="ti ti-help-circle"></i> ¿Necesitas ayuda?
        </button>
    </p>
</footer>

<script>
// [FIX-UX] Cache-busting para las capturas de /img/ayuda/: sin esto, el
// navegador puede seguir sirviendo una copia vieja de una imagen aunque
// el archivo ya se haya reemplazado en el servidor (no hay Cache-Control
// explícito en Apache, así que aplica caché heurística por Last-Modified).
var ssAyudaImgVer = '<?php
    $ssAyudaImgDir = APPROOT . "/../public/img/ayuda/";
    $ssAyudaMtimes = is_dir($ssAyudaImgDir) ? array_map("filemtime", glob($ssAyudaImgDir . "*.png") ?: []) : [];
    echo $ssAyudaMtimes ? max($ssAyudaMtimes) : time();
?>';

var ssAyudaIdx = 0;
var ssAyudaTotal = 8;

function ssAyudaUpdate() {
    var track = document.querySelector('.ss-carousel-track');
    if (!track) return;
    track.style.transform = 'translateX(-' + (ssAyudaIdx * 100) + '%)';
    document.querySelectorAll('.ss-dot').forEach(function (d, i) {
        d.classList.toggle('active', i === ssAyudaIdx);
    });
    var counter = document.querySelector('.ss-counter');
    if (counter) counter.textContent = 'Paso ' + (ssAyudaIdx + 1) + ' de ' + ssAyudaTotal;
    var prev = document.querySelector('.ss-prev');
    var next = document.querySelector('.ss-next');
    if (prev) prev.disabled = (ssAyudaIdx === 0);
    if (next) next.disabled = (ssAyudaIdx === ssAyudaTotal - 1);
}
function ssAyudaNav(dir) {
    ssAyudaIdx = Math.max(0, Math.min(ssAyudaTotal - 1, ssAyudaIdx + dir));
    ssAyudaUpdate();
}
function ssAyudaGoTo(i) {
    ssAyudaIdx = i;
    ssAyudaUpdate();
}
var ssZoom = { scale: 1, x: 0, y: 0, dragging: false, startX: 0, startY: 0, moved: false };

// [FIX-UX] El zoom vivía en un Swal.fire() separado que se cerraba y volvía a
// abrir el modal de ayuda al terminar. Ese ciclo de cerrar+reabrir un Swal
// distinto rompía el focus-trap del modal original: al volver, el foco del
// teclado quedaba en un elemento de la página de fondo (el formulario de
// login oculto detrás), Escape dejaba de cerrar el modal de forma confiable
// y Tab se "escapaba" del diálogo. Ahora el zoom es un overlay que vive
// DENTRO del mismo popup — nunca se destruye la instancia de Swal, así que
// su focus-trap nunca se pierde.
function ssAyudaZoom(src, alt) {
    var overlay = document.getElementById('ssZoomOverlay');
    var img = document.getElementById('ssZoomImg2');
    if (!overlay || !img) return;
    img.src = src;
    img.alt = alt;
    overlay.classList.add('visible');
    ssZoomInit();
    var closeBtn = document.getElementById('ssZoomCloseBtn');
    if (closeBtn) closeBtn.focus();
}
function ssAyudaZoomClose() {
    var overlay = document.getElementById('ssZoomOverlay');
    if (!overlay) return;
    overlay.classList.remove('visible');
    ssZoomDestroy();
    // Devolver el foco a la miniatura que abrió el zoom, para no perder la posición del teclado
    var activeSlide = document.querySelectorAll('.ss-slide')[ssAyudaIdx];
    var thumb = activeSlide ? activeSlide.querySelector('.ss-help-img') : null;
    if (thumb) thumb.focus();
}

function ssZoomApply() {
    var img = document.getElementById('ssZoomImg2');
    if (!img) return;
    img.style.transform = 'translate(' + ssZoom.x + 'px,' + ssZoom.y + 'px) scale(' + ssZoom.scale + ')';
    img.style.cursor = ssZoom.scale > 1 ? 'grab' : 'zoom-in';
    var label = document.getElementById('ssZoomLevel2');
    if (label) label.textContent = Math.round(ssZoom.scale * 100) + '%';
    // [FIX-UX] Pista visual de "arrastra para mover" — antes solo cambiaba
    // el cursor, algo que la mayoría de usuarios no técnicos no nota.
    var hint = document.getElementById('ssZoomPanHint');
    if (hint) hint.classList.toggle('visible', ssZoom.scale > 1);
}
function ssZoomStep(dir) {
    ssZoom.scale = Math.min(3, Math.max(1, ssZoom.scale + dir * 0.5));
    if (ssZoom.scale === 1) { ssZoom.x = 0; ssZoom.y = 0; }
    ssZoomApply();
}
function ssZoomReset() {
    ssZoom.scale = 1; ssZoom.x = 0; ssZoom.y = 0;
    ssZoomApply();
}
function ssZoomDragStart(clientX, clientY) {
    if (ssZoom.scale <= 1) return;
    ssZoom.dragging = true;
    ssZoom.moved = false;
    ssZoom.startX = clientX - ssZoom.x;
    ssZoom.startY = clientY - ssZoom.y;
    var img = document.getElementById('ssZoomImg2');
    if (img) img.style.cursor = 'grabbing';
}
function ssZoomDragMove(clientX, clientY) {
    if (!ssZoom.dragging) return;
    ssZoom.moved = true;
    ssZoom.x = clientX - ssZoom.startX;
    ssZoom.y = clientY - ssZoom.startY;
    ssZoomApply();
}
function ssZoomDragEnd() {
    if (!ssZoom.dragging) return;
    ssZoom.dragging = false;
    var img = document.getElementById('ssZoomImg2');
    if (img) img.style.cursor = 'grab';
}
function ssZoomMouseDown(e) { ssZoomDragStart(e.clientX, e.clientY); }
function ssZoomMouseMove(e) { ssZoomDragMove(e.clientX, e.clientY); }
function ssZoomTouchStart(e) { var t = e.touches[0]; ssZoomDragStart(t.clientX, t.clientY); }
function ssZoomTouchMove(e) {
    if (!ssZoom.dragging) return;
    e.preventDefault();
    var t = e.touches[0];
    ssZoomDragMove(t.clientX, t.clientY);
}
function ssZoomDblClick() {
    if (ssZoom.scale === 1) { ssZoom.scale = 2; } else { ssZoom.scale = 1; ssZoom.x = 0; ssZoom.y = 0; }
    ssZoomApply();
}
function ssZoomInit() {
    ssZoom = { scale: 1, x: 0, y: 0, dragging: false, startX: 0, startY: 0, moved: false };
    var img = document.getElementById('ssZoomImg2');
    if (!img) return;
    ssZoomApply();
    img.addEventListener('mousedown', ssZoomMouseDown);
    img.addEventListener('touchstart', ssZoomTouchStart);
    img.addEventListener('dblclick', ssZoomDblClick);
    window.addEventListener('mousemove', ssZoomMouseMove);
    window.addEventListener('mouseup', ssZoomDragEnd);
    window.addEventListener('touchmove', ssZoomTouchMove, { passive: false });
    window.addEventListener('touchend', ssZoomDragEnd);
}
function ssZoomDestroy() {
    var img = document.getElementById('ssZoomImg2');
    if (img) {
        img.removeEventListener('mousedown', ssZoomMouseDown);
        img.removeEventListener('touchstart', ssZoomTouchStart);
        img.removeEventListener('dblclick', ssZoomDblClick);
    }
    window.removeEventListener('mousemove', ssZoomMouseMove);
    window.removeEventListener('mouseup', ssZoomDragEnd);
    window.removeEventListener('touchmove', ssZoomTouchMove);
    window.removeEventListener('touchend', ssZoomDragEnd);
}

// [FIX-UX] Navegación del carrusel con flechas de teclado (antes solo
// funcionaba haciendo clic en los botones prev/next). Se activa/desactiva
// junto con la apertura/cierre del modal para no interferir con el resto
// de la página. Si el overlay de zoom está abierto, Escape lo cierra a él
// primero (no todo el modal), y las flechas no mueven el carrusel de fondo.
function ssAyudaKeydown(e) {
    var overlay = document.getElementById('ssZoomOverlay');
    var zoomOpen = overlay && overlay.classList.contains('visible');
    if (e.key === 'Escape' && zoomOpen) {
        e.preventDefault();
        e.stopPropagation();
        ssAyudaZoomClose();
        return;
    }
    if (zoomOpen) return;
    if (e.key === 'ArrowLeft') { e.preventDefault(); ssAyudaNav(-1); }
    else if (e.key === 'ArrowRight') { e.preventDefault(); ssAyudaNav(1); }
}

function abrirAyudaLogin() {
    ssAyudaIdx = 0;

    var slide = function (icon, iconClass, title, img, alt, placeholder, body) {
        var src = `${URLROOT}/img/ayuda/${img}?v=${ssAyudaImgVer}`;
        return `
            <div class="ss-slide">
                <div class="ss-slide-header">
                    <div class="ss-slide-icon ${iconClass}"><i class="ti ${icon}"></i></div>
                    <span class="ss-slide-title">${title}</span>
                </div>
                <div class="ss-help-shot">
                    <img src="${src}" alt="${alt}" class="ss-help-img" tabindex="0" role="button"
                         aria-label="Ver más grande: ${alt}"
                         onclick="ssAyudaZoom('${src}','${alt}')"
                         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();ssAyudaZoom('${src}','${alt}');}"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';this.parentElement.querySelector('.ss-zoom-hint').style.display='none';">
                    <div class="ss-help-placeholder"><i class="ti ti-camera"></i><span>${placeholder}</span></div>
                    <div class="ss-zoom-hint" title="Ver más grande"><i class="ti ti-zoom-in"></i></div>
                </div>
                <div class="ss-slide-body">${body}</div>
            </div>
        `;
    };

    var slides = [
        slide('ti-login', 'ss-icon-blue', 'Iniciar Sesión', 'login-form.png',
            'Pantalla de inicio de sesión', '[ Captura: Pantalla de inicio de sesión ]',
            'Ingresa tu <strong>correo institucional</strong>, <strong>contraseña</strong> y el ' +
            '<strong>código CAPTCHA</strong>. Haz clic en <strong>Ingresar</strong> para acceder a tu panel según tu rol.' +
            '<div class="ss-slide-box ss-box-warn"><strong>¿Error de acceso?</strong> Contacta al Administrador del sistema.</div>'),

        slide('ti-clock-check', 'ss-icon-green', 'Marcar Asistencia (Kiosco)', 'kiosco-boton.png',
            'Botón verde Marcar Asistencia', '[ Captura: Botón "Marcar Asistencia" ]',
            'El botón <strong>verde</strong> en la esquina superior derecha es el <strong>Kiosco de Asistencia Pública</strong>: ' +
            'permite a los <strong>pasantes</strong> registrar su entrada con su <strong>PIN de 4 dígitos</strong>, sin iniciar sesión.' +
            '<div class="ss-slide-box ss-box-ok">Si eres <strong>Administrador o Tutor</strong>, no uses el kiosco — inicia sesión normalmente.</div>'),

        slide('ti-id-badge-2', 'ss-icon-green', 'Vista del Kiosco', 'kiosco-vista.png',
            'Vista del kiosco con campos de cédula y PIN', '[ Captura: Vista del kiosco — cédula y PIN ]',
            'Al hacer clic se abre esta pantalla con la hora actual. Ingresa tu <strong>número de cédula</strong> y tu ' +
            '<strong>PIN de Asistencia</strong> (4 dígitos) en los campos indicados, luego presiona <strong>Marcar Asistencia</strong>.'),

        slide('ti-lock-question', 'ss-icon-amber', '¿Olvidaste tu contraseña? · Paso 1', 'recuperar-paso1-correo.png',
            'Pantalla para ingresar correo de recuperación', '[ Captura: Recuperar — ingresar correo ]',
            'Haz clic en <strong>¿Olvidaste tu contraseña?</strong> debajo del botón Ingresar, e ingresa tu ' +
            '<strong>correo institucional</strong> para iniciar la recuperación.'),

        slide('ti-shield-check', 'ss-icon-amber', '¿Olvidaste tu contraseña? · Paso 2', 'recuperar-paso2-preguntas.png',
            'Pantalla de preguntas de seguridad', '[ Captura: Recuperar — preguntas de seguridad ]',
            'Responde tus <strong>preguntas de seguridad</strong> configuradas previamente para verificar tu identidad.' +
            '<div class="ss-slide-box ss-box-warn">Si no configuraste preguntas de seguridad, contacta al Administrador.</div>'),

        slide('ti-key', 'ss-icon-amber', '¿Olvidaste tu contraseña? · Paso 3', 'recuperar-paso3-nueva-clave.png',
            'Pantalla de nueva contraseña con barra de fortaleza', '[ Captura: Recuperar — nueva contraseña ]',
            'Define tu <strong>nueva contraseña</strong>. La barra de color (rojo → amarillo → verde) y la lista de requisitos ' +
            'se actualizan mientras escribes, indicando qué tan segura es.'),

        slide('ti-user-plus', 'ss-icon-purple', '¿No tienes cuenta? · Paso 1', 'registro-paso1-datos.png',
            'Formulario de registro paso 1', '[ Captura: Registro — datos personales ]',
            'El enlace <strong>Regístrate aquí</strong> abre este formulario. Completa tus <strong>datos personales</strong>, ' +
            '<strong>cédula</strong> y <strong>correo</strong> institucional.'),

        slide('ti-shield-lock', 'ss-icon-purple', '¿No tienes cuenta? · Paso 2', 'registro-paso2-preguntas.png',
            'Formulario de registro paso 2 preguntas de seguridad', '[ Captura: Registro — preguntas de seguridad ]',
            'Configura tus <strong>preguntas de seguridad</strong> — las usarás más adelante si necesitas <strong>recuperar tu contraseña</strong>.' +
            '<div class="ss-slide-box ss-box-ok">Tu cuenta queda <strong>activa de inmediato</strong>: ya puedes iniciar sesión con tu correo y contraseña.</div>')
    ];

    ssAyudaTotal = slides.length;

    var dots = '';
    for (var i = 0; i < slides.length; i++) {
        dots += '<button type="button" class="ss-dot' + (i === 0 ? ' active' : '') + '" onclick="ssAyudaGoTo(' + i + ')"></button>';
    }

    var html = `
        <div class="ss-carousel">
            <div class="ss-carousel-viewport"><div class="ss-carousel-track">${slides.join('')}</div></div>
            <button type="button" class="ss-nav ss-prev" onclick="ssAyudaNav(-1)" disabled aria-label="Paso anterior"><i class="ti ti-chevron-left"></i></button>
            <button type="button" class="ss-nav ss-next" onclick="ssAyudaNav(1)" aria-label="Paso siguiente"><i class="ti ti-chevron-right"></i></button>
            <div class="ss-carousel-footer">
                <div class="ss-dots">${dots}</div>
                <div class="ss-counter">Paso 1 de ${slides.length}</div>
            </div>
            <div class="ss-zoom-overlay" id="ssZoomOverlay">
                <div class="ss-zoom-overlay-inner">
                    <img id="ssZoomImg2" src="" alt="" class="ss-zoom-img2" draggable="false">
                    <button type="button" class="ss-zoom-close2" id="ssZoomCloseBtn" onclick="ssAyudaZoomClose()" title="Cerrar" aria-label="Cerrar vista ampliada"><i class="ti ti-x"></i></button>
                    <div class="ss-zoom-pan-hint" id="ssZoomPanHint"><i class="ti ti-arrows-move"></i> Arrastra para mover</div>
                </div>
                <div class="ss-zoom-toolbar2">
                    <button type="button" class="ss-zoom-btn2" onclick="ssZoomStep(-1)" title="Alejar" aria-label="Alejar"><i class="ti ti-zoom-out"></i></button>
                    <span class="ss-zoom-level2" id="ssZoomLevel2">100%</span>
                    <button type="button" class="ss-zoom-btn2" onclick="ssZoomStep(1)" title="Acercar" aria-label="Acercar"><i class="ti ti-zoom-in"></i></button>
                    <button type="button" class="ss-zoom-btn2" onclick="ssZoomReset()" title="Restablecer" aria-label="Restablecer zoom"><i class="ti ti-refresh"></i></button>
                </div>
            </div>
        </div>
    `;

    Swal.fire({
        title: '<div style="display:flex;align-items:center;gap:10px;font-size:1.05rem;font-weight:800;color:#1e293b;"><i class="ti ti-lifebuoy" style="color:#2563eb;font-size:1.3rem;"></i> Guía de la Pantalla de Inicio</div>',
        html: html,
        width: 600,
        showConfirmButton: true,
        confirmButtonText: '<i class="ti ti-check"></i> Entendido',
        confirmButtonColor: '#2563eb',
        // [FIX-UX] Navegación por teclado (flechas) mientras el modal está abierto;
        // se desengancha al cerrar para no dejar listeners colgando en la página.
        didOpen: function () { document.addEventListener('keydown', ssAyudaKeydown, true); },
        didClose: function () { document.removeEventListener('keydown', ssAyudaKeydown, true); },
        showCloseButton: true,
        customClass: { popup: 'sgp-swal-help-login' }
    });
}
</script>
