<?php
// api/get_course_detail.php
require 'db_connect.php';
header('Content-Type: application/json');

// 1. Obtener el ID de la URL (ej: api/get_course_detail.php?id=1)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 2. Preparar la consulta para buscar ESE curso específico
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
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