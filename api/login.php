<?php
session_start();
require 'db_connect.php';
require 'validators.php';

header('Content-Type: application/json');

// IMPORTANTE: FormData se lee desde $_POST, NO desde file_get_contents
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// 1. Validar campos obligatorios
if (empty($email) || empty($password)) {
    jsonError(400, 'Email y contraseña son requeridos.');
}

// 2. Validar formato de email (función compartida de validators.php)
if (!isValidEmail($email)) {
    jsonError(400, 'Formato de email inválido.');
}

// 3. Buscar el usuario por email
$stmt = $conn->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de preparación: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// 4. Verificar si el usuario existe
if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['message' => 'El correo o contraseña son incorrectos.']);
    $stmt->close();
    $conn->close();
    exit();
}

// 5. Obtener datos del usuario
$user = $result->fetch_assoc();
$stmt->close();

// 6. Verificar contraseña
if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['message' => 'El correo o contraseña son incorrectos.']);
    $conn->close();
    exit();
}

// 7. Crear sesión
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];

// 8. Retornar respuesta exitosa
http_response_code(200);
echo json_encode([
    'message' => '¡Sesión iniciada correctamente!',
    'name' => $user['name'],
    'email' => $user['email']
]);

$conn->close();
?>