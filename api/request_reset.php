<?php
session_start();
require 'db_connect.php';
require 'validators.php';

// Incluir PHPMailer (sin Composer)
require dirname(__FILE__) . '/../lib/PHPMailer/Exception.php';
require dirname(__FILE__) . '/../lib/PHPMailer/PHPMailer.php';
require dirname(__FILE__) . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// 1. Validar campo obligatorio
if (empty($email)) {
    jsonError(400, 'El correo electrónico es requerido.');
}

// 2. Validar formato de email (función compartida de validators.php)
if (!isValidEmail($email)) {
    jsonError(400, 'Formato de correo electrónico inválido.');
}

// 3. Buscar usuario por email
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor.']);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// 4. Por seguridad, siempre respondemos éxito (no revelar si el email existe)
if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    http_response_code(200);
    echo json_encode(['message' => 'Si el correo está registrado, recibirás un código de verificación.']);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// 5. Generar código aleatorio de 5 dígitos
$resetCode = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

// 6. Guardar código y fecha de expiración (30 minutos)
$stmtUpdate = $conn->prepare(
    "UPDATE users SET reset_code = ?, reset_code_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?"
);
if (!$stmtUpdate) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor.']);
    exit();
}

$stmtUpdate->bind_param("si", $resetCode, $user['id']);
$stmtUpdate->execute();
$stmtUpdate->close();

// 7. Enviar correo con PHPMailer
try {
    $mail = new PHPMailer(true);

    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';

    // Remitente y destinatario
    $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    $mail->addAddress($user['email'], $user['name']);

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña - Con Tribu Ir';
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: #054581; margin: 0;">Con Tribu Ir</h2>
            <p style="color: #57534e; margin-top: 5px;">Recuperación de contraseña</p>
        </div>
        
        <p style="color: #44403c; font-size: 16px;">Hola <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>
        
        <p style="color: #57534e; font-size: 14px;">
            Recibimos una solicitud para restablecer tu contraseña. 
            Usa el siguiente código de verificación:
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <div style="background: #f5f5f4; border: 2px solid #054581; border-radius: 12px; padding: 20px; display: inline-block;">
                <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #054581;">' . $resetCode . '</span>
            </div>
        </div>
        
        <p style="color: #57534e; font-size: 14px;">
            Este código expira en <strong>30 minutos</strong>.
        </p>
        
        <p style="color: #dc2626; font-size: 13px; font-weight: 600; background: #fef2f2; padding: 10px; border-radius: 8px; margin-top: 15px;">
            ⚠️ No compartas este código con nadie. Nuestro equipo nunca te pedirá este código por teléfono, correo o redes sociales.
        </p>
        
        <p style="color: #a8a29e; font-size: 12px; margin-top: 30px; border-top: 1px solid #e7e5e4; padding-top: 15px;">
            Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña no será modificada.
        </p>
    </div>';

    $mail->AltBody = "Hola {$user['name']}, tu código de verificación es: {$resetCode}. Este código expira en 30 minutos. IMPORTANTE: No compartas este código con nadie.";

    $mail->send();

    http_response_code(200);
    echo json_encode(['message' => 'Si el correo está registrado, recibirás un código de verificación.']);

} catch (Exception $e) {
    error_log("Error al enviar correo de recuperación: " . $mail->ErrorInfo);
    
    http_response_code(500);
    echo json_encode(['message' => 'Error al enviar el correo. Por favor intenta más tarde.']);
}

$conn->close();
?>
