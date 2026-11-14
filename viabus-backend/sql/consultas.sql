-- Chat de consultas (asistente automático) — PostgreSQL. Se puede correr varias veces.
--   psql -h 127.0.0.1 -U postgres -d viabus -f consultas.sql
--
-- Guarda las preguntas que el asistente NO entendió, para ir mejorando las palabras
-- clave en api/ChatBot/consultas_faq.php. Si la tabla no existe el chat igual funciona,
-- pero esas preguntas se pierden.
CREATE TABLE IF NOT EXISTS consultas_sin_respuesta (
    id      SERIAL PRIMARY KEY,
    texto   VARCHAR(300) NOT NULL,
    creada  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Para revisarlas:
-- SELECT creada, texto FROM consultas_sin_respuesta ORDER BY creada DESC LIMIT 50;

-- (Las tablas "consultas" y "consultas_mensajes" eran del diseño anterior, con atención
--  por personas. El chat actual es automático y ya no las usa.)
