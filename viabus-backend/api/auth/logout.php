<?php
// backend/api/logout.php
// Registra el cierre de sesión de un usuario en user_metrics_log.
// Espera un JSON: { "user_id": 123 }

require_once __DIR__ . '/../config/database.php'; // ajustá a donde está getConnection()

// Si ya tenés estas funciones en tu proyecto, usalas; si no, dejá las versiones de abajo.
if (!function_exists('setCorsHeaders')) {
    function setCorsHeaders(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json; charset=utf-8');
    }
}

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id invalido']);
    exit;
}

try {
    $pdo = getConnection();

    // Verificamos que el usuario exista antes de registrar el logout
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $check->execute([$userId]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO user_metrics_log (user_id, event) VALUES (?, 'logout')");
$stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Logout registrado']);
} catch (Throwable $e) {
    error_log('logout.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al registrar logout']);
}