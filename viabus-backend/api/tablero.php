<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Cantidad de andenes físicos disponibles en la terminal.
const TOTAL_ANDENES = 7;
const VENTANA_EMBARQUE_MIN = 15; // "próximos a salir" = salen dentro de estos minutos

// GET /api/tablero.php
//   → todos los servicios de HOY que salen desde Melo, separados en
//     "proximos" (no salieron todavía) y "salidos" (ya salieron), con
//     estado calculado en vivo según la hora del servidor.
//
// PUT /api/tablero.php?servicio_id=5
//   body: { "demorado": true|false }
//   → marca (o saca) la demora manual de un servicio para el día de hoy.

if ($method === 'GET') {
    $hoy   = date('Y-m-d');
    $ahora = date('H:i');

    // Todos los viajes salen desde Melo, así que no necesitamos hacer JOIN
    // a origen. Solo traemos los servicios activos ordenados por hora.
    $stmt = $pdo->prepare("
        SELECT s.id, c_d.nombre AS destino, e.nombre AS empresa, e.logo AS empresa_logo,
               s.hora, s.tipo, s.bus,
               COALESCE(ed.demorado, 0) AS demorado
        FROM servicios s
        JOIN ciudades c_d ON s.destino_id = c_d.id
        JOIN empresas e   ON s.empresa_id = e.id
        LEFT JOIN estado_diario ed ON ed.servicio_id = s.id AND ed.fecha = ?
        WHERE s.activo = 1
        ORDER BY s.hora
    ");
    $stmt->execute([$hoy]);
    $todos = $stmt->fetchAll();

    // minutos desde medianoche, para poder restar contra "ahora"
    $toMinutos = fn(string $hhmm) => ((int)substr($hhmm, 0, 2)) * 60 + (int)substr($hhmm, 3, 2);
    $minAhora  = $toMinutos($ahora);

    $proximos = [];
    $salidos  = [];

    foreach ($todos as $row) {
        $minServicio = $toMinutos($row['hora']);
        $faltan      = $minServicio - $minAhora;

        if ($faltan < 0) {
            $row['estado'] = 'partio';
            $row['anden']  = null;
            $salidos[] = $row;
            continue;
        }

        if ((int)$row['demorado'] === 1) {
            $row['estado'] = 'demorado';
        } elseif ($faltan <= VENTANA_EMBARQUE_MIN) {
            $row['estado'] = 'embarcando';
        } else {
            $row['estado'] = 'horario';
        }
        $row['minutos_restantes'] = $faltan;
        $row['demorado'] = (bool)(int)$row['demorado'];
        $proximos[] = $row;
    }

    // Los andenes solo se asignan a los servicios que están saliendo ya
    // (dentro de la ventana de embarque), rotando entre los físicamente
    // disponibles. El resto todavía no tiene andén asignado.
    $enVentana = array_values(array_filter(
        $proximos,
        fn($r) => $r['minutos_restantes'] <= VENTANA_EMBARQUE_MIN
    ));

    $andenPorServicio = [];
    foreach ($enVentana as $i => $r) {
        $andenPorServicio[$r['id']] = 'A-0' . (($i % TOTAL_ANDENES) + 1);
    }

    foreach ($proximos as &$r) {
        $r['anden'] = $andenPorServicio[$r['id']] ?? null;
    }
    unset($r);

    // orden salidos: los más recientes primero
    usort($salidos, fn($a, $b) => $toMinutos($b['hora']) <=> $toMinutos($a['hora']));

    sendResponse([
        'fecha'           => $hoy,
        'hora_actual'     => $ahora,
        'andenes_activos' => count($enVentana),
        'andenes_totales' => TOTAL_ANDENES,
        'proximos'        => $proximos,
        'salidos'         => $salidos,
    ]);
}

if ($method === 'PUT') {
    if (empty($_GET['servicio_id'])) sendError('servicio_id requerido');
    $body = getRequestBody();
    if (!array_key_exists('demorado', $body)) sendError('El campo "demorado" es obligatorio');

    $servicioId = (int)$_GET['servicio_id'];
    $demorado   = $body['demorado'] ? 1 : 0;
    $hoy        = date('Y-m-d');

    $stmtCheck = $pdo->prepare("SELECT id FROM servicios WHERE id = ?");
    $stmtCheck->execute([$servicioId]);
    if (!$stmtCheck->fetch()) sendError('Servicio no encontrado', 404);

    $pdo->prepare("
        INSERT INTO estado_diario (servicio_id, fecha, demorado)
        VALUES (?, ?, ?)
        ON CONFLICT(servicio_id, fecha) DO UPDATE SET
            demorado = excluded.demorado,
            updated_at = CURRENT_TIMESTAMP
    ")->execute([$servicioId, $hoy, $demorado]);

    sendResponse(['message' => $demorado ? 'Servicio marcado como demorado' : 'Demora quitada']);
}

sendError('Método no soportado', 405);
