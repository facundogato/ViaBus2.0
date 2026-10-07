<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/verification-helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
$body = getRequestBody();

$userId = (int)($body['user_id'] ?? 0);
$code = trim($body['code'] ?? '');

if ($userId <= 0 || !preg_match('/^\d{6}$/', $code)) {
    jsonOut(['success' => false, 'error' => 'Datos inválidos'], 400);
}

$stmt = $pdo->prepare(
    "SELECT id, email_verified FROM usuarios WHERE id = :id"
);
$stmt->execute(['id' => $userId]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    jsonOut(['success' => false, 'error' => 'Usuario no existe'], 404);
}

if ($usuario['email_verified']) {
    jsonOut([
        'success' => true,
        'message' => 'Email ya verificado'
    ]);
}

$result = validateVerificationCode($pdo, $userId, $code);

if (!$result['ok']) {
    jsonOut([
        'success' => false,
        'error' => $result['error']
    ], 401);
}

jsonOut([
    'success' => true,
    'message' => 'Email verificado correctamente'
]);
