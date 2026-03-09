<?php
require 'db_connect.php';

header('Content-Type: application/json');

// Obtener datos - aceptar JSON o Form-data
$data = $_POST;
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

if (strpos($content_type, 'application/json') !== false) {
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true) ?? [];
}

// Validar que todos los campos requeridos estén presentes
if (!isset($data['course_id']) || empty($data['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => "El campo 'course_id' es requerido"]);
    exit();
}

// Obtener y limpiar datos
$course_id = (int)$data['course_id'];
//$learning_objectives = isset($data['learning_objectives']) ? trim($data['learning_objectives']) : null;
//$requirements = isset($data['requirements']) ? trim($data['requirements']) : null;
$intro_video = isset($data['intro_video']) ? trim($data['intro_video']) : null;
$recursos = isset($data['recursos']) ? trim($data['recursos']) : null;

$learning_objectives = null;

if (isset($data['learning_objectives'])) {
    if (is_array($data['learning_objectives'])) {
        $learning_objectives = json_encode($data['learning_objectives']);
    } else {
        $learning_objectives = trim($data['learning_objectives']);
    }
}

$requirements = null;

if (isset($data['requirements'])) {
    if (is_array($data['requirements'])) {
        $requirements = json_encode($data['requirements']);
    } else {
        $requirements = trim($data['requirements']);
    }
}




// Validar que el curso existe
$check_course = $conn->prepare("SELECT id FROM courses WHERE id = ?");
$check_course->bind_param("i", $course_id);
$check_course->execute();
$result = $check_course->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'El curso no existe']);
    exit();
}

$check_course->close();

// Validar JSON si están presentes
if ($learning_objectives !== null && !empty($learning_objectives)) {
    if (!is_valid_json($learning_objectives)) {
        http_response_code(400);
        echo json_encode(['error' => 'learning_objectives debe ser un JSON válido']);
        exit();
    }
}

if ($requirements !== null && !empty($requirements)) {
    if (!is_valid_json($requirements)) {
        http_response_code(400);
        echo json_encode(['error' => 'requirements debe ser un JSON válido']);
        exit();
    }
}

// Validar URL del video intro si está presente
if ($intro_video !== null && !empty($intro_video)) {
    if (!filter_var($intro_video, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'intro_video debe ser una URL válida']);
        exit();
    }
}

// Verificar si el detalle ya existe
$check_detail = $conn->prepare("SELECT id FROM detail_courses WHERE course_id = ?");
$check_detail->bind_param("i", $course_id);
$check_detail->execute();
$detail_exists = $check_detail->get_result()->num_rows > 0;
$check_detail->close();

if ($detail_exists) {
    // Actualizar si ya existe
    $stmt = $conn->prepare(
        "UPDATE detail_courses SET 
        learning_objectives = ?,
        requirements = ?,
        intro_video = ?,
        recursos = ?
        WHERE course_id = ?"
    );
    
    $stmt->bind_param("ssssi", $learning_objectives, $requirements, $intro_video, $recursos, $course_id);
} else {
    // Insertar si no existe
    $stmt = $conn->prepare(
        "INSERT INTO detail_courses (course_id, learning_objectives, requirements, intro_video, recursos)
        VALUES (?, ?, ?, ?, ?)"
    );
    
    $stmt->bind_param("issss", $course_id, $learning_objectives, $requirements, $intro_video, $recursos);
}

if ($stmt->execute()) {
    http_response_code($detail_exists ? 200 : 201);
    echo json_encode([
        'success' => true,
        'message' => $detail_exists ? 'Detalles del curso actualizados correctamente' : 'Detalles del curso creados correctamente',
        'course_id' => $course_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar los detalles del curso: ' . $conn->error]);
}

$stmt->close();
$conn->close();

// Helper function para validar JSON
function is_valid_json($string) {
    if (!is_string($string)) {
        return false;
    }
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}
?>
