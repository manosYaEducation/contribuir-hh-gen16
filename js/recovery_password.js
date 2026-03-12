/**
 * ============================================================
 * recovery_password.js — Módulo de Recuperación de Contraseña
 * ============================================================
 * 
 * Maneja el flujo completo de recuperación en 3 pasos:
 *   1. Solicitar código → api/request_reset.php
 *   2. Verificar código → api/verify_code.php
 *   3. Nueva contraseña → api/reset_password.php
 * 
 * Dependencias:
 *   - Funciones globales de script.js: openModal(), closeModal(), switchToModal()
 *   - Componente HTML: ModeladoHTML/recovery_password_modal.html (cargado vía fetch)
 *   - Estilos CSS: Css/recovery_password.css
 * 
 * ============================================================
 */

/* ===== VARIABLES GLOBALES DEL MÓDULO ===== */
var recoveryEmail = '';        // Email del usuario durante el flujo de recuperación
var verifiedCode = '';         // Código verificado exitosamente, persistido entre modales
var resendCooldownEnd = 0;     // Timestamp hasta el cual no se puede reenviar código
var resendTimerInterval = null; // Intervalo para la cuenta regresiva del cooldown

/* ===== INICIALIZACIÓN DEL MÓDULO ===== */

/**
 * Carga el HTML de los modales de recuperación desde components/recovery_password_modal.html
 * e inicializa los event listeners después de insertarlo en el DOM.
 * Se llama automáticamente al cargar la página.
 */
async function initRecoveryModule() {
    try {
        // Cargar el HTML del componente de recuperación
        const container = document.getElementById('recovery-modals');
        if (!container) {
            console.warn('recovery_password.js: No se encontró #recovery-modals en el DOM.');
            return;
        }

        const response = await fetch('ModeladoHTML/recovery_password_modal.html');
        if (!response.ok) {
            console.error('recovery_password.js: Error al cargar recovery_password_modal.html:', response.status);
            return;
        }

        container.innerHTML = await response.text();

        // Conectar formularios a sus handlers
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const verifyCodeForm = document.getElementById('verifyCodeForm');
        const newPasswordForm = document.getElementById('newPasswordForm');

        if (forgotPasswordForm) forgotPasswordForm.addEventListener('submit', handleForgotPassword);
        if (verifyCodeForm) verifyCodeForm.addEventListener('submit', handleVerifyCode);
        if (newPasswordForm) newPasswordForm.addEventListener('submit', handleResetPassword);

        // Configurar auto-focus en inputs de código
        setupCodeInputs();

        console.log('recovery_password.js: Módulo de recuperación inicializado correctamente.');
    } catch (error) {
        console.error('recovery_password.js: Error al inicializar módulo de recuperación:', error);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initRecoveryModule);

/* ===== FUNCIONES DE INPUTS DE CÓDIGO ===== */

/**
 * Configura los 5 inputs de código de verificación:
 * - Auto-avance al escribir un dígito
 * - Retroceso con Backspace
 * - Soporte para pegar código completo
 */
function setupCodeInputs() {
    const codeInputs = document.querySelectorAll('.code-input');
    if (!codeInputs.length) return;

    codeInputs.forEach((input, index) => {
        // Al escribir un dígito, pasar al siguiente input
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }
        });

        // Al presionar Backspace, volver al input anterior
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                codeInputs[index - 1].focus();
            }
        });

        // Permitir pegar el código completo (ej: 12345)
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 5);
            pastedData.split('').forEach((char, i) => {
                if (codeInputs[i]) {
                    codeInputs[i].value = char;
                }
            });
            const lastIndex = Math.min(pastedData.length, codeInputs.length) - 1;
            if (lastIndex >= 0) codeInputs[lastIndex].focus();
        });
    });
}

/** Obtener el código de 5 dígitos concatenando los 5 inputs */
function getCodeFromInputs() {
    const codeInputs = document.querySelectorAll('.code-input');
    let code = '';
    codeInputs.forEach(input => code += input.value);
    return code;
}

/** Limpiar todos los inputs de código y enfocar el primero */
function clearCodeInputs() {
    const codeInputs = document.querySelectorAll('.code-input');
    codeInputs.forEach(input => input.value = '');
    if (codeInputs[0]) codeInputs[0].focus();
}

/* ===== FUNCIONES DE MENSAJES ===== */

/**
 * Muestra un mensaje de éxito o error dentro de un modal
 * @param {string} elementId - ID del div de mensaje (ej: 'forgotPasswordMessage')
 * @param {string} message   - Texto a mostrar
 * @param {string} type      - 'success' o 'error'
 */
function showRecoveryMessage(elementId, message, type) {
    const messageEl = document.getElementById(elementId);
    if (!messageEl) return;
    messageEl.textContent = message;
    messageEl.className = 'modal-message ' + type;
    messageEl.style.display = 'block';
}

