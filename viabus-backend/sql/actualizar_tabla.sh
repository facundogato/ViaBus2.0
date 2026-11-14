#!/usr/bin/env bash
#
# actualizar_tabla.sh — Actualiza columnas de UNA tabla de la base con los datos
# que escribiste en schema.sql, sin tocar el resto de la base.
#
# Uso:
#   ./actualizar_tabla.sh TABLA COLUMNAS [CLAVE] [--ver]
#
#   TABLA     tabla a actualizar (ej. empresas)
#   COLUMNAS  columnas a actualizar, separadas por coma (ej. descripcion  o  descripcion,telefono)
#   CLAVE     columna que identifica cada fila para emparejarla (por defecto: id)
#   --ver     solo muestra el SQL que se ejecutaría, sin tocar la base
#
# Ejemplos:
#./actualizar_tabla.sh empresas descripcion nombre

#   ./actualizar_tabla.sh empresas descripcion
#   ./actualizar_tabla.sh empresas descripcion,telefono nombre
#   ./actualizar_tabla.sh empresas descripcion nombre --ver
#
# Cómo funciona: toma los INSERT de esa tabla que están en schema.sql, los carga en una
# tabla temporal y copia SOLO las COLUMNAS elegidas a las filas que ya existen
# (emparejadas por CLAVE). No inserta ni borra filas. Todo va en una transacción:
# si algo falla, no cambia nada.
#
# Variables opcionales:
#   SCHEMA=otro.sql                       archivo de donde leer (por defecto schema.sql)
#   VIABUS_DB=viabus                      nombre de la base
#   PSQL="psql -h localhost -U usuario"   cómo conectarse (por defecto: sudo -u postgres psql)

set -euo pipefail

SCHEMA=${SCHEMA:-schema.sql}
BASE=${VIABUS_DB:-viabus}
PSQL=${PSQL:-sudo -u postgres psql}

VER=0
ARGS=()
for a in "$@"; do
  if [ "$a" = "--ver" ]; then VER=1; else ARGS+=("$a"); fi
done

if [ "${#ARGS[@]}" -lt 2 ]; then
  echo "Uso: ./actualizar_tabla.sh TABLA COLUMNAS [CLAVE] [--ver]"
  echo "Ej.: ./actualizar_tabla.sh empresas descripcion"
  exit 1
fi

TABLA=${ARGS[0]}
COLUMNAS=${ARGS[1]}
CLAVE=${ARGS[2]:-id}

valido() { [[ "$1" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]]; }

valido "$TABLA" || { echo "Nombre de tabla no válido: $TABLA"; exit 1; }
valido "$CLAVE" || { echo "Nombre de clave no válido: $CLAVE"; exit 1; }
[ -f "$SCHEMA" ] || { echo "No encuentro $SCHEMA. Ejecutá el script desde la carpeta donde está."; exit 1; }

# Arma "col1 = n.col1, col2 = n.col2"
ASIGNACIONES=""
IFS=',' read -ra LISTA <<< "$COLUMNAS"
for c in "${LISTA[@]}"; do
  c=${c// /}
  valido "$c" || { echo "Nombre de columna no válido: $c"; exit 1; }
  ASIGNACIONES+="${ASIGNACIONES:+, }$c = n.$c"
done

# Saca de schema.sql solo los INSERT de esa tabla (incluso si ocupan varias líneas)
INSERTS=$(awk -v tabla="$(echo "$TABLA" | tr 'A-Z' 'a-z')" '
  {
    linea = tolower($0)
    patron = "^[ \t]*insert[ \t]+into[ \t]+(public\\.)?\"?" tabla "\"?([ \t]|\\(|$)"
    if (!dentro && linea ~ patron) dentro = 1
    if (dentro) print $0
    if (dentro && $0 ~ /;[ \t]*(--.*)?$/) dentro = 0
  }' "$SCHEMA" | sed -E "s/^([[:space:]]*insert[[:space:]]+into[[:space:]]+)(public\.)?\"?${TABLA}\"?/\1_nuevo/I")

if [ -z "$INSERTS" ]; then
  echo "No encontré ningún INSERT INTO $TABLA en $SCHEMA."
  exit 1
fi

SQL=$(cat <<EOF
BEGIN;
CREATE TEMP TABLE _nuevo (LIKE ${TABLA} INCLUDING ALL) ON COMMIT DROP;
${INSERTS}
-- Filas que están en schema.sql pero NO existen en la base (este script no las crea):
SELECT n.${CLAVE} AS no_existen_en_la_base FROM _nuevo n
  WHERE NOT EXISTS (SELECT 1 FROM ${TABLA} t WHERE t.${CLAVE} = n.${CLAVE});
UPDATE ${TABLA} AS t SET ${ASIGNACIONES} FROM _nuevo AS n WHERE t.${CLAVE} = n.${CLAVE};
COMMIT;
EOF
)

if [ "$VER" = 1 ]; then
  printf '%s\n' "$SQL"
  exit 0
fi

echo "Actualizando $TABLA ($COLUMNAS, emparejando por $CLAVE) en la base $BASE..."
printf '%s\n' "$SQL" | $PSQL -v ON_ERROR_STOP=1 -d "$BASE"
echo "Listo. Arriba figura 'UPDATE N': son las filas actualizadas."
