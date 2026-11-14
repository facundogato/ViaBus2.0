-- Estado de la tarjeta
ALTER TABLE tarjetas
  ADD COLUMN IF NOT EXISTS estado        VARCHAR(20) NOT NULL DEFAULT 'solicitada',
  ADD COLUMN IF NOT EXISTS solicitada_en TIMESTAMP   NOT NULL DEFAULT NOW(),
  ADD COLUMN IF NOT EXISTS entregada_en  TIMESTAMP;

-- Sesiones (si no la creaste todavía)
CREATE TABLE IF NOT EXISTS sesiones (
    id         SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash CHAR(64) UNIQUE NOT NULL,
    expira_en  TIMESTAMP NOT NULL,
    creada_en  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Rol en usuarios (si no lo agregaste todavía)
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NOT NULL DEFAULT 'cliente';

-- Tarjetas (si no la creaste todavía)
CREATE TABLE IF NOT EXISTS tarjetas (
    id          SERIAL PRIMARY KEY,
    card_uid    VARCHAR(32) UNIQUE NOT NULL,
    usuario_id  INT REFERENCES usuarios(id),
    activa      BOOLEAN NOT NULL DEFAULT TRUE,
    creada_en   TIMESTAMP NOT NULL DEFAULT NOW(),
    impresa_en  TIMESTAMP
);

-- Abordajes (si no la creaste todavía)
CREATE TABLE IF NOT EXISTS abordajes (
    id           SERIAL PRIMARY KEY,
    servicio_id  INT NOT NULL REFERENCES servicios(id),
    usuario_id   INT NOT NULL REFERENCES usuarios(id),
    tarjeta_id   INT NOT NULL REFERENCES tarjetas(id),
    chofer_id    INT NOT NULL REFERENCES usuarios(id),
    escaneado_en TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (servicio_id, usuario_id)
);

-- Si ya existe la columna activa en tarjetas, convertila a estado
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='tarjetas' AND column_name='activa') THEN
    UPDATE tarjetas SET estado = CASE
        WHEN NOT activa             THEN 'anulada'
        WHEN impresa_en IS NOT NULL THEN 'impresa'
        ELSE 'solicitada' END;
    ALTER TABLE tarjetas DROP COLUMN activa;
  END IF;
END $$;

-- Índice único: una sola tarjeta vigente por usuario
CREATE UNIQUE INDEX IF NOT EXISTS tarjeta_vigente_unica ON tarjetas (usuario_id) WHERE estado <> 'anulada';

-- Chofer asignado a cada servicio
ALTER TABLE servicios ADD COLUMN IF NOT EXISTS chofer_id INT REFERENCES usuarios(id);