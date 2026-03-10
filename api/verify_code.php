<?php
require 'db_connect.php';

header('Content-Type: application/json');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$code  = isset($_POST['code'])  ? trim($_POST['code'])  : '';

// 1. Validar campos obligatorios
if (empty($email) || empty($code)) {
    http_response_code(400);
    echo json_encode(['message' => 'El correo y el código son requeridos.']);
    exit();
}

// 2. Validar formato del código (5 dígitos)
if (!preg_match('/^\d{5}$/', $code)) {
    http_response_code(400);
    echo json_encode(['message' => 'El código debe ser de 5 dígitos.']);
    exit();
}

// 3. Buscar usuario con ese email y código, verificar expiración
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

$stmt->close();
$conn->close();

// 4. Código válido
http_response_code(200);
echo json_encode(['message' => 'Código verificado correctamente.', 'valid' => true]);
?>
