<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Hasta cuántas horas antes de la salida se puede cancelar una reserva.
const PLAZO_CANCELACION_HORAS = 3;

// GET  /api/reservas.php                        → todas las reservas (uso interno/admin)
// GET  /api/reservas.php?id=3                   → reserva por ID
// GET  /api/reservas.php?usuario_id=5           → reservas de un usuario (para "Mi Perfil")
// GET  /api/reservas.php?servicio_id=5&fecha=... → asientos ocupados de un servicio/fecha
// POST /api/reservas.php                        → crear reserva
// PUT  /api/reservas.php?id=3                    → cambiar estado
// DELETE /api/reservas.php?id=3                  → cancelar (respeta el plazo mínimo)

if ($method === 'GET') {

    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("
            SELECT r.id, r.fecha_viaje, r.asiento, r.estado, r.estado_pago, r.total, r.created_at,
                   s.hora, s.tipo,
                   c_o.nombre AS origen, c_d.nombre AS destino,
                   e.nombre   AS empresa,
                   p.nombre   AS pasajero_nombre, p.apellido AS pasajero_apellido,
                   p.documento, p.email, p.telefono
            FROM reservas r
            JOIN servicios s ON r.servicio_id = s.id
            JOIN ciudades c_o ON s.origen_id  = c_o.id
            JOIN ciudades c_d ON s.destino_id = c_d.id
            JOIN empresas e   ON s.empresa_id = e.id
            JOIN pasajeros p  ON r.pasajero_id = p.id
            WHERE r.id = ?
        ");
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) sendError('Reserva no encontrada', 404);
        sendResponse($row);
    }

    // Reservas de un usuario logueado (para la página "Mi Perfil").
    // Comment de luck: En la linea del primer Select saqué temporalmente el "r.estado_pago" hasta que se arregle bien
    // el tema de la base de datos y el mercado pago
    if (isset($_GET['usuario_id'])) {
        $stmt = $pdo->prepare("

            SELECT r.id, r.fecha_viaje, r.asiento, r.estado, r.total, r.created_at,
                   s.id AS servicio_id, s.hora, s.tipo, s.anden, s.bus,
                   c_o.nombre AS origen, c_d.nombre AS destino,
                   e.nombre   AS empresa,
                   (p.nombre || ' ' || p.apellido) AS pasajero
            FROM reservas r
            JOIN servicios s ON r.servicio_id = s.id
            JOIN ciudades c_o ON s.origen_id  = c_o.id
            JOIN ciudades c_d ON s.destino_id = c_d.id
            JOIN empresas e   ON s.empresa_id = e.id
            JOIN pasajeros p  ON r.pasajero_id = p.id
            WHERE r.usuario_id = ?
            ORDER BY r.fecha_viaje DESC, s.hora DESC
        ");
        $stmt->execute([(int)$_GET['usuario_id']]);
        sendResponse($stmt->fetchAll());
    }

    // Asientos ocupados para un servicio en una fecha
    if (isset($_GET['servicio_id'], $_GET['fecha'])) {
        $stmt = $pdo->prepare("
            SELECT asiento FROM reservas
            WHERE servicio_id = ? AND fecha_viaje = ? AND estado != 'cancelada'
        ");
        $stmt->execute([(int)$_GET['servicio_id'], $_GET['fecha']]);
        $ocupados = array_map('intval', array_column($stmt->fetchAll(), 'asiento'));
        sendResponse(['asientos_ocupados' => $ocupados]);
    }

    $stmt = $pdo->query("
        SELECT r.id, r.fecha_viaje, r.asiento, r.estado, r.total,
               s.hora,
               c_o.nombre AS origen, c_d.nombre AS destino,
               e.nombre   AS empresa,
               (p.nombre || ' ' || p.apellido) AS pasajero
        FROM reservas r
        JOIN servicios s ON r.servicio_id = s.id
        JOIN ciudades c_o ON s.origen_id  = c_o.id
        JOIN ciudades c_d ON s.destino_id = c_d.id
        JOIN empresas e   ON s.empresa_id = e.id
        JOIN pasajeros p  ON r.pasajero_id = p.id
        ORDER BY r.created_at DESC
    ");
    sendResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $body = getRequestBody();
    $required = ['servicio_id','fecha_viaje','asiento','pasajero'];
    foreach ($required as $f) {
        if (empty($body[$f]) && $body[$f] !== 0) sendError("El campo '$f' es obligatorio");
    }

    $p = $body['pasajero'];
    foreach (['nombre','apellido','documento'] as $f) {
        if (empty($p[$f])) sendError("El campo pasajero.$f es obligatorio");
    }

    // Verificar que el servicio exista
    $stmtS = $pdo->prepare("SELECT id, precio FROM servicios WHERE id = ?");
    $stmtS->execute([(int)$body['servicio_id']]);
    $servicio = $stmtS->fetch();
    if (!$servicio) sendError('Servicio no encontrado', 404);

    // Upsert pasajero por documento
    $stmtP = $pdo->prepare("SELECT id FROM pasajeros WHERE documento = ?");
    $stmtP->execute([$p['documento']]);
    $pasajeroId = $stmtP->fetchColumn();

    if (!$pasajeroId) {
        $pdo->prepare("
            INSERT INTO pasajeros (nombre,apellido,documento,email,telefono)
            VALUES (?,?,?,?,?)
        ")->execute([$p['nombre'],$p['apellido'],$p['documento'],$p['email'] ?? null,$p['telefono'] ?? null]);
        $pasajeroId = $pdo->lastInsertId();
    }

    // Verificar que el asiento no esté ocupado
    $stmtA = $pdo->prepare("
        SELECT id FROM reservas
        WHERE servicio_id=? AND fecha_viaje=? AND asiento=? AND estado!='cancelada'
    ");
    $stmtA->execute([(int)$body['servicio_id'], $body['fecha_viaje'], (int)$body['asiento']]);
    if ($stmtA->fetch()) sendError('El asiento ya está ocupado para esa fecha');

    $total     = $body['total'] ?? $servicio['precio'];
    $usuarioId = !empty($body['usuario_id']) ? (int)$body['usuario_id'] : null;

    $pdo->prepare("
        INSERT INTO reservas (servicio_id,pasajero_id,usuario_id,fecha_viaje,asiento,estado,total)
        VALUES (?,?,?,?,?,'pendiente',?)
    ")->execute([
        (int)$body['servicio_id'],
        $pasajeroId,
        $usuarioId,
        $body['fecha_viaje'],
        (int)$body['asiento'],
        $total,
    ]);

    sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Reserva creada', 'total' => $total], 201);
}

if ($method === 'PUT') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $body   = getRequestBody();
    $id     = (int)$_GET['id'];
    $estado = $body['estado'] ?? null;
    if (!in_array($estado, ['pendiente','confirmada','cancelada'])) {
        sendError('Estado inválido. Use: pendiente | confirmada | cancelada');
    }
    $pdo->prepare("UPDATE reservas SET estado=? WHERE id=?")->execute([$estado, $id]);
    sendResponse(['message' => 'Estado actualizado']);
}

if ($method === 'DELETE') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("
        SELECT r.estado, r.fecha_viaje, s.hora
        FROM reservas r
        JOIN servicios s ON r.servicio_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $reserva = $stmt->fetch();
    if (!$reserva) sendError('Reserva no encontrada', 404);

    if ($reserva['estado'] === 'cancelada') {
        sendError('Esta reserva ya estaba cancelada');
    }

    $salida         = new DateTime($reserva['fecha_viaje'] . ' ' . $reserva['hora']);
    $horasRestantes = (strtotime($salida->format('Y-m-d H:i:s')) - time()) / 3600;

    if ($horasRestantes < PLAZO_CANCELACION_HORAS) {
        sendError(
            'Ya no se puede cancelar: faltan menos de ' . PLAZO_CANCELACION_HORAS . ' horas para la salida',
            409
        );
    }

    $pdo->prepare("UPDATE reservas SET estado='cancelada' WHERE id=?")->execute([$id]);
    sendResponse(['message' => 'Reserva cancelada']);
}

sendError('Método no soportado', 405);
