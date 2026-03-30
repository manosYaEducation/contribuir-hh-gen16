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
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'course_id inválido']);
    exit;
}

// Verificar inscripción
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
$enrollStmt->close();

// Total lecciones / módulos
$totalStmt = $conn->prepare('SELECT COUNT(*) AS total FROM lessons WHERE course_id = ?');
$totalStmt->bind_param('i', $course_id);
$totalStmt->execute();
$totalResult = $totalStmt->get_result()->fetch_assoc();
$totalModules = (int)$totalResult['total'];
$totalStmt->close();

// Conseguir el enrollment_id actual del usuario en este curso
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

// Lecciones completadas en lesson_progress
$doneStmt = $conn->prepare('SELECT COUNT(*) AS done FROM lesson_progress WHERE enrollment_id = ? AND is_completed = 1');
$doneStmt->bind_param('i', $enrollment_id);
$doneStmt->execute();
$doneResult = $doneStmt->get_result()->fetch_assoc();
$completedModules = (int)$doneResult['done'];
$doneStmt->close();

$courseProgress = $totalModules > 0 ? (int)round($completedModules * 100.0 / $totalModules) : 0;

// Detalle por lección
$progressDetailStmt = $conn->prepare('SELECT lesson_id as modulo_id, is_completed FROM lesson_progress WHERE enrollment_id = ?');
$progressDetailStmt->bind_param('i', $enrollment_id);
$progressDetailStmt->execute();
$progressDetailResult = $progressDetailStmt->get_result();
$lessons = [];
while ($row = $progressDetailResult->fetch_assoc()) {
    $lessons[] = [
        'modulo_id' => (int)$row['modulo_id'],
        'is_completed' => (int)$row['is_completed']
    ];
}
$progressDetailStmt->close();

// Actualiza también enrollments.progress para sincronizar
$updateEnrollStmt = $conn->prepare('UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?');
$updateEnrollStmt->bind_param('iii', $courseProgress, $user_id, $course_id);
$updateEnrollStmt->execute();
$updateEnrollStmt->close();

echo json_encode([
    'success' => true,
    'course_progress' => $courseProgress,
    'total_modules' => $totalModules,
    'completed_modules' => $completedModules,
    'lessons' => $lessons
]);

$conn->close();
