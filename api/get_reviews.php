<?php
// api/get_reviews.php
require 'db_connect.php';
header('Content-Type: application/json');

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id > 0) {
    $stmt = $conn->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, u.name as user_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.course_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    echo json_encode($reviews);
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso inválido']);
}
$conn->close();
?>
