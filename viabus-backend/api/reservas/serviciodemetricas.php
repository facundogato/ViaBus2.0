<?php

namespace App\Services;

use PDO;

class MetricsService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra un evento de usuario (login/logout)
     */
    public function logUserEvent($userId, $eventType, $ipAddress, $sessionId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_metrics_log (user_id, event_type, ip_address, session_id)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $eventType, $ipAddress, $sessionId]);
    }

    /**
     * Obtiene métricas en formato Prometheus
     */
    public function getPrometheusMetrics()
    {
        $metrics = [];

        // 1. Total de usuarios registrados
        $totalUsers = $this->pdo->query("SELECT COUNT(*) as count FROM usuarios")->fetch(PDO::FETCH_ASSOC)['count'];
        $metrics[] = "# HELP viabus_total_users Total de usuarios registrados";
        $metrics[] = "# TYPE viabus_total_users gauge";
        $metrics[] = "viabus_total_users {environment=\"production\"} " . $totalUsers;

        // 2. Usuarios activos HOY
        $activesToday = $this->pdo->query("
            SELECT COUNT(DISTINCT user_id) as count 
            FROM user_metrics_log 
            WHERE DATE(event_timestamp) = CURDATE()
        ")->fetch(PDO::FETCH_ASSOC)['count'];
        $metrics[] = "\n# HELP viabus_active_today Usuarios activos en el día";
        $metrics[] = "# TYPE viabus_active_today gauge";
        $metrics[] = "viabus_active_today {period=\"today\"} " . $activesToday;

        // 3. Usuarios activos ÚLTIMOS 7 DÍAS
        $activesWeek = $this->pdo->query("
            SELECT COUNT(DISTINCT user_id) as count 
            FROM user_metrics_log 
            WHERE DATE(event_timestamp) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ")->fetch(PDO::FETCH_ASSOC)['count'];
        $metrics[] = "\n# HELP viabus_active_week Usuarios activos en los últimos 7 días";
        $metrics[] = "# TYPE viabus_active_week gauge";
        $metrics[] = "viabus_active_week {period=\"7days\"} " . $activesWeek;

        // 4. Usuarios CONECTADOS AHORA (último login en los últimos 30 minutos sin logout)
        $connectedNow = $this->pdo->query("
            SELECT COUNT(DISTINCT user_id) as count 
            FROM user_metrics_log uml
            WHERE uml.event_type = 'login' 
            AND uml.event_timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            AND NOT EXISTS (
                SELECT 1 FROM user_metrics_log uml2 
                WHERE uml2.user_id = uml.user_id 
                AND uml2.event_type = 'logout'
                AND uml2.event_timestamp > uml.event_timestamp
            )
        ")->fetch(PDO::FETCH_ASSOC)['count'];
        $metrics[] = "\n# HELP viabus_connected_now Usuarios conectados en este momento";
        $metrics[] = "# TYPE viabus_connected_now gauge";
        $metrics[] = "viabus_connected_now {status=\"online\"} " . $connectedNow;

        // 5. Contador de logins totales
        $totalLogins = $this->pdo->query("
            SELECT COUNT(*) as count FROM user_metrics_log WHERE event_type = 'login'
        ")->fetch(PDO::FETCH_ASSOC)['count'];
        $metrics[] = "\n# HELP viabus_total_logins Total de logins registrados";
        $metrics[] = "# TYPE viabus_total_logins counter";
        $metrics[] = "viabus_total_logins {event=\"login\"} " . $totalLogins;

        // 6. Logins por rol
        $loginsByRole = $this->pdo->query("
            SELECT u.rol, COUNT(uml.id) as count 
            FROM user_metrics_log uml
            JOIN usuarios u ON uml.user_id = u.id
            WHERE uml.event_type = 'login'
            GROUP BY u.rol
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $metrics[] = "\n# HELP viabus_logins_by_role Logins agrupados por rol";
        $metrics[] = "# TYPE viabus_logins_by_role counter";
        foreach ($loginsByRole as $row) {
            $metrics[] = "viabus_logins_by_role {role=\"" . $row['rol'] . "\"} " . $row['count'];
        }

        return implode("\n", $metrics);
    }
}