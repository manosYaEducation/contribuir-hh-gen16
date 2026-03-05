<?php
// api/check_session.php

// Inicia o reanuda la sesión existente para poder leer sus variables.
session_start();

// Le decimos al navegador que la respuesta será en formato JSON.
header('Content-Type: application/json');

// Verificamos si las variables de sesión que creamos en login.php existen.
if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    // Si existen, el usuario tiene una sesión activa.
    http_response_code(200); // Código 200 OK
    echo json_encode([
        'loggedIn' => true,
        'user_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'] ?? null
    ]);
} else {
    // Si no existen, no hay sesión activa.
    http_response_code(401); // Código 401 No Autorizado
    echo json_encode([
        'loggedIn' => false
    ]);
}
?>