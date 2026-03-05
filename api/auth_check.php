<?php
// api/auth_check.php
// Middleware de autenticación y autorización por roles.
//
// USO en cualquier endpoint:
//   require 'auth_check.php';
//   requireAuth();                          // solo verifica que haya sesión
//   requireRole(['admin']);                 // solo admins
//   requireRole(['admin', 'instructor']);   // admins o instructores
//
// Para validar ownership de un curso (instructor solo edita los suyos):
//   requireCourseOwnership($conn, $courseId);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que el usuario esté autenticado.
 * Si no hay sesión activa, responde 401 y detiene la ejecución.
 */
function requireAuth(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado. Iniciá sesión para continuar.']);
        exit();
    }
}

/**
 * Verifica que el usuario tenga uno de los roles permitidos.
 *
 * @param string[] $allowedRoles  Ej: ['admin', 'instructor']
 */
function requireRole(array $allowedRoles): void {
    requireAuth();

    $userRole = $_SESSION['user_role'] ?? null;

    if (!$userRole || !in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode([
            'error'    => 'No tenés permisos para realizar esta acción.',
            'required' => $allowedRoles,
            'yours'    => $userRole ?? 'sin rol'
        ]);
        exit();
    }
}

/**
 * Verifica que el usuario sea admin, O que sea instructor y dueño del curso.
 * Útil para endpoints de edición/eliminación (PUT/POST) donde el instructor solo toca los suyos.
 *
 * @param mysqli $conn      Conexión activa a la BD
 * @param int    $courseId  ID del curso a verificar
 */
function requireCourseOwnership(mysqli $conn, int $courseId): void {
    requireAuth();

    $userRole = $_SESSION['user_role'] ?? null;
    $userId   = $_SESSION['user_id'];

    // Admin siempre puede
    if ($userRole === 'admin') {
        return;
    }

    // Instructor solo puede si el curso le pertenece
    if ($userRole === 'instructor') {
        $stmt = $conn->prepare("SELECT instructor_id FROM courses WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al verificar permisos.']);
            exit();
        }
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Curso no encontrado.']);
            exit();
        }

        if ((int)$result['instructor_id'] === $userId) {
            return; // es el dueño, permitir
        }

        http_response_code(403);
        echo json_encode(['error' => 'Solo podés modificar tus propios cursos.']);
        exit();
    }

    // Cualquier otro rol (ej: student): sin permiso
    http_response_code(403);
    echo json_encode(['error' => 'No tenés permisos para realizar esta acción.']);
    exit();
}

/**
 * Helper: devuelve el rol del usuario actual o null si no hay sesión.
 */
function getCurrentRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Helper: devuelve el ID del usuario actual o null si no hay sesión.
 */
function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}