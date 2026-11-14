<?php
// Devuelve el viaje de hoy que le toca al chofer logueado (el más cercano a la hora actual).
// Los servicios son horarios que se repiten (columna `hora`), así que se arma la salida de HOY.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../api/auth/auth.php';
setCorsHeaders();
handlePreflight();
requireMethod('GET');

const CIUDAD_ORIGEN = 'Melo';   // igual que en reservas.php: no existe origen_id

$pdo    = getConnection();
$chofer = obtenerChoferAutenticado($pdo);

// Hora de Uruguay, sin importar la zona horaria del servidor
$stmt = $pdo->prepare("
    WITH ahora AS (SELECT timezone('America/Montevideo', NOW()) AS t)
    SELECT s.id, ?::varchar AS origen, c_d.nombre AS destino, s.hora
    FROM servicios s
    JOIN ciudades c_d ON c_d.id = s.destino_id, ahora
    WHERE s.chofer_id = ?
      AND s.activo = 1
      AND (ahora.t::date + s.hora::time)
          BETWEEN ahora.t - INTERVAL '3 hours' AND ahora.t + INTERVAL '30 minutes'
    ORDER BY ABS(EXTRACT(EPOCH FROM ((ahora.t::date + s.hora::time) - ahora.t)))
    LIMIT 1
");
$stmt->execute([CIUDAD_ORIGEN, $chofer['id']]);   // todos los viajes salen de Melo

sendResponse(['success' => true, 'servicio' => $stmt->fetch() ?: null]);
