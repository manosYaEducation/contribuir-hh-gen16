<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php';

// Obtener el ID del curso
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'course_id inválido']);
    exit;
}

// --- Control de acceso ---
// Sin sesión → siempre restringido (gratuito o de paga)
// Con sesión + curso gratuito → acceso libre
// Con sesión + curso de paga → solo si está inscrito
$hasAccess = false;

if (isset($_SESSION['user_id'])) {
    $courseStmt = $conn->prepare("SELECT price FROM courses WHERE id = ?");
    $courseStmt->bind_param("i", $course_id);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $courseRow = $courseResult->fetch_assoc();
    $isPaid = $courseRow && floatval($courseRow['price']) > 0;
    $courseStmt->close();

    if (!$isPaid) {
        // Curso gratuito + sesión activa → acceso libre
        $hasAccess = true;
    } else {
        // Curso de paga → verificar inscripción
        $enrollStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $enrollStmt->bind_param("ii", $_SESSION['user_id'], $course_id);
        $enrollStmt->execute();
        $hasAccess = $enrollStmt->get_result()->num_rows > 0;
        $enrollStmt->close();
    }
}

// --- Obtener lecciones ---
$sql = "SELECT id, title, description, content, video_url, order_number FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();

$result = $stmt->get_result();
$lessons = [];

while ($row = $result->fetch_assoc()) {
    // Si no tiene acceso, omitir contenido protegido
    if (!$hasAccess) {
        unset($row['content']);
        unset($row['video_url']);
    }
    $lessons[] = $row;
}

// Indicar en la respuesta si el contenido está restringido
$response = $hasAccess
    ? $lessons
    : ['restricted' => true, 'lessons' => $lessons];

echo json_encode($response);
$stmt->close();
$conn->close();
