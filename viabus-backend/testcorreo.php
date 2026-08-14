<?php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'lucasperezsampayo7@gmail.com';
    $mail->Password = 'zbca jqdy pupv wnyo';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Remitente
    $mail->setFrom(
        'lucasperezsampayo7@gmail.com',
        'ViaBus'
    );

    // Destinatario
    $mail->addAddress(
        'facugatonegro@gmail.com'
    );

    // Mensaje
    $mail->isHTML(true);
    $mail->Subject = 'Prueba de ViaBus';

    $mail->Body = '
        <h1>¡Funciona!</h1>
        <p>Este correo fue enviado desde ViaBus usando PHPMailer.</p>
    ';

    $mail->send();

    echo 'Correo enviado correctamente';

} catch (Exception $e) {
    echo 'Error: ' . $mail->ErrorInfo;
}