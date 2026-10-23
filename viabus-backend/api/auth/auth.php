<?php

// Devuelve el usuario dueño del token Bearer, o corta con 401
function obtenerUsuarioAutenticado(PDO $pdo): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!$header && function_exists('getallheaders')) {
        $h = array_change_key_case(getallheaders(), CASE_LOWER);
        $header = $h['authorization'] ?? '';
    }

    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        sendError('No autenticado', 401);
    }

    $stmt = $pdo->prepare("
        SELECT u.*
        FROM sesiones s
        JOIN usuarios u ON u.id = s.usuario_id
        WHERE s.token_hash = ? AND s.expira_en > NOW()
    ");
    $stmt->execute([hash('sha256', $m[1])]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        sendError('Sesión inválida o vencida', 401);
    }

    unset($usuario['password_hash']);
    return $usuario;
}

function obtenerChoferAutenticado(PDO $pdo): array
{
    $usuario = obtenerUsuarioAutenticado($pdo);
    if (($usuario['rol'] ?? '') !== 'chofer') {
        sendError('Acceso solo para choferes', 403);
    }
    return $usuario;
}