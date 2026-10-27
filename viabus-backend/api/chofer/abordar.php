<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../api/auth/auth.php';

setCorsHeaders();
handlePreflight();
requireMethod('POST');

$pdo    = getConnection();
$chofer = obtenerChoferAutenticado($pdo);
$body   = getRequestBody();

$cardUid    = trim($body['card_uid'] ?? '');
$servicioId = (int) ($body['servicio_id'] ?? 0);

if ($cardUid === '' || !$servicioId) {
    sendError('Datos incompletos');
}

// 1. El servicio tiene que ser de ESTE chofer
$stmt = $pdo->prepare("SELECT 1 FROM servicios WHERE id = ? AND chofer_id = ?");
$stmt->execute([$servicioId, $chofer['id']]);
if (!$stmt->fetch()) {
    sendError('Ese viaje no está asignado a vos', 403);
}

// 2. Tarjeta activa y asignada a un usuario
$stmt = $pdo->prepare("
    SELECT t.id AS tarjeta_id, u.id AS usuario_id, u.nombre
    FROM tarjetas t
    JOIN usuarios u ON u.id = t.usuario_id
    WHERE t.card_uid = ? AND t.estado = 'entregada'
");
$stmt->execute([$cardUid]);
$tarjeta = $stmt->fetch();
if (!$tarjeta) {
    sendError('Tarjeta no válida o inactiva', 404);
}

// 3. Pasaje confirmado para este servicio
$stmt = $pdo->prepare("
    SELECT 1 FROM reservas
    WHERE usuario_id = ? AND servicio_id = ? AND estado = 'confirmada'   -- ajustá
    LIMIT 1
");
$stmt->execute([$tarjeta['usuario_id'], $servicioId]);
if (!$stmt->fetch()) {
    sendError($tarjeta['nombre'] . ' no tiene pasaje para este viaje', 403);
}

// 4. Registrar (el UNIQUE evita duplicados)
$stmt = $pdo->prepare("
    INSERT INTO abordajes (servicio_id, usuario_id, tarjeta_id, chofer_id)
    VALUES (?, ?, ?, ?)
    ON CONFLICT (servicio_id, usuario_id) DO NOTHING
");
$stmt->execute([$servicioId, $tarjeta['usuario_id'], $tarjeta['tarjeta_id'], $chofer['id']]);

if ($stmt->rowCount() === 0) {
    sendError($tarjeta['nombre'] . ' ya había abordado', 409);
}

sendResponse(['success' => true, 'nombre' => $tarjeta['nombre']]);