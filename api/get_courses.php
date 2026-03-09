<?php
// api/get_courses.php
// Retorna TODOS los cursos del catálogo sin restricciones de autenticación

header('Content-Type: application/json');
require 'auth_check.php';  // inicia sesión y expone $_SESSION
require 'db_connect.php';

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$courses = [];

// SIEMPRE obtener todos los cursos disponibles, sin importar si está autenticado
$sql = "SELECT c.id, c.title, c.instructor_id, u.name as instructor, c.avatar, c.category, c.price, c.rating, c.students, c.duration, c.image, c.description FROM courses c LEFT JOIN users u ON c.instructor_id = u.id ORDER BY c.id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Recorrer todos los cursos
    while($row = $result->fetch_assoc()) {
        // Convertir valores a números
        $row['id'] = (int)$row['id'];
        $row['instructor_id'] = (int)$row['instructor_id'];
        $row['price'] = (int)$row['price'];
        $row['rating'] = (float)$row['rating'];
        $row['students'] = (int)$row['students'];
        $row['show_details'] = $isAdmin;
        
        $courses[] = $row;
    }
}

$conn->close();

// Retornar todos los cursos
echo json_encode($courses);
?>