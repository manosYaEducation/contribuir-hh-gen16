<?php
// api/get_users.php
header('Content-Type: application/json');
require 'auth_check.php';
require 'db_connect.php';

// Solo administradores pueden listar usuarios
requireRole(['admin']);

$users = [];

$sql = "SELECT id, name, email, role FROM users ORDER BY id ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $users[] = $row;
    }
}

$conn->close();

echo json_encode($users);
?>
