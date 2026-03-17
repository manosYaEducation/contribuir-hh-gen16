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
// para probar =>/delete_lesson.php/5

// Solo permitir método DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use DELETE']);
    exit;
}

// Obtener el ID de la lección del PATH (estándar REST)
$lesson_id = null;

// 1. Intentar obtener del PATH_INFO (ej: /api/delete_lesson.php/5) - ESTÁNDAR REST
if (!empty($_SERVER['PATH_INFO'])) {
    $path_parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (!empty($path_parts[0])) {
        $lesson_id = intval($path_parts[0]);
    }
}

// 2. Fallback: obtener del query parameter (ej: /api/delete_lesson.php?id=5)
if (!$lesson_id && isset($_GET['id'])) {
    $lesson_id = intval($_GET['id']);
}

// 3. Fallback: obtener del body JSON
if (!$lesson_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['id'])) {
        $lesson_id = intval($input['id']);
    }
}

if ($lesson_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de lección inválido']);
    exit;
}

// Verificar que la lección existe
$check_sql = "SELECT id, course_id FROM lessons WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $lesson_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Lección no encontrada']);
    exit;
}

$lesson_info = $check_result->fetch_assoc();
$course_id = (int)$lesson_info['course_id'];

// Validar que el usuario tiene autorización para eliminar del curso
requireCourseOwnership($conn, $course_id);

// Eliminar la lección
$delete_sql = "DELETE FROM lessons WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $lesson_id);

if ($delete_stmt->execute()) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lección eliminada correctamente',
        'lesson_id' => $lesson_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al eliminar la lección',
        'details' => $conn->error
    ]);
}

$check_stmt->close();
$delete_stmt->close();
$conn->close();
