<?php
/**
 * consultas.php — API del asistente automático de consultas.
 *
 *   GET  ?accion=faq        Preguntas frecuentes (para mostrarlas como botones).
 *   POST ?accion=preguntar  Body: { "id": "horarios" }  o  { "mensaje": "texto libre" }
 *                           Devuelve { "respuesta": "...", "entendida": true | false }
 *
 * Qué responde se edita en consultas_faq.php (mismo directorio).
 * Las preguntas que no se entienden se guardan en la tabla consultas_sin_respuesta
 * para mejorar las palabras clave; si la base falla, el chat sigue funcionando.
 */

$CONFIG = [
    // Solo si el front corre en otro origen (ej. 'http://localhost:9000'). Vacío = mismo origen.
    'origen' => '',
];

header('Content-Type: application/json; charset=utf-8');

if ($CONFIG['origen'] !== '') {
    header('Access-Control-Allow-Origin: ' . $CONFIG['origen']);
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Vary: Origin');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ===================== UTILIDADES =====================
function responder_json(int $codigo, array $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function solo_post()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responder_json(405, ['error' => 'Método no permitido.']);
    }
}

/** Conexión a la base: reutiliza la que ya tiene el proyecto. Solo se usa para guardar preguntas sin respuesta. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $ruta = __DIR__ . '/../../config/database.php';
        if (!is_file($ruta)) {
            throw new RuntimeException('No se encontró ' . $ruta);
        }
        require_once $ruta;
        $pdo = getConnection(); // si tu función de conexión se llama distinto, cambialo acá
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

function registrar_sin_respuesta(string $texto)
{
    try {
        db()->prepare('INSERT INTO consultas_sin_respuesta (texto) VALUES (?)')->execute([$texto]);
    } catch (Throwable $e) {
        error_log('Consultas: no se pudo guardar la pregunta sin respuesta: ' . $e->getMessage());
    }
}

// ===================== MOTOR DE PALABRAS CLAVE =====================

/** Minúsculas, sin tildes ni signos: "¿Cuánto CUESTA?" → "cuanto cuesta". */
function normalizar(string $texto): string
{
    static $mapa = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'ñ' => 'n', 'Ñ' => 'n',
    ];
    $texto = strtolower(strtr($texto, $mapa));
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $texto));
}

/** ¿La palabra escrita empieza con la raíz? Tolera una letra equivocada si la raíz tiene 5+ letras. */
function coincide(string $palabra, string $raiz): bool
{
    $n = strlen($raiz);
    if (strncmp($palabra, $raiz, $n) === 0) {
        return true;
    }
    if ($n >= 5 && strlen($palabra) >= $n - 1) {
        return levenshtein(substr($palabra, 0, $n), $raiz) <= 1;
    }
    return false;
}

/** Devuelve la entrada de la FAQ que mejor responde, o null si no hay una clara. */
function buscar_respuesta(string $texto, array $faq): ?array
{
    $norm = normalizar($texto);
    if ($norm === '') {
        return null;
    }
    $palabras = array_slice(explode(' ', $norm), 0, 40);
    $conEspacios = ' ' . $norm . ' ';

    $puntajes = [];
    foreach ($faq as $i => $entrada) {
        $peso = $entrada['peso'] ?? 1;
        $puntaje = 0.0;
        $usadas = []; // cada palabra escrita por el cliente suma una sola vez por pregunta
        foreach ($entrada['palabras'] as $clave) {
            $clave = normalizar($clave);
            if ($clave === '') {
                continue;
            }
            if (strpos($clave, ' ') !== false) {            // frase completa: vale el doble
                if (strpos($conEspacios, ' ' . $clave . ' ') !== false) {
                    $puntaje += 2 * $peso;
                }
                continue;
            }
            foreach ($palabras as $j => $p) {               // raíz: busca una palabra aún no usada
                if (!isset($usadas[$j]) && coincide($p, $clave)) {
                    $usadas[$j] = true;
                    $puntaje += $peso;
                    break;
                }
            }
        }
        if ($puntaje > 0) {
            $puntajes[$i] = $puntaje;
        }
    }

    if (!$puntajes) {
        return null;
    }
    arsort($puntajes);
    $indices = array_keys($puntajes);
    // Empate entre dos preguntas: mejor no adivinar
    if (isset($indices[1]) && $puntajes[$indices[1]] === $puntajes[$indices[0]]) {
        return null;
    }
    return $faq[$indices[0]];
}

// ===================== RUTAS =====================
try {
    $faq = require __DIR__ . '/consultas_faq.php';

    switch ($_GET['accion'] ?? '') {

           case 'faq':
       $completa = ($_GET['completa'] ?? '') === '1';
       $preguntas = [];
       foreach ($faq as $entrada) {
           if ($entrada['visible'] ?? true) {
               $item = ['id' => $entrada['id'], 'pregunta' => $entrada['pregunta']];
               if ($completa) {
                   $item['respuesta'] = $entrada['respuesta'];
               }
               $preguntas[] = $item;
           }
       }
       responder_json(200, ['preguntas' => $preguntas]);

        case 'preguntar':
            solo_post();
            $cuerpo = json_decode(file_get_contents('php://input') ?: '', true);
            if (!is_array($cuerpo)) {
                $cuerpo = [];
            }

            // Pregunta elegida con un botón
            if (isset($cuerpo['id'])) {
                foreach ($faq as $entrada) {
                    if ($entrada['id'] === (string)$cuerpo['id']) {
                        responder_json(200, ['respuesta' => $entrada['respuesta'], 'entendida' => true]);
                    }
                }
                responder_json(404, ['error' => 'Esa pregunta no existe.']);
            }

            // Pregunta escrita a mano
            $texto = trim((string)($cuerpo['mensaje'] ?? ''));
            if ($texto === '' || strlen($texto) > 300) {
                responder_json(422, ['error' => 'Escribí una consulta de hasta 300 caracteres.']);
            }

            $entrada = buscar_respuesta($texto, $faq);
            if ($entrada) {
                responder_json(200, ['respuesta' => $entrada['respuesta'], 'entendida' => true]);
            }

            registrar_sin_respuesta($texto);
            responder_json(200, [
                'respuesta' => 'No encontré una respuesta para eso. Probá con otras palabras o elegí una de las preguntas frecuentes.',
                'entendida' => false,
            ]);

        default:
            responder_json(404, ['error' => 'Acción desconocida.']);
    }
} catch (Throwable $e) {
    error_log('Consultas: ' . $e->getMessage());
    responder_json(500, ['error' => 'Error interno del servidor.']);
}
