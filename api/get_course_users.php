<?php
// api/get_course_users.php
// AP-07: Endpoint GET para obtener usuarios inscritos en un curso específico
// Retorna la lista completa de usuarios matriculados en un curso dado
// Uso: api/get_course_users.php?course_id=1

session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// ============================================================
// 1. VERIFICACIÓN DE AUTENTICACIÓN
// ============================================================
// Requiere que el usuario esté autenticado para consultar esta información
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autenticado',
        'message' => 'Debe iniciar sesión para acceder a esta información'
    ]);
    exit();
}

// ============================================================
// 2. VALIDACIÓN DE PARÁMETROS
// ============================================================
// Obtener y validar el course_id desde la URL
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Parámetro inválido',
        'message' => 'Debe proporcionar un course_id válido'
    ]);
    exit();
}

// ============================================================
// 3. VERIFICAR QUE EL CURSO EXISTE
// ============================================================
$checkCourse = $conn->prepare("SELECT id, title FROM courses WHERE id = ?");
$checkCourse->bind_param("i", $courseId);
$checkCourse->execute();
$courseResult = $checkCourse->get_result();

if ($courseResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Curso no encontrado',
        'message' => 'No existe un curso con el ID proporcionado'
    ]);
    $checkCourse->close();
    $conn->close();
    exit();
}

// Obtener información del curso para incluirla en la respuesta
$courseInfo = $courseResult->fetch_assoc();
$checkCourse->close();

// ============================================================
// 4. CONSULTAR USUARIOS INSCRITOS EN EL CURSO
// ============================================================
$query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        IFNULL(e.progress, 0) as progress,
        IFNULL(e.hours_completed, 0) as hoursCompleted,
        e.enrolled_date as enrolledDate,
        IFNULL(e.is_completed, 0) as isCompleted,
        e.completed_date as completedDate
    FROM enrollments e
    INNER JOIN users u ON e.user_id = u.id
    WHERE e.course_id = ?
    ORDER BY e.enrolled_date DESC
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de servidor',
        'message' => 'Error al preparar la consulta'
    ]);
    $conn->close();
    exit();
}

$stmt->bind_param("i", $courseId);
$stmt->execute();
$result = $stmt->get_result();

// ============================================================
// 5. PROCESAR RESULTADOS
// ============================================================
$users = [];

while ($row = $result->fetch_assoc()) {
    // Formatear la fecha de inscripción
    if ($row['enrolledDate']) {
        $date = new DateTime($row['enrolledDate']);
        $row['enrolledDate'] = $date->format('d/m/Y');
    }
    
    // Formatear la fecha de completado si existe
    if ($row['completedDate']) {
        $completedDate = new DateTime($row['completedDate']);
        $row['completedDate'] = $completedDate->format('d/m/Y');
    }
    
    // Convertir valores a tipos apropiados
    $row['id'] = (int)$row['id'];
    $row['progress'] = (int)$row['progress'];
    $row['hoursCompleted'] = (int)$row['hoursCompleted'];
    $row['isCompleted'] = (bool)$row['isCompleted'];
    
    $users[] = $row;
}

$stmt->close();
$conn->close();

// ============================================================
// 6. RETORNAR RESPUESTA JSON
// ============================================================
echo json_encode([
    'success' => true,
    'courseId' => (int)$courseId,
    'courseTitle' => $courseInfo['title'],
    'totalUsers' => count($users),
    'users' => $users
]);
?>
