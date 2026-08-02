<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/mercadopago.php';

setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// POST /api/pagos.php?action=crear-preferencia   body: {reserva_id}
//      -> crea la preferencia de pago en Mercado Pago para una reserva
//         ya existente y devuelve la URL de checkout (init_point)
//
// GET  /api/pagos.php?action=estado&reserva_id=3
//      -> el frontend consulta esto al volver de Mercado Pago para saber
//         si el pago quedó aprobado, rechazado o pendiente
//
// POST /api/pagos.php?action=webhook
//      -> lo llama Mercado Pago (no el frontend) cada vez que un pago
//         cambia de estado

if ($method === 'POST' && $action === 'crear-preferencia') {
    crearPreferencia($pdo);
}

if ($method === 'GET' && $action === 'estado') {
    consultarEstado($pdo);
}

if ($method === 'POST' && $action === 'webhook') {
    procesarWebhook($pdo);
}

sendError('Acción no soportada. Use action=crear-preferencia | estado | webhook', 404);

// --------------------------------------------------------------------

function crearPreferencia(PDO $pdo): void {
    $body = getRequestBody();
    if (empty($body['reserva_id'])) sendError('El campo reserva_id es obligatorio');

    $reservaId = (int)$body['reserva_id'];

    $stmt = $pdo->prepare("
        SELECT r.id, r.total, r.estado_pago,
               c_o.nombre AS origen, c_d.nombre AS destino
        FROM reservas r
        JOIN servicios s  ON r.servicio_id = s.id
        JOIN ciudades c_o ON s.origen_id  = c_o.id
        JOIN ciudades c_d ON s.destino_id = c_d.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reservaId]);
    $reserva = $stmt->fetch();

    if (!$reserva) sendError('Reserva no encontrada', 404);
    if ($reserva['estado_pago'] === 'pagado') sendError('Esta reserva ya fue pagada');

    try {
        $preferencia = mpRequest('POST', '/checkout/preferences', [
            'items' => [[
                'title'       => "Pasaje {$reserva['origen']} → {$reserva['destino']} (Reserva #{$reservaId})",
                'quantity'    => 1,
                'currency_id' => 'UYU',
                'unit_price'  => (float)$reserva['total'],
            ]],
            'external_reference' => (string)$reservaId,
            'back_urls' => [
                'success' => MP_FRONTEND_URL . "/comprar?reserva_id={$reservaId}",
                'failure' => MP_FRONTEND_URL . "/comprar?reserva_id={$reservaId}",
                'pending' => MP_FRONTEND_URL . "/comprar?reserva_id={$reservaId}",
            ],
            'auto_return'      => 'approved',
            'notification_url' => MP_WEBHOOK_URL,
        ]);
    } catch (Exception $e) {
        sendError('No se pudo crear la preferencia de pago: ' . $e->getMessage(), 502);
    }

    // Una reserva tiene, como mucho, un pago: si ya había una preferencia
    // (por ejemplo el usuario volvió atrás y reintentó), se reemplaza.
    $pdo->prepare("
        INSERT INTO pagos (reserva_id, mercadopago_preference_id, estado, monto)
        VALUES (?, ?, 'pendiente', ?)
        ON CONFLICT(reserva_id) DO UPDATE SET
            mercadopago_preference_id = excluded.mercadopago_preference_id,
            estado     = 'pendiente',
            monto      = excluded.monto,
            updated_at = CURRENT_TIMESTAMP
    ")->execute([$reservaId, $preferencia['id'], $reserva['total']]);

    sendResponse([
        'preference_id' => $preferencia['id'],
        'init_point'    => $preferencia['init_point'],
    ]);
}

function consultarEstado(PDO $pdo): void {
    if (empty($_GET['reserva_id'])) sendError('reserva_id requerido');

    $stmt = $pdo->prepare("
        SELECT p.estado, p.monto, p.mercadopago_payment_id,
               r.estado AS estado_reserva
        FROM pagos p
        JOIN reservas r ON r.id = p.reserva_id
        WHERE p.reserva_id = ?
    ");
    $stmt->execute([(int)$_GET['reserva_id']]);
    $pago = $stmt->fetch();

    if (!$pago) sendError('No hay un pago registrado para esa reserva', 404);
    sendResponse($pago);
}

function procesarWebhook(PDO $pdo): void {
    // Mercado Pago avisa de dos formas posibles según cómo esté
    // configurada la integración; se soportan ambas.
    $body      = getRequestBody();
    $tipo      = $_GET['type'] ?? $_GET['topic'] ?? $body['type'] ?? null;
    $paymentId = $_GET['data_id'] ?? $_GET['id'] ?? ($body['data']['id'] ?? null);

    if ($tipo !== 'payment' || !$paymentId) {
        // No es una notificación de pago (Mercado Pago también avisa otros
        // eventos). Se responde 200 igual para que no reintente.
        sendResponse(['message' => 'Notificación ignorada (no es de tipo payment)']);
    }

    try {
        $pago = mpRequest('GET', "/v1/payments/{$paymentId}");
    } catch (Exception $e) {
        // Si la consulta falla, respondemos 200 para no entrar en un loop
        // de reintentos, pero queda registrado el error en el log del
        // servidor para poder revisarlo.
        error_log('Error al consultar pago de Mercado Pago: ' . $e->getMessage());
        sendResponse(['message' => 'No se pudo verificar el pago en este intento']);
    }

    $reservaId = (int)($pago['external_reference'] ?? 0);
    if (!$reservaId) {
        sendResponse(['message' => 'Pago sin external_reference, ignorado']);
    }

    $estado = match ($pago['status']) {
        'approved'  => 'pagado',
        'rejected'  => 'rechazado',
        'cancelled' => 'cancelado',
        default     => 'pendiente',
    };

    $pdo->prepare("
        UPDATE pagos SET
            mercadopago_payment_id = ?,
            estado       = ?,
            respuesta_mp = ?,
            updated_at   = CURRENT_TIMESTAMP
        WHERE reserva_id = ?
    ")->execute([$paymentId, $estado, json_encode($pago), $reservaId]);

    $pdo->prepare("UPDATE reservas SET estado_pago = ? WHERE id = ?")
        ->execute([$estado, $reservaId]);

    if ($estado === 'pagado') {
        $pdo->prepare("UPDATE reservas SET estado = 'confirmada' WHERE id = ?")
            ->execute([$reservaId]);
    }

    sendResponse(['message' => 'Pago actualizado', 'estado' => $estado]);
}