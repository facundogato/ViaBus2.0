<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../api/auth/auth.php';
setCorsHeaders();
handlePreflight();
requireMethod('GET');

$pdo    = getConnection();
$chofer = obtenerChoferAutenticado($pdo);

$stmt = $pdo->prepare("
    SELECT id, origen, destino, salida          -- ajustá a tus columnas
    FROM servicios
    WHERE chofer_id = ?
      AND salida BETWEEN NOW() - INTERVAL '3 hours' AND NOW() + INTERVAL '30 minutes'
    ORDER BY ABS(EXTRACT(EPOCH FROM (salida - NOW())))
    LIMIT 1
");
$stmt->execute([$chofer['id']]);

sendResponse(['success' => true, 'servicio' => $stmt->fetch() ?: null]);