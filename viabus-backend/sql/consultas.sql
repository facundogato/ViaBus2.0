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
