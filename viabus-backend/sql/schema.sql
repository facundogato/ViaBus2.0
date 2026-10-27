-- =============================================================
-- ViaBus - Base de Datos PostgreSQL
-- ESQUEMA COMPLETO CORREGIDO
-- =============================================================

BEGIN;

-- =============================================================
-- FUNCIÓN AUXILIAR
-- =============================================================

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- =============================================================
-- ELIMINAR TABLAS EXISTENTES
-- =============================================================

DROP TABLE IF EXISTS pagos CASCADE;
DROP TABLE IF EXISTS reservas CASCADE;
DROP TABLE IF EXISTS pasajeros CASCADE;
DROP TABLE IF EXISTS estado_diario CASCADE;
DROP TABLE IF EXISTS servicio_paradas CASCADE;
DROP TABLE IF EXISTS email_verification_codes CASCADE;
DROP TABLE IF EXISTS servicios CASCADE;
DROP TABLE IF EXISTS ciudades CASCADE;
DROP TABLE IF EXISTS empresas CASCADE;
DROP TABLE IF EXISTS user_metrics_log CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;


-- =============================================================
-- USUARIOS
-- =============================================================

CREATE TABLE usuarios (
    id             SERIAL PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    apellido       VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,

    rol            VARCHAR(20) NOT NULL DEFAULT 'cliente'
                   CHECK (
                       rol IN ('cliente', 'admin')
                   ),

    email_verified BOOLEAN NOT NULL DEFAULT FALSE
);


-- =============================================================
-- EMPRESAS
-- =============================================================

