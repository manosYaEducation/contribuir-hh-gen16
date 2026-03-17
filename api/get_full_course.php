<?php
// api/get_full_course.php
// Capturar cualquier output espurio
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

// Limpiar cualquier output previo
ob_end_clean();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// Curso + detalle + instructor
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.instructor_id, c.avatar, c.category, c.price, c.rating, c.students, c.duration, c.image, c.description,
           u.name as instructor,
           d.learning_objectives,
           d.requirements,
           d.intro_video
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    LEFT JOIN detail_courses d ON c.id = d.course_id
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!($course = $result->fetch_assoc())) {
    http_response_code(404);
    echo json_encode(['error' => 'Curso no encontrado']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

if ($course['learning_objectives']) {
    $course['learning_objectives'] = json_decode($course['learning_objectives'], true);
}
if ($course['requirements']) {
    $course['requirements'] = json_decode($course['requirements'], true);
}

// --- Control de acceso para lecciones ---
// Sin sesión → siempre restringido
// Con sesión + gratuito → acceso libre
// Con sesión + paga → solo si inscrito
$isPaid = floatval($course['price']) > 0;
$hasAccess = false;

if (isset($_SESSION['user_id'])) {
    if (!$isPaid) {
        $hasAccess = true;
    } else {
        $enrollStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $enrollStmt->bind_param("ii", $_SESSION['user_id'], $id);
        $enrollStmt->execute();
        $hasAccess = $enrollStmt->get_result()->num_rows > 0;
        $enrollStmt->close();
    }
}

// Lecciones del curso
$stmt2 = $conn->prepare("
    SELECT id, title, description, order_number, content, video_url, duration, is_free
    FROM lessons
    WHERE course_id = ?
    ORDER BY order_number ASC
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$lessons = [];
while ($row = $result2->fetch_assoc()) {
    // Si no tiene acceso, omitir contenido protegido
    if (!$hasAccess) {
        unset($row['content']);
        unset($row['video_url']);
    }
    $lessons[] = $row;
}
$stmt2->close();

$course['lessons'] = $lessons;
$course['content_restricted'] = !$hasAccess;

echo json_encode($course);
$conn->close();
