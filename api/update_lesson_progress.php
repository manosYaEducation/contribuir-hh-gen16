<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$course_id = isset($input['course_id']) ? (int)$input['course_id'] : 0;
$lesson_id = isset($input['lesson_id']) ? (int)$input['lesson_id'] : 0;
$completed = isset($input['completed']) ? (int)$input['completed'] : 0;
$completed = $completed === 1 ? 1 : 0;

if ($course_id <= 0 || $lesson_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'course_id o lesson_id inválido']);
    exit;
}

// Verificar inscripción del usuario y obtener enrollment_id
$enrollStmt = $conn->prepare('SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?');
$enrollStmt->bind_param('ii', $user_id, $course_id);
$enrollStmt->execute();
$enrollResult = $enrollStmt->get_result();
if ($enrollResult->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No inscrito en el curso']);
    $enrollStmt->close();
    $conn->close();
    exit;
}
$enrollment = $enrollResult->fetch_assoc();
$enrollment_id = (int)$enrollment['id'];
$enrollStmt->close();

// Validar que la lección pertenece a ese curso
$lessonStmt = $conn->prepare('SELECT id FROM lessons WHERE id = ? AND course_id = ?');
$lessonStmt->bind_param('ii', $lesson_id, $course_id);
$lessonStmt->execute();
$lessonResult = $lessonStmt->get_result();
if ($lessonResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Lección no encontrada en ese curso']);
    $lessonStmt->close();
    $conn->close();
    exit;
}
$lessonStmt->close();

// Insertar o actualizar en lesson_progress
$stmt = $conn->prepare(
    'INSERT INTO lesson_progress (enrollment_id, lesson_id, is_completed, completed_date) VALUES (?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE is_completed = VALUES(is_completed), completed_date = CASE WHEN VALUES(is_completed) = 1 THEN NOW() ELSE completed_date END'
);
$stmt->bind_param('iii', $enrollment_id, $lesson_id, $completed);
$success = $stmt->execute();
if (!$success) {
    $dbError = $stmt->error;
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar progreso', 'detail' => $dbError]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Recalcular progreso
$totalStmt = $conn->prepare('SELECT COUNT(*) AS total FROM lessons WHERE course_id = ?');
$totalStmt->bind_param('i', $course_id);
$totalStmt->execute();
$totalResult = $totalStmt->get_result()->fetch_assoc();
$totalModules = (int)$totalResult['total'];
$totalStmt->close();

$doneStmt = $conn->prepare('SELECT COUNT(*) AS done FROM lesson_progress WHERE enrollment_id = ? AND is_completed = 1');
$doneStmt->bind_param('i', $enrollment_id);
$doneStmt->execute();
$doneResult = $doneStmt->get_result()->fetch_assoc();
$completedModules = (int)$doneResult['done'];
$doneStmt->close();

$courseProgress = $totalModules > 0 ? (int)round($completedModules * 100.0 / $totalModules) : 0;

$updateEnroll = $conn->prepare('UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?');
$updateEnroll->bind_param('iii', $courseProgress, $user_id, $course_id);
$updateEnroll->execute();
$updateEnroll->close();

echo json_encode([
    'success' => true,
    'course_progress' => $courseProgress,
    'total_modules' => $totalModules,
    'completed_modules' => $completedModules
]);

$conn->close();
