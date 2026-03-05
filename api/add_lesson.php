<?php
header('Content-Type: application/json');
require 'db_connect.php';

// Validar que los datos requeridos estén presentes
$required_fields = ['course_id', 'title'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        http_response_code(400);
        echo json_encode(['error' => "El campo '$field' es requerido"]);
        exit();
    }
}

// Obtener y limpiar datos obligatorios
$course_id = (int)$_POST['course_id'];
$title = trim($_POST['title']);

// Obtener datos opcionales
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$order_number = isset($_POST['order_number']) ? (int)$_POST['order_number'] : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : null;
$video_url = isset($_POST['video_url']) ? trim($_POST['video_url']) : null;
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : null;
$is_free = isset($_POST['is_free']) ? (int)$_POST['is_free'] : 0;

// Validaciones
if ($course_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El course_id debe ser mayor a 0']);
    exit();
}

if (strlen($title) < 3 || strlen($title) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'El título debe tener entre 3 y 255 caracteres']);
    exit();
}

// Validar que el curso exista
$check_course = "SELECT id FROM courses WHERE id = ?";
$stmt_check = $conn->prepare($check_course);
$stmt_check->bind_param("i", $course_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'El curso especificado no existe']);
    exit();
}
$stmt_check->close();

if ($duration !== null && $duration < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'La duración no puede ser negativa']);
    exit();
}

if ($is_free !== 0 && $is_free !== 1) {
    http_response_code(400);
    echo json_encode(['error' => 'is_free debe ser 0 o 1']);
    exit();
}

// Preparar la consulta de inserción
$sql = "INSERT INTO lessons (course_id, title, description, order_number, content, video_url, duration, is_free) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la preparación de la consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param("issiisii", $course_id, $title, $description, $order_number, $content, $video_url, $duration, $is_free);

if ($stmt->execute()) {
    $lesson_id = $conn->insert_id;
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Lección creada exitosamente',
        'lesson_id' => $lesson_id,
        'data' => [
            'id' => $lesson_id,
            'course_id' => $course_id,
            'title' => $title,
            'description' => $description,
            'order_number' => $order_number,
            'content' => $content,
            'video_url' => $video_url,
            'duration' => $duration,
            'is_free' => $is_free
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear la lección: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
