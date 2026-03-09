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
$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

// Validar curso
if (!$courseId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso inválido']);
    exit();
}

// Verificar si el curso existe
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

// Verificar si ya está inscrito
$checkStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$checkStmt->bind_param("ii", $userId, $courseId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Ya estás inscrito en este curso']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Inscribir al usuario
$stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, progress, hours_completed) VALUES (?, ?, 0, 0)");
$stmt->bind_param("ii", $userId, $courseId);

if ($stmt->execute()) {
    $enrollmentId = $stmt->insert_id;
    
    // Actualizar cantidad de estudiantes en el curso
    $updateStmt = $conn->prepare("UPDATE courses SET students = students + 1 WHERE id = ?");
    $updateStmt->bind_param("i", $courseId);
    $updateStmt->execute();
    $updateStmt->close();
    
    http_response_code(201);
    echo json_encode([
        'message' => '¡Inscripción exitosa!',
        'enrollmentId' => $enrollmentId,
        'success' => true
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al inscribirse: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>