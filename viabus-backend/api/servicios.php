<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// NOTA: Todos los viajes salen desde Melo, así que no hay columna origen_id.
// La columna "origen" se asume como constante (Melo) en todas las queries.

// GET /api/servicios.php              → todos los servicios activos
// GET /api/servicios.php?id=5         → un servicio por ID
// GET /api/servicios.php?destino=Montevideo  → búsqueda por destino
// POST /api/servicios.php             → crear servicio (admin)
// PUT  /api/servicios.php?id=5        → editar servicio (admin)
// DELETE /api/servicios.php?id=5      → desactivar servicio (admin)

// --------------------------------------------------------------------
// Helpers para interpretar la columna "dias" (texto libre en español).
// --------------------------------------------------------------------

const ORDEN_DIAS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];

function normalizarTexto(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $mapa = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u'];
    return strtr($s, $mapa);
}

// $diasTexto es el valor crudo de la columna "dias" (puede ser NULL,
// 'Todos', 'Lunes a Viernes', 'Lunes, Martes, Viernes, Sábado', 'Domingo', etc.)
// $diaSemana es 0=domingo … 6=sábado (mismo formato que PHP date('w')).
function servicioCorreEseDia(?string $diasTexto, int $diaSemana): bool {
    if ($diasTexto === null || trim($diasTexto) === '') return true; // sin dato = corre siempre

    $texto = normalizarTexto($diasTexto);
    if ($texto === 'todos') return true;

    $diaBuscado = ORDEN_DIAS[$diaSemana];

    // Rango: "lunes a viernes", "domingo a viernes", "lunes a sabado"
    if (preg_match('/^(\w+)\s+a\s+(\w+)$/', $texto, $m)) {
        $inicio = array_search($m[1], ORDEN_DIAS);
        $fin    = array_search($m[2], ORDEN_DIAS);
        if ($inicio === false || $fin === false) return false;

        if ($inicio <= $fin) {
            return $diaSemana >= $inicio && $diaSemana <= $fin;
        }
        // rango que da la vuelta (ej: "viernes a lunes")
        return $diaSemana >= $inicio || $diaSemana <= $fin;
    }

    // Lista separada por comas: "lunes, martes, viernes, sabado"
    $partes = array_map('trim', explode(',', $texto));
    return in_array($diaBuscado, $partes, true);
}

if ($method === 'GET') {

    // Detalle por ID
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("
            SELECT s.id, 'Melo' AS origen, c_d.nombre AS destino,
                   e.nombre AS empresa, e.logo AS empresa_logo, s.hora, s.tipo,
                   s.duracion, s.precio, s.asientos, s.anden, s.bus, s.estado, s.activo, s.dias
            FROM servicios s
            JOIN ciudades c_d ON s.destino_id = c_d.id
            JOIN empresas e   ON s.empresa_id = e.id
            WHERE s.id = ?
        ");
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) sendError('Servicio no encontrado', 404);
        sendResponse($row);
    }

    // Búsqueda por destino (origen siempre es Melo)
    if (isset($_GET['destino'])) {
        $sql = "
            SELECT s.id, 'Melo' AS origen, c_d.nombre AS destino,
                   e.nombre AS empresa, e.logo AS empresa_logo, s.hora,
                   s.tipo, s.duracion, s.precio, s.asientos, s.anden, s.bus, s.estado, s.dias
            FROM servicios s
            JOIN ciudades c_d ON s.destino_id = c_d.id
            JOIN empresas e   ON s.empresa_id = e.id
            WHERE c_d.nombre = ? AND s.activo = 1
            ORDER BY s.hora
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_GET['destino']]);
        $servicios = $stmt->fetchAll();

        // Si el frontend mandó una fecha, filtramos en PHP por día de la semana
        // (la columna "dias" es texto libre: "Todos", rangos, listas — no se
        // puede resolver de forma confiable con un LIKE en SQL).
        if (!empty($_GET['fecha'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $_GET['fecha']);
            if ($fecha) {
                $diaSemana = (int)$fecha->format('w'); // 0=domingo … 6=sábado
                $servicios = array_values(array_filter(
                    $servicios,
                    fn($s) => servicioCorreEseDia($s['dias'], $diaSemana)
                ));
            }
        }

        sendResponse($servicios);
    }

    // Todos los servicios
    $stmt = $pdo->query("
        SELECT s.id, 'Melo' AS origen, c_d.nombre AS destino,
               e.nombre AS empresa, e.logo AS empresa_logo, s.hora,
               s.tipo, s.duracion, s.precio, s.asientos, s.anden, s.bus, s.estado, s.activo, s.dias
        FROM servicios s
        JOIN ciudades c_d ON s.destino_id = c_d.id
        JOIN empresas e   ON s.empresa_id = e.id
        WHERE s.activo = 1
        ORDER BY s.hora
    ");
    sendResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $body = getRequestBody();
    // Origen es Melo siempre, no se pide en el POST
    $required = ['destino', 'empresa', 'hora', 'tipo'];
    foreach ($required as $field) {
        if (empty($body[$field])) sendError("El campo '$field' es obligatorio");
    }

    // Resolver IDs (origen no se necesita porque es siempre Melo)
    $stmtD   = $pdo->prepare("SELECT id FROM ciudades WHERE nombre = ?"); $stmtD->execute([$body['destino']]);
    $stmtE   = $pdo->prepare("SELECT id FROM empresas  WHERE nombre = ?"); $stmtE->execute([$body['empresa']]);
    $did = $stmtD->fetchColumn();
    $eid = $stmtE->fetchColumn();
    if (!$did) sendError('Ciudad de destino no encontrada');
    if (!$eid) sendError('Empresa no encontrada');

    $stmt = $pdo->prepare("
        INSERT INTO servicios (destino_id,empresa_id,hora,tipo,duracion,precio,asientos,anden,bus,estado,dias)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $did, $eid,
        $body['hora'],
        $body['tipo'],
        $body['duracion'] ?? null,
        $body['precio']   ?? null,
        $body['asientos'] ?? 40,
        $body['anden']    ?? null,
        $body['bus']      ?? null,
        $body['estado']   ?? 'horario',
        $body['dias']     ?? null,
    ]);
    sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Servicio creado'], 201);
}

if ($method === 'PUT') {
    if (empty($_GET['id'])) sendError('ID requerido');
    $body = getRequestBody();
    $id   = (int)$_GET['id'];

    $fields = [];
    $values = [];
    foreach (['hora','tipo','duracion','precio','asientos','bus','dias'] as $f) {
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