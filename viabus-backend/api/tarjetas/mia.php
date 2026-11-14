<?php
// Tarjeta QR del usuario logueado (ruta: /api/tarjetas/mia.php)
//   GET                          -> estado de mi tarjeta vigente (o null) + los datos que se imprimirían
//   POST {accion:'solicitar'}    -> pide la tarjeta con los datos actuales del perfil
//   POST {accion:'reportar_perdida'} -> anula mi tarjeta entregada
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../api/auth/auth.php';

setCorsHeaders();
handlePreflight();

$pdo     = getConnection();
$usuario = obtenerUsuarioAutenticado($pdo);
$uid     = (int) $usuario['id'];

// Nombre tal como va impreso en la tarjeta: "Nombre Apellido" del perfil
function nombreDePerfil(array $usuario): string
{
    return trim(preg_replace('/\s+/', ' ', ($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? '')));
}

function tarjetaVigente(PDO $pdo, int $usuarioId): ?array
{
    $st = $pdo->prepare("
        SELECT id, estado, nombre_impreso, solicitada_en, impresa_en, entregada_en
        FROM tarjetas WHERE usuario_id = ? AND estado <> 'anulada'
    ");
    $st->execute([$usuarioId]);
    return $st->fetch() ?: null;   // nunca devolvemos el card_uid al usuario
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendResponse([
        'success' => true,
        'tarjeta' => tarjetaVigente($pdo, $uid),
        'perfil'  => ['nombre' => nombreDePerfil($usuario), 'email' => $usuario['email']]
    ]);
}

requireMethod('POST');
$accion = getRequestBody()['accion'] ?? '';

if ($accion === 'solicitar') {
    $nombre = nombreDePerfil($usuario);
    if ($nombre === '') {
        sendError('Completá tu nombre y apellido en el perfil antes de solicitar la tarjeta');
    }
    if (tarjetaVigente($pdo, $uid)) {
        sendError('Ya tenés una tarjeta solicitada o activa', 409);
    }
    try {
        $st = $pdo->prepare("INSERT INTO tarjetas (card_uid, usuario_id, nombre_impreso) VALUES (?, ?, ?)");
        $st->execute(['VB' . strtoupper(bin2hex(random_bytes(8))), $uid, $nombre]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') sendError('Ya tenés una tarjeta solicitada o activa', 409);
        throw $e;
    }
    sendResponse(['success' => true, 'tarjeta' => tarjetaVigente($pdo, $uid)]);
}

if ($accion === 'reportar_perdida') {
    $st = $pdo->prepare("UPDATE tarjetas SET estado = 'anulada' WHERE usuario_id = ? AND estado = 'entregada'");
    $st->execute([$uid]);
    if ($st->rowCount() === 0) sendError('No tenés una tarjeta activa para anular', 404);
    sendResponse(['success' => true]);
}

sendError('Acción inválida');
