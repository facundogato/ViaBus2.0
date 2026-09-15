
<?php


$archivoEnv = __DIR__ . '/../.env';

// Verificamos que el archivo .env exista antes de intentar leerlo
if (file_exists($archivoEnv)) {

    // Leemos todas las líneas del archivo .env
    $lineasEnv = file($archivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineasEnv as $linea) {

        // Ignoramos líneas que comiencen con # porque son comentarios.
        if (str_starts_with(trim($linea), '#')) {
            continue;
        }

        // Buscamos el signo "=" que separa nombre y valor
        $partes = explode('=', $linea, 2);

        // Si la línea tiene correctamente nombre y valor
        if (count($partes) === 2) {

            $nombre = trim($partes[0]);
            $valor  = trim($partes[1]);

            // Guardamos la variable para que pueda ser utilizada
            // mediante getenv() y $_ENV.
            putenv("$nombre=$valor");
            $_ENV[$nombre] = $valor;
        }
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Cargue Phpmailer para hacer la confirmacion de correo
require_once __DIR__ . '/../vendor/autoload.php';

// Clases necesarias para usar PHPMailer.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


setCorsHeaders();
handlePreflight();

$pdo    = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';



// Configuración del correo
$correoRemitente = getenv('MAIL_USERNAME');
$correoPassword  = getenv('MAIL_PASSWORD');

// POST /api/auth.php?action=login
// → iniciar sesión (email + password)

if ($method === 'POST' && $action === 'login') {

    $body = getRequestBody();
    if (empty($body['email']) || empty($body['password'])) {
        sendError('Correo y contraseña son obligatorios');
    }
    $stmt = $pdo->prepare("
        SELECT *
        FROM usuarios
        WHERE email = ?
    ");

    $stmt->execute([$body['email']]);
    $usuario = $stmt->fetch();


    if ( !$usuario || !password_verify( $body['password'], $usuario['password_hash'])) {
        sendError('Correo o contraseña incorrectos', 401);
    }

    // Comprobamos si el usuario confirmó su correo
    // email_verificado = 0 → todavía NO confirmó
    // email_verificado = 1 → correo confirmado

    if ((int)$usuario['email_verificado'] !== 1) {
        sendError(
            'Debés confirmar tu correo electrónico antes de iniciar sesión',
            403
        );
    }

    unset($usuario['password_hash']);
    sendResponse($usuario);
}

// POST /api/auth.php?action=register
// → crear una cuenta nueva

if ($method === 'POST' && $action === 'register') {
    $body = getRequestBody();
    foreach (['nombre', 'apellido', 'email', 'password'] as $f) {
        if (empty($body[$f])) {
            sendError("El campo '$f' es obligatorio");
        }
    }


    if (strlen($body['password']) < 6) {
        sendError(
            'La contraseña debe tener al menos 6 caracteres'
        );
    }


    try {
        $hash = password_hash(
            $body['password'],
            PASSWORD_DEFAULT
        );

        // Generamos un token aleatorio para confirmar el correo.
        // random_bytes() genera datos aleatorios seguros.
        // bin2hex() los convierte en texto.
        // Resultado de ejemplo:
        // 8f3a1c9e7b
        $token = bin2hex(random_bytes(32));

        // El token tendrá una duración de 24 horas.
        $stmt = $pdo->prepare(" INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, email_verificado, token_verificacion, token_expira)
            VALUES ( ?, ?, ?, ?, 'cliente', 0, ?, datetime('now', '+24 hours')
            )
        ");


        $stmt->execute( [$body['nombre'], $body['apellido'], $body['email'], $hash, $token]);
        $id = $pdo->lastInsertId();

        // Creamos la URL que recibirá el usuario por correo.
        $urlVerificacion =
            'http://localhost:9000/verificar-correo?token=' .
            urlencode($token);
        // Enviamos el correo de confirmación.
        enviarCorreoVerificacion(
            $body['email'],
            $body['nombre'],
            $urlVerificacion,
            $correoRemitente,
            $correoPassword
        );

        // El usuario tiene que confirmar correo antes de coso
        sendResponse([
            'mensaje' => 'Cuenta creada. Revisá tu correo para confirmar tu cuenta.'
        ], 201);

    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') {
            sendError(
                'Ese correo ya está registrado'
            );
        }
        throw $e;
    }
}


// GET /api/auth.php?action=verify&token=...
// Este endpoint se ejecutará cuando el usuario haga clic
// en el enlace que recibió por correo

if ($method === 'GET' && $action === 'verify') {
    $token = $_GET['token'] ?? '';

    // Comprobamos que exista el token.
    if (empty($token)) {
        sendError(
            'Token de verificación no proporcionado',
            400
        );
    }

    // Buscamos el usuario cuyo token coincida
    // También comprobamos que el token no haya expirado

    $stmt = $pdo->prepare("
        SELECT id, nombre, email, email_verificado
        FROM usuarios
        WHERE token_verificacion = ?
        AND token_expira > datetime('now')
    ");

    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    // Si no encontramos usuario, el token:
    // - no existe
    // - ya fue utilizado
    // - o expiró

    if (!$usuario) {
        sendError(
            'El enlace de verificación no es válido o ya expiró',
            400
        );
    }

    // Marcamos el correo como confirmado
    // También eliminamos el token para que no pueda volver
    // a utilizarse
    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET
            email_verificado = 1,
            token_verificacion = NULL,
            token_expira = NULL
        WHERE id = ?
    ");

    $stmt->execute([$usuario['id']]);

    // Le decimos al front que todo salió piola
    sendResponse([
        'mensaje' => 'Correo electrónico confirmado correctamente'
    ]);
}

// Si ninguna de las acciones anteriores coincide.
sendError('Método no soportado', 405);

// FUNCIÓN PARA ENVIAR EL CORREO DE VERIFICACIÓN
function enviarCorreoVerificacion(
    $destinatario,
    $nombre,
    $urlVerificacion,
    $correoRemitente,
    $correoPassword
) {

    // Creamos una nueva instancia de PHPMailer.
    $mail = new PHPMailer(true);

    try {

        // Configuración SMTP de Gmail.
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $correoRemitente;
        $mail->Password = $correoPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Remitente y destinatario
        $mail->setFrom(
            $correoRemitente,
            'ViaBus'
        );

        $mail->addAddress(
            $destinatario,
            $nombre
        );

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject =
            'Confirmá tu correo electrónico - ViaBus';
        $mail->Body = "
            <div style=\"
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: auto;
                padding: 30px;
                background: #060c22;
                color: white;
                border-radius: 20px;
            \">

                <h1 style=\"color: #f4b400;\">
                    ¡Bienvenido a ViaBus!
                </h1>

                <p>
                    Hola <strong>{$nombre}</strong>,
                </p>

                <p>
                    Gracias por crear tu cuenta.
                    Para poder iniciar sesión necesitás
                    confirmar tu correo electrónico.
                </p>

                <p style=\"text-align: center;\">
                    <a
                        href=\"{$urlVerificacion}\"
                        style=\"
                            display: inline-block;
                            padding: 14px 25px;
                            background: #f4b400;
                            color: #060c22;
                            text-decoration: none;
                            border-radius: 10px;
                            font-weight: bold;
                        \"
                    >
                        Confirmar mi correo
                    </a>
                </p>

                <p>
                    Este enlace es válido durante 24 horas.
                </p>

                <p>
                    Si vos no creaste esta cuenta,
                    simplemente ignorá este correo.
                </p>

            </div>
        ";

        // Versión de texto plano para clientes de correo
        // que no muestran HTML
        $mail->AltBody =
            "Hola {$nombre}.\n\n" .
            "Confirmá tu correo electrónico de ViaBus " .
            "visitando este enlace:\n\n" .
            $urlVerificacion .
            "\n\nEste enlace es válido durante 24 horas.";

        $mail->send();

    } catch (Exception $e) {
        throw new Exception(
            'No se pudo enviar el correo de verificación: ' .
            $mail->ErrorInfo
        );
    }
}

