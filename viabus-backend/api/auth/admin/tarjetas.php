<?php
// Administración de tarjetas QR (solo admin) — ruta: /api/auth/admin/tarjetas.php
//   GET  -> todas las tarjetas (con su estado) + usuarios que todavía no tienen una
//   POST {accion, ids, ...}:
//       crear            ids = usuarios      -> crea tarjetas "solicitada"
//       cambiar_estado   ids = tarjetas, estado = solicitada|impresa|entregada|anulada  (manual, cualquier salto)
//       editar_nombre    ids = [tarjeta], nombre = texto que se imprime
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../auth.php';

setCorsHeaders();
handlePreflight();

$pdo = getConnection();
obtenerAdminAutenticado($pdo);

const ESTADOS = ['solicitada', 'impresa', 'entregada', 'anulada'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tarjetas = $pdo->query("
        SELECT t.id, t.card_uid, t.estado, t.usuario_id,
               COALESCE(NULLIF(t.nombre_impreso, ''), TRIM(u.nombre || ' ' || u.apellido)) AS nombre,
               u.email, t.solicitada_en, t.impresa_en, t.entregada_en
        FROM tarjetas t
        JOIN usuarios u ON u.id = t.usuario_id
        ORDER BY t.solicitada_en, t.id
    ")->fetchAll();

    $sinTarjeta = $pdo->query("
        SELECT u.id, TRIM(u.nombre || ' ' || u.apellido) AS nombre, u.email
        FROM usuarios u
        WHERE u.rol = 'cliente'
          AND NOT EXISTS (SELECT 1 FROM tarjetas t WHERE t.usuario_id = u.id AND t.estado <> 'anulada')
        ORDER BY u.nombre
    ")->fetchAll();

    sendResponse(['success' => true, 'tarjetas' => $tarjetas, 'sin_tarjeta' => $sinTarjeta]);
}

requireMethod('POST');
$body   = getRequestBody();
$accion = $body['accion'] ?? '';
$ids    = array_values(array_unique(array_filter(array_map('intval', $body['ids'] ?? []))));
if (!$ids) sendError('No se seleccionó nada');

$marcadores = implode(',', array_fill(0, count($ids), '?'));

switch ($accion) {
    case 'crear':   // ids = usuarios
        $nombres = $pdo->prepare("SELECT TRIM(nombre || ' ' || apellido) FROM usuarios WHERE id = ?");
        $st = $pdo->prepare("
            INSERT INTO tarjetas (card_uid, usuario_id, nombre_impreso)
            SELECT ?::varchar, ?::int, ?::varchar
            WHERE NOT EXISTS (SELECT 1 FROM tarjetas WHERE usuario_id = ?::int AND estado <> 'anulada')
        ");
        $n = 0;
        foreach ($ids as $usuarioId) {
            $nombres->execute([$usuarioId]);
            $nombre = (string) $nombres->fetchColumn();
            if ($nombre === '') continue;   // usuario inexistente
            $st->execute(['VB' . strtoupper(bin2hex(random_bytes(8))), $usuarioId, $nombre, $usuarioId]);
            $n += $st->rowCount();
        }
        sendResponse(['success' => true, 'creadas' => $n]);

    case 'cambiar_estado':   // ids = tarjetas; sirve para avanzar, retroceder o corregir a mano
        $estado = $body['estado'] ?? '';
        if (!in_array($estado, ESTADOS, true)) sendError('Estado inválido');

        // Las fechas acompañan al estado: al retroceder se borran las que ya no corresponden
        $sets = [
            'solicitada' => "estado = 'solicitada', impresa_en = NULL, entregada_en = NULL",
            'impresa'    => "estado = 'impresa', impresa_en = COALESCE(impresa_en, NOW()), entregada_en = NULL",
            'entregada'  => "estado = 'entregada', impresa_en = COALESCE(impresa_en, NOW()), entregada_en = COALESCE(entregada_en, NOW())",
            'anulada'    => "estado = 'anulada'",
        ];
        try {
            $st = $pdo->prepare("UPDATE tarjetas SET {$sets[$estado]} WHERE id IN ($marcadores)");
            $st->execute($ids);
        } catch (PDOException $e) {
            // El índice único frena reactivar una tarjeta anulada si el usuario ya tiene otra vigente
            if ($e->getCode() === '23505') {
                sendError('Ese usuario ya tiene otra tarjeta vigente; anulá o corregí esa primero', 409);
            }
            throw $e;
        }
        sendResponse(['success' => true, 'actualizadas' => $st->rowCount()]);

    case 'editar_nombre':
        $nombre = trim(preg_replace('/\s+/', ' ', (string) ($body['nombre'] ?? '')));
        if (!preg_match('/^.{1,100}$/u', $nombre)) sendError('Nombre inválido');   // 1 a 100 caracteres
        $st = $pdo->prepare("UPDATE tarjetas SET nombre_impreso = ? WHERE id = ?");
        $st->execute([$nombre, $ids[0]]);
        sendResponse(['success' => true]);
}

sendError('Acción inválida');
