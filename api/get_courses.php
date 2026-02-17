<?php
// api/get_courses.php
// Retorna TODOS los cursos del catálogo sin restricciones de autenticación

header('Content-Type: application/json');
require 'db_connect.php';

$courses = [];

// SIEMPRE obtener todos los cursos disponibles, sin importar si está autenticado
$sql = "SELECT id, title, instructor, avatar, category, price, rating, students, duration, image, description FROM courses ORDER BY id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Recorrer todos los cursos
    while($row = $result->fetch_assoc()) {
        // Convertir valores a números
        $row['id'] = (int)$row['id'];
        $row['price'] = (int)$row['price'];
        $row['rating'] = (float)$row['rating'];
        $row['students'] = (int)$row['students'];
        
        $courses[] = $row;
    }
}

$conn->close();

// Retornar todos los cursos
echo json_encode($courses);
?>