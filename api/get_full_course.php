<?php
// api/get_full_course.php
require 'db_connect.php';
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
    $lessons[] = $row;
}
$stmt2->close();

$course['lessons'] = $lessons;

echo json_encode($course);
$conn->close();
?>
