<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../api/auth/auth.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
obtenerAdminAutenticado($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tarjetas = $pdo->query("
        SELECT t.id, t.card_uid, t.estado, t.solicitada_en, t.impresa_en, t.entregada_en,
               u.nombre, u.email
        FROM tarjetas t
        JOIN usuarios u ON u.id = t.usuario_id
        WHERE t.estado <> 'anulada'
        ORDER BY t.solicitada_en
    ")->fetchAll();

    $sinTarjeta = $pdo->query("
        SELECT u.id, u.nombre, u.email
        FROM usuarios u
        WHERE u.rol = 'cliente'
          AND NOT EXISTS (SELECT 1 FROM tarjetas t WHERE t.usuario_id = u.id AND t.estado <> 'anulada')
        ORDER BY u.nombre
    ")->fetchAll();

    sendResponse(['success' => true, 'tarjetas' => $tarjetas, 'sin_tarjeta' => $sinTarjeta]);
}

requireMethod('POST');
$body   = getRequestBody();
$accion = $body['accion'] ?? '';
$ids    = array_values(array_unique(array_map('intval', $body['ids'] ?? [])));
if (!$ids) sendError('No se seleccionó nada');

$marcadores = implode(',', array_fill(0, count($ids), '?'));

switch ($accion) {
    case 'crear':   // ids = usuarios
        $st = $pdo->prepare("
            INSERT INTO tarjetas (card_uid, usuario_id)
            SELECT ?::varchar, ?::int
            WHERE NOT EXISTS (SELECT 1 FROM tarjetas WHERE usuario_id = ?::int AND estado <> 'anulada')
        ");
        $n = 0;
        foreach ($ids as $usuarioId) {
            $st->execute(['VB' . strtoupper(bin2hex(random_bytes(8))), $usuarioId, $usuarioId]);
            $n += $st->rowCount();
        }
        sendResponse(['success' => true, 'creadas' => $n]);

    case 'marcar_impresas':   // ids = tarjetas
        $st = $pdo->prepare("UPDATE tarjetas SET estado = 'impresa', impresa_en = NOW()
                             WHERE id IN ($marcadores) AND estado = 'solicitada'");
        $st->execute($ids);
        sendResponse(['success' => true, 'marcadas' => $st->rowCount()]);

    case 'entregar':
        $st = $pdo->prepare("UPDATE tarjetas SET estado = 'entregada', entregada_en = NOW()
                             WHERE id IN ($marcadores) AND estado = 'impresa'");
        $st->execute($ids);
        sendResponse(['success' => true, 'entregadas' => $st->rowCount()]);

    case 'anular':
        $st = $pdo->prepare("UPDATE tarjetas SET estado = 'anulada'
                             WHERE id IN ($marcadores) AND estado <> 'anulada'");
        $st->execute($ids);
        sendResponse(['success' => true, 'anuladas' => $st->rowCount()]);
}

sendError('Acción inválida');