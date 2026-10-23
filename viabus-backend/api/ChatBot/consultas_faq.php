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
 * IMPORTANTE: reemplazá todo lo que diga [COMPLETAR ...] por los datos reales
 * antes de publicar. No inventé horarios, precios ni direcciones.
 */
return [

    [
        'id'        => 'horarios',
        'pregunta'  => '¿Cuáles son los horarios de salida?',
        'respuesta' => 'Los horarios dependen de la compañía y del destino. [COMPLETAR: dónde se consultan los horarios y, si querés, el horario general de la terminal.]',
        'palabras'  => ['horari', 'hora', 'salida', 'sale', 'salen', 'parte', 'parten', 'partida', 'llegada', 'proximo', 'a que hora', 'cuando sale'],
    ],
    [
        'id'        => 'precios',
        'pregunta'  => '¿Cuánto cuesta un pasaje?',
        'respuesta' => 'El valor de los pasajes depende de que compañia e destino usted elija.
        Puedes derigirte a la secciòn de reservas para ver los precios de los pasajes, o bien dirigirte a la boletería de la terminal para consultar personalmente.',
        'palabras'  => ['preci', 'cuest', 'cost', 'valor', 'tarifa', 'cuanto sale', 'cuanto vale', 'cuanto es'],
    ],
    [
        'id'        => 'comprar',
        'pregunta'  => '¿Cómo compro o reservo un pasaje?',
        'respuesta' => 'Dirigete a la secciòn de Reservas, elige origen, destino, fecha y asiento. Completa tus datos y realiza el pago. Recibirás un correo con tu pasaje electrónico. También podés comprarlo en la boletería de la terminal.',
        'palabras'  => ['compr', 'reserv', 'adquir', 'sacar pasaje', 'sacar boleto', 'comprar boleto', 'comprar pasaje', 'reservar boleto', 'reservar pasaje'],
    ],
    [
        'id'        => 'destinos',
        'pregunta'  => '¿A qué destinos puedo viajar?',
        'respuesta' => 'Ve a Reservas y despliega la lista de Destinos para ver todas las ciudades y rutas disponibles en tiempo real',
        'palabras'  => ['destin', 'ciudad', 'localidad', 'recorrido', 'ruta', 'a donde', 'a que ciudades', 'hasta donde', 'que lugares', 'que localidades', 'que destinos', 'ir a', 'viajar a', 'a que lugares', 'a que localidades', 'a que destinos'],
    ],
    [
        'id'        => 'equipaje',
        'pregunta'  => '¿Cuánto equipaje puedo llevar?',
        'respuesta' => 'Puedes llevar equipaje de mano o una valija en bodej¡ga sin costo. Para quipaje extra o cargas especiales consultá con la compañía de tu viaje.',
        'palabras'  => ['equipaj', 'valija', 'maleta', 'bolso', 'mochila', 'encomienda', 'paquete', 'bulto', 'kilos', 'carga', 'ekipaje', 'equipaje', 'equipaje de mano', 'equipaje extra', 'equipaje adicional', 'equipaje especial', 'equipaje permitido', 'equipaje permitido por pasajero'],
    ],
    [
        'id'        => 'ubicacion',
        'pregunta'  => '¿Dónde queda la terminal?',
        'respuesta' => 'Nos ubicamos en la TERMINAL DE MELO, sobre la calle Treinta y tres entre Villa Nueva Pinto y Manuel Lestido',
        'palabras'  => ['ubicacion', 'direccion', 'queda', 'mapa', 'donde esta', 'donde es', 'como llego', 'como llegar', 'como ir'],
    ],
    [
        'id'        => 'contacto',
        'pregunta'  => '¿Cómo puedo contactarlos?',
        'respuesta' => 'Puedes contactarnos por teléfono, WhatsApp o correo electrónico.',
        'palabras'  => ['contact', 'telefon', 'llam', 'whatsapp', 'mail', 'correo', 'email', 'comunicar', 'atencion', 'atiend', 'horario de atencion', 'que hora atienden', 'que hora abren'],
    ],
    [
        'id'        => 'cancelar',
        'pregunta'  => '¿Puedo cancelar o cambiar mi pasaje?',
        'respuesta' => 'Para cancelar o devolver tu pasaje, ingresa a Mis Reservas o consulta en la secciòn Contacto antes del horario de salida.',
        'palabras'  => ['cancel', 'cambi', 'devol', 'reembols', 'anul', 'cancelar', 'anular', 'cancelaciòn'],
    ],
    [
        'id'        => 'menores',
        'pregunta'  => '¿Pueden viajar menores de edad?',
        'respuesta' => 'Los menores deben viajar acompañados de un adulto o presentar la autorizaciòn correspondiente junto con su documento de identidad vigente,',
        'palabras'  => ['menor', 'niño', 'niña', 'hijo', 'hija', 'adolescente', 'joven', 'menores de edad', 'viajar con menores', 'viajar con niños', 'viajar con niñas'],
    ],
    [
        'id'        => 'mascotas',
        'pregunta'  => '¿Puedo viajar con mascotas?',
        'respuesta' => 'Puedes viajar con tui mascota pequeña asegurandote de llevarla en su canil o jaula de transporte reglamentaria sin excepciones ',
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
