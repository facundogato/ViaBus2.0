-- =============================================================
-- TARJETAS QR + ROL CHOFER + ABORDAJES  (script idempotente)
-- Se puede correr varias veces sin romper nada:
--   psql -U postgres -d viabus -f viabus-backend/sql/tarjetas-migracion.sql
-- Reemplaza a schema-nuevo.sql y a los parches sueltos del final de schema.sql.
-- =============================================================
BEGIN;

-- 1) Sesiones (token que usan las páginas de tarjetas y escáner)
CREATE TABLE IF NOT EXISTS sesiones (
    id         SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash CHAR(64) UNIQUE NOT NULL,
    expira_en  TIMESTAMP NOT NULL,
    creada_en  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 2) El rol "chofer" tiene que estar permitido (antes el CHECK solo dejaba cliente/admin)
ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_rol_check;
ALTER TABLE usuarios ADD CONSTRAINT usuarios_rol_check
    CHECK (rol IN ('cliente', 'admin', 'chofer'));

-- 3) Tarjetas
CREATE TABLE IF NOT EXISTS tarjetas (
    id             SERIAL PRIMARY KEY,
    card_uid       VARCHAR(32) UNIQUE NOT NULL,   -- lo que lleva el QR (nunca se muestra al usuario)
    usuario_id     INT REFERENCES usuarios(id),
    estado         VARCHAR(20) NOT NULL DEFAULT 'solicitada',
    nombre_impreso VARCHAR(100),                  -- nombre que se imprime (copia del perfil al solicitar)
    solicitada_en  TIMESTAMP NOT NULL DEFAULT NOW(),
    impresa_en     TIMESTAMP,
    entregada_en   TIMESTAMP
);

-- Si la tabla ya existía de las pruebas anteriores, completamos lo que falte
ALTER TABLE tarjetas
    ADD COLUMN IF NOT EXISTS estado         VARCHAR(20) NOT NULL DEFAULT 'solicitada',
    ADD COLUMN IF NOT EXISTS nombre_impreso VARCHAR(100),
    ADD COLUMN IF NOT EXISTS solicitada_en  TIMESTAMP NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS impresa_en     TIMESTAMP,
    ADD COLUMN IF NOT EXISTS entregada_en   TIMESTAMP;

-- La vieja columna "activa" se convierte a "estado" y se elimina
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name = 'tarjetas' AND column_name = 'activa') THEN
    UPDATE tarjetas SET estado = CASE
        WHEN NOT activa             THEN 'anulada'
        WHEN impresa_en IS NOT NULL THEN 'impresa'
        ELSE 'solicitada' END;
    ALTER TABLE tarjetas DROP COLUMN activa;
  END IF;
END $$;

-- Solo estos cuatro estados son válidos
ALTER TABLE tarjetas DROP CONSTRAINT IF EXISTS tarjetas_estado_check;
ALTER TABLE tarjetas ADD CONSTRAINT tarjetas_estado_check
    CHECK (estado IN ('solicitada', 'impresa', 'entregada', 'anulada'));

-- Una sola tarjeta vigente por usuario (también frena el doble clic)
CREATE UNIQUE INDEX IF NOT EXISTS tarjeta_vigente_unica
    ON tarjetas (usuario_id) WHERE estado <> 'anulada';

-- 4) Chofer asignado a cada servicio
ALTER TABLE servicios ADD COLUMN IF NOT EXISTS chofer_id INT REFERENCES usuarios(id);

-- 5) Abordajes: un pasajero aborda una sola vez por servicio y día
CREATE TABLE IF NOT EXISTS abordajes (
    id           SERIAL PRIMARY KEY,
    servicio_id  INT NOT NULL REFERENCES servicios(id),
    usuario_id   INT NOT NULL REFERENCES usuarios(id),
    tarjeta_id   INT NOT NULL REFERENCES tarjetas(id),
    chofer_id    INT NOT NULL REFERENCES usuarios(id),
    fecha        DATE NOT NULL DEFAULT CURRENT_DATE,
    escaneado_en TIMESTAMP NOT NULL DEFAULT NOW()
);
ALTER TABLE abordajes ADD COLUMN IF NOT EXISTS fecha DATE NOT NULL DEFAULT CURRENT_DATE;
ALTER TABLE abordajes DROP CONSTRAINT IF EXISTS abordajes_servicio_id_usuario_id_key;
CREATE UNIQUE INDEX IF NOT EXISTS abordaje_unico_por_dia
    ON abordajes (servicio_id, usuario_id, fecha);

COMMIT;

-- Para probar el escáner (cambiá el email y el id del servicio):
-- UPDATE usuarios  SET rol = 'chofer' WHERE email = 'chofer@ejemplo.com';
-- UPDATE servicios SET chofer_id = (SELECT id FROM usuarios WHERE email = 'chofer@ejemplo.com') WHERE id = 123;
