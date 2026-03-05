<?php
require 'db_connect.php';

header('Content-Type: application/json');

// Validar que sea una solicitud PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use PUT']);
    exit();
}

// Obtener datos del PUT
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar que el ID esté presente
if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El ID del detalle es requerido']);
    exit();
}

$detail_id = (int)($data['id'] ?? 0);

if ($detail_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID del detalle inválido']);
    exit();
}

// Verificar que el detalle existe
$verify_sql = "SELECT id, course_id FROM detail_courses WHERE id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("i", $detail_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Detalle del curso no encontrado']);
    exit();
}

$row = $verify_result->fetch_assoc();
$course_id = $row['course_id'];

// Campos actualizables
$updates = [];
$params = [];
$types = '';

// learning_objectives
if (isset($data['learning_objectives'])) {
    $objectives = $data['learning_objectives'];
    
    // Validar que sea JSON válido
    if (!is_array($objectives)) {
        $objectives = json_decode($objectives, true);
    }
    
    if ($objectives === null) {
        http_response_code(400);
        echo json_encode(['error' => 'learning_objectives debe ser un JSON válido']);
        exit();
    }
    
    $objectives_json = json_encode($objectives);
    $updates[] = "learning_objectives = ?";
    $params[] = $objectives_json;
    $types .= 's';
}

// requirements
if (isset($data['requirements'])) {
    $requirements = $data['requirements'];
    
    // Validar que sea JSON válido
    if (!is_array($requirements)) {
        $requirements = json_decode($requirements, true);
    }
    
    if ($requirements === null) {
        http_response_code(400);
        echo json_encode(['error' => 'requirements debe ser un JSON válido']);
        exit();
    }
    
    $requirements_json = json_encode($requirements);
    $updates[] = "requirements = ?";
    $params[] = $requirements_json;
    $types .= 's';
}

// intro_video
if (isset($data['intro_video']) && $data['intro_video'] !== '') {
    $intro_video = trim($data['intro_video']);
    $updates[] = "intro_video = ?";
    $params[] = $intro_video;
    $types .= 's';
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No hay campos para actualizar']);
    exit();
}

// Agregar ID al final
$params[] = $detail_id;
$types .= 'i';

// Construir y ejecutar la actualización
$update_sql = "UPDATE detail_courses SET " . implode(", ", $updates) . " WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);

if (!$update_stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la preparación de la consulta']);
    exit();
}

$update_stmt->bind_param($types, ...$params);

if ($update_stmt->execute()) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Detalle del curso actualizado correctamente',
        'detail_id' => $detail_id,
        'course_id' => $course_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el detalle: ' . $update_stmt->error]);
}

$update_stmt->close();
$verify_stmt->close();
$conn->close();
?>
