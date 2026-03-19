<?php
// api/get_course_detail.php
require 'db_connect.php';
header('Content-Type: application/json');

// 1. Obtener el ID de la URL (ej: api/get_course_detail.php?id=1)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 2. Preparar la consulta para buscar ESE curso específico con sus detalles
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.instructor_id, c.avatar, c.category, c.price, c.students, c.duration, c.image, c.description,
               u.name as instructor,
               d.learning_objectives,
               d.requirements,
               d.intro_video,
               d.recursos,
               COALESCE(ROUND(AVG(rv.rating), 1), c.rating) as rating,
               COUNT(rv.id) as review_count
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        LEFT JOIN detail_courses d ON c.id = d.course_id
        LEFT JOIN reviews rv ON c.id = rv.course_id
        WHERE c.id = ?
        GROUP BY c.id, c.title, c.instructor_id, c.avatar, c.category, c.price,
                 c.students, c.duration, c.image, c.description,
                 u.name, d.learning_objectives, d.requirements, d.intro_video, d.recursos
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Parsear JSON en los campos si existen
        if ($row['learning_objectives']) {
            $row['learning_objectives'] = json_decode($row['learning_objectives'], true);
        }
        if ($row['requirements']) {
            $row['requirements'] = json_decode($row['requirements'], true);
        }
        // Devolver los datos del curso encontrado
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Curso no encontrado']);
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
}
$conn->close();
?>