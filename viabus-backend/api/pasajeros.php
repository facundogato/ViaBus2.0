<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET  /api/pasajeros.php?documento=12345678  → buscar por documento
// GET  /api/pasajeros.php?id=3               → por ID
// POST /api/pasajeros.php                    → crear
// PUT  /api/pasajeros.php?id=3               → editar

if ($method === 'GET') {
    if (isset($_GET['documento'])) {
        $stmt = $pdo->prepare("SELECT * FROM pasajeros WHERE documento = ?");
        $stmt->execute([$_GET['documento']]);
        $row = $stmt->fetch();
        if (!$row) sendError('Pasajero no encontrado', 404);
        sendResponse($row);
    }
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM pasajeros WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) sendError('Pasajero no encontrado', 404);
        sendResponse($row);
    }
    $stmt = $pdo->query("SELECT * FROM pasajeros ORDER BY apellido, nombre");
    sendResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $body = getRequestBody();
    foreach (['nombre','apellido','documento'] as $f) {
        if (empty($body[$f])) sendError("El campo '$f' es obligatorio");
    }
    try {
        $pdo->prepare("
            INSERT INTO pasajeros (nombre,apellido,documento,email,telefono)
            VALUES (?,?,?,?,?)
        ")->execute([$body['nombre'],$body['apellido'],$body['documento'],$body['email'] ?? null,$body['telefono'] ?? null]);
        sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Pasajero creado'], 201);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') sendError('El documento ya está registrado');
        throw $e;
    }
}

if ($method === 'PUT') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $body   = getRequestBody();
    $id     = (int)$_GET['id'];
    $fields = [];
    $values = [];
    foreach (['nombre','apellido','email','telefono'] as $f) {
        if (array_key_exists($f, $body)) {
            $fields[] = "$f = ?";
            $values[] = $body[$f];
        }
    }
    if (empty($fields)) sendError('Sin campos para actualizar');
    $values[] = $id;
    $pdo->prepare("UPDATE pasajeros SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
    sendResponse(['message' => 'Pasajero actualizado']);
}

sendError('Método no soportado', 405);
