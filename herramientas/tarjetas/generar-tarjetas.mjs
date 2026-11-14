// Genera los archivos de impresión 3D de las tarjetas QR y los GUARDA EN LA COMPU.
//
//   cd herramientas/tarjetas
//   npm install                       (solo la primera vez)
//   node generar-tarjetas.mjs         -> tarjetas "solicitadas" (las que faltan imprimir)
//   node generar-tarjetas.mjs --marcar        -> además las pasa a estado "impresa"
//   node generar-tarjetas.mjs --estado=impresa  -> reimprime las que ya estaban impresas
//   node generar-tarjetas.mjs --ids=3,7       -> solo esas tarjetas, sin importar el estado
//
// Resultado en  herramientas/tarjetas/salida/ :
//   placa-001.scad / .stl / .txt   (8 tarjetas por placa; el .stl necesita OpenSCAD instalado)
//   qr/0001-nombre.png             (el QR de cada tarjeta, por si se quiere imprimir en papel)
//   lista.csv                      (quién va en cada placa, para la entrega)
//
// La base se toma de las variables DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS
// (las mismas que usa el backend PHP) o de DATABASE_URL. Sin nada, usa los valores de desarrollo.

import pg from 'pg'
import QRCode from 'qrcode'
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { armarPlacas, listaCsv, numeroTarjeta, nombreArchivo } from './tarjetas-lib.mjs'

const AQUI = dirname(fileURLToPath(import.meta.url))
const SALIDA = join(AQUI, 'salida')

const arg = (nombre) => process.argv.find(a => a.startsWith(`--${nombre}=`))?.split('=')[1]
const marcar = process.argv.includes('--marcar')
const estado = arg('estado') ?? 'solicitada'
const ids = arg('ids')?.split(',').map(Number).filter(Boolean)

const e = process.env
const conexion = e.DATABASE_URL ??
  `postgres://${e.DB_USER ?? 'postgres'}:${e.DB_PASS ?? 'postgres'}@${e.DB_HOST ?? '127.0.0.1'}:${e.DB_PORT ?? '5432'}/${e.DB_NAME ?? 'viabus'}`

const db = new pg.Client({ connectionString: conexion })
try {
  await db.connect()
} catch (err) {
  console.error(`No pude conectarme a la base (${err.message}). ¿Está levantado PostgreSQL?`)
  process.exit(1)
}

const { rows } = ids
  ? await db.query(`
      SELECT t.id, t.card_uid, u.email,
             COALESCE(NULLIF(t.nombre_impreso, ''), TRIM(u.nombre || ' ' || u.apellido)) AS nombre
      FROM tarjetas t JOIN usuarios u ON u.id = t.usuario_id
      WHERE t.id = ANY($1) ORDER BY t.id`, [ids])
  : await db.query(`
      SELECT t.id, t.card_uid, u.email,
             COALESCE(NULLIF(t.nombre_impreso, ''), TRIM(u.nombre || ' ' || u.apellido)) AS nombre
      FROM tarjetas t JOIN usuarios u ON u.id = t.usuario_id
      WHERE t.estado = $1 ORDER BY t.id`, [estado])

if (rows.length === 0) {
  console.log(`No hay tarjetas para generar (estado: ${ids ? 'ids indicados' : estado}).`)
  await db.end()
  process.exit(0)
}

mkdirSync(join(SALIDA, 'qr'), { recursive: true })
const plantilla = readFileSync(join(AQUI, 'tarjeta.scad'), 'utf8')
const placas = armarPlacas(rows, QRCode, (m) => console.warn('⚠ ' + m))

// QR sueltos en PNG
for (const t of rows) {
  await QRCode.toFile(
    join(SALIDA, 'qr', `${numeroTarjeta(t.id)}-${nombreArchivo(t.nombre)}.png`),
    t.card_uid, { width: 600, margin: 2, errorCorrectionLevel: 'Q' }
  )
}
writeFileSync(join(SALIDA, 'lista.csv'), listaCsv(rows, placas), 'utf8')

let hayOpenscad = true
for (const placa of placas) {
  const base = join(SALIDA, placa.nombre)
  // El .scad queda autosuficiente: lleva adentro el diseño de tarjeta.scad
  writeFileSync(`${base}.scad`, plantilla + '\n' + placa.scad, 'utf8')
  writeFileSync(`${base}.txt`, placa.lista, 'utf8')

  if (hayOpenscad) {
    try {
      console.log(`Generando ${placa.nombre}.stl (${placa.tarjetas.length} tarjetas)...`)
      execFileSync('openscad', ['-o', `${base}.stl`, `${base}.scad`], { stdio: 'inherit' })
    } catch {
      hayOpenscad = false
      console.warn('⚠ No encontré OpenSCAD: se guardaron los .scad; abrilos con OpenSCAD y exportá el STL (F6 → F7).')
    }
  }
}

if (marcar) {
  const { rowCount } = await db.query(
    `UPDATE tarjetas SET estado = 'impresa', impresa_en = NOW()
     WHERE id = ANY($1) AND estado = 'solicitada'`,
    [rows.map(t => t.id)]
  )
  console.log(`${rowCount} tarjeta(s) marcadas como impresas.`)
}

await db.end()
console.log(`\nListo: ${rows.length} tarjeta(s) en ${placas.length} placa(s) → ${SALIDA}`)
