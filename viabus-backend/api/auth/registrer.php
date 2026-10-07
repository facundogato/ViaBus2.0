<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/verification-helpers.php';

setCorsHeaders();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Método no soportado', 405);
}

$pdo = getConnection();
$body = getRequestBody();

foreach (
    ['nombre', 'apellido', 'email', 'password'] as $campo
) {
    if (empty($body[$campo])) {
        sendError("El campo '$campo' es obligatorio", 400);
    }
}

if (strlen($body['password']) < 6) {
    sendError(
        'La contraseña debe tener al menos 6 caracteres',
        400
    );
}

try {

    $hash = password_hash(
        $body['password'],
        PASSWORD_DEFAULT
    );

    $stmt = $pdo->prepare("
        INSERT INTO usuarios (
            nombre,
            apellido,
            email,
            password_hash,
            rol,
            email_verified
        )
        VALUES (?, ?, ?, ?, 'cliente', FALSE)
        RETURNING id
    ");

    $stmt->execute([
        $body['nombre'],
        $body['apellido'],
        $body['email'],
        $hash
    ]);

    $userId = (int) $stmt->fetchColumn();

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'message' => 'Usuario registrado correctamente'
    ]);

    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23505' || $e->getCode() === '23000') {
    sendError('Ese correo ya está registrado', 409);
}

    throw $e;
}
