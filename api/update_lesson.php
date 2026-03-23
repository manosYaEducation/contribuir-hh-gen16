<?php
// Capturar cualquier output espurio
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require 'db_connect.php';
require 'auth_check.php';

// Limpiar cualquier output previo
ob_end_clean();
header('Content-Type: application/json');

// Validar que el método sea PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use PUT.']);
    exit();
}

// Parsear datos de PUT desde php://input
$input = file_get_contents("php://input");
$_PUT = [];

// Intentar parsear como JSON primero
if (!empty($input)) {
    $decoded = json_decode($input, true);
    if ($decoded !== null) {
        $_PUT = $decoded;
    } else {
        // Si no es JSON, intentar parsear como form-data
        parse_str($input, $_PUT);
    }
}

// Obtener el lesson_id desde query parameter o POST/PUT data
$lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : (isset($_PUT['lesson_id']) ? intval($_PUT['lesson_id']) : 0);

if ($lesson_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'lesson_id es requerido y debe ser mayor a 0']);
    exit();
}

// Verificar que la lección existe y obtener el course_id asociado
$check_sql = "SELECT id, course_id FROM lessons WHERE id = ?";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("i", $lesson_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'La lección especificada no existe']);
    exit();
}

$lesson_info = $result_check->fetch_assoc();
$course_id = (int)$lesson_info['course_id'];
$stmt_check->close();

// Validar que el usuario tiene autorización para editar este curso
requireCourseOwnership($conn, $course_id);

// Recolectar campos a actualizar
$fields_to_update = [];
$params = [];
$param_types = "";

// Title - opcional pero si se proporciona debe ser válido
if (isset($_PUT['title'])) {
    $title = trim($_PUT['title']);
    if (strlen($title) < 3 || strlen($title) > 255) {
        http_response_code(400);
        echo json_encode(['error' => 'El título debe tener entre 3 y 255 caracteres']);
        exit();
    }
    $fields_to_update[] = "title = ?";
    $params[] = $title;
    $param_types .= "s";
}

// Description - opcional
if (isset($_PUT['description'])) {
    $description = $_PUT['description'] !== '' ? trim($_PUT['description']) : null;
    $fields_to_update[] = "description = ?";
    $params[] = $description;
    $param_types .= "s";
}

// Order number - opcional
if (isset($_PUT['order_number'])) {
    $order_number = $_PUT['order_number'] !== '' ? intval($_PUT['order_number']) : null;
    $fields_to_update[] = "order_number = ?";
    $params[] = $order_number;
    $param_types .= "i";
}

// Content - opcional
if (isset($_PUT['content'])) {
    $content = $_PUT['content'] !== '' ? trim($_PUT['content']) : null;
    $fields_to_update[] = "content = ?";
    $params[] = $content;
    $param_types .= "s";
}

// Video URL - opcional
if (isset($_PUT['video_url'])) {
    $video_url = $_PUT['video_url'] !== '' ? trim($_PUT['video_url']) : null;
    $fields_to_update[] = "video_url = ?";
    $params[] = $video_url;
    $param_types .= "s";
}

// Duration - opcional
if (isset($_PUT['duration'])) {
    $duration = $_PUT['duration'] !== '' ? intval($_PUT['duration']) : null;
    
    if ($duration !== null && $duration < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'La duración no puede ser negativa']);
        exit();
    }
    
    $fields_to_update[] = "duration = ?";
    $params[] = $duration;
    $param_types .= "i";
}

// Is free - opcional
if (isset($_PUT['is_free'])) {
    $is_free = intval($_PUT['is_free']);
    
    if ($is_free !== 0 && $is_free !== 1) {
        http_response_code(400);
        echo json_encode(['error' => 'is_free debe ser 0 o 1']);
        exit();
    }
    
    $fields_to_update[] = "is_free = ?";
    $params[] = $is_free;
    $param_types .= "i";
}

// Verificar que al menos un campo fue proporcionado para actualizar
if (empty($fields_to_update)) {
    http_response_code(400);
    echo json_encode(['error' => 'Debe proporcionar al menos un campo para actualizar']);
    exit();
}

// Construir la consulta UPDATE
$update_sql = "UPDATE lessons SET " . implode(", ", $fields_to_update) . " WHERE id = ?";

// Agregar lesson_id al final de los parámetros
$params[] = $lesson_id;
$param_types .= "i";

$stmt = $conn->prepare($update_sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la preparación de la consulta: ' . $conn->error]);
    exit();
}

// Dinamicamente bind los parámetros
$stmt->bind_param($param_types, ...$params);

if ($stmt->execute()) {
    // Obtener los datos actualizados
    $fetch_sql = "SELECT id, course_id, title, description, order_number, content, video_url, duration, is_free, created_at FROM lessons WHERE id = ?";
    $stmt_fetch = $conn->prepare($fetch_sql);
    $stmt_fetch->bind_param("i", $lesson_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $updated_lesson = $result_fetch->fetch_assoc();
    $stmt_fetch->close();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lección actualizada exitosamente',
        'data' => $updated_lesson
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar la lección: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
