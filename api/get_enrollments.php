<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Verificar que el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$userId = $_SESSION['user_id'];

// Consulta para obtener los cursos inscritos del usuario
// Primero verificamos si las tablas existen
$checkTables = "
    SELECT COUNT(*) as count 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
    AND table_name IN ('enrollments', 'courses')
";
$tablesExist = $conn->query($checkTables);
$tableCount = $tablesExist->fetch_assoc()['count'];

if ($tableCount < 2) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de configuración: Tablas no encontradas',
        'detail' => 'Las tablas necesarias no existen en la base de datos'
    ]);
    exit();
}

$query = "
    SELECT 
        e.id as enrollmentId,
        IFNULL(e.progress, 0) as progress,
        e.enrolled_date as enrolledDate,
        IFNULL(e.hours_completed, 0) as hoursCompleted,
        IFNULL(e.is_completed, 0) as isCompleted,
        e.certificate_url as certificateUrl,
        c.id,
        c.title,
        c.instructor,
        c.avatar,
        c.image,
        c.category,
        c.price,
        c.rating,
        c.students,
        IFNULL(c.duration, '0') as totalHours
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_date DESC
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de preparación: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$enrollments = [];
while ($row = $result->fetch_assoc()) {
    // Formatear la fecha
    $date = new DateTime($row['enrolledDate']);
    $row['enrolledDate'] = $date->format('d/m/Y');
    
    // Convertir valores a números
    $row['progress'] = (int)$row['progress'];
    $row['hoursCompleted'] = (int)$row['hoursCompleted'];
    $row['isCompleted'] = (int)$row['isCompleted'];
    $row['price'] = (int)$row['price'];
    $row['rating'] = (float)$row['rating'];
    $row['students'] = (int)$row['students'];
    
    // Extraer horas del texto de duración (ej: "28 horas" -> 28)
    preg_match('/\d+/', $row['totalHours'], $matches);
    $row['totalHours'] = isset($matches[0]) ? (int)$matches[0] : 0;
    
    $enrollments[] = $row;
}

$stmt->close();
$conn->close();

http_response_code(200);
echo json_encode($enrollments);
?>