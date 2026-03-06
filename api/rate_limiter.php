<?php
/**
 * rate_limiter.php — Ubicación: api/rate_limiter.php
 *
 * Protección de doble capa:
 *   - Por IP:    bloquea ataques desde un mismo origen contra cualquier cuenta
 *   - Por email: bloquea ataques dirigidos a una cuenta específica desde distintas IPs
 */

define('MAX_ATTEMPTS',    5);
define('LOCKOUT_MINUTES', 15);

function checkRateLimit(mysqli $conn, string $ip, string $email): void
{
    // Sincronizar timezone con la BD
    $conn->query("SET time_zone = '+00:00'");
    date_default_timezone_set('UTC');

    $window = date('Y-m-d H:i:s', strtotime('-' . LOCKOUT_MINUTES . ' minutes'));

    // Capa 1: verificar por IP
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS attempts FROM login_attempts
         WHERE identifier = ? AND type = 'ip' AND attempted_at > ?"
    );
    $stmt->bind_param("ss", $ip, $window);
    $stmt->execute();
    $ipAttempts = (int) $stmt->get_result()->fetch_assoc()['attempts'];
    $stmt->close();

    if ($ipAttempts >= MAX_ATTEMPTS) {
        _respondTooManyRequests(
            'Demasiados intentos fallidos desde tu red. ' .
            'Intenta de nuevo en ' . LOCKOUT_MINUTES . ' minutos.'
        );
    }

    // Capa 2: verificar por email (guardado hasheado, nunca en claro)
    $emailHash = hash('sha256', strtolower(trim($email)));
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS attempts FROM login_attempts
         WHERE identifier = ? AND type = 'email' AND attempted_at > ?"
    );
    $stmt->bind_param("ss", $emailHash, $window);
    $stmt->execute();
    $emailAttempts = (int) $stmt->get_result()->fetch_assoc()['attempts'];
    $stmt->close();

    if ($emailAttempts >= MAX_ATTEMPTS) {
        _respondTooManyRequests(
            'Esta cuenta ha sido bloqueada temporalmente. ' .
            'Intenta de nuevo en ' . LOCKOUT_MINUTES . ' minutos.'
        );
    }
}

function recordFailedAttempt(mysqli $conn, string $ip, string $email): void
{
    $emailHash = hash('sha256', strtolower(trim($email)));
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (identifier, type) VALUES (?, 'ip'), (?, 'email')"
    );
    $stmt->bind_param("ss", $ip, $emailHash);
    $stmt->execute();
    $stmt->close();
}

function clearFailedAttempts(mysqli $conn, string $ip, string $email): void
{
    $emailHash = hash('sha256', strtolower(trim($email)));
    $stmt = $conn->prepare(
        "DELETE FROM login_attempts
         WHERE (identifier = ? AND type = 'ip') OR (identifier = ? AND type = 'email')"
    );
    $stmt->bind_param("ss", $ip, $emailHash);
    $stmt->execute();
    $stmt->close();
}

function getClientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }
    return $ip;
}

function _respondTooManyRequests(string $message): void
{
    http_response_code(429);
    header('Retry-After: ' . (LOCKOUT_MINUTES * 60));
    echo json_encode(['message' => $message, 'retry_after' => LOCKOUT_MINUTES * 60]);
    exit();
}