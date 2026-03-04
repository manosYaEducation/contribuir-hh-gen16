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

// Validar que el ID del curso esté presente
if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El ID del curso es requerido']);
    exit();
}

$course_id = (int)($data['id'] ?? 0);

if ($course_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID del curso inválido']);
    exit();
}

// Verificar que el curso existe
$verify_sql = "SELECT id FROM courses WHERE id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("i", $course_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Curso no encontrado']);
    exit();
}

// Campos actualizables
$fields = ['title', 'category', 'instructor', 'rating', 'price', 'students', 'duration', 'image', 'avatar', 'description'];
$updates = [];
$params = [];
$types = '';

foreach ($fields as $field) {
    if (isset($data[$field]) && $data[$field] !== '') {
        $updates[] = "$field = ?";
        $params[] = trim($data[$field]);
        
        // Determinar tipo de dato
        if (in_array($field, ['price', 'students', 'rating'])) {
            $types .= (strpos($field, 'rating') !== false) ? 'd' : 'i';
        } else {
            $types .= 's';
        }
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No hay campos para actualizar']);
    exit();
}

// Validaciones
if (isset($data['rating'])) {
    $rating = (float)$data['rating'];
    if ($rating < 0 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'La calificación debe estar entre 0 y 5']);
        exit();
    }
}

if (isset($data['price'])) {
    $price = (float)$data['price'];
    if ($price < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'El precio no puede ser negativo']);
        exit();
    }
}

// Agregar ID al final
$params[] = $course_id;
$types .= 'i';

// Construir y ejecutar la actualización
$update_sql = "UPDATE courses SET " . implode(", ", $updates) . " WHERE id = ?";
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
        'message' => 'Curso actualizado correctamente',
        'course_id' => $course_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el curso: ' . $update_stmt->error]);
}

$update_stmt->close();
$verify_stmt->close();
$conn->close();
?>
