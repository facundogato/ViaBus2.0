<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {

    // Obtener ID de la reserva
    $reservaId = $_GET['reserva_id'] ?? null;

    // Obtener datos enviados por Vue
    $input = json_decode(file_get_contents('php://input'), true);

    $email = $input['email'] ?? null;

    if (!$reservaId) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Falta el ID de la reserva.'
        ]);

        exit;
    }

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'El email no es válido.'
        ]);

        exit;
    }


   // El QR se genera en el frontend (canvas) y llega acá como base64
    $qrBase64 = $input['qrBase64'] ?? null;

    if (!$qrBase64) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Falta el QR de la reserva.'
        ]);

        exit;
    }

    $qrData = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $qrBase64));

    // Crear PHPMailer
    $mail = new PHPMailer(true);

    // SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'servicioviabus@gmail.com';
    $mail->Password = 'wxbi krul lfmq mklk';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;


    // Remitente
    $mail->setFrom(
        'servicioviabus@gmail.com',
        'ViaBus'
    );

    // Destinatario
    $mail->addAddress($email);


    // Adjuntar QR
    
    $mail->addStringAttachment(
    $qrData,
    "QR_Reserva_$reservaId.png"
    );


    // Contenido
    $mail->isHTML(true);

    $mail->Subject = "QR de tu reserva - ViaBus";

    $mail->Body = "
        <h2>¡Reserva confirmada!</h2>

        <p>Adjuntamos el código QR correspondiente a tu reserva.</p>

        <p>
            <strong>Reserva:</strong> #$reservaId
        </p>

        <p>
            Presentá este código QR al momento de abordar.
        </p>

        <p>Gracias por utilizar ViaBus.</p>
    ";

    $mail->AltBody =
        "Reserva #$reservaId\n\n" .
        "Adjuntamos el código QR correspondiente a tu reserva.\n\n" .
        "Gracias por utilizar ViaBus.";


    // Enviar
    $mail->send();


    echo json_encode([
        'success' => true,
        'message' => 'QR enviado correctamente.'
    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudo enviar el correo.'
    ]);
}