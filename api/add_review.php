<?php
// api/add_review.php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// Validar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para dejar una reseña']);
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($course_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o incompletos']);
    exit();
}

// Verificar que el usuario esté inscrito en el curso
$stmt_check = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt_check->bind_param("ii", $user_id, $course_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Debes estar inscrito en este curso para dejar una reseña']);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

// Verificar si ya dejó una reseña (opcional: limita 1 reseña por usuario/curso)
$stmt_exist = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND course_id = ?");
$stmt_exist->bind_param("ii", $user_id, $course_id);
$stmt_exist->execute();
$result_exist = $stmt_exist->get_result();

if ($result_exist->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Ya has dejado una reseña para este curso']);
    $stmt_exist->close();
    $conn->close();
    exit();
}
$stmt_exist->close();


// Insertar nueva reseña
$stmt = $conn->prepare("INSERT INTO reviews (user_id, course_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $user_id, $course_id, $rating, $comment);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(['message' => 'Reseña enviada correctamente']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar la reseña: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
