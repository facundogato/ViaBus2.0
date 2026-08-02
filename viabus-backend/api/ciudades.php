<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Método no soportado', 405);

$stmt = $pdo->query("SELECT id, nombre FROM ciudades ORDER BY nombre");
sendResponse($stmt->fetchAll());
