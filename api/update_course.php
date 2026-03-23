<?php
// Capturar cualquier output espurio (warnings, notices con HTML)
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

require 'db_connect.php';
require 'auth_check.php';

// Limpiar cualquier output previo
ob_end_clean();
header('Content-Type: application/json');

// Aceptar POST (multipart/form-data con imagen) o PUT (JSON sin imagen)
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use POST o PUT']);
    exit();
}

// Leer datos según el método
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
} else {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
}

// Manejar subida de imagen de portada
if (isset($_FILES['courseImageFile']) && $_FILES['courseImageFile']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/';
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['courseImageFile']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de imagen no permitido. Use JPG, PNG, GIF o WEBP']);
        exit();
    }
    $ext = strtolower(pathinfo($_FILES['courseImageFile']['name'], PATHINFO_EXTENSION));
    $safeFilename = 'course_' . uniqid('', true) . '.' . $ext;
    if (move_uploaded_file($_FILES['courseImageFile']['tmp_name'], $uploadDir . $safeFilename)) {
        $data['image'] = 'uploads/' . $safeFilename;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al subir la imagen. Verifique permisos del directorio uploads/']);
        exit();
    }
}

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

// Validar que el usuario tiene autorización para editar este curso
requireCourseOwnership($conn, $course_id);

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
