<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET /api/servicios.php              → todos los servicios activos
// GET /api/servicios.php?id=5         → un servicio por ID
// GET /api/servicios.php?origen=Melo&destino=Montevideo  → búsqueda
// POST /api/servicios.php             → crear servicio (admin)
// PUT  /api/servicios.php?id=5        → editar servicio (admin)
// DELETE /api/servicios.php?id=5      → desactivar servicio (admin)

if ($method === 'GET') {

    // Detalle por ID
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("
            SELECT s.id, c_o.nombre AS origen, c_d.nombre AS destino,
                   e.nombre AS empresa, e.logo AS empresa_logo, s.hora, s.tipo,
                   s.duracion, s.precio, s.asientos, s.anden, s.bus, s.estado, s.activo
            FROM servicios s
            JOIN ciudades c_o ON s.origen_id  = c_o.id
            JOIN ciudades c_d ON s.destino_id = c_d.id
            JOIN empresas e   ON s.empresa_id = e.id
            WHERE s.id = ?
        ");
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) sendError('Servicio no encontrado', 404);
        sendResponse($row);
    }

    // Búsqueda por origen + destino
    if (isset($_GET['origen']) && isset($_GET['destino'])) {
        $stmt = $pdo->prepare("
            SELECT s.id, c_o.nombre AS origen, c_d.nombre AS destino,
                   e.nombre AS empresa, e.logo AS empresa_logo, s.hora,
                   s.tipo, s.duracion, s.precio, s.asientos, s.anden, s.bus, s.estado
            FROM servicios s
            JOIN ciudades c_o ON s.origen_id  = c_o.id
            JOIN ciudades c_d ON s.destino_id = c_d.id
            JOIN empresas e   ON s.empresa_id = e.id
            WHERE c_o.nombre = ? AND c_d.nombre = ? AND s.activo = 1
            ORDER BY s.hora
        ");
        $stmt->execute([$_GET['origen'], $_GET['destino']]);
        sendResponse($stmt->fetchAll());
    }

    // Todos los servicios
    $stmt = $pdo->query("
        SELECT s.id, c_o.nombre AS origen, c_d.nombre AS destino,
               e.nombre AS empresa, e.logo AS empresa_logo, s.hora,
               s.tipo, s.duracion, s.precio, s.asientos, s.anden, s.bus, s.estado, s.activo
        FROM servicios s
        JOIN ciudades c_o ON s.origen_id  = c_o.id
        JOIN ciudades c_d ON s.destino_id = c_d.id
        JOIN empresas e   ON s.empresa_id = e.id
        WHERE s.activo = 1
        ORDER BY c_o.nombre, s.hora
    ");
    sendResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $body = getRequestBody();
    $required = ['origen', 'destino', 'empresa', 'hora', 'tipo'];
    foreach ($required as $field) {
        if (empty($body[$field])) sendError("El campo '$field' es obligatorio");
    }

    // Resolver IDs
    $stmtO   = $pdo->prepare("SELECT id FROM ciudades WHERE nombre = ?"); $stmtO->execute([$body['origen']]);
    $stmtD   = $pdo->prepare("SELECT id FROM ciudades WHERE nombre = ?"); $stmtD->execute([$body['destino']]);
    $stmtE   = $pdo->prepare("SELECT id FROM empresas  WHERE nombre = ?"); $stmtE->execute([$body['empresa']]);
    $oid = $stmtO->fetchColumn();
    $did = $stmtD->fetchColumn();
    $eid = $stmtE->fetchColumn();
    if (!$oid) sendError('Ciudad de origen no encontrada');
    if (!$did) sendError('Ciudad de destino no encontrada');
    if (!$eid) sendError('Empresa no encontrada');

    $stmt = $pdo->prepare("
        INSERT INTO servicios (origen_id,destino_id,empresa_id,hora,tipo,duracion,precio,asientos,anden,bus,estado)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $oid, $did, $eid,
        $body['hora'],
        $body['tipo'],
        $body['duracion'] ?? null,
        $body['precio']   ?? null,
        $body['asientos'] ?? 40,
        $body['anden']    ?? null,
        $body['bus']      ?? null,
        $body['estado']   ?? 'horario',
    ]);
    sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Servicio creado'], 201);
}

if ($method === 'PUT') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $body = getRequestBody();
    $id   = (int)$_GET['id'];

    $fields = [];
    $values = [];
    foreach (['hora','tipo','duracion','precio','asientos','anden','bus','estado','activo'] as $f) {
        if (array_key_exists($f, $body)) {
            $fields[] = "$f = ?";
            $values[] = $body[$f];
        }
    }
    if (empty($fields)) sendError('Sin campos para actualizar');
    $values[] = $id;
    $pdo->prepare("UPDATE servicios SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
    sendResponse(['message' => 'Servicio actualizado']);
}

if ($method === 'DELETE') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $pdo->prepare("UPDATE servicios SET activo = 0 WHERE id = ?")->execute([(int)$_GET['id']]);
    sendResponse(['message' => 'Servicio desactivado']);
}

sendError('Método no soportado', 405);
