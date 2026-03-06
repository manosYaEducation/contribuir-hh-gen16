<?php
require 'db_connect.php';

header('Content-Type: application/json');

// Validar que todos los campos requeridos estén presentes
// Campos string que no pueden estar vacíos
$required_string_fields = ['title', 'category', 'duration', 'description', 'image', 'avatar'];

foreach ($required_string_fields as $field) {
    if (!isset($_POST[$field]) || (is_string($_POST[$field]) && empty(trim($_POST[$field])))) {
        http_response_code(400);
        echo json_encode(['error' => "El campo '$field' es requerido"]);
        exit();
    }
}

// Validar que campos numéricos existan (pueden ser 0)
$required_numeric_fields = ['instructor_id', 'rating', 'price'];
foreach ($required_numeric_fields as $field) {
    if (!isset($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "El campo '$field' es requerido"]);
        exit();
    }
}

// Obtener y limpiar datos
$title = trim($_POST['title']);
$category = trim($_POST['category']);
$instructor_id = (int)$_POST['instructor_id'];
$rating = (float)$_POST['rating'];
$price = (int)$_POST['price'];
$duration = trim($_POST['duration']);
$description = trim($_POST['description']);
$image = trim($_POST['image']);
$avatar = trim($_POST['avatar']);
$students = isset($_POST['students']) ? (int)$_POST['students'] : 0;

// Validaciones
if ($rating < 0 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'La calificación debe estar entre 0 y 5']);
    exit();
}

if ($price < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El precio no puede ser negativo']);
    exit();
}

if (empty($duration) || strlen($duration) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'La duración no puede estar vacía']);
    exit();
}

if (strlen($title) < 5 || strlen($title) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'El título debe tener entre 5 y 255 caracteres']);
    exit();
}

if (strlen($description) < 20) {
    http_response_code(400);
    echo json_encode(['error' => 'La descripción debe tener al menos 20 caracteres']);
    exit();
}

// Validar URLs o nombres de archivo
if (!filter_var($image, FILTER_VALIDATE_URL) && !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $image)) {
    http_response_code(400);
    echo json_encode(['error' => 'La imagen debe ser una URL válida o un nombre de archivo']);
    exit();
}

if (!filter_var($avatar, FILTER_VALIDATE_URL) && !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $avatar)) {
    http_response_code(400);
    echo json_encode(['error' => 'El avatar debe ser una URL válida o un nombre de archivo']);
    exit();
}

// Verificar que el título no exista
$checkStmt = $conn->prepare("SELECT id FROM courses WHERE title = ?");
$checkStmt->bind_param("s", $title);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Ya existe un curso con ese título']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Insertar curso
$stmt = $conn->prepare(
    "INSERT INTO courses 
    (title, description, category, instructor_id, rating, students, price, image, avatar, duration) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de preparación: ' . $conn->error]);
    exit();
}

$stmt->bind_param(
    "sssiidisss",
    $title,
    $description,
    $category,
    $instructor_id,
    $rating,
    $students,
    $price,
    $image,
    $avatar,
    $duration
);

if ($stmt->execute()) {
    $courseId = $stmt->insert_id;
    http_response_code(201);
    echo json_encode([
        'message' => 'Curso creado exitosamente',
        'courseId' => $courseId,
        'course' => [
            'id' => $courseId,
            'title' => $title,
            'category' => $category,
            'instructor_id' => $instructor_id
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el curso: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>