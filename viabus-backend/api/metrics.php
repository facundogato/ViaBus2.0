<?php
// backend/api/metrics.php
// Expone métricas de usuarios en formato Prometheus.
// Solo puede consultarlo el propio servidor (Prometheus corre local).

require_once __DIR__ . '/../config/database.php';

// Permitir únicamente conexiones locales
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden";
    exit;
}

// Formato compatible con Prometheus
header('Content-Type: text/plain; version=0.0.4; charset=utf-8');

// Obtener conexión a PostgreSQL
$pdo = getConnection();

$metrics = [
    'viabus_users_total' => [
        'help' => 'Total de usuarios registrados',
        'sql'  => "SELECT COUNT(*) FROM usuarios",
    ],
    'viabus_users_active_today' => [
        'help' => 'Usuarios distintos que iniciaron sesion hoy',
        'sql'  => "SELECT COUNT(DISTINCT user_id) FROM user_metrics_log
                   WHERE event = 'login' AND created_at >= CURRENT_DATE",
    ],
    'viabus_users_active_7d' => [
        'help' => 'Usuarios distintos que iniciaron sesion en los ultimos 7 dias',
        'sql'  => "SELECT COUNT(DISTINCT user_id) FROM user_metrics_log
                   WHERE event = 'login' AND created_at >= NOW() - INTERVAL '7 days'",
    ],
    'viabus_users_connected_now' => [
        'help' => 'Usuarios con sesion abierta (login en los ultimos 30 min sin logout posterior)',
        'sql'  => "SELECT COUNT(DISTINCT l.user_id) FROM user_metrics_log l
                   WHERE l.event = 'login'
                     AND l.created_at >= NOW() - INTERVAL '30 minutes'
                     AND NOT EXISTS (
                         SELECT 1 FROM user_metrics_log o
                         WHERE o.user_id = l.user_id
                           AND o.event = 'logout'
                           AND o.created_at > l.created_at
                     )",
    ],
    'viabus_logins_today' => [
        'help' => 'Logins totales de hoy',
        'sql'  => "SELECT COUNT(*) FROM user_metrics_log
                   WHERE event = 'login' AND created_at >= CURRENT_DATE",
    ],
];
// Ejecutar las métricas
foreach ($metrics as $name => $m) {
    $value = (int) $pdo->query($m['sql'])->fetchColumn();

    echo "# HELP {$name} {$m['help']}\n";
    echo "# TYPE {$name} gauge\n";
    echo "{$name} {$value}\n";
}