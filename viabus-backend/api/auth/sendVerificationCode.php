<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/verification-helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
$body = getRequestBody();
$email = trim(strtolower($body['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonOut(['success' => false, 'error' => 'Email inválido'], 400);
}

// Buscar usuario
$stmt = $pdo->prepare("SELECT id, nombre, email_verified FROM usuarios WHERE email = :email");
$stmt->execute(['email' => $email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    jsonOut(['success' => false, 'error' => 'Usuario no encontrado'], 404);
}
if ($usuario['email_verified']) {
    jsonOut(['success' => false, 'error' => 'Email ya verificado'], 400);
}

// Rate limiting: máx 3 códigos por 15 min
$rateStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM email_verification_codes
     WHERE user_id = :uid AND created_at > NOW() - INTERVAL '15 minutes'"
);
$rateStmt->execute(['uid' => $usuario['id']]);
if ($rateStmt->fetchColumn() >= 3) {
    jsonOut(['success' => false, 'error' => 'Demasiados intentos. Espera unos minutos.'], 429);
}

$code = generateVerificationCode();
storeVerificationCode($pdo, (int)$usuario['id'], $email, $code);

if (!sendVerificationEmail($email, $usuario['nombre'], $code)) {
    jsonOut(['success' => false, 'error' => 'Error al enviar el email'], 500);
}

jsonOut([
    'success' => true,
    'message' => 'Código enviado al correo',
    'user_id' => (int)$usuario['id']
]);
