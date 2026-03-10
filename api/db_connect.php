<?php
// Incluir configuración desde archivo .env
require_once dirname(__FILE__) . '/../config/config.php';

// Crear conexión usando variables seguras
$conn = new mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Establecer charset a UTF-8
$conn->set_charset("utf8mb4");

