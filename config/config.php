<?php
/**
 * Archivo de configuración
 * Lee las variables desde el archivo .env
 */

// Determinar la ruta del archivo .env
$env_file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '.env';

// Función para cargar variables del archivo .env
function loadEnv($file) {
    if (!file_exists($file)) {
        die('ERROR: Archivo .env no encontrado. Por favor crea .env desde .env.example');
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remover comillas si existen
        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
        if (!isset($_SERVER[$key])) {
            $_SERVER[$key] = $value;
        }
    }
}

// Cargar las variables del .env
loadEnv($env_file);

// Obtener variables de configuración
define('DB_SERVERNAME', $_ENV['DB_SERVERNAME'] ?? 'localhost');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Validar que las variables necesarias estén definidas
if (empty(DB_NAME)) {
    die('ERROR: DB_NAME no está configurada en .env');
}

?>
