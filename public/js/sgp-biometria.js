/**
 * SGP — Autenticación Biométrica Móvil (WebAuthn / Passkeys)
 * Instituto de Salud Pública del Estado Bolívar (ISPEB)
 *
 * Habilita de manera inteligente el acceso por Huella Dactilar / Face ID / Patrón
 * EXCLUSIVAMENTE en teléfonos móviles y dispositivos con sensor biométrico integrado.
 */

(function () {
    'use strict';

    // ── Resuelve la URL base institucional de forma 100% segura ───────
    function getBaseUrl() {
        if (typeof window.URLROOT === 'string' && window.URLROOT.length > 0) {
            return window.URLROOT.replace(/\/+$/, '');
        }
        if (typeof URLROOT === 'string' && URLROOT.length > 0) {
            return URLROOT.replace(/\/+$/, '');
        }
        // Fallback dinámico automático según la URL actual del navegador
        const path = window.location.pathname;
        const sgpIdx = path.indexOf('/SGP');
        if (sgpIdx !== -1) {
            return window.location.origin + path.substring(0, sgpIdx + 4);
        }
        return window.location.origin;
    }

    // ── Ejecutor seguro de Fetch con captura de errores HTTP y JSON ───
    async function fetchJsonSafe(url, options = {}) {
        let resp;
        try {
            resp = await fetch(url, options);
        } catch (netErr) {
            throw new Error('Error de conexión a la red. Verifica tu señal o conexión Wi-Fi.');
        }

        const text = await resp.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (jsonErr) {
            console.error('[SGP-BIOMETRIA] Error parseando respuesta de ' + url + ':\n', text);
            throw new Error('El servidor no devolvió una respuesta válida (Código ' + resp.status + ').');
        }

        return data;
    }

    // ── Utilidades de conversión Base64URL ────────────────────────────
    function bufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function base64UrlToBuffer(base64Url) {
        let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        while (base64.length % 4) {
            base64 += '=';
        }
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    // ── Verificación de compatibilidad con Hardware Móvil ────────────
    async function isBiometricsAvailable() {
        if (!window.isSecureContext) return false;
        if (!window.PublicKeyCredential) return false;
        if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function') return false;

        try {
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            return !!available;
        } catch (e) {
            console.warn('[SGP-BIOMETRIA] Error comprobando soporte de hardware:', e);
            return false;
        }
    }

    // ── Verificar si este dispositivo ya fue registrado y vinculado por el usuario ──
    async function checkDeviceVinculadoStatus() {
        const localTrusted = localStorage.getItem('sgp_device_trusted') === 'true' 
                          || localStorage.getItem('sgp_biometria_enrolled') === 'true';
        const deviceUuid = localStorage.getItem('sgp_device_uuid');

        // Si no está registrado localmente ni tiene UUID, retornar inmediatamente false
        if (!localTrusted && !deviceUuid) {
            return { vinculado: false };
        }

        // Consultar al servidor para verificar si el dispositivo está activo y autorizado
        if (deviceUuid) {
            try {
                const resp = await fetch(getBaseUrl() + '/dispositivos/estadoDispositivo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ device_uuid: deviceUuid })
                });
                const data = await resp.json();
                if (data && data.success) {
                    if (data.vinculado) {
                        localStorage.setItem('sgp_device_trusted', 'true');
                        if (data.metodo) localStorage.setItem('sgp_auth_method', data.metodo);
                        if (data.cedula) localStorage.setItem('sgp_user_cedula', data.cedula);
                        return { 
                            vinculado: true, 
                            metodo: data.metodo || 'huella', 
                            cedula: data.cedula || '',
                            dispositivo: data.dispositivo || ''
                        };
                    } else if (localTrusted) {
                        // El dispositivo fue desvinculado o revocado en el servidor
                        localStorage.removeItem('sgp_device_trusted');
                        localStorage.removeItem('sgp_biometria_enrolled');
                        localStorage.removeItem('sgp_auth_method');
                        return { vinculado: false };
                    }
                }
            } catch (netErr) {
                // Si hay fallo momentáneo de red, usar el estado local
                if (localTrusted) {
                    return {
                        vinculado: true,
                        metodo: localStorage.getItem('sgp_auth_method') || 'huella',
                        cedula: localStorage.getItem('sgp_user_cedula') || ''
                    };
                }
            }
        }

        if (localTrusted) {
            return {
                vinculado: true,
                metodo: localStorage.getItem('sgp_auth_method') || 'huella',
                cedula: localStorage.getItem('sgp_user_cedula') || ''
            };
        }

        return { vinculado: false };
    }

    // ── Inicialización en la pantalla de Login ────────────────────────
    async function initLoginBiometrics() {
        const container = document.getElementById('biometria-login-container');
        const btn = document.getElementById('btnBiometriaLogin');
        const linkPatron = document.getElementById('linkLoginPatronAlt');
        if (!container && !btn) return;

        // REGLA FUNDAMENTAL: Se activa ÚNICAMENTE si el dispositivo ya fue registrado y vinculado por el usuario
        const estado = await checkDeviceVinculadoStatus();
        if (!estado || !estado.vinculado) {
            if (container) container.style.display = 'none';
            if (btn) btn.style.display = 'none';
            if (linkPatron) linkPatron.style.display = 'none';
            return;
        }

        // Si el dispositivo SÍ está vinculado por el usuario:
        const supported = await isBiometricsAvailable();
        const metodo = estado.metodo || localStorage.getItem('sgp_auth_method') || 'huella';

        if (container) container.style.display = 'block';

        // Por indicación directa: RESTAURAR EL BOTÓN DE HUELLA DACTILAR
        // Siempre que el método sea huella o el dispositivo cuente con soporte de huella/biometría
        if (metodo === 'huella' || supported) {
            if (btn) {
                btn.style.display = 'inline-flex';
                btn.className = 'btn-biometria-mobile';
                btn.style.background = '#ffffff';
                btn.style.borderColor = '#cbd5e1';
                btn.style.color = '#0f172a';
                btn.style.boxShadow = '0 2px 5px rgba(0,0,0,0.03)';
                btn.innerHTML = '<i class="ti ti-fingerprint"></i><span>Ingresar con Huella Dactilar</span>';
                btn.onclick = onBiometricLoginClick;
            }

            // Enlace discreto para ingresar con patrón gestual como alternativa
            if (linkPatron) {
                linkPatron.style.display = 'block';
                linkPatron.innerHTML = '<a href="javascript:void(0)" style="font-size:0.78rem;color:#7c3aed;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;"><i class="ti ti-grid-dots"></i>O ingresa con tu patrón gestual</a>';
                linkPatron.onclick = function (e) {
                    if (e) e.preventDefault();
                    const idInput = document.getElementById('email') || document.getElementById('cedula') || document.querySelector('input[name="email"]');
                    const val = idInput ? idInput.value.trim() : (estado.cedula || localStorage.getItem('sgp_user_cedula') || '');
                    if (window.SgpDispositivos && window.SgpDispositivos.abrirModalLoginPatron) {
                        window.SgpDispositivos.abrirModalLoginPatron(val);
                    }
                };
            }
        } else {
            // Caso donde el dispositivo vinculado NO cuenta con lector biométrico y fue vinculado con Patrón
            if (btn) {
                btn.style.display = 'inline-flex';
                btn.className = 'btn-biometria-mobile';
                btn.style.background = 'linear-gradient(135deg, #7c3aed 0%, #6366f1 100%)';
                btn.style.borderColor = 'transparent';
                btn.style.color = '#ffffff';
                btn.style.boxShadow = '0 4px 14px rgba(124, 58, 237, 0.28)';
                btn.innerHTML = '<i class="ti ti-grid-dots" style="color:#ffffff;"></i><span>Ingresar con Patrón de Seguridad</span>';
                btn.onclick = function (e) {
                    if (e) e.preventDefault();
                    const idInput = document.getElementById('email') || document.getElementById('cedula') || document.querySelector('input[name="email"]');
                    const val = idInput ? idInput.value.trim() : (estado.cedula || localStorage.getItem('sgp_user_cedula') || '');
                    if (window.SgpDispositivos && window.SgpDispositivos.abrirModalLoginPatron) {
                        window.SgpDispositivos.abrirModalLoginPatron(val);
                    }
                };
            }

            if (linkPatron) {
                linkPatron.style.display = 'none';
            }
        }
    }

    // ── Flujo de Inicio de Sesión con Huella Dactilar ─────────────────
    async function onBiometricLoginClick(e) {
        if (e) e.preventDefault();

        const btn = document.getElementById('btnBiometriaLogin');
        const originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-2"></i>Esperando huella...';
        }

        try {
            // Obtener identificador si el usuario escribió algo o si está recordado
            const idInput = document.getElementById('email') || document.getElementById('cedula') || document.querySelector('input[name="email"]');
            let identifier = idInput ? idInput.value.trim() : '';
            if (!identifier) {
                identifier = localStorage.getItem('sgp_user_cedula') || '';
            }

            // 1. Solicitar desafío al servidor
            const dataDesafio = await fetchJsonSafe(getBaseUrl() + '/biometria/loginDesafio', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ identifier })
            });

            if (!dataDesafio.success) {
                throw new Error(dataDesafio.message || 'No se pudo iniciar el proceso biométrico');
            }

            // 2. Preparar opciones para navigator.credentials.get
            const getOptions = {
                challenge: base64UrlToBuffer(dataDesafio.challenge),
                rpId: dataDesafio.rpId,
                userVerification: dataDesafio.userVerification || 'preferred',
                timeout: 60000
            };

            if (dataDesafio.allowCredentials && dataDesafio.allowCredentials.length > 0) {
                getOptions.allowCredentials = dataDesafio.allowCredentials.map(c => ({
                    id: base64UrlToBuffer(c.id),
                    type: 'public-key'
                }));
            }

            // 3. Activar el sensor biométrico del teléfono móvil
            const assertion = await navigator.credentials.get({ publicKey: getOptions });
            if (!assertion) {
                throw new Error('No se recibió respuesta del sensor biométrico');
            }

            if (btn) {
                btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-2"></i>¡Huella detectada! Accediendo...';
            }

            // 4. Enviar firma al servidor para verificación
            const payload = {
                credential_id: bufferToBase64Url(assertion.rawId),
                client_data_json: bufferToBase64Url(assertion.response.clientDataJSON),
                authenticator_data: bufferToBase64Url(assertion.response.authenticatorData),
                signature_der: bufferToBase64Url(assertion.response.signature)
            };

            const dataVerify = await fetchJsonSafe(getBaseUrl() + '/biometria/verificar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (dataVerify.success) {
                localStorage.setItem('sgp_device_trusted', 'true');
                localStorage.setItem('sgp_biometria_enrolled', 'true');
                localStorage.setItem('sgp_auth_method', 'huella');
                if (dataVerify.cedula) {
                    localStorage.setItem('sgp_user_cedula', dataVerify.cedula);
                }

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Acceso Concedido!',
                        text: 'Identidad biométrica verificada. Bienvenido ' + (dataVerify.user_name || ''),
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#0f172a',
                        color: '#f8fafc'
                    }).then(() => {
                        window.location.href = dataVerify.redirect || (getBaseUrl() + '/dashboard');
                    });
                } else {
                    window.location.href = dataVerify.redirect || (getBaseUrl() + '/dashboard');
                }
            } else {
                throw new Error(dataVerify.message || 'La verificación biométrica no fue exitosa');
            }

        } catch (err) {
            console.warn('[SGP-BIOMETRIA] Error en login:', err);
            // Si el dispositivo no tiene llaves registradas aún o el usuario canceló
            if (err.name === 'NotAllowedError') {
                if (window.Swal && localStorage.getItem('sgp_biometria_enrolled') !== 'true') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Acceso Biométrico / PIN de Teléfono',
                        html: '<p style="font-size:14px;color:#64748b;margin-bottom:12px;">Para ingresar con <strong>huella dactilar, Face ID o el PIN/patrón de tu teléfono</strong>, primero debes iniciar sesión una vez con tu correo y contraseña.</p><div style="background:#eff6ff;border-radius:10px;padding:12px;font-size:13px;color:#1e3a8a;text-align:left;"><i class="ti ti-shield-check me-2 text-primary"></i>Al iniciar sesión, podrás activar este dispositivo en un solo toque para acceder sin escribir contraseñas.</div>',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#2563eb'
                    });
                }
            } else if (err.name !== 'AbortError') {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Autenticación del Dispositivo',
                        text: err.message || 'No se pudo verificar la huella o el bloqueo de pantalla. Puedes ingresar con tu contraseña.',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    alert(err.message || 'No se pudo verificar la identidad.');
                }
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    }

    window.sgpRegistrarBiometria = async function (customDeviceName) {
        const isIpAddress = /^(\d{1,3}\.){3}\d{1,3}$/.test(window.location.hostname) && window.location.hostname !== '127.0.0.1';
        if (isIpAddress) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dominio o Túnel requerido',
                    html: '<p style="font-size:14px;color:#475569;">Los navegadores (Chrome y Safari) <b>prohíben por estándar W3C</b> el registro de huellas dactilares (Passkeys / WebAuthn) en direcciones IP numéricas directas (como <code>' + window.location.hostname + '</code>).</p><p style="font-size:13px;color:#1e3a8a;background:#eff6ff;padding:10px;border-radius:10px;margin-top:10px;">Para usar la huella dactilar, accede a través del <b>Túnel de Cloudflare</b> o un nombre de dominio oficial.</p>',
                    confirmButtonColor: '#2563eb'
                });
            }
            return false;
        }

        const supported = await isBiometricsAvailable();
        if (!supported) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Seguridad de pantalla requerida',
                    text: 'Este navegador o dispositivo no detecta un método de desbloqueo configurado (Huella dactilar, Face ID, Patrón o PIN de pantalla). Configura un bloqueo de pantalla en los Ajustes de tu teléfono para continuar.',
                    confirmButtonColor: '#2563eb'
                });
            }
            return false;
        }

        try {
            // 1. Obtener desafío de registro
            const dataDesafio = await fetchJsonSafe(getBaseUrl() + '/biometria/registroDesafio', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            if (!dataDesafio.success) {
                throw new Error(dataDesafio.message || 'Error iniciando registro');
            }

            // 2. Preparar opciones de creación
            const createOptions = {
                challenge: base64UrlToBuffer(dataDesafio.challenge),
                rp: dataDesafio.rp,
                user: {
                    id: base64UrlToBuffer(dataDesafio.user.id),
                    name: dataDesafio.user.name,
                    displayName: dataDesafio.user.displayName
                },
                pubKeyCredParams: dataDesafio.pubKeyCredParams,
                authenticatorSelection: dataDesafio.authenticatorSelection,
                timeout: dataDesafio.timeout || 60000,
                attestation: 'none'
            };

            // 3. Solicitar registro de huella en el móvil
            const cred = await navigator.credentials.create({ publicKey: createOptions });
            if (!cred) throw new Error('No se generó la credencial en el dispositivo');

            // Feedback visual instantáneo: Notificar al usuario que la huella fue leída exitosamente
            if (window.Swal) {
                Swal.fire({
                    title: '¡Huella Detectada!',
                    html: '<div style="margin:14px 0;"><i class="ti ti-fingerprint" style="font-size:48px;color:#2563eb;"></i></div><p style="font-size:14px;color:#64748b;margin:0;">Guardando credencial segura en el servidor...</p>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }

            // 4. Extraer clave pública (SPKI DER) si está disponible en la API moderna
            let pubKeyDer = '';
            if (cred.response.getPublicKey && typeof cred.response.getPublicKey === 'function') {
                const pkBuffer = cred.response.getPublicKey();
                if (pkBuffer) pubKeyDer = bufferToBase64Url(pkBuffer);
            }

            const payload = {
                credential_id: bufferToBase64Url(cred.rawId),
                public_key_der: pubKeyDer,
                client_data_json: bufferToBase64Url(cred.response.clientDataJSON),
                attestation_object: bufferToBase64Url(cred.response.attestationObject),
                device_name: customDeviceName || ''
            };

            // 5. Guardar en el servidor
            const dataReg = await fetchJsonSafe(getBaseUrl() + '/biometria/registrar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (dataReg.success) {
                localStorage.setItem('sgp_biometria_enrolled', 'true');
                localStorage.setItem('sgp_device_trusted', 'true');
                localStorage.setItem('sgp_auth_method', 'huella');
                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Huella Dactilar Activada!',
                        html: `<p style="font-size:14px;color:#1e3a8a;margin-bottom:8px;">${dataReg.message || 'Tu dispositivo ha sido registrado para acceso biométrico instantáneo.'}</p><div style="font-size:13px;color:#15803d;background:#dcfce7;border-radius:10px;padding:10px;"><i class="ti ti-mail me-2"></i>Se ha enviado un correo institucional confirmando la activación.</div>`,
                        confirmButtonColor: '#16a34a',
                        confirmButtonText: 'Continuar'
                    });
                }
                if (window.location.pathname.includes('/perfil')) {
                    window.location.reload();
                }
                return true;
            } else {
                throw new Error(dataReg.message || 'Error guardando credencial');
            }

        } catch (err) {
            console.warn('[SGP-BIOMETRIA] Error en enrolamiento:', err);
            if (err.name !== 'NotAllowedError' && err.name !== 'AbortError') {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Registro',
                        text: err.message || 'No se pudo registrar la huella en este dispositivo.',
                        confirmButtonColor: '#dc2626'
                    });
                }
            }
            return false;
        }
    };

    window.sgpIsBiometricsAvailable = isBiometricsAvailable;

    // ── Desafiliación / Eliminación de Dispositivo Biométrico ────────
    window.sgpEliminarBiometria = async function (deviceId, deviceName) {
        if (!window.Swal) {
            if (!confirm('¿Deseas desafiliar y eliminar el acceso biométrico de este dispositivo?')) return false;
        } else {
            const result = await Swal.fire({
                title: '¿Desafiliar Acceso Biométrico?',
                html: `<p style="font-size:14px;color:#64748b;">Se eliminará la credencial biométrica vinculada a <strong>${deviceName || 'este dispositivo'}</strong>.<br>Para acceder en el futuro tendrás que usar tu correo y contraseña.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-trash me-1"></i>Sí, desafiliar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true
            });
            if (!result.isConfirmed) return false;
        }

        try {
            const res = await fetchJsonSafe(getBaseUrl() + '/biometria/eliminar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: deviceId })
            });

            if (res.success) {
                localStorage.removeItem('sgp_biometria_enrolled');
                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Dispositivo Desafiliado',
                        html: `<p style="font-size:14px;color:#1e293b;">${res.message || 'El acceso biométrico ha sido desvinculado.'}</p><div style="font-size:13px;color:#9a3412;background:#fff7ed;border-radius:10px;padding:10px;"><i class="ti ti-mail me-2"></i>Se ha enviado una notificación de seguridad a tu correo.</div>`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                window.location.reload();
                return true;
            } else {
                throw new Error(res.message || 'No se pudo desafiliar el dispositivo');
            }
        } catch (err) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al desafiliar',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            } else {
                alert(err.message);
            }
            return false;
        }
    };

    // ── Prompt estilo Banca Móvil al iniciar sesión en el teléfono ───
    async function checkMobileEnrollmentPrompt() {
        // Solo ofrecer en panel de usuario (no en pantalla de login)
        if (document.getElementById('biometria-login-container')) return;
        
        // Respetar si ya está enrolado o si el usuario eligió "Más tarde"
        const enrollStatus = localStorage.getItem('sgp_biometria_enrolled');
        if (enrollStatus === 'true' || enrollStatus === 'dismissed') return;

        // WebAuthn W3C prohíbe el uso de biometría en direcciones IP numéricas (10.x, 192.168.x)
        const isIpAddress = /^(\d{1,3}\.){3}\d{1,3}$/.test(window.location.hostname);
        if (isIpAddress) {
            // En IP local no mostrar el prompt porque el navegador móvil abortará el sensor
            return;
        }

        const supported = await isBiometricsAvailable();
        if (!supported) return;

        // Comprobar si el usuario ya tiene huella registrada en el servidor
        try {
            const data = await fetchJsonSafe(getBaseUrl() + '/biometria/dispositivos');
            if (data.success && data.devices && data.devices.length > 0) {
                localStorage.setItem('sgp_biometria_enrolled', 'true');
                return;
            }
        } catch (e) {
            return;
        }

        // Mostrar prompt bancario tras 1.5 segundos
        setTimeout(() => {
            if (!window.Swal) return;
            Swal.fire({
                title: '🔐 Activar Huella Dactilar',
                html: `
                    <p style="font-size: 14px; color: #64748b; margin-bottom: 12px;">
                        Detectamos que estás navegando desde un teléfono con sensor biométrico.
                    </p>
                    <div style="background: #eff6ff; border-radius: 12px; padding: 12px; text-align: left; font-size: 13px; color: #1e3a8a; margin-bottom: 8px;">
                        <i class="ti ti-fingerprint me-2 text-primary"></i>¿Deseas activar el acceso rápido con <strong>Huella / Face ID</strong> para no tener que escribir tu contraseña?
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-fingerprint me-2"></i>Activar Huella Ahora',
                cancelButtonText: 'Más tarde',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.sgpRegistrarBiometria();
                } else {
                    localStorage.setItem('sgp_biometria_enrolled', 'dismissed');
                }
            });
        }, 1500);
    }

    // Inicializar al cargar el DOM
    document.addEventListener('DOMContentLoaded', () => {
        initLoginBiometrics();
        checkMobileEnrollmentPrompt();
    });

})();
