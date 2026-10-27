import pg from 'pg'
import QRCode from 'qrcode'
import { mkdirSync, writeFileSync } from 'node:fs'
import { execFileSync } from 'node:child_process'

const POR_PLACA = 8   // 2 columnas x 4 filas (cama de 256 mm)
const COLUMNAS = 2
const SEP = 5         // separación entre tarjetas (mm)
const ANCHO_TEXTO = 33
const marcar = process.argv.includes('--marcar')

// Nombre en mayúsculas, partido en dos líneas parejas
function partirNombre(nombre) {
  const limpio = nombre.trim().replace(/\s+/g, ' ').toUpperCase()
  const palabras = limpio.split(' ')
  if (limpio.length <= 12 || palabras.length === 1) return [limpio, '']
  let mejor = 1, dif = Infinity
  for (let i = 1; i < palabras.length; i++) {
    const d = Math.abs(palabras.slice(0, i).join(' ').length - palabras.slice(i).join(' ').length)
    if (d < dif) { dif = d; mejor = i }
  }
  return [palabras.slice(0, mejor).join(' '), palabras.slice(mejor).join(' ')]
}

// Letra lo más grande posible (máx. 6 mm) sin salirse de la tarjeta
function tamanoTexto(l1, l2) {
  const largo = Math.max(l1.length, l2.length)
  const tam = Math.min(6, ANCHO_TEXTO / (largo * 0.68))
  if (tam < 3) console.warn(`⚠ Nombre muy largo, letra chica: ${l1} ${l2}`)
  return Math.round(Math.max(tam, 3) * 10) / 10
}

// Convierte el QR en filas de cuadraditos oscuros [columna, fila, largo]
function rectsQR(uid) {
  const qr = QRCode.create(uid, { errorCorrectionLevel: 'Q' })
  const n = qr.modules.size
  const rects = []
  for (let fila = 0; fila < n; fila++) {
    let inicio = -1
    for (let col = 0; col <= n; col++) {
      const oscuro = col < n && qr.modules.get(fila, col)
      if (oscuro && inicio < 0) inicio = col
      if (!oscuro && inicio >= 0) {
        rects.push([inicio, n - 1 - fila, col - inicio])
        inicio = -1
      }
    }
  }
  return { n, rects }
}

const db = new pg.Client({ connectionString: process.env.DATABASE_URL })
await db.connect()

const { rows } = await db.query(`
  SELECT t.id, t.card_uid, u.nombre
  FROM tarjetas t
  JOIN usuarios u ON u.id = t.usuario_id
  WHERE t.activa AND t.impresa_en IS NULL
  ORDER BY t.id
`)

if (rows.length === 0) console.log('No hay tarjetas pendientes.')
mkdirSync('salida', { recursive: true })

for (let i = 0; i < rows.length; i += POR_PLACA) {
  const grupo = rows.slice(i, i + POR_PLACA)
  const base = `salida/placa-${String(i / POR_PLACA + 1).padStart(3, '0')}`
  let scad = 'include <../tarjeta.scad>;\n'

  grupo.forEach((t, k) => {
    const [l1, l2] = partirNombre(t.nombre)
    const { n, rects } = rectsQR(t.card_uid)
    const numero = String(t.id).padStart(4, '0')
    scad +=
      `translate([${k % COLUMNAS} * (W + ${SEP}), ${Math.floor(k / COLUMNAS)} * (H + ${SEP}), 0]) ` +
      `tarjeta(${JSON.stringify(l1)}, ${JSON.stringify(l2)}, ${tamanoTexto(l1, l2)}, ` +
      `${JSON.stringify(numero)}, ${n}, ${JSON.stringify(rects)});\n`
  })

  writeFileSync(`${base}.scad`, scad, 'utf8')
  // Lista de quién va en cada bandeja, para la entrega presencial
  writeFileSync(
    `${base}.txt`,
    grupo.map(t => `N° ${String(t.id).padStart(4, '0')} - ${t.nombre}`).join('\n'),
    'utf8'
  )

  console.log(`Generando ${base}.stl (${grupo.length} tarjetas)...`)
  execFileSync('openscad', ['-o', `${base}.stl`, `${base}.scad`], { stdio: 'inherit' })

  if (marcar) {
    await db.query('UPDATE tarjetas SET impresa_en = NOW() WHERE id = ANY($1)', [grupo.map(t => t.id)])
  }
}

await db.end()
