<?php
// api/enroll_course.php

require 'db_connect.php';
require 'auth_check.php';

header('Content-Type: application/json');

// Solo estudiantes (y admins) pueden inscribirse — los instructores no se "inscriben" a cursos
requireRole(['admin', 'student']);

$userId   = getCurrentUserId();
$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

if (!$courseId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso inválido']);
    exit();
}

// Verificar que el curso existe
$courseCheck = $conn->prepare("SELECT id FROM courses WHERE id = ?");
$courseCheck->bind_param("i", $courseId);
$courseCheck->execute();
if ($courseCheck->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'El curso no existe']);
    $courseCheck->close();
    exit();
}
$courseCheck->close();

// Verificar si ya está inscrito
$checkStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$checkStmt->bind_param("ii", $userId, $courseId);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Ya estás inscrito en este curso']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Inscribir al usuario
$stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, progress, hours_completed) VALUES (?, ?, 0, 0)");
$stmt->bind_param("ii", $userId, $courseId);

if ($stmt->execute()) {
    $enrollmentId = $stmt->insert_id;

    // Actualizar contador de estudiantes
    $updateStmt = $conn->prepare("UPDATE courses SET students = students + 1 WHERE id = ?");
    $updateStmt->bind_param("i", $courseId);
    $updateStmt->execute();
    $updateStmt->close();

    http_response_code(201);
    echo json_encode([
        'message'      => '¡Inscripción exitosa!',
        'enrollmentId' => $enrollmentId,
        'success'      => true
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al inscribirse: ' . $stmt->error]);
}

$stmt->close();
$conn->close();