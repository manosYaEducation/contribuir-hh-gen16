<?php
// api/delete_course.php
// Elimina un curso y su detalle asociado (courses + detail_courses)

session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// 1. Solo aceptar método DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usar DELETE.']);
    exit();
}

// 2. Verificar rol de administrador
// TODO: reemplazar este bloque completo cuando el sistema de roles esté implementado:
//
//   if (!isset($_SESSION['user_id'])) {
//       http_response_code(401);
//       echo json_encode(['error' => 'No autenticado']);
//       exit();
//   }
//
//   if ($_SESSION['user_role'] !== 'admin') {
//       http_response_code(403);
//       echo json_encode(['error' => 'Solo el Administrador puede eliminar cursos']);
//       exit();
//   }

// TODO: Bloqueo temporal hasta que el sistema de roles esté implementado
http_response_code(403);
echo json_encode(['error' => 'Endpoint no disponible aún. Requiere sistema de roles.']);
exit();

// 3. Leer el ID desde la URL (ej: api/delete_course.php?id=5)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso inválido']);
    exit();
}

// 4. Verificar que el curso existe antes de intentar borrar
$checkStmt = $conn->prepare("SELECT id, title FROM courses WHERE id = ?");
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de preparación: ' . $conn->error]);
    exit();
}
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Curso no encontrado']);
    $checkStmt->close();
    $conn->close();
    exit();
}

$course = $checkResult->fetch_assoc();
$checkStmt->close();

// 5. Iniciar transacción para borrar en ambas tablas de forma segura
$conn->begin_transaction();

try {
    // Borrar primero el detalle (tabla hija) para respetar FK
    $deleteDetail = $conn->prepare("DELETE FROM detail_courses WHERE course_id = ?");
    if (!$deleteDetail) {
        throw new Exception('Error preparando borrado de detalle: ' . $conn->error);
    }
    $deleteDetail->bind_param("i", $id);
    $deleteDetail->execute();
    $deleteDetail->close();

    // Borrar el curso (tabla padre)
    $deleteCourse = $conn->prepare("DELETE FROM courses WHERE id = ?");
    if (!$deleteCourse) {
        throw new Exception('Error preparando borrado de curso: ' . $conn->error);
    }
    $deleteCourse->bind_param("i", $id);
    $deleteCourse->execute();

    if ($deleteCourse->affected_rows === 0) {
        throw new Exception('No se pudo eliminar el curso');
    }
    $deleteCourse->close();

    // Confirmar transacción
    $conn->commit();

    http_response_code(200);
    echo json_encode([
        'message' => "Curso '{$course['title']}' eliminado correctamente",
        'deletedId' => $id
    ]);

} catch (Exception $e) {
    // Revertir si algo falló
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
}

$conn->close();
?>