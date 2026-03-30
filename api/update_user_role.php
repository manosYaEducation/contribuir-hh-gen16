<?php
// api/update_user_role.php
header('Content-Type: application/json');
require 'auth_check.php';
require 'db_connect.php';

// Solo administradores pueden cambiar roles
requireRole(['admin']);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$new_role = isset($_POST['new_role']) ? trim($_POST['new_role']) : '';

$valid_roles = ['student', 'instructor', 'admin'];

if ($user_id <= 0 || empty($new_role)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (user_id o new_role)."]);
    exit();
}

if (!in_array($new_role, $valid_roles)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Rol no válido."]);
    exit();
}

// Opcional: Evitar que un admin se quite el rol a sí mismo si es el único? No lo haremos tan complejo, 
// pero evitemos actualizar si el userId no existe.
$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al preparar la consulta."]);
    exit();
}

$stmt->bind_param("si", $new_role, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Rol actualizado correctamente."]);
    } else {
        // Pudo haber sido el mismo rol, o usuario no encontrado
        echo json_encode(["status" => "success", "message" => "Sin cambios / Usuario no encontrado."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al actualizar el rol: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