CREATE TABLE empresas (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    logo        VARCHAR(255) DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    telefono    VARCHAR(30) DEFAULT NULL,
    email       VARCHAR(100) DEFAULT NULL,
    sitio_web   VARCHAR(150) DEFAULT NULL,
    direccion   VARCHAR(150) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================
-- CÓDIGOS DE VERIFICACIÓN DE EMAIL
-- IMPORTANTE:
-- usuarios debe existir antes de esta tabla.
-- =============================================================

CREATE TABLE email_verification_codes (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL,
    email       VARCHAR(150) NOT NULL,
    code        VARCHAR(6) NOT NULL,

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  TIMESTAMP NOT NULL,
    verified_at TIMESTAMP DEFAULT NULL,

    CONSTRAINT fk_email_verification_user
        FOREIGN KEY (user_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_email_verification_user
    ON email_verification_codes(user_id);

CREATE INDEX idx_email_verification_expires
    ON email_verification_codes(expires_at);


-- =============================================================
-- CIUDADES
-- =============================================================

CREATE TABLE ciudades (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================
-- SERVICIOS
-- =============================================================

CREATE TABLE servicios (
    id          SERIAL PRIMARY KEY,

    destino_id  INTEGER NOT NULL,
    empresa_id  INTEGER NOT NULL,

    hora        VARCHAR(5) NOT NULL,
    tipo        VARCHAR(80) NOT NULL DEFAULT 'De Linea',
    duracion    VARCHAR(50) DEFAULT NULL,
    precio      DECIMAL(10,2) DEFAULT NULL,

    asientos    INTEGER DEFAULT 40,
    anden       VARCHAR(10) DEFAULT NULL,
    bus         VARCHAR(30) DEFAULT NULL,

    estado      VARCHAR(20) DEFAULT 'horario',
    activo      INTEGER DEFAULT 1,
    dias        VARCHAR(60) DEFAULT NULL,

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_servicios_destino
        FOREIGN KEY (destino_id)
        REFERENCES ciudades(id),

    CONSTRAINT fk_servicios_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
);


-- =============================================================
-- PARADAS DE SERVICIO
-- =============================================================

CREATE TABLE servicio_paradas (
    id            SERIAL PRIMARY KEY,
    servicio_id   INTEGER NOT NULL,
    ciudad_id     INTEGER NOT NULL,
    orden         SMALLINT NOT NULL,
    hora_llegada  TIME DEFAULT NULL,

    CONSTRAINT fk_servicio_paradas_servicio
        FOREIGN KEY (servicio_id)
        REFERENCES servicios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_servicio_paradas_ciudad
        FOREIGN KEY (ciudad_id)
        REFERENCES ciudades(id)
);


-- =============================================================
-- PASAJEROS
-- =============================================================

CREATE TABLE pasajeros (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    apellido    VARCHAR(100) NOT NULL,
    documento   VARCHAR(20) NOT NULL UNIQUE,
    email       VARCHAR(150) DEFAULT NULL,
    telefono    VARCHAR(30) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================
-- ESTADO DIARIO
-- =============================================================

CREATE TABLE estado_diario (
    id          SERIAL PRIMARY KEY,

    servicio_id INTEGER NOT NULL,
    fecha       DATE NOT NULL,

    demorado    INTEGER NOT NULL DEFAULT 0,

    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_estado_diario_servicio
        FOREIGN KEY (servicio_id)
        REFERENCES servicios(id),

    CONSTRAINT uq_estado_diario
        UNIQUE (servicio_id, fecha)
);


CREATE INDEX idx_estado_diario_servicio_fecha
    ON estado_diario(servicio_id, fecha);


-- =============================================================
-- RESERVAS
-- =============================================================

CREATE TABLE reservas (
    id              SERIAL PRIMARY KEY,

    servicio_id     INTEGER NOT NULL,
    pasajero_id     INTEGER NOT NULL,
    usuario_id      INTEGER DEFAULT NULL,

    fecha_viaje     DATE NOT NULL,
    asiento         INTEGER NOT NULL,

    estado          VARCHAR(20) NOT NULL DEFAULT 'pendiente'
                    CHECK (
                        estado IN (
                            'pendiente',
                            'confirmada',
                            'cancelada'
                        )
                    ),

    estado_pago     VARCHAR(20) NOT NULL DEFAULT 'pendiente'
                    CHECK (
                        estado_pago IN (
                            'pendiente',
                            'pagado',
                            'rechazado',
                            'cancelado'
                        )
                    ),

    total           DECIMAL(10,2) DEFAULT NULL,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reservas_servicio
        FOREIGN KEY (servicio_id)
        REFERENCES servicios(id),

    CONSTRAINT fk_reservas_pasajero
        FOREIGN KEY (pasajero_id)
        REFERENCES pasajeros(id),

    CONSTRAINT fk_reservas_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id),

    CONSTRAINT uq_reserva_asiento
        UNIQUE (servicio_id, fecha_viaje, asiento)
);


CREATE INDEX idx_reservas_servicio_fecha
    ON reservas(servicio_id, fecha_viaje);

CREATE INDEX idx_reservas_usuario
    ON reservas(usuario_id);

CREATE INDEX idx_reservas_pasajero
    ON reservas(pasajero_id);


CREATE TRIGGER trg_reservas_updated_at
BEFORE UPDATE ON reservas
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

-- Tablas del chat de consultas (PostgreSQL)

CREATE TABLE consultas (
    id             SERIAL PRIMARY KEY,
    token_hash     CHAR(64)    NOT NULL,              -- sha256 del token que guarda el navegador del cliente
    email          VARCHAR(254),                      -- opcional: para avisarle por mail
    estado         VARCHAR(10) NOT NULL DEFAULT 'abierta' CHECK (estado IN ('abierta', 'cerrada')),
    ip_hash        CHAR(64),                          -- sha256 de la IP, solo para limitar abusos
    creada         TIMESTAMPTZ NOT NULL DEFAULT now(),
    actualizada    TIMESTAMPTZ NOT NULL DEFAULT now(),
    cliente_visto  TIMESTAMPTZ NOT NULL DEFAULT now(), -- última vez que el chat del cliente consultó
    ultimo_aviso   TIMESTAMPTZ                        -- último mail de aviso enviado a atención
);

CREATE TABLE consultas_mensajes (
    id           BIGSERIAL PRIMARY KEY,
    consulta_id  INTEGER     NOT NULL REFERENCES consultas (id) ON DELETE CASCADE,
    rol          VARCHAR(10) NOT NULL CHECK (rol IN ('cliente', 'atencion')),
    texto        TEXT        NOT NULL,
    creada       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_consultas_mensajes_consulta ON consultas_mensajes (consulta_id, id);
CREATE INDEX idx_consultas_ip ON consultas (ip_hash, creada);

-- =============================================================
-- PAGOS
-- =============================================================

CREATE TABLE pagos (
    id                          SERIAL PRIMARY KEY,

    reserva_id                  INTEGER NOT NULL UNIQUE,

    mercadopago_preference_id   VARCHAR(255) DEFAULT NULL,
    mercadopago_payment_id      VARCHAR(255) DEFAULT NULL,

    estado                      VARCHAR(20) NOT NULL DEFAULT 'pendiente'
                                CHECK (
                                    estado IN (
                                        'pendiente',
                                        'pagado',
                                        'rechazado',
                                        'cancelado'
                                    )
                                ),

    monto                       DECIMAL(10,2) DEFAULT NULL,

    respuesta_mp                TEXT DEFAULT NULL,

    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pagos_reserva
        FOREIGN KEY (reserva_id)
        REFERENCES reservas(id)
        ON DELETE CASCADE
);


CREATE INDEX idx_pagos_estado
    ON pagos(estado);

CREATE INDEX idx_pagos_mp_preference
    ON pagos(mercadopago_preference_id);

CREATE INDEX idx_pagos_mp_payment
    ON pagos(mercadopago_payment_id);


CREATE TRIGGER trg_pagos_updated_at
BEFORE UPDATE ON pagos
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();


-- =============================================================
-- MÉTRICAS DE USUARIOS
-- =============================================================

CREATE TABLE IF NOT EXISTS user_metrics_log (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event       VARCHAR(10) NOT NULL CHECK (event IN ('login', 'logout')),
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_umlog_event_time ON user_metrics_log (event, created_at);
CREATE INDEX IF NOT EXISTS idx_umlog_user_time  ON user_metrics_log (user_id, created_at);

-- =============================================================
-- EMPRESAS
-- =============================================================

INSERT INTO empresas (
    nombre,
    logo,
    descripcion,
    telefono,
    email,
    sitio_web,
    direccion
) VALUES
(
    'Decatur',
    'Decatur-logo.jpg',
    'Decatur es una empresa uruguaya de transporte de pasajeros con sede en la ciudad de Melo, departamento de Cerro Largo. Cuenta con una trayectoria de más de tres décadas en el sector y cumple un papel importante en la conexión entre Melo y distintas localidades de la región, facilitando los desplazamientos cotidianos de los habitantes del departamento.
    La empresa realiza servicios de transporte departamental y regional, conectando Melo con destinos como Río Branco, Aceguá y Lago Merín. Sus recorridos permiten viajar por motivos laborales, educativos, comerciales, turísticos y personales, contribuyendo a la movilidad de quienes viven en el interior del departamento. En febrero de 2026, Decatur amplió su cobertura hasta Fraile Muerto mediante un servicio por la Ruta 7, con salidas desde Melo y regreso a la capital departamental.
    Además de sus servicios regulares de pasajeros, Decatur está vinculada al sector de los viajes y el transporte turístico. Su presencia en la Terminal de Ómnibus de Melo permite a los usuarios acceder a información sobre sus servicios y consultar las opciones de viaje disponibles.',
    '4642 4877',
    'decatur@adinet.com.uy',
    NULL,
    'Ansina 330, Melo, Cerro Largo'
),
(
    'Ega',
    'Ega-logo.jpg',
    'EGA es una empresa uruguaya de transporte terrestre de pasajeros, fundada en 1982, que ofrece servicios nacionales e internacionales, además de viajes turísticos y transporte de encomiendas. A lo largo de su trayectoria, se ha destacado por conectar Uruguay con distintos destinos de la región, apostando por la comodidad de los pasajeros, la atención a bordo y la incorporación de servicios que mejoran la experiencia de viaje.

La empresa comenzó a desarrollar servicios nacionales a fines de la década de 1980 y, en 1990, se fusionó con EGA, ampliando sus posibilidades de crecimiento en el transporte internacional. Durante los años siguientes incorporó unidades de doble piso y amplió su red de destinos, consolidando su presencia en el transporte terrestre de larga distancia.

En Uruguay, uno de sus recorridos nacionales más importantes conecta Montevideo con Minas, José Pedro Varela, Treinta y Tres y Melo, a través de la Ruta 8. Esta línea facilita la conexión entre la capital del país y distintas localidades del este, siendo una alternativa de transporte para quienes viajan por motivos laborales, educativos, comerciales o personales.

Desde la Terminal de Ómnibus de Melo, EGA brinda conexión con Montevideo y otras localidades de su recorrido. Además, la empresa cuenta con servicios internacionales que conectan Uruguay con destinos de Argentina, Brasil y Paraguay, entre ellos Buenos Aires, Córdoba, Mendoza, Rosario, Porto Alegre, Florianópolis y Asunción. Las rutas y frecuencias pueden variar según el destino y el día.

En cuanto a sus servicios, EGA se caracteriza por ofrecer viajes de larga distancia con unidades equipadas para brindar comodidad durante el trayecto. Según el servicio y el vehículo, los pasajeros pueden disponer de conexión Wi-Fi, puertos de carga USB o tomas de corriente, entretenimiento a bordo y atención del personal durante el viaje. En determinados recorridos también se ofrecen bebidas y alimentos.

Además del transporte de pasajeros, EGA realiza el envío de cartas y encomiendas, facilitando el traslado de paquetes entre distintas localidades y destinos de su red. También ofrece propuestas de turismo y excursiones, orientadas a quienes desean viajar por vacaciones o conocer otros lugares de Uruguay y la región.

Gracias a su trayectoria y a su red de conexiones nacionales e internacionales, EGA constituye una de las alternativas de transporte de larga distancia disponibles en la Terminal de Melo, vinculando a Cerro Largo con Montevideo y con otros destinos de Uruguay y países vecinos.',
    '4642 0505',
    'comercial@ega.com.uy',
    'www.ega.com.uy',
    'Terminal de Ómnibus de Melo, Cerro Largo'
),
(
    'La Flotta',
    'Laflota-logo.jpg',
    'La Flota es una empresa de transporte de pasajeros que opera principalmente en el departamento de Cerro Largo, conectando Melo con diferentes localidades de la región. Entre sus principales recorridos se encuentran las líneas hacia Río Branco, Aceguá, Santa Clara de Olimar, Fraile Muerto y Lago Merín, con servicios que permiten desplazarse tanto entre centros urbanos como hacia localidades del interior.

Sus líneas cumplen una función importante para la movilidad cotidiana de la población, facilitando los viajes por motivos laborales, educativos, comerciales, familiares y turísticos. La conexión con Río Branco también favorece el desplazamiento hacia la zona fronteriza con Brasil, mientras que los servicios hacia Lago Merín permiten acceder a uno de los principales destinos turísticos de la región.

La empresa cuenta con una agencia en la Terminal de Ómnibus de Melo, ubicada en el local 6, donde los pasajeros pueden realizar consultas relacionadas con sus viajes y los servicios disponibles. La Flota constituye una alternativa de transporte para quienes necesitan trasladarse entre Melo y otras localidades de Cerro Largo, contribuyendo a la conectividad departamental y regional.',
    '4642 7668',
    NULL,
    NULL,
    'Terminal de Ómnibus de Melo, Cerro Largo'
),
(
    'Nuñez',
    'Nunez-logo.jpg',
    'Núñez es una empresa uruguaya de transporte de pasajeros que, junto con Cynsa, brinda servicios de ómnibus interdepartamentales y conecta diferentes regiones del país. Desde Melo, ofrece conexiones con Montevideo y otros destinos mediante recorridos que incluyen localidades como Treinta y Tres, José Pedro Varela, Mariscala, Minas, Santa Clara de Olimar, Tupambaé y Fraile Muerto, entre otras.

Sus servicios facilitan los desplazamientos por motivos laborales, educativos, familiares, comerciales y turísticos, conectando la ciudad de Melo con importantes centros urbanos y localidades del interior. La empresa también dispone de recorridos hacia destinos como Punta del Este y cuenta con una red de servicios que comunica Montevideo con ciudades de distintos departamentos, entre ellas Salto, Paysandú, Tacuarembó y Rivera.

Además del transporte de pasajeros, Núñez y Cynsa ofrecen servicios de encomiendas y cargas, venta de pasajes por internet y excursiones. Estas prestaciones permiten atender distintas necesidades de movilidad y transporte, tanto para viajeros particulares como para quienes necesitan enviar paquetes o mercaderías.

Para consultar horarios, recorridos, tarifas y disponibilidad de pasajes, los pasajeros pueden acceder al sitio web oficial de la empresa. Los horarios y las frecuencias varían según el destino y el día de viaje, por lo que se recomienda comprobar los detalles del servicio antes de salir.',
    '4642 2566',
    NULL,
    'www.nunez.com.uy',
    'Manuela Lestido S/N, Melo, Cerro Largo'
),
(
    'Posada',
    'Posada-logo.jpg',
    'Posada Turismo es una empresa uruguaya de transporte de pasajeros que conecta distintas localidades de los departamentos de Cerro Largo, Tacuarembó y Rivera. Su servicio principal vincula Melo con Rivera, atravesando la región por las rutas 26 y 5 y facilitando los desplazamientos entre el noreste del país y la frontera con Brasil.

Desde Melo, la empresa ofrece servicios hacia Rivera con diferentes frecuencias a lo largo del día. También conecta Rivera con San Gregorio de Polanco, en el departamento de Tacuarembó, permitiendo el traslado de pasajeros entre ambas localidades y las poblaciones situadas a lo largo de sus recorridos.

Sus servicios son utilizados por personas que viajan por motivos laborales, educativos, familiares, comerciales y personales. La conexión entre Melo y Rivera resulta especialmente importante para quienes necesitan trasladarse entre dos ciudades relevantes del interior del país sin depender de un vehículo particular.

La empresa opera en las terminales de Melo, Tacuarembó y Rivera, y cuenta con servicios que contemplan diferentes días de la semana. Los horarios y las frecuencias pueden variar según el recorrido y el día del viaje.',
    '4642 0302',
    NULL,
    NULL,
    'Terminal de Ómnibus de Melo, Cerro Largo'
),
(
    'Turismar',
    'Turismar-logo.jpg',
    'Turismar es una empresa uruguaya de transporte terrestre de pasajeros que ofrece servicios regulares de ómnibus entre Montevideo y diferentes ciudades y localidades del interior del país. Su red conecta importantes centros urbanos y destinos turísticos, facilitando los desplazamientos por motivos laborales, educativos, familiares, comerciales y recreativos.

En la Terminal de Ómnibus de Melo, Turismar dispone de una agencia que atiende a los pasajeros y brinda acceso a sus servicios. La empresa opera una línea que conecta Montevideo con Melo a través de la ruta 7, con paradas en distintas localidades intermedias, entre ellas Fraile Muerto, Santa Clara de Olimar y Tupambaé. Esta conexión contribuye a la movilidad de los habitantes de Cerro Largo y de otras localidades del interior.

Entre sus principales destinos también se encuentran Durazno, Paso de los Toros, San Gregorio de Polanco, Minas, Maldonado, Piriápolis y Punta del Este, además de otros puntos del territorio nacional. Los recorridos permiten conectar regiones del centro, este y sur del país, incluyendo destinos turísticos de gran importancia.

Además del transporte de pasajeros, Turismar ofrece venta de pasajes por internet, servicios de encomiendas y cargas, alquiler de unidades y propuestas vinculadas al turismo. Estas prestaciones amplían las opciones disponibles para quienes necesitan viajar o enviar paquetes dentro de Uruguay',
    '4642 5214',
    NULL,
    'www.turismar.com.uy',
    'Terminal de Ómnibus de Melo, Cerro Largo'
),
(
    'Unitur',
    'Unitur-logo.jpg',
    'Unitur es una empresa de transporte de pasajeros que desarrolla su actividad en Melo y otras localidades del departamento de Cerro Largo, Uruguay. Su titular es Alcides Pereda González, empresario vinculado al transporte de pasajeros en la región.

La empresa se dedica al transporte terrestre de pasajeros mediante servicios de ómnibus que conectan distintas localidades del departamento. Su actividad está orientada a facilitar la movilidad de la población, atendiendo necesidades de traslado por motivos laborales, educativos, comerciales, familiares y personales.

Uno de los aspectos destacados de su trayectoria es su participación en el desarrollo de nuevas conexiones de transporte dentro de Cerro Largo. Un ejemplo es la línea entre Melo y Río Branco, cuya autorización fue gestionada por el titular de la empresa desde enero de 2018. Después de un proceso administrativo y de adjudicación impulsado por la Intendencia de Cerro Largo, Unitur obtuvo la concesión del servicio y comenzó a operarlo en marzo de 2024.

La incorporación de esta conexión representó una ampliación de las alternativas de transporte disponibles para los habitantes de ambas ciudades. Según explicó el propio empresario en declaraciones a medios locales, la iniciativa buscaba responder a una necesidad de movilidad que afectaba especialmente a trabajadores y estudiantes que debían trasladarse entre las dos localidades.

Unitur forma parte del sistema de transporte regional de Cerro Largo, donde los servicios de ómnibus cumplen una función importante para conectar las localidades del interior con Melo, centro administrativo y comercial del departamento. Su presencia en la terminal permite integrar sus servicios a la red de transporte utilizada por quienes se desplazan por la región.

La empresa se caracteriza por su participación en la creación y ampliación de conexiones interurbanas, asumiendo la organización y los recursos necesarios para prestar los servicios que tiene adjudicados. Su actividad contribuye a mejorar las posibilidades de desplazamiento de los pasajeros y a mantener la comunicación entre distintas poblaciones del departamento.',
    '4643 1233',
    NULL,
    NULL,
    'Melo, Cerro Largo'
);


-- =============================================================
-- CIUDADES
-- =============================================================

INSERT INTO ciudades (nombre) VALUES
    ('Aceguá'),
    ('Batlle y Ordóñez'),
    ('Casupá'),
    ('Cerro Chato'),
    ('Cerro Colorado'),
    ('Fraile Muerto'),
    ('José Pedro Varela'),
    ('Lago Merín'),
    ('Maldonado'),
    ('Mariscala'),
    ('Melo'),
    ('Minas'),
    ('Montevideo'),
    ('Pando'),
    ('Punta del Este'),
    ('Rio Branco'),
    ('Rivera'),
    ('San Ramón'),
    ('Santa Clara'),
    ('Tacuarembó'),
    ('Treinta y Tres'),
    ('Tupambaé');


-- =============================================================
-- SERVICIOS
-- =============================================================
-- -- Servicios (Solo salidas con Origen en Melo)
INSERT INTO servicios (destino_id, empresa_id, hora, tipo, duracion, precio, asientos, anden, bus, estado, dias) VALUES
    -- NUÑEZ
    ((SELECT id FROM ciudades WHERE nombre='Punta del Este'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','5h 00m',540.0,39,'A-01','MELPUN-9174','demorado','Lunes, Martes, Viernes, Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Maldonado'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','4h 42m',510.0,44,'A-04','MELMAL-9810','horario','Lunes, Martes, Viernes, Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','5h 44m',610.0,42,'A-03','MELMON-3785','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'06:00','De Linea','5h 44m',610.0,39,'A-04','MELMON-9685','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'10:15','De Linea','5h 44m',610.0,36,'A-01','MELMON-6321','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'14:30','De Linea','5h 44m',610.0,37,'A-02','MELMON-2401','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'23:45','De Linea','5h 44m',610.0,32,'A-03','MELMON-9228','embarcando','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','3h 14m',380.0,39,'A-01','MELMIN-9719','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'06:00','De Linea','3h 14m',380.0,37,'A-02','MELMIN-8312','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'10:15','De Linea','3h 14m',380.0,43,'A-03','MELMIN-9735','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'14:30','De Linea','3h 14m',380.0,40,'A-04','MELMIN-2468','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','2h 39m',320.0,34,'A-02','MELMAR-4738','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'06:00','De Linea','2h 39m',320.0,37,'A-03','MELMAR-3451','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'10:15','De Linea','2h 39m',320.0,32,'A-04','MELMAR-8246','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'14:30','De Linea','2h 39m',320.0,41,'A-01','MELMAR-8654','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','2h 12m',280.0,40,'A-03','MELJOS-4128','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'06:00','De Linea','2h 12m',280.0,43,'A-04','MELJOS-7167','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'10:15','De Linea','2h 12m',280.0,34,'A-01','MELJOS-6078','embarcando','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'14:30','De Linea','2h 12m',280.0,41,'A-02','MELJOS-4873','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'00:30','De Linea','1h 28m',220.0,43,'A-04','MELTRE-1434','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'06:00','De Linea','1h 28m',220.0,38,'A-01','MELTRE-3511','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'10:15','De Linea','1h 28m',220.0,44,'A-02','MELTRE-4914','demorado','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'14:30','De Linea','1h 28m',220.0,36,'A-03','MELTRE-5578','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'21:30','De Linea','1h 28m',220.0,37,'A-04','MELTRE-7927','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Rivera'),(SELECT id FROM empresas WHERE nombre='Nuñez'),'14:00','De Linea','3h 14m',380.0,42,'A-01','MELRIV-7113','embarcando','Todos'),

    -- POSADA
    ((SELECT id FROM ciudades WHERE nombre='Rivera'),(SELECT id FROM empresas WHERE nombre='Posada'),'04:30','De Linea','3h 14m',380.0,44,'A-01','MELRIV-4362','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Rivera'),(SELECT id FROM empresas WHERE nombre='Posada'),'10:00','De Linea','3h 14m',380.0,33,'A-02','MELRIV-2247','horario','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Rivera'),(SELECT id FROM empresas WHERE nombre='Posada'),'15:30','De Linea','3h 14m',380.0,34,'A-03','MELRIV-7680','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Tacuarembó'),(SELECT id FROM empresas WHERE nombre='Posada'),'06:45','De Linea','2h 39m',320.0,33,'A-03','MELTAC-4531','horario','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Tacuarembó'),(SELECT id FROM empresas WHERE nombre='Posada'),'13:45','De Linea','2h 39m',320.0,35,'A-04','MELTAC-8592','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Tacuarembó'),(SELECT id FROM empresas WHERE nombre='Posada'),'17:00','De Linea','2h 39m',320.0,35,'A-01','MELTAC-3450','horario','Todos'),

    -- UNITUR
    ((SELECT id FROM ciudades WHERE nombre='Santa Clara'),(SELECT id FROM empresas WHERE nombre='Unitur'),'17:00','De Linea','1h 28m',220.0,37,'A-03','MELSAN-7792','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Santa Clara'),(SELECT id FROM empresas WHERE nombre='Unitur'),'20:10','De Linea','1h 28m',220.0,40,'A-04','MELSAN-3057','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Aceguá'),(SELECT id FROM empresas WHERE nombre='Unitur'),'10:30','De Linea','26m',120.0,35,'A-01','MELACE-3168','embarcando','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Aceguá'),(SELECT id FROM empresas WHERE nombre='Unitur'),'12:30','De Linea','26m',120.0,40,'A-02','MELACE-5070','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='Unitur'),'10:00','De Linea','1h 19m',200.0,39,'A-02','MELRIO-3043','embarcando','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='Unitur'),'11:00','De Linea','1h 19m',200.0,35,'A-01','MELRIO-8172','horario','Todos'),

    -- DECATUR
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='Decatur'),'06:00','De Linea','1h 19m',200.0,32,'A-01','MELRIO-7443','embarcando','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='Decatur'),'07:00','De Linea','1h 19m',200.0,36,'A-02','MELRIO-5089','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='Decatur'),'10:30','De Linea','1h 19m',200.0,44,'A-03','MELRIO-9809','demorado','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='Decatur'),'12:00','De Linea','1h 19m',200.0,32,'A-04','MELRIO-4755','horario','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Lago Merín'),(SELECT id FROM empresas WHERE nombre='Decatur'),'07:00','De Linea','53m',160.0,36,'A-01','MELLAG-1867','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Fraile Muerto'),(SELECT id FROM empresas WHERE nombre='Decatur'),'06:00','De Linea','26m',120.0,40,'A-03','MELFRA-5015','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Aceguá'),(SELECT id FROM empresas WHERE nombre='Decatur'),'06:45','De Linea','26m',120.0,37,'A-01','MELACE-9277','horario','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Aceguá'),(SELECT id FROM empresas WHERE nombre='Decatur'),'11:30','De Linea','26m',120.0,43,'A-02','MELACE-4374','demorado','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Aceguá'),(SELECT id FROM empresas WHERE nombre='Decatur'),'16:00','De Linea','26m',120.0,34,'A-03','MELACE-3389','demorado','Lunes a Viernes'),

    -- LA FLOTTA
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='La Flotta'),'14:00','De Linea','1h 19m',200.0,44,'A-02','MELRIO-4752','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Rio Branco'),(SELECT id FROM empresas WHERE nombre='La Flotta'),'18:00','De Linea','1h 19m',200.0,33,'A-03','MELRIO-1899','demorado','Todos'),

    -- EGA
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Ega'),'02:00','De Linea','5h 44m',610.0,41,'A-04','MELMON-4266','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Ega'),'07:00','De Linea','5h 44m',610.0,36,'A-01','MELMON-5463','embarcando','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Ega'),'12:30','De Linea','5h 44m',610.0,40,'A-02','MELMON-3159','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Ega'),'17:00','De Linea','5h 44m',610.0,40,'A-03','MELMON-7778','embarcando','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Pando'),(SELECT id FROM empresas WHERE nombre='Ega'),'02:00','De Linea','5h 18m',570.0,37,'A-04','MELPAN-3703','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Pando'),(SELECT id FROM empresas WHERE nombre='Ega'),'07:00','De Linea','5h 18m',570.0,32,'A-01','MELPAN-4254','demorado','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Pando'),(SELECT id FROM empresas WHERE nombre='Ega'),'12:30','De Linea','5h 18m',570.0,39,'A-02','MELPAN-9026','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Pando'),(SELECT id FROM empresas WHERE nombre='Ega'),'17:00','De Linea','5h 18m',570.0,35,'A-03','MELPAN-8943','embarcando','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Ega'),'02:00','De Linea','3h 14m',380.0,39,'A-04','MELMIN-6310','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Ega'),'07:00','De Linea','3h 14m',380.0,39,'A-01','MELMIN-5009','demorado','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Ega'),'12:30','De Linea','3h 14m',380.0,36,'A-02','MELMIN-3204','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Minas'),(SELECT id FROM empresas WHERE nombre='Ega'),'17:00','De Linea','3h 14m',380.0,43,'A-03','MELMIN-1807','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Ega'),'02:00','De Linea','2h 39m',320.0,41,'A-04','MELMAR-2591','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Ega'),'07:00','De Linea','2h 39m',320.0,41,'A-01','MELMAR-7766','horario','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Ega'),'12:30','De Linea','2h 39m',320.0,44,'A-02','MELMAR-9278','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Mariscala'),(SELECT id FROM empresas WHERE nombre='Ega'),'17:00','De Linea','2h 39m',320.0,41,'A-03','MELMAR-9953','embarcando','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Ega'),'02:00','De Linea','2h 12m',280.0,41,'A-04','MELJOS-7565','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Ega'),'07:00','De Linea','2h 12m',280.0,36,'A-01','MELJOS-4458','embarcando','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Ega'),'12:30','De Linea','2h 12m',280.0,35,'A-02','MELJOS-4734','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='José Pedro Varela'),(SELECT id FROM empresas WHERE nombre='Ega'),'17:00','De Linea','2h 12m',280.0,40,'A-03','MELJOS-2131','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Ega'),'02:00','De Linea','1h 28m',220.0,43,'A-04','MELTRE-5642','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Ega'),'07:00','De Linea','1h 28m',220.0,38,'A-01','MELTRE-5243','embarcando','Lunes a Sábado'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Ega'),'12:30','De Linea','1h 28m',220.0,41,'A-02','MELTRE-3011','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Treinta y Tres'),(SELECT id FROM empresas WHERE nombre='Ega'),'17:00','De Linea','1h 28m',220.0,37,'A-03','MELTRE-4942','horario','Domingo a Viernes'),

    -- TURISMAR
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','5h 44m',610.0,37,'A-01','MELMON-1257','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','5h 44m',610.0,44,'A-04','MELMON-3839','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','5h 44m',610.0,38,'A-02','MELMON-9799','demorado','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='San Ramón'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','4h 25m',480.0,43,'A-03','MELSAN-5055','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='San Ramón'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','4h 25m',480.0,40,'A-02','MELSAN-5609','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='San Ramón'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','4h 25m',480.0,32,'A-04','MELSAN-2289','demorado','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Casupá'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','4h 07m',460.0,32,'A-01','MELCAS-5952','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Casupá'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','4h 07m',460.0,36,'A-04','MELCAS-5040','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Casupá'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','4h 07m',460.0,37,'A-02','MELCAS-3028','embarcando','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Cerro Colorado'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','2h 04m',270.0,35,'A-03','MELCER-2130','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Cerro Colorado'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','2h 04m',270.0,43,'A-02','MELCER-6783','embarcando','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Cerro Colorado'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','2h 04m',270.0,35,'A-04','MELCER-5856','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Batlle y Ordóñez'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','2h 12m',280.0,39,'A-01','MELBAT-7157','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Batlle y Ordóñez'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','2h 12m',280.0,42,'A-04','MELBAT-4019','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Batlle y Ordóñez'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','2h 12m',280.0,37,'A-02','MELBAT-4934','demorado','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Cerro Chato'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','1h 55m',260.0,36,'A-03','MELCER-4115','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Cerro Chato'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','1h 55m',260.0,32,'A-02','MELCER-9479','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Cerro Chato'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','1h 55m',260.0,39,'A-04','MELCER-6541','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Santa Clara'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','1h 28m',220.0,37,'A-01','MELSAN-2595','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Santa Clara'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','1h 28m',220.0,43,'A-04','MELSAN-4281','horario','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Santa Clara'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','1h 28m',220.0,32,'A-02','MELSAN-2866','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Tupambaé'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','53m',160.0,35,'A-03','MELTUP-3224','demorado','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Tupambaé'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','53m',160.0,41,'A-02','MELTUP-5639','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Tupambaé'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','53m',160.0,42,'A-04','MELTUP-7326','horario','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Fraile Muerto'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','De Linea','26m',120.0,33,'A-01','MELFRA-2866','horario','Lunes a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Fraile Muerto'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','De Linea','26m',120.0,37,'A-04','MELFRA-5094','demorado','Todos'),
    ((SELECT id FROM ciudades WHERE nombre='Fraile Muerto'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','De Linea','26m',120.0,36,'A-02','MELFRA-2219','demorado','Domingo a Viernes'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Turismar'),'13:30','Directo Domingo','5h 44m',610.0,39,'A-02','MELMON-5298','embarcando','Domingo'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Turismar'),'18:00','Directo Domingo','5h 44m',610.0,36,'A-03','MELMON-6186','horario','Domingo'),
    ((SELECT id FROM ciudades WHERE nombre='Montevideo'),(SELECT id FROM empresas WHERE nombre='Turismar'),'02:15','Directo Lunes','5h 44m',610.0,43,'A-04','MELMON-8874','demorado','Lunes');


-- =============================================================
-- USUARIOS DE PRUEBA
-- =============================================================

INSERT INTO usuarios (
    nombre,
    apellido,
    email,
    password_hash,
    rol,
    email_verified
) VALUES
(
    'Admin',
    'Terminal',
    'admin@terminal.com',
    '$2y$12$wU6LMu.SDhYvfijWxfptKeCTiTTUJraNZWUDynEwFVrgCM7eSxH4S',
    'admin',
    TRUE
),
(
    'Cliente',
    'De Prueba',
    'cliente@ejemplo.com',
    '$2y$12$msY/pEnsL.NTLy.bnmxLIuMv9Gr/fdMLeyBHOfPZtzeQClCYyJZxe',
    'cliente',
    TRUE
);


-- desarrollo: agregar extensión para generar UIDs de tarjetas y columna para fecha de impresión

CREATE EXTENSION IF NOT EXISTS pgcrypto;
ALTER TABLE tarjetas ADD COLUMN impresa_en TIMESTAMP;

-- Crear tarjeta a quienes todavía no tienen una activa
INSERT INTO tarjetas (card_uid, usuario_id)
SELECT 'VB' || upper(encode(gen_random_bytes(8), 'hex')), u.id
FROM usuarios u
WHERE u.id IN (12, 25, 31)   -- ids de los usuarios a los que les toca tarjeta
  AND NOT EXISTS (
      SELECT 1 FROM tarjetas t WHERE t.usuario_id = u.id AND t.activa
  );


  ALTER TABLE tarjetas
  ADD COLUMN estado        VARCHAR(20) NOT NULL DEFAULT 'solicitada',
  ADD COLUMN solicitada_en TIMESTAMP   NOT NULL DEFAULT NOW(),
  ADD COLUMN entregada_en  TIMESTAMP;

-- Conservar lo que ya hayas creado en pruebas
UPDATE tarjetas SET estado = CASE
    WHEN NOT activa             THEN 'anulada'
    WHEN impresa_en IS NOT NULL THEN 'impresa'
    ELSE 'solicitada' END;

ALTER TABLE tarjetas DROP COLUMN activa;

-- Una sola tarjeta vigente por usuario (también frena el doble clic)
CREATE UNIQUE INDEX tarjeta_vigente_unica ON tarjetas (usuario_id) WHERE estado <> 'anulada';

UPDATE usuarios SET rol = 'chofer' WHERE email = 'chofer@ejemplo.com';
UPDATE servicios SET chofer_id = (SELECT id FROM usuarios WHERE email = 'chofer@ejemplo.com') WHERE id = 123;
-- =============================================================
-- FINALIZAR
-- =============================================================

COMMIT;


