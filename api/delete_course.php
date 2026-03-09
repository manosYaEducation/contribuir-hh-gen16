<?php
// api/delete_course.php
// Elimina un curso y su detalle asociado (courses + detail_courses)

require 'db_connect.php';
require 'auth_check.php';

header('Content-Type: application/json');

// Solo aceptar método DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usar DELETE.']);
    exit();
}

// Leer el ID desde la URL: api/delete_course.php?id=5
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso inválido']);
    exit();
}

// Verificar autenticación + ownership (admin puede cualquiera, instructor solo los suyos)
requireCourseOwnership($conn, $id);

// Verificar que el curso existe
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

// Transacción para borrar en ambas tablas
$conn->begin_transaction();

try {
    // Borrar detalle primero (tabla hija)
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

    $conn->commit();

    http_response_code(200);
    echo json_encode([
        'message'   => "Curso '{$course['title']}' eliminado correctamente",
        'deletedId' => $id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
}

$conn->close();