<?php
/**
 * ============================================================
 * validators.php — Funciones de validación reutilizables
 * ============================================================
 * 
 * Centraliza las validaciones comunes usadas en múltiples endpoints:
 *   - register.php
 *   - reset_password.php
 *   - request_reset.php
 *   - login.php
 * 
 * Esto evita duplicar reglas de validación entre archivos.
 * Si se cambia una regla (ej: complejidad de contraseña),
 * se actualiza aquí y aplica en todos los endpoints.
 * 
 * ============================================================
 */

/**
 * Valida el formato de un correo electrónico.
 * 
 * @param string $email  Correo a validar
 * @return bool          true si el formato es válido
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida la complejidad de una contraseña.
 * Regla: mínimo 8 caracteres, al menos una letra y un número.
 * 
 * @param string $password  Contraseña a validar
 * @return bool             true si cumple los requisitos
 */
function isValidPassword($password) {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password) === 1;
}

/**
 * Verifica que dos contraseñas sean iguales.
 * 
 * @param string $password        Contraseña
 * @param string $confirmPassword Confirmación
 * @return bool                   true si coinciden
 */
function passwordsMatch($password, $confirmPassword) {
    return $password === $confirmPassword;
}

/**
 * Genera el hash bcrypt de una contraseña.
 * 
 * @param string $password  Contraseña en texto plano
 * @return string           Hash bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Envía una respuesta JSON de error y detiene la ejecución.
 * 
 * @param int    $httpCode  Código HTTP (ej: 400, 500)
 * @param string $message   Mensaje de error para el usuario
 */
function jsonError($httpCode, $message) {
    http_response_code($httpCode);
    echo json_encode(['message' => $message]);
    exit();
}

/**
 * Envía una respuesta JSON de éxito.
 * 
 * @param string $message     Mensaje para el usuario
 * @param array  $extraData   Datos adicionales (opcional)
 * @param int    $httpCode    Código HTTP (default: 200)
 */
function jsonSuccess($message, $extraData = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['message' => $message], $extraData));
    exit();
}
?>
