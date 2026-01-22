<?php
// Reemplaza con tus propios datos de la base de datos de Hostinger
$servername = "localhost"; // Generalmente es localhost en Hostinger
$username = "root";
$password = "";
$dbname = "u125784517_contribuir_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>