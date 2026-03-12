<?php
session_start();
require 'db_connect.php';
require 'validators.php';
require 'rate_limiter.php';

header('Content-Type: application/json');

$email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
$password = isset($_POST['password']) ? $_POST['password']       : '';

if (empty($email) || empty($password)) {
    jsonError(400, 'Email y contraseña son requeridos.');
}

// 2. Validar formato de email (función compartida de validators.php)
if (!isValidEmail($email)) {
    jsonError(400, 'Formato de email inválido.');
}

// Verificar límite ANTES de consultar la BD
$clientIp = getClientIp(); // ← NUEVO
checkRateLimit($conn, $clientIp, $email); // ← NUEVO

$stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.password_hash, r.name AS role_name
                        FROM users u
                        INNER JOIN roles r ON u.role_id = r.id
                        WHERE u.email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de preparación: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    recordFailedAttempt($conn, $clientIp, $email); // ← NUEVO
    http_response_code(401);
    echo json_encode(['message' => 'El correo o contraseña son incorrectos.']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password_hash'])) {
    recordFailedAttempt($conn, $clientIp, $email); // ← NUEVO
    http_response_code(401);
    echo json_encode(['message' => 'El correo o contraseña son incorrectos.']);
    $conn->close();
    exit();
}

// Login exitoso: limpiar intentos fallidos previos
clearFailedAttempts($conn, $clientIp, $email); // ← NUEVO

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role']  = $user['role_name']; 

http_response_code(200);
echo json_encode([
    'message' => '¡Sesión iniciada correctamente!',
    'name'    => $user['name'],
    'email'   => $user['email'],
     'role' => $user['role_name']
    ]);


$conn->close();
?>