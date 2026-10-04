<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// POST /api/auth.php?action=login    → iniciar sesión (email + password)
// POST /api/auth.php?action=register → crear una cuenta nueva (rol "cliente")

if ($method === 'POST' && $action === 'login') {
    $body = getRequestBody();

    if (empty($body['email']) || empty($body['password'])) {
        sendError('Correo y contraseña son obligatorios');
    }

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$body['email']]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($body['password'], $usuario['password_hash'])) {
        sendError('Correo o contraseña incorrectos', 401);
    }

    unset($usuario['password_hash']);
    sendResponse($usuario);
}

if ($method === 'POST' && $action === 'register') {
    $body = getRequestBody();

    foreach (['nombre', 'apellido', 'email', 'password'] as $f) {
        if (empty($body[$f])) sendError("El campo '$f' es obligatorio");
    }
    if (strlen($body['password']) < 6) {
        sendError('La contraseña debe tener al menos 6 caracteres');
    }

    try {
        $hash = password_hash($body['password'], PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password_hash, rol)
            VALUES (?, ?, ?, ?, 'cliente')
        ")->execute([$body['nombre'], $body['apellido'], $body['email'], $hash]);

        $id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, rol, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse($stmt->fetch(), 201);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') sendError('Ese correo ya está registrado');
        throw $e;
    }
}

// Después de login exitoso
$metricsService = new MetricsService($pdo);
$metricsService->logUserEvent(
    $userId,
    'login',
    $_SERVER['REMOTE_ADDR'],
    session_id()
);

// En tu ruta de logout
$metricsService = new MetricsService($pdo);
$metricsService->logUserEvent(
    $_SESSION['user_id'],
    'logout',
    $_SERVER['REMOTE_ADDR'],
    session_id()
);
sendError('Método no soportado', 405);