/** Oculta un mensaje de un modal */
function hideRecoveryMessage(elementId) {
    const messageEl = document.getElementById(elementId);
    if (messageEl) messageEl.style.display = 'none';
}

/* ===== COOLDOWN DE REENVÍO (60 SEGUNDOS) ===== */

/**
 * Inicia la cuenta regresiva de 60 segundos para el reenvío de código.
 * Muestra los segundos restantes junto al link "Reenviar código".
 */
function startResendCooldown() {
    resendCooldownEnd = Date.now() + 60000; // 60 segundos
    const resendLink = document.getElementById('resendCodeLink');
    const timerSpan = document.getElementById('resendTimer');

    if (resendLink) {
        resendLink.style.pointerEvents = 'none';
        resendLink.style.opacity = '0.5';
    }

    // Actualizar cuenta regresiva cada segundo
    if (resendTimerInterval) clearInterval(resendTimerInterval);
    resendTimerInterval = setInterval(() => {
        const remaining = Math.ceil((resendCooldownEnd - Date.now()) / 1000);
        if (remaining <= 0) {
            clearInterval(resendTimerInterval);
            resendTimerInterval = null;
            if (timerSpan) timerSpan.textContent = '';
            if (resendLink) {
                resendLink.style.pointerEvents = 'auto';
                resendLink.style.opacity = '1';
            }
        } else {
            if (timerSpan) timerSpan.textContent = ' (' + remaining + 's)';
        }
    }, 1000);

    // Mostrar inmediatamente
    if (timerSpan) timerSpan.textContent = ' (60s)';
}

/** Verifica si el cooldown está activo */
function isResendOnCooldown() {
    return Date.now() < resendCooldownEnd;
}

/* ===== PASO 1: SOLICITAR CÓDIGO ===== */

/**
 * Envía el correo electrónico al API para generar y enviar un código de recuperación.
 * Si el correo existe en la BD, se genera un código de 5 dígitos válido por 30 minutos.
 * El mensaje de éxito se mantiene visible hasta que el usuario presione "Continuar →".
 */
async function handleForgotPassword(event) {
    event.preventDefault();
    const email = document.getElementById('resetEmail').value.trim();
    const btnSendCode = document.getElementById('btnSendCode');
    const btnContinue = document.getElementById('btnContinueToCode');

    hideRecoveryMessage('forgotPasswordMessage');

    if (!email) {
        showRecoveryMessage('forgotPasswordMessage', 'Por favor ingresa tu correo electrónico.', 'error');
        return;
    }

    btnSendCode.disabled = true;
    btnSendCode.textContent = 'Enviando...';

    try {
        const formData = new FormData();
        formData.append('email', email);

        const response = await fetch('api/request_reset.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            recoveryEmail = email;
            showRecoveryMessage('forgotPasswordMessage', result.message, 'success');

            // Mostrar botón "Continuar" y ocultar "Enviar Código"
            btnSendCode.style.display = 'none';
            if (btnContinue) btnContinue.style.display = 'block';

            // Iniciar cooldown de reenvío
            startResendCooldown();
        } else {
            showRecoveryMessage('forgotPasswordMessage', result.message, 'error');
        }
    } catch (error) {
        showRecoveryMessage('forgotPasswordMessage', 'Error de conexión. Intenta nuevamente.', 'error');
        console.error('Error en recuperación:', error);
    } finally {
        btnSendCode.disabled = false;
        btnSendCode.textContent = 'Enviar Código';
    }
}

/**
 * Continúa al modal de verificación de código.
 * Se llama al presionar el botón "Continuar →" después de enviar el código.
 */
function continueToVerifyCode() {
    const btnSendCode = document.getElementById('btnSendCode');
    const btnContinue = document.getElementById('btnContinueToCode');

    // Restaurar estado del modal para futuras visitas
    hideRecoveryMessage('forgotPasswordMessage');
    if (btnSendCode) btnSendCode.style.display = 'block';
    if (btnContinue) btnContinue.style.display = 'none';

    switchToModal('forgotPasswordModal', 'verifyCodeModal');
    clearCodeInputs();
}

/**
 * Atajo: "¿Ya tienes un código?" — Toma el email del input y va directo
 * al modal de verificación sin enviar un nuevo código.
 */
function goToVerifyWithEmail() {
    const emailInput = document.getElementById('resetEmail');
    const email = emailInput ? emailInput.value.trim() : '';

    if (!email) {
        showRecoveryMessage('forgotPasswordMessage', 'Primero ingresa tu correo electrónico.', 'error');
        return;
    }

    recoveryEmail = email;
    switchToModal('forgotPasswordModal', 'verifyCodeModal');
    clearCodeInputs();
}

/* ===== PASO 2: VERIFICAR CÓDIGO ===== */

