-- =============================================================
--  ViaBus - Base de Datos (PostgreSQL)
--  Generado a partir de los datos reales del frontend (Origen: Melo)
-- =============================================================

-- Función auxiliar para actualizar automáticamente "updated_at" en cada UPDATE.
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS pasajeros;
DROP TABLE IF EXISTS estado_diario;
DROP TABLE IF EXISTS servicio_paradas;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS ciudades;
DROP TABLE IF EXISTS empresas;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE empresas (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    logo        VARCHAR(255) DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    telefono    VARCHAR(30)  DEFAULT NULL,
    email       VARCHAR(100) DEFAULT NULL,
    sitio_web   VARCHAR(150) DEFAULT NULL,
    direccion   VARCHAR(150) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuarios (
    id             SERIAL PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    apellido       VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    rol            VARCHAR(20) NOT NULL DEFAULT 'cliente' CHECK(rol IN ('cliente','admin')),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ciudades (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE servicios (
    id SERIAL PRIMARY KEY,
    destino_id INT NOT NULL,
    empresa_id INT NOT NULL,
    hora TIME NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'De Linea',
    duracion VARCHAR(20),
    precio DECIMAL(10,2),
    asientos INT,
    anden VARCHAR(10),
    bus VARCHAR(20) NOT NULL,
    estado VARCHAR(20) DEFAULT 'horario',
    dias VARCHAR(100) NOT NULL,
    FOREIGN KEY (destino_id) REFERENCES ciudades(id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

CREATE TABLE servicio_paradas (
    id SERIAL PRIMARY KEY,
    servicio_id INT NOT NULL,
    ciudad_id INT NOT NULL,
    orden SMALLINT NOT NULL,
    hora_llegada TIME,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    FOREIGN KEY (ciudad_id) REFERENCES ciudades(id)
);

CREATE TABLE pasajeros (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    apellido    VARCHAR(100) NOT NULL,
    documento   VARCHAR(20)  NOT NULL UNIQUE,
    email       VARCHAR(150) DEFAULT NULL,
    telefono    VARCHAR(30)  DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE estado_diario (
    id          SERIAL PRIMARY KEY,
    servicio_id INTEGER NOT NULL,
    fecha       DATE NOT NULL,
    demorado    INTEGER NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    UNIQUE (servicio_id, fecha)
);

CREATE TABLE reservas (
    id              SERIAL PRIMARY KEY,
    servicio_id     INTEGER NOT NULL,
    pasajero_id     INTEGER NOT NULL,
    usuario_id      INTEGER DEFAULT NULL,
    fecha_viaje     DATE NOT NULL,
    asiento         INTEGER NOT NULL,
    estado          VARCHAR(20) DEFAULT 'pendiente' CHECK(estado IN ('pendiente','confirmada','cancelada')),
    estado_pago     VARCHAR(20) DEFAULT 'pendiente' CHECK(estado_pago IN ('pendiente','pagado','rechazado','cancelado')),
    total           DECIMAL(10,2) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (pasajero_id) REFERENCES pasajeros(id),
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
    UNIQUE (servicio_id, fecha_viaje, asiento)
);

CREATE TRIGGER trg_reservas_updated_at
BEFORE UPDATE ON reservas
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TABLE pagos (
    id                          SERIAL PRIMARY KEY,
    reserva_id                  INTEGER NOT NULL UNIQUE,
    mercadopago_preference_id   VARCHAR(255),
    mercadopago_payment_id      VARCHAR(255),
    estado                      VARCHAR(20) DEFAULT 'pendiente' CHECK(estado IN ('pendiente','pagado','rechazado','cancelado')),
    monto                       DECIMAL(10,2),
    respuesta_mp                TEXT,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id)
);

CREATE TRIGGER trg_pagos_updated_at
BEFORE UPDATE ON pagos
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

-- Empresas
INSERT INTO empresas (nombre, logo, descripcion, telefono, email, sitio_web, direccion) VALUES
    ('Decatur', 'Decatur-logo.jpg', 'Decatur es una empresa de transporte de pasajeros y agencia de viajes ubicada en Melo, Cerro Largo.', '4642 4877', 'decatur@adinet.com.uy', NULL, 'Ansina 330, Melo, Cerro Largo'),
    ('Ega', 'Ega-logo.jpg', 'EGA es una empresa uruguaya de transporte de pasajeros y turismo.', '4642 0505', 'comercial@ega.com.uy', 'www.ega.com.uy', 'Terminal de Ómnibus de Melo, Cerro Largo'),
    ('La Flotta', 'Laflota-logo.jpg', 'La Flota es una empresa de transporte de pasajeros con sede en la ciudad de Melo.', '4642 7668', NULL, NULL, 'Terminal de Ómnibus de Melo, Cerro Largo'),
    ('Nuñez', 'Nunez-logo.jpg', 'Nuñez Transporte y Turismo es una empresa uruguaya fundada en 1964 en Melo.', '4642 2566', NULL, 'www.nunez.com.uy', 'Manuela Lestido S/N, Melo, Cerro Largo'),
    ('Posada', 'Posada-logo.jpg', 'Posada es una empresa familiar de transporte de pasajeros fundada en 1993.', '4642 0302', NULL, NULL, 'Terminal de Ómnibus de Melo, Cerro Largo'),
    ('Turismar', 'Turismar-logo.jpg', 'Turismar es una empresa de transporte uruguayo fundada en 1976.', '4642 5214', NULL, 'www.turismar.com.uy', 'Terminal de Ómnibus de Melo, Cerro Largo'),
    ('Unitur', 'Unitur-logo.jpg', 'Unitur es una empresa de transporte de pasajeros, turismo y encomiendas.', '4643 1233', NULL, NULL, 'Melo, Cerro Largo');
    
-- Ciudades
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

-- Servicios (Solo salidas con Origen en Melo)
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

-- Usuarios de prueba
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol) VALUES
    ('Admin', 'Terminal', 'admin@terminal.com', '$2y$12$wU6LMu.SDhYvfijWxfptKeCTiTTUJraNZWUDynEwFVrgCM7eSxH4S', 'admin'),
    ('Cliente', 'De Prueba', 'cliente@ejemplo.com', '$2y$12$msY/pEnsL.NTLy.bnmxLIuMv9Gr/fdMLeyBHOfPZtzeQClCYyJZxe', 'cliente');