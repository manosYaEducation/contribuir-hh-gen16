<?php
require 'db_connect.php';
require 'validators.php';

header('Content-Type: application/json');

$email           = isset($_POST['email'])           ? trim($_POST['email'])           : '';
$code            = isset($_POST['code'])            ? trim($_POST['code'])            : '';
$password        = isset($_POST['password'])        ? $_POST['password']              : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword']       : '';

// 1. Validar campos obligatorios
if (empty($email) || empty($code) || empty($password) || empty($confirmPassword)) {
    jsonError(400, 'Todos los campos son requeridos.');
}

// 2. Verificar que las contraseñas coincidan
if (!passwordsMatch($password, $confirmPassword)) {
    jsonError(400, 'Las contraseñas no coinciden.');
}

// 3. Validar complejidad de contraseña (misma regla centralizada en validators.php)
if (!isValidPassword($password)) {
    jsonError(400, 'La contraseña debe tener al menos 8 caracteres, incluyendo letras y números.');
}

// 4. Re-validar el código (seguridad doble)
$stmt = $conn->prepare(
    "SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expires_at > NOW()"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor.']);
    exit();
}

$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['message' => 'El código es inválido o ha expirado. Por favor solicita uno nuevo.']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// 5. Hash de la nueva contraseña (función compartida de validators.php)
$passwordHash = hashPassword($password);

// 6. Actualizar contraseña y limpiar código de recuperación
$stmtUpdate = $conn->prepare(
    "UPDATE users SET password_hash = ?, reset_code = NULL, reset_code_expires_at = NULL WHERE id = ?"
);
if (!$stmtUpdate) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor.']);
    exit();
}

$stmtUpdate->bind_param("si", $passwordHash, $user['id']);

if ($stmtUpdate->execute()) {
    http_response_code(200);
    echo json_encode(['message' => '¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión.']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Error al actualizar la contraseña.']);
}

$stmtUpdate->close();
$conn->close();
?>
