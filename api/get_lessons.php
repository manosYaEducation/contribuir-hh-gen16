<?php
header('Content-Type: application/json');
require 'db_connect.php';

//Obtener el ID del curso
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'course_id inválido']);
    exit;
}

$sql = "SELECT id, title, description, content, video_url, order_number FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();

$result = $stmt->get_result();
$lessons = [];

while ($row = $result->fetch_assoc()) {
    $lessons[] = $row;
}

echo json_encode($lessons);
$stmt->close();
$conn->close();
?>
