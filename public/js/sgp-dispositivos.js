/**
 * SGP Dispositivos — Módulo de Vinculación Bancaria de Dispositivos y Login con Patrón
 * Sistema de Gestión de Pasantes (SGP) - ISPEB
 */

(function () {
    'use strict';

    function getBaseUrl() {
        if (typeof window.URLROOT === 'string' && window.URLROOT.length > 0) {
            return window.URLROOT.replace(/\/+$/, '');
        }
        if (typeof window.APP_URL === 'string' && window.APP_URL.length > 0) {
            return window.APP_URL.replace(/\/+$/, '');
        }
        const path = window.location.pathname;
        const sgpIdx = path.indexOf('/SGP');
        if (sgpIdx !== -1) {
            return window.location.origin + path.substring(0, sgpIdx + 4) + '/public';
        }
        return window.location.origin + '/SGP/public';
    }

    async function fetchJsonSafe(url, options = {}) {
        let resp;
        try {
            resp = await fetch(url, options);
        } catch (netErr) {
            throw new Error('Error de conexión a la red. Verifica tu señal o conexión.');
        }

        const text = await resp.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (jsonErr) {
            console.error('[SGP-DISPOSITIVOS] Error parseando respuesta de ' + url + ':\n', text);
            throw new Error('El servidor devolvió un error inesperado (Código HTTP ' + resp.status + ').');
        }

        return data;
    }

    // ── Obtener o Generar UUID de Dispositivo Único ───────────────────
    function getDeviceUuid() {
        let uuid = localStorage.getItem('sgp_device_uuid');
        if (!uuid) {
            uuid = 'sgp_dev_' + ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
            );
            localStorage.setItem('sgp_device_uuid', uuid);
        }
        return uuid;
    }

    // ── Detectar Nombre Amigable del Dispositivo ──────────────────────
    function getDeviceName() {
        const ua = navigator.userAgent;
        let os = 'Dispositivo';
        if (/Android/i.test(ua)) os = 'Android';
        else if (/iPhone/i.test(ua)) os = 'iPhone';
        else if (/iPad/i.test(ua)) os = 'iPad';
        else if (/Windows NT 10/i.test(ua)) os = 'Windows 10/11';
        else if (/Windows/i.test(ua)) os = 'Windows PC';
        else if (/Macintosh/i.test(ua)) os = 'Mac';
        else if (/Linux/i.test(ua)) os = 'Linux';

        let browser = 'Navegador';
        if (/Edg/i.test(ua)) browser = 'Edge';
        else if (/Chrome/i.test(ua)) browser = 'Chrome';
        else if (/Firefox/i.test(ua)) browser = 'Firefox';
        else if (/Safari/i.test(ua)) browser = 'Safari';

        return `${os} (${browser})`;
    }

    // ── Verificar si el Navegador Soporta Biometría de Hardware ───────
    async function hasBiometricsHardware() {
        if (!window.PublicKeyCredential) return false;
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch (_) {
            return false;
        }
    }

    // ── Modal de Vinculación Bancaria (Paso 1: Patrón / Huella + Paso 2: OTP) ──
    async function iniciarVinculacionBancaria(metodoForzado) {
        if (!window.Swal) {
            alert('Librería de alertas no disponible');
            return;
        }

        const tieneHuella = await hasBiometricsHardware();
        let metodoElegido = metodoForzado || (tieneHuella ? 'huella' : 'patron');

        // Si tiene huella y no se forzó método, dejar elegir
        if (!metodoForzado && tieneHuella) {
            const { value: selMetodo } = await Swal.fire({
                title: '🔐 Vincular Dispositivo Frecuente',
                html: `
                    <p style="color:#64748b;font-size:13px;margin-bottom:18px;">
                        Selecciona el método de acceso rápido que deseas usar en este dispositivo:
                    </p>
                    <div style="display:flex;gap:12px;justify-content:center;">
                        <button id="btnEligeHuella" class="swal2-confirm swal2-styled" style="background:#16a34a;padding:12px 18px;display:flex;align-items:center;gap:8px;">
                            <i class="ti ti-fingerprint" style="font-size:20px;"></i> Huella Dactilar
                        </button>
                        <button id="btnEligePatron" class="swal2-confirm swal2-styled" style="background:#2563eb;padding:12px 18px;display:flex;align-items:center;gap:8px;">
                            <i class="ti ti-grid-dots" style="font-size:20px;"></i> Patrón Gestual
                        </button>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    document.getElementById('btnEligeHuella').onclick = () => Swal.close({ value: 'huella' });
                    document.getElementById('btnEligePatron').onclick = () => Swal.close({ value: 'patron' });
                }
            });

            if (!selMetodo) return;
            metodoElegido = selMetodo;
        }

        const deviceUuid = getDeviceUuid();
        const deviceName = getDeviceName();

        // Si es Huella: ejecutar primero el enrolamiento biométrico WebAuthn
        if (metodoElegido === 'huella') {
            await ejecutarVinculacionHuellaConOtp(deviceUuid, deviceName);
        } else {
            // Si es Patrón: dibujar patrón y luego verificar con OTP
            await ejecutarVinculacionPatronConOtp(deviceUuid, deviceName);
        }
    }

    // ── Flujo: Vincular Patrón con OTP al Correo ──────────────────────
    async function ejecutarVinculacionPatronConOtp(deviceUuid, deviceName) {
        let patronTrazado = '';

        // Paso A: Dibujar Patrón
        const step1 = await Swal.fire({
            title: '🎨 Dibuja tu Patrón de Seguridad',
            html: `
                <p style="color:#64748b;font-size:13px;margin:0 0 12px;">
                    Conecta al menos <strong>4 puntos</strong> con tu dedo o mouse:
                </p>
                <div style="display:flex;justify-content:center;margin-bottom:12px;">
                    <canvas id="swalPatternCanvas" style="border:1.5px solid #e2e8f0;border-radius:18px;background:#f8fafc;touch-action:none;box-shadow:0 6px 18px rgba(0,0,0,0.06);"></canvas>
                </div>
                <div id="swalPatternInfo" style="font-size:12px;color:#64748b;font-weight:600;">
                    Traza tu patrón para continuar
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Continuar <i class="ti ti-arrow-right ms-1"></i>',
            confirmButtonColor: '#2563eb',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                if (!patronTrazado || patronTrazado.split('-').length < 4) {
                    Swal.showValidationMessage('El patrón debe conectar al menos 4 puntos.');
                    return false;
                }
                return patronTrazado;
            },
            didOpen: () => {
                const lock = new SgpPatternLock('swalPatternCanvas', {
                    size: 260,
                    onComplete: (pattern) => {
                        patronTrazado = pattern;
                        const count = pattern.split('-').length;
                        const info = document.getElementById('swalPatternInfo');
                        if (count >= 4) {
                            lock.success();
                            info.innerHTML = `<span style="color:#16a34a;">✅ Patrón válido (${count} puntos). Pulsa Continuar.</span>`;
                        } else {
                            lock.error();
                            info.innerHTML = `<span style="color:#dc2626;">❌ Conecta al menos 4 puntos (${count} elegidos).</span>`;
                            patronTrazado = '';
                        }
                    }
                });
            }
        });

        if (!step1.isConfirmed) return;
        const patronFinal = step1.value;

        // Paso B: Solicitar OTP al servidor
        Swal.fire({
            title: 'Enviando código de seguridad...',
            html: 'Conectando con el servidor de correo institucional.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const data = await fetchJsonSafe(getBaseUrl() + '/dispositivos/solicitarOtp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    device_uuid: deviceUuid,
                    device_name: deviceName,
                    metodo: 'patron'
                })
            });

            if (!data.success) {
                throw new Error(data.message || 'Error al solicitar código OTP');
            }

            // Paso C: Ingreso de Código OTP de 6 dígitos con Morphing SETTIGATION
            let bentoInst = null;
            let otpFinalResuelto = false;
            let finalResData = null;

            const step2Confirmed = await new Promise((resolve) => {
                Swal.fire({
                    title: '📧 Código de Autorización',
                    html: `
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px;margin-bottom:16px;text-align:left;font-size:13px;color:#1e40af;">
                            <i class="ti ti-mail-check me-1"></i> Se ha enviado un código de 6 dígitos a <strong>${data.correo_mask || 'tu correo'}</strong>.
                            ${data.codigo_otp ? `<div style="margin-top:10px;padding:8px 12px;background:#ffffff;border:1.5px dashed #3b82f6;border-radius:8px;text-align:center;"><div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Código de Respaldo Directo:</div><div style="font-size:22px;letter-spacing:4px;font-weight:800;color:#1d4ed8;font-family:monospace;margin-top:2px;">${data.codigo_otp}</div></div>` : ''}
                        </div>
                        <div id="swalBentoOtpContainer" class="bento-otp-wrapper" style="margin: 10px auto;"></div>
                    `,
                    showCancelButton: true,
                    showConfirmButton: false,
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-bento-popup'
                    },
                    didOpen: () => {
                        const container = document.getElementById('swalBentoOtpContainer');
                        if (container && window.BentoOtp) {
                            bentoInst = new BentoOtp({
                                container: container,
                                length: 6,
                                resendCooldown: 60,
                                onResend: async () => {
                                    try {
                                        await fetchJsonSafe(getBaseUrl() + '/dispositivos/solicitarOtp', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                device_uuid: deviceUuid,
                                                device_name: deviceName,
                                                metodo: 'patron'
                                            })
                                        });
                                    } catch (e) {
                                        console.error('[BENTO-OTP] Error reenviando código:', e);
                                    }
                                },
                                onComplete: async (codigoOtp) => {
                                    try {
                                        bentoInst.startVerification('Validando autorización criptográfica...');
                                        const resData = await fetchJsonSafe(getBaseUrl() + '/dispositivos/verificarYVincular', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                device_uuid: deviceUuid,
                                                otp: codigoOtp,
                                                patron: patronFinal,
                                                metodo: 'patron'
                                            })
                                        });

                                        if (!resData.success) {
                                            bentoInst.setError(resData.message || 'Código incorrecto o vencido');
                                            return;
                                        }

                                        finalResData = resData;
                                        otpFinalResuelto = true;
                                        bentoInst.setSuccess('¡Patrón y Dispositivo Vinculados!', () => {
                                            Swal.close();
                                            resolve(true);
                                        });

                                    } catch (err) {
                                        bentoInst.setError(err.message || 'Error de conexión con el servidor');
                                    }
                                }
                            });
                        }
                    }
                }).then(() => {
                    if (!otpFinalResuelto) {
                        resolve(false);
                    }
                });
            });

            if (!step2Confirmed || !finalResData) return;

            localStorage.setItem('sgp_device_trusted', 'true');
            localStorage.setItem('sgp_auth_method', 'patron');

            await Swal.fire({
                icon: 'success',
                title: '¡Dispositivo Vinculado!',
                text: finalResData.message || 'Dispositivo autorizado satisfactoriamente.',
                confirmButtonColor: '#2563eb'
            });

            window.location.reload();

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo vincular',
                text: err.message,
                confirmButtonColor: '#2563eb'
            });
        }
    }

    // ── Flujo: Vincular Huella con OTP al Correo ──────────────────────
    async function ejecutarVinculacionHuellaConOtp(deviceUuid, deviceName) {
        // Solicitar OTP
        Swal.fire({
            title: 'Enviando código de seguridad...',
            html: 'Conectando con el servidor de correo institucional.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const data = await fetchJsonSafe(getBaseUrl() + '/dispositivos/solicitarOtp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    device_uuid: deviceUuid,
                    device_name: deviceName,
                    metodo: 'huella'
                })
            });

            if (!data.success) {
                throw new Error(data.message || 'Error al solicitar código OTP');
            }

            // Paso C: Ingreso de Código OTP de 6 dígitos con Morphing SETTIGATION
            let bentoInst = null;
            let otpFinalResuelto = false;

            const stepOtpConfirmed = await new Promise((resolve) => {
                Swal.fire({
                    title: '📧 Código de Autorización',
                    html: `
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px;margin-bottom:16px;text-align:left;font-size:13px;color:#1e40af;">
                            <i class="ti ti-mail-check me-1"></i> Se ha enviado un código de 6 dígitos a <strong>${data.correo_mask || 'tu correo'}</strong>.
                            ${data.codigo_otp ? `<div style="margin-top:10px;padding:8px 12px;background:#ffffff;border:1.5px dashed #3b82f6;border-radius:8px;text-align:center;"><div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Código de Respaldo Directo:</div><div style="font-size:22px;letter-spacing:4px;font-weight:800;color:#1d4ed8;font-family:monospace;margin-top:2px;">${data.codigo_otp}</div></div>` : ''}
                        </div>
                        <p style="font-size:12px;color:#64748b;margin:0 0 10px;">Ingresa el código para autorizar el registro de tu huella en este equipo:</p>
                        <div id="swalBentoOtpHuellaContainer" class="bento-otp-wrapper" style="margin: 10px auto;"></div>
                    `,
                    showCancelButton: true,
                    showConfirmButton: false,
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-bento-popup'
                    },
                    didOpen: () => {
                        const container = document.getElementById('swalBentoOtpHuellaContainer');
                        if (container && window.BentoOtp) {
                            bentoInst = new BentoOtp({
                                container: container,
                                length: 6,
                                resendCooldown: 60,
                                onResend: async () => {
                                    try {
                                        await fetchJsonSafe(getBaseUrl() + '/dispositivos/solicitarOtp', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                device_uuid: deviceUuid,
                                                device_name: deviceName,
                                                metodo: 'huella'
                                            })
                                        });
                                    } catch (e) {
                                        console.error('[BENTO-OTP] Error reenviando código:', e);
                                    }
                                },
                                onComplete: async (codigoOtp) => {
                                    try {
                                        bentoInst.startVerification('Validando autorización biométrica...');
                                        const resData = await fetchJsonSafe(getBaseUrl() + '/dispositivos/verificarYVincular', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                device_uuid: deviceUuid,
                                                otp: codigoOtp,
                                                metodo: 'huella'
                                            })
                                        });

                                        if (!resData.success) {
                                            bentoInst.setError(resData.message || 'Código incorrecto o vencido');
                                            return;
                                        }

                                        otpFinalResuelto = true;
                                        bentoInst.setSuccess('¡Código Aprobado! Preparando Sensor...', () => {
                                            Swal.close();
                                            resolve(true);
                                        });

                                    } catch (err) {
                                        bentoInst.setError(err.message || 'Error de verificación');
                                    }
                                }
                            });
                        }
                    }
                }).then(() => {
                    if (!otpFinalResuelto) {
                        resolve(false);
                    }
                });
            });

            if (!stepOtpConfirmed) return;

            // Ahora registrar la credencial WebAuthn
            if (window.sgpRegistrarBiometria) {
                await window.sgpRegistrarBiometria(deviceName);
            }

            localStorage.setItem('sgp_device_trusted', 'true');
            localStorage.setItem('sgp_auth_method', 'huella');

            await Swal.fire({
                icon: 'success',
                title: '¡Dispositivo y Huella Vinculados!',
                text: 'A partir de ahora podrás acceder tocando el lector de huella de este equipo.',
                confirmButtonColor: '#16a34a'
            });

            window.location.reload();

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Vinculación',
                text: err.message,
                confirmButtonColor: '#2563eb'
            });
        }
    }

    // ── Login con Patrón Gestual (Modal para la Pantalla de Login) ────
    async function abrirModalLoginPatron(identificadorInicial) {
        let patronTrazado = '';
        const deviceUuid = getDeviceUuid();

        await Swal.fire({
            title: '🟣 Acceso con Patrón de Seguridad',
            html: `
                <div style="margin-bottom:14px;text-align:left;">
                    <label style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;margin-bottom:4px;display:block;">
                        Cédula o Correo Institucional:
                    </label>
                    <input type="text" id="loginPatronIdentificador" value="${identificadorInicial || ''}" placeholder="Ej: V-12345678" 
                        style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:14px;font-weight:600;box-sizing:border-box;">
                </div>
                <p style="color:#64748b;font-size:13px;margin:0 0 10px;">Dibuja tu patrón para ingresar:</p>
                <div style="display:flex;justify-content:center;margin-bottom:8px;">
                    <canvas id="loginPatternCanvas" style="border:1.5px solid #e2e8f0;border-radius:18px;background:#f8fafc;touch-action:none;box-shadow:0 6px 18px rgba(0,0,0,0.06);"></canvas>
                </div>
                <div id="loginPatternInfo" style="font-size:12px;color:#64748b;font-weight:600;">
                    Conecta tus puntos registrados
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-login me-1"></i> Iniciar Sesión',
            confirmButtonColor: '#2563eb',
            cancelButtonText: 'Cancelar',
            preConfirm: async () => {
                const idVal = document.getElementById('loginPatronIdentificador')?.value.trim();
                if (!idVal) {
                    Swal.showValidationMessage('Ingresa tu cédula o correo.');
                    return false;
                }
                if (!patronTrazado) {
                    Swal.showValidationMessage('Dibuja tu patrón en la cuadrícula.');
                    return false;
                }

                try {
                    const res = await fetchJsonSafe(getBaseUrl() + '/dispositivos/loginPatron', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            identificador: idVal,
                            patron: patronTrazado,
                            device_uuid: deviceUuid
                        })
                    });

                    if (!res.success) {
                        Swal.showValidationMessage(res.message || 'Patrón incorrecto');
                        return false;
                    }

                    return res;
                } catch (e) {
                    Swal.showValidationMessage('Error de conexión con el servidor.');
                    return false;
                }
            },
            didOpen: () => {
                const lock = new SgpPatternLock('loginPatternCanvas', {
                    size: 260,
                    onComplete: (pattern) => {
                        patronTrazado = pattern;
                        const info = document.getElementById('loginPatternInfo');
                        if (pattern.split('-').length >= 4) {
                            lock.success();
                            info.innerHTML = '<span style="color:#16a34a;">Patrón capturado. Pulsa Iniciar Sesión.</span>';
                        } else {
                            lock.error();
                            info.innerHTML = '<span style="color:#dc2626;">Patrón muy corto.</span>';
                            patronTrazado = '';
                        }
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value && result.value.redirect) {
                localStorage.setItem('sgp_device_trusted', 'true');
                localStorage.setItem('sgp_auth_method', 'patron');
                if (result.value.cedula) {
                    localStorage.setItem('sgp_user_cedula', result.value.cedula);
                }
                window.location.href = result.value.redirect;
            }
        });
    }

    // ── Exponer Métodos Globales ──────────────────────────────────────
    window.SgpDispositivos = {
        getDeviceUuid,
        getDeviceName,
        hasBiometricsHardware,
        iniciarVinculacionBancaria,
        abrirModalLoginPatron
    };

})();
