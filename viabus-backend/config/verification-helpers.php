<?php
/**
 * Helpers para verificación de email - ViaBus
 * Requiere: $pdo (conexión PostgreSQL) definida por el caller
 * Usa PHPMailer con Gmail (como en el envío del QR)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateVerificationCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendVerificationEmail(string $email, string $nombre, string $code): bool {
    try {
        // Crear PHPMailer
        $mail = new PHPMailer(true);

        // En desarrollo, loguear y simular
        if (defined('VIABUS_DEV') && VIABUS_DEV) {
            error_log("[ViaBus DEV] Código para {$email}: {$code}");
            return true;
        }

        // SMTP (mismo que en QR)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'servicioviabus@gmail.com';
        $mail->Password = 'wxbi krul lfmq mklk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Remitente (mismo que en QR)
        $mail->setFrom('servicioviabus@gmail.com', 'ViaBus');

        // Destinatario
        $mail->addAddress($email);

        // Asunto y contenido
        $mail->isHTML(true);
        $mail->Subject = 'Código de verificación - ViaBus';

        $mail->Body = getVerificationEmailTemplate($nombre, $code);

        $mail->AltBody = "Hola {$nombre},\n\nTu código de verificación es: {$code}\n\nEste código expira en 15 minutos.\n\nSi no solicitaste este código, ignora este email.\n\nViaBus - Terminal de Melo";

        // Enviar
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("[ViaBus ERROR] No se pudo enviar email de verificación: " . $e->getMessage());
        return false;
    }
}

function getVerificationEmailTemplate(string $nombre, string $code): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            max-width: 480px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #060c22 0%, #0a1028 100%);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 3px solid #f4b400;
        }
        .header h1 {
            color: #f4b400;
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }
        .content {
            padding: 30px 20px;
            color: #333;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555;
        }
        .code-section {
            background: #f9f9f9;
            border: 2px solid #f4b400;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }
        .code-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .code {
            font-size: 40px;
            font-weight: bold;
            color: #f4b400;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 0;
        }
        .info {
            font-size: 13px;
            color: #999;
            margin-top: 20px;
            line-height: 1.6;
        }
        .footer {
            background: #f5f5f5;
            border-top: 1px solid #eee;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        .footer-brand {
            color: #f4b400;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>ViaBus</h1>
        </div>

        <!-- Contenido -->
        <div class="content">
            <p class="greeting">Hola <strong>{$nombre}</strong>,</p>

            <p>Para completar tu registro en ViaBus, necesitamos verificar tu correo electrónico.</p>

            <!-- Código -->
            <div class="code-section">
                <div class="code-label">Código de Verificación</div>
                <p class="code">{$code}</p>
            </div>

            <!-- Información -->
            <div class="info">
                <p><strong>⏱️ Este código expira en 15 minutos.</strong></p>
                <p>Ingresa este código en la página de verificación para confirmar tu email.</p>
                <p>Si no creaste una cuenta en ViaBus, ignora este correo.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>ViaBus - Terminal de Melo</p>
            <p>© 2026 Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function invalidatePreviousCodes(PDO $pdo, int $userId): void {
    // Marca los códigos anteriores como expirados (borrado suave)
    $stmt = $pdo->prepare(
        "UPDATE email_verification_codes 
         SET expires_at = NOW() 
         WHERE user_id = :uid AND verified_at IS NULL"
    );
    $stmt->execute(['uid' => $userId]);
}

function storeVerificationCode(PDO $pdo, int $userId, string $email, string $code): void {
    // Limpieza de códigos verificados con más de 24h
    $pdo->query("DELETE FROM email_verification_codes 
                 WHERE verified_at < NOW() - INTERVAL '24 hours' 
                 OR expires_at < NOW() - INTERVAL '24 hours'");

    invalidatePreviousCodes($pdo, $userId);

    $stmt = $pdo->prepare(
        "INSERT INTO email_verification_codes (user_id, email, code, expires_at)
         VALUES (:uid, :email, :code, NOW() + INTERVAL '15 minutes')"
    );
    $stmt->execute(['uid' => $userId, 'email' => $email, 'code' => $code]);
}

function validateVerificationCode(PDO $pdo, int $userId, string $code): array {
    // [ok => bool, error => string|null]
    $stmt = $pdo->prepare(
        "SELECT id, code, expires_at FROM email_verification_codes
         WHERE user_id = :uid AND verified_at IS NULL
         ORDER BY created_at DESC LIMIT 1"
    );
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['ok' => false, 'error' => 'No hay código pendiente. Solicita uno nuevo.'];
    }
    if (strtotime($row['expires_at']) < time()) {
        return ['ok' => false, 'error' => 'El código ha expirado. Solicita uno nuevo.'];
    }
    if (!hash_equals($row['code'], $code)) {
        return ['ok' => false, 'error' => 'Código incorrecto.'];
    }

    // Marcar verificado
    $pdo->prepare("UPDATE email_verification_codes 
                   SET verified_at = NOW() WHERE id = :id")
        ->execute(['id' => $row['id']]);

    // Marcar usuario verificado
    $pdo->prepare("UPDATE usuarios SET email_verified = TRUE WHERE id = :uid")
        ->execute(['uid' => $userId]);

    return ['ok' => true, 'error' => null];
}

function jsonOut(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function readJsonBody(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}