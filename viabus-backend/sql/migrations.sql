-- Agregar columna a usuarios
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE;

-- Tabla de códigos
CREATE TABLE IF NOT EXISTS email_verification_codes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    expires_at TIMESTAMP DEFAULT NOW() + INTERVAL '15 minutes',
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_evc_user_id ON email_verification_codes(user_id);
CREATE INDEX IF NOT EXISTS idx_evc_email ON email_verification_codes(email);

-- Limpieza de códigos viejos (ejecutar via cron o al generar nuevos códigos)
-- DELETE FROM email_verification_codes WHERE expires_at < NOW() - INTERVAL '24 hours';
