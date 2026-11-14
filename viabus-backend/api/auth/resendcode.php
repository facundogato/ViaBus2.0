<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/verification-helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
$body = getRequestBody();

$userId = (int)($body['user_id'] ?? 0);

if ($userId <= 0) {
    jsonOut([
        'success' => false,
        'error' => 'Usuario inválido'
    ], 400);
}

$stmt = $pdo->prepare("
    SELECT id, email, nombre, email_verified
    FROM usuarios
    WHERE id = :id
");

$stmt->execute([
    'id' => $userId
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    jsonOut([
        'success' => false,
        'error' => 'Usuario no existe'
    ], 404);
}

if ($usuario['email_verified']) {
    jsonOut([
        'success' => false,
        'error' => 'Email ya verificado'
    ], 400);
}


// Rate limiting:
// máximo 3 códigos por 15 minutos
$rateStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM email_verification_codes
    WHERE user_id = :uid
      AND created_at > NOW() - INTERVAL '15 minutes'
");

$rateStmt->execute([
    'uid' => $userId
]);

if ($rateStmt->fetchColumn() >= 3) {
    jsonOut([
        'success' => false,
        'error' => 'Demasiados intentos. Espera unos minutos.'
    ], 429);
}


// Generar y guardar nuevo código
$code = generateVerificationCode();

storeVerificationCode(
    $pdo,
    $userId,
    $usuario['email'],
    $code
);


// Enviar email
if (
    !sendVerificationEmail(
        $usuario['email'],
        $usuario['nombre'],
        $code
    )
) {
    jsonOut([
        'success' => false,
        'error' => 'Error al enviar el email'
    ], 500);
}


jsonOut([
    'success' => true,
    'message' => 'Nuevo código enviado'
]);
