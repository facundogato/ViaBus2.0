<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Método no soportado', 405);

// GET /api/empresas.php         -> lista liviana (id, nombre, logo) para la grilla de logos
// GET /api/empresas.php?id=3    -> ficha completa de una empresa + las rutas que opera

if (isset($_GET['id'])) {
    obtenerEmpresa($pdo, (int)$_GET['id']);
}

$stmt = $pdo->query("SELECT id, nombre, logo FROM empresas ORDER BY nombre");
sendResponse($stmt->fetchAll());

// --------------------------------------------------------------------

function obtenerEmpresa(PDO $pdo, int $id): void {
    $stmt = $pdo->prepare("
        SELECT id, nombre, logo, descripcion, telefono, email, sitio_web, direccion
        FROM empresas
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $empresa = $stmt->fetch();

    if (!$empresa) sendError('Empresa no encontrada', 404);

    // Rutas que opera esta empresa, con la cantidad de frecuencias diarias
    // de cada una. Es información real, calculada a partir de sus
    // servicios activos (no hay que cargarla a mano en ningún lado).
    $stmt = $pdo->prepare("
        SELECT c_o.nombre AS origen, c_d.nombre AS destino, COUNT(*) AS frecuencias
        FROM servicios s
        JOIN ciudades c_o ON s.origen_id  = c_o.id
        JOIN ciudades c_d ON s.destino_id = c_d.id
        WHERE s.empresa_id = ? AND s.activo = 1
        GROUP BY c_o.nombre, c_d.nombre
        ORDER BY c_o.nombre, c_d.nombre
    ");
    $stmt->execute([$id]);
    $empresa['rutas'] = $stmt->fetchAll();

    sendResponse($empresa);
}