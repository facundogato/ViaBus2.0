<?php

// Credenciales de Mercado Pago.
// Se obtienen en: https://www.mercadopago.com.uy/developers/panel/app
// Mientras se prueba, usar SIEMPRE las credenciales de TEST (empiezan con
// "TEST-"); las de producción recién cuando el negocio esté dado de alta
// ante Mercado Pago y se vaya a cobrar de verdad.
define('MP_ACCESS_TOKEN', 'TEST-000000000000000-000000-00000000000000000000000000000000-000000000');

// URL pública donde corre el FRONTEND (Quasar). Mercado Pago redirige acá
// al usuario después de pagar (aprobado, rechazado o pendiente).
// En desarrollo normalmente es http://localhost:9000.
define('MP_FRONTEND_URL', 'http://localhost:9000');

// URL pública donde Mercado Pago puede alcanzar ESTE backend para avisar
// que un pago cambió de estado (webhook). Mercado Pago no puede llegar a
// "localhost", así que en desarrollo tiene que ser una URL de ngrok
// (ver README de este backend). En producción, el dominio real.
define('MP_WEBHOOK_URL', 'http://localhost:8000/api/pagos.php?action=webhook');

define('MP_API_BASE', 'https://api.mercadopago.com');

/**
 * Hace un request autenticado a la API de Mercado Pago y devuelve la
 * respuesta ya decodificada. Lanza una excepción si Mercado Pago responde
 * con un error o si falla la conexión.
 */
function mpRequest(string $method, string $endpoint, ?array $body = null): array {
    $ch = curl_init(MP_API_BASE . $endpoint);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Error de conexión con Mercado Pago: ' . $curlError);
    }

    $data = json_decode($response, true) ?? [];

    if ($httpCode >= 400) {
        $msg = $data['message'] ?? 'Error desconocido de Mercado Pago';
        throw new Exception("Mercado Pago respondió $httpCode: $msg");
    }

    return $data;
}