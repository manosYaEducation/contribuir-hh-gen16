<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$userId = $_SESSION['user_id'];
$invitedEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

// Validaciones 
if (!$invitedEmail || !filter_var($invitedEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email inválido']);
    exit();
}

if (!$courseId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso inválido']);
    exit();
}

// Verificar que el curso existe
$courseCheck = $conn->prepare("SELECT id FROM courses WHERE id = ?");
$courseCheck->bind_param("i", $courseId);
$courseCheck->execute();
$courseResult = $courseCheck->get_result();

if ($courseResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'El curso no existe']);
    $courseCheck->close();
    exit();
}
$courseCheck->close();

// Buscar usuario con ese email
$userCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
$userCheck->bind_param("s", $invitedEmail);
$userCheck->execute();
$userResult = $userCheck->get_result();

if ($userResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'El usuario con este email no existe en el sistema']);
    $userCheck->close();
    exit();
}

$invitedUser = $userResult->fetch_assoc();
$invitedUserId = $invitedUser['id'];
$userCheck->close();

// Prevenir auto-invitación
if ($invitedUserId === $userId) {
    http_response_code(400);
    echo json_encode(['error' => 'No puedes invitarte a ti mismo']);
    exit();
}

// Verificar si ya está inscrito
$enrollmentCheck = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$enrollmentCheck->bind_param("ii", $invitedUserId, $courseId);
$enrollmentCheck->execute();
$enrollmentResult = $enrollmentCheck->get_result();

if ($enrollmentResult->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Este usuario ya está inscrito en el curso']);
    $enrollmentCheck->close();
    exit();
}
$enrollmentCheck->close();

// Inscribir al usuario en el curso
$enrollStmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, progress, hours_completed) VALUES (?, ?, 0, 0)");
$enrollStmt->bind_param("ii", $invitedUserId, $courseId);

if ($enrollStmt->execute()) {
    $enrollmentId = $enrollStmt->insert_id;
    
    // Actualizar cantidad de estudiantes
    $updateStmt = $conn->prepare("UPDATE courses SET students = students + 1 WHERE id = ?");
    $updateStmt->bind_param("i", $courseId);
    $updateStmt->execute();
    $updateStmt->close();
    
    http_response_code(201);
    echo json_encode([
        'message' => '¡Usuario invitado exitosamente!',
        'enrollmentId' => $enrollmentId,
        'success' => true
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al invitar usuario: ' . $enrollStmt->error]);
}

$enrollStmt->close();
$conn->close();
?>
