<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();
handlePreflight();
requireMethod('POST');

$pdo = getConnection();
$body = getRequestBody();

if (empty($body['email']) || empty($body['password'])) {
    sendError('Correo y contraseña son obligatorios');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM usuarios
    WHERE email = ?
");

$stmt->execute([$body['email']]);

$usuario = $stmt->fetch();

if (
    !$usuario ||
    !password_verify(
        $body['password'],
        $usuario['password_hash']
    )
) {
    sendError('Correo o contraseña incorrectos', 401);
}

if (
    isset($usuario['email_verified']) &&
    !$usuario['email_verified']
) {
    sendResponse([
        'success' => false,
        'error' => 'Debes verificar tu email antes de iniciar sesión',
        'code' => 'EMAIL_NOT_VERIFIED',
        'user_id' => (int) $usuario['id']
    ], 403);
}

unset($usuario['password_hash']);

try {
    $stmt = $pdo->prepare("INSERT INTO user_metrics_log (user_id, event) VALUES (?, 'login')");
    $stmt->execute([$usuario['id']]);
} catch (Throwable $e) {
    error_log('login.php metrics: ' . $e->getMessage());
}

sendResponse([
    'success' => true,
    'user' => $usuario
]);