/**
 * Envía el código de 5 dígitos al API para validar que sea correcto y no esté expirado.
 */
async function handleVerifyCode(event) {
    event.preventDefault();
    const code = getCodeFromInputs();
    const btnVerifyCode = document.getElementById('btnVerifyCode');

    hideRecoveryMessage('verifyCodeMessage');

    if (code.length !== 5) {
        showRecoveryMessage('verifyCodeMessage', 'Por favor ingresa el código completo de 5 dígitos.', 'error');
        return;
    }

    btnVerifyCode.disabled = true;
    btnVerifyCode.textContent = 'Verificando...';

    try {
        const formData = new FormData();
        formData.append('email', recoveryEmail);
        formData.append('code', code);

        const response = await fetch('api/verify_code.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok && result.valid) {
            verifiedCode = code; // Guardar código para usarlo en el paso 3 (nueva contraseña)
            showRecoveryMessage('verifyCodeMessage', '¡Código verificado!', 'success');

            setTimeout(() => {
                hideRecoveryMessage('verifyCodeMessage');
                switchToModal('verifyCodeModal', 'newPasswordModal');
            }, 1000);
        } else {
            showRecoveryMessage('verifyCodeMessage', result.message, 'error');
            clearCodeInputs();
        }
    } catch (error) {
        showRecoveryMessage('verifyCodeMessage', 'Error de conexión. Intenta nuevamente.', 'error');
        console.error('Error en verificación:', error);
    } finally {
        btnVerifyCode.disabled = false;
        btnVerifyCode.textContent = 'Verificar →';
    }
}

/* ===== PASO 3: NUEVA CONTRASEÑA ===== */

/**
 * Valida la nueva contraseña en el frontend y la envía al API para actualizarla.
 * Reglas: mínimo 8 caracteres, al menos una letra y un número.
 */
async function handleResetPassword(event) {
    event.preventDefault();
    const password = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;
    const code = verifiedCode || getCodeFromInputs(); // Usar código guardado del paso 2

    hideRecoveryMessage('newPasswordMessage');

    if (password !== confirmPassword) {
        showRecoveryMessage('newPasswordMessage', 'Las contraseñas no coinciden.', 'error');
        return;
    }

    if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(password)) {
        showRecoveryMessage('newPasswordMessage', 'La contraseña debe tener al menos 8 caracteres, incluyendo letras y números.', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('email', recoveryEmail);
        formData.append('code', code);
        formData.append('password', password);
        formData.append('confirmPassword', confirmPassword);

        const response = await fetch('api/reset_password.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            showRecoveryMessage('newPasswordMessage', result.message, 'success');

            setTimeout(() => {
                hideRecoveryMessage('newPasswordMessage');
                document.getElementById('newPasswordForm').reset();
                recoveryEmail = '';
                verifiedCode = ''; // Limpiar código verificado
                switchToModal('newPasswordModal', 'loginModal');
            }, 2000);
        } else {
            showRecoveryMessage('newPasswordMessage', result.message, 'error');
        }
    } catch (error) {
        showRecoveryMessage('newPasswordMessage', 'Error de conexión. Intenta nuevamente.', 'error');
        console.error('Error al restablecer contraseña:', error);
    }
}

/* ===== REENVIAR CÓDIGO ===== */

/**
 * Reenvía un nuevo código al correo del usuario.
 * Tiene un cooldown de 60 segundos para evitar abuso.
 */
async function handleResendCode() {
    const resendLink = document.getElementById('resendCodeLink');

    if (!recoveryEmail) {
        switchToModal('verifyCodeModal', 'forgotPasswordModal');
        return;
    }

    // Verificar cooldown de 60 segundos
    if (isResendOnCooldown()) {
        const remaining = Math.ceil((resendCooldownEnd - Date.now()) / 1000);
        showRecoveryMessage('verifyCodeMessage', 'Debes esperar ' + remaining + ' segundos antes de reenviar.', 'error');
        return;
    }

    resendLink.textContent = 'Enviando...';
    resendLink.style.pointerEvents = 'none';

    try {
        const formData = new FormData();
        formData.append('email', recoveryEmail);

        const response = await fetch('api/request_reset.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            showRecoveryMessage('verifyCodeMessage', 'Nuevo código enviado a tu correo.', 'success');
            clearCodeInputs();
            // Iniciar cooldown de 60 segundos
            startResendCooldown();
        } else {
            showRecoveryMessage('verifyCodeMessage', result.message, 'error');
            resendLink.style.pointerEvents = 'auto';
        }
    } catch (error) {
        showRecoveryMessage('verifyCodeMessage', 'Error al reenviar. Intenta nuevamente.', 'error');
        resendLink.style.pointerEvents = 'auto';
    } finally {
        resendLink.textContent = 'Reenviar código';
    }
}
