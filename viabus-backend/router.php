<?php
// Router para el servidor embebido de PHP (php -S).
// Permite servir directamente los archivos de /api y devuelve 404 JSON
// para cualquier otra cosa (el front-end vive en el servidor de Quasar).

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    // Deja que el servidor built-in sirva el archivo (PHP se ejecuta si es .php)
    return false;
}

header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode(['error' => 'Ruta no encontrada. Endpoints disponibles en /api/*.php']);
