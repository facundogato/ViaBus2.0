<?php
/**
 * consultas_faq.php — Preguntas frecuentes del asistente automático.
 *
 * Es el ÚNICO archivo que hay que tocar para cambiar lo que responde el chat.
 *
 * Cada entrada tiene:
 *   id         Identificador único, sin espacios (ej. 'horarios').
 *   pregunta   Texto del botón que ve el cliente.
 *   respuesta  Lo que contesta el asistente.
 *   palabras   Palabras clave para reconocer preguntas escritas a mano.
 *   visible    (opcional) false = no aparece como botón (se usa para saludos).
 *   peso       (opcional) importancia de las palabras; 1 por defecto.
 *
 * Cómo funcionan las palabras clave:
 *   - Da igual si llevan tildes o mayúsculas.
 *   - Una palabra es una RAÍZ: 'horari' reconoce horario, horarios, horarios...
 *     También tolera una letra mal escrita en palabras de 5 letras o más.
 *   - Una frase con espacios ('a que hora') debe aparecer completa y vale el doble.
 *   - Si dos preguntas empatan en puntaje, el asistente NO adivina:
 *     muestra las preguntas frecuentes para que el cliente elija.
 *   - Evitá palabras muy generales (pasaje, terminal, quiero): aparecerían en todo.
 *
 * Las respuestas no inventan horarios ni precios: mandan a las secciones del sitio donde están.
 * Cuando tengas los teléfonos y el correo reales, agregalos en la respuesta 'contacto'.
 */
return [

    [
        'id'        => 'horarios',
        'pregunta'  => '¿Cuáles son los horarios de salida?',
        'respuesta' => 'Los horarios de salida dependen de la compañía y del destino. Podés verlos en el Tablero de la terminal, o en Comprar pasaje al elegir tu destino.',
        'palabras'  => ['horari', 'hora', 'salida', 'sale', 'salen', 'parte', 'parten', 'partida', 'llegada', 'proximo', 'a que hora', 'cuando sale'],
    ],
    [
        'id'        => 'precios',
        'pregunta'  => '¿Cuánto cuesta un pasaje?',
        'respuesta' => 'El valor del pasaje depende de la compañía y del destino. Lo ves en Comprar pasaje al elegir tu viaje, o podés consultarlo en la boletería de la terminal.',
        'palabras'  => ['preci', 'cuest', 'cost', 'valor', 'tarifa', 'cuanto sale', 'cuanto vale', 'cuanto es'],
    ],
    [
        'id'        => 'comprar',
        'pregunta'  => '¿Cómo compro o reservo un pasaje?',
        'respuesta' => 'Entrá a Comprar pasaje, elegí destino, fecha, servicio y asiento, completá los datos del pasajero y pagá. Al confirmar, te llega el pasaje con su código QR por correo. También podés comprarlo en la boletería de la terminal.',
        'palabras'  => ['compr', 'reserv', 'adquir', 'sacar pasaje', 'sacar boleto', 'comprar boleto', 'comprar pasaje', 'reservar boleto', 'reservar pasaje', 'quiero un pasaje', 'quiero un boleto', 'necesito un pasaje', 'necesito un boleto', 'quiero viajar'],
    ],
    [
        'id'        => 'destinos',
        'pregunta'  => '¿A qué destinos puedo viajar?',
        'respuesta' => 'En Comprar pasaje ves todos los destinos disponibles desde Melo y los servicios de cada uno. También los encontrás en el Tablero.',
        'palabras'  => ['destin', 'ciudad', 'localidad', 'recorrido', 'ruta', 'a donde', 'a que ciudades', 'hasta donde', 'que lugares', 'que localidades', 'que destinos', 'ir a', 'viajar a', 'a que lugares', 'a que localidades', 'a que destinos'],
    ],
    [
        'id'        => 'equipaje',
        'pregunta'  => '¿Cuánto equipaje puedo llevar?',
        'respuesta' => 'Podés llevar equipaje de mano y una valija en bodega sin costo. Para equipaje extra o cargas especiales, consultá con la compañía de tu viaje.',
        'palabras'  => ['equipaj', 'ekipaj', 'valija', 'maleta', 'bolso', 'mochila', 'encomienda', 'paquete', 'bulto', 'kilos', 'carga'],
    ],
    [
        'id'        => 'ubicacion',
        'pregunta'  => '¿Dónde queda la terminal?',
        'respuesta' => 'Estamos en la Terminal de Melo, sobre la calle Treinta y Tres, entre Villa Nueva Pinto y Manuel Lestido.',
        'palabras'  => ['ubicacion', 'direccion', 'queda', 'mapa', 'donde esta', 'donde es', 'como llego', 'como llegar', 'como ir'],
    ],
    [
        'id'        => 'contacto',
        'pregunta'  => '¿Cómo puedo contactarlos?',
        'respuesta' => 'Podés contactarnos por teléfono, WhatsApp o correo electrónico, o acercarte a la terminal.',
        'palabras'  => ['contact', 'telefon', 'llam', 'whatsapp', 'mail', 'correo', 'email', 'comunicar', 'atencion', 'atiend', 'horario de atencion', 'que hora atienden', 'que hora abren'],
    ],
    [
        'id'        => 'cancelar',
        'pregunta'  => '¿Puedo cancelar o cambiar mi pasaje?',
        'respuesta' => 'Podés cancelar tu pasaje desde Mi Perfil, en tus próximos viajes, hasta 3 horas antes de la salida. Pasado ese plazo ya no se puede cancelar desde la web.',
        'palabras'  => ['cancel', 'cambi', 'devol', 'reembols', 'anul', 'cancelaciones'],
    ],
    [
        'id'        => 'menores',
        'pregunta'  => '¿Pueden viajar menores de edad?',
        'respuesta' => 'Los menores deben viajar acompañados de un adulto, o llevar la autorización correspondiente junto con su documento de identidad vigente.',
        'palabras'  => ['menor', 'niño', 'niña', 'hijo', 'hija', 'adolescente', 'joven', 'menores de edad', 'viajar con menores', 'viajar con niños', 'viajar con niñas'],
    ],
    [
        'id'        => 'mascotas',
        'pregunta'  => '¿Puedo viajar con mascotas?',
        'respuesta' => 'Podés viajar con una mascota pequeña, siempre que vaya en su canil o jaula de transporte reglamentaria, sin excepciones.',
        'palabras'  => ['mascota', 'perro', 'gato', 'animal', 'cachorro'],
    ],

    // ---- Charla básica (no aparece como botón) ----
    [
        'id'        => 'saludo',
        'visible'   => false,
        'peso'      => 0.5,
        'pregunta'  => 'Hola',
        'respuesta' => 'Hola, ¿en qué te puedo ayudar? Elegí una pregunta frecuente o escribí la tuya.',
        'palabras'  => ['hola', 'buenas', 'buen dia', 'buenos dias', 'buenas tardes', 'buenas noches', 'que tal'],
    ],
    [
        'id'        => 'gracias',
        'visible'   => false,
        'peso'      => 0.5,
        'pregunta'  => 'Gracias',
        'respuesta' => '¡De nada! Si tenés otra consulta, escribila o elegí una pregunta frecuente.',
        'palabras'  => ['gracia', 'agradezco'],
    ],
    [
        'id'        => 'despedida',
        'visible'   => false,
        'peso'      => 0.5,
        'pregunta'  => 'Chau',
        'respuesta' => '¡Hasta luego! Cuando quieras, acá estoy.',
        'palabras'  => ['chau', 'adios', 'hasta luego', 'nos vemos'],
    ],

];
