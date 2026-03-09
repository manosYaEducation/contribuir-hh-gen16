<?php
require 'db_connect.php';
require 'validators.php';

header('Content-Type: application/json');

// Leer datos del formulario
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

// --- VALIDACIONES DE SEGURIDAD (usando funciones compartidas de validators.php) ---

// 1. Verificar campos obligatorios
if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
    jsonError(400, 'Error: Todos los campos del formulario son obligatorios.');
}

// 2. Verificar que las contraseñas coincidan
if (!passwordsMatch($password, $confirmPassword)) {
    jsonError(400, 'Error: Las contraseñas no coinciden.');
}

// 3. Validación de formato de email
if (!isValidEmail($email)) {
    jsonError(400, 'Error: El formato del correo electrónico no es válido.');
}

// 4. Validación de complejidad de contraseña
if (!isValidPassword($password)) {
    jsonError(400, 'La contraseña debe tener al menos 8 caracteres, incluyendo letras y números.');
}

// 5. Validación de longitud del nombre
if (strlen($name) < 2 || strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(['message' => 'Error: El nombre debe tener entre 2 y 100 caracteres.']);
    exit();
}

// 6. Verificar si el email ya existe
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de preparación de consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['message' => 'Este correo electrónico ya está registrado.']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// --- CREAR USUARIO ---

$password_hash = hashPassword($password);
$stmt_insert = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");

if (!$stmt_insert) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de preparación: ' . $conn->error]);
    exit();
}

$stmt_insert->bind_param("sss", $name, $email, $password_hash);

if ($stmt_insert->execute()) {
    http_response_code(201);
    echo json_encode(['message' => '¡Registro exitoso! Ya puedes iniciar sesión.']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Error al registrar usuario: ' . $stmt_insert->error]);
}

$stmt_insert->close();
$conn->close();
?>