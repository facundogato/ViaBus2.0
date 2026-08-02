<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// PUT /api/usuarios.php?id=5                    → actualizar nombre/apellido/email
// PUT /api/usuarios.php?id=5&action=password     → cambiar contraseña

if ($method === 'PUT') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $id     = (int)$_GET['id'];
    $body   = getRequestBody();
    $action = $_GET['action'] ?? 'datos';

    $stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtU->execute([$id]);
    $usuario = $stmtU->fetch();
    if (!$usuario) sendError('Usuario no encontrado', 404);

    if ($action === 'password') {
        if (empty($body['actual']) || empty($body['nueva'])) {
            sendError('Ingresá la contraseña actual y la nueva');
        }
        if (!password_verify($body['actual'], $usuario['password_hash'])) {
            sendError('La contraseña actual es incorrecta', 401);
        }
        if (strlen($body['nueva']) < 6) {
            sendError('La nueva contraseña debe tener al menos 6 caracteres');
        }

        $hash = password_hash($body['nueva'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
        sendResponse(['message' => 'Contraseña actualizada']);
    }

    // Actualizar datos personales
    foreach (['nombre', 'apellido', 'email'] as $f) {
        if (empty($body[$f])) sendError("El campo '$f' es obligatorio");
    }

    try {
        $pdo->prepare("
            UPDATE usuarios SET nombre = ?, apellido = ?, email = ? WHERE id = ?
        ")->execute([$body['nombre'], $body['apellido'], $body['email'], $id]);

        $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, rol, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse($stmt->fetch());
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') sendError('Ese correo ya está en uso por otra cuenta');
        throw $e;
    }
}

sendError('Método no soportado', 405);
