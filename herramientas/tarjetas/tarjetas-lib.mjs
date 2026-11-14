// Lógica compartida para armar los archivos de impresión 3D de las tarjetas.
// La usan DOS cosas, para que el resultado sea siempre idéntico:
//   - generar-tarjetas.mjs  (script de terminal, guarda los archivos en la compu)
//   - AdminTarjetasPage.vue (botón "Descargar paquete" de la página)
// No importa nada a propósito: la librería `qrcode` se recibe por parámetro,
// así funciona igual en Node y en el navegador.

export const POR_PLACA = 8      // 2 columnas x 4 filas (cama de 256 mm)
export const COLUMNAS = 2
export const SEP = 5            // separación entre tarjetas (mm)
export const ANCHO_TEXTO = 33   // ancho disponible para el nombre (mm)

// Número de tarjeta con ceros: 7 -> "0007"
export const numeroTarjeta = (id) => String(id).padStart(4, '0')

// Nombre en mayúsculas, partido en dos líneas parejas
export function partirNombre(nombre) {
  const limpio = nombre.trim().replace(/\s+/g, ' ').toUpperCase()
  const palabras = limpio.split(' ')
  if (limpio.length <= 12 || palabras.length === 1) return [limpio, '']
  let mejor = 1
  let dif = Infinity
  for (let i = 1; i < palabras.length; i++) {
    const d = Math.abs(palabras.slice(0, i).join(' ').length - palabras.slice(i).join(' ').length)
    if (d < dif) { dif = d; mejor = i }
  }
  return [palabras.slice(0, mejor).join(' '), palabras.slice(mejor).join(' ')]
}

// Letra lo más grande posible (máx. 6 mm) sin salirse de la tarjeta
export function tamanoTexto(l1, l2, avisar = () => {}) {
  const largo = Math.max(l1.length, l2.length)
  const tam = Math.min(6, ANCHO_TEXTO / (largo * 0.68))
  if (tam < 3) avisar(`Nombre muy largo, letra chica: ${l1} ${l2}`)
  return Math.round(Math.max(tam, 3) * 10) / 10
}

// Convierte el QR en filas de cuadraditos oscuros [columna, fila, largo]
export function rectsQR(QRCode, uid) {
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

// Nombre seguro para usar en archivos: sin tildes ni símbolos
export function nombreArchivo(texto) {
  return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase()
}

/**
 * Divide las tarjetas en placas de 8 y arma, para cada una, las líneas de
 * OpenSCAD que las dibujan (sin el encabezado: quien llama agrega el
 * `include` o pega el contenido de tarjeta.scad antes).
 * tarjetas: [{ id, card_uid, nombre }]
 */
export function armarPlacas(tarjetas, QRCode, avisar = () => {}) {
  const placas = []
  for (let i = 0; i < tarjetas.length; i += POR_PLACA) {
    const grupo = tarjetas.slice(i, i + POR_PLACA)
    let scad = ''
    grupo.forEach((t, k) => {
      const [l1, l2] = partirNombre(t.nombre)
      const { n, rects } = rectsQR(QRCode, t.card_uid)
      scad +=
        `translate([${k % COLUMNAS} * (W + ${SEP}), ${Math.floor(k / COLUMNAS)} * (H + ${SEP}), 0]) ` +
        `tarjeta(${JSON.stringify(l1)}, ${JSON.stringify(l2)}, ${tamanoTexto(l1, l2, avisar)}, ` +
        `${JSON.stringify(numeroTarjeta(t.id))}, ${n}, ${JSON.stringify(rects)});\n`
    })
    placas.push({
      nombre: `placa-${String(i / POR_PLACA + 1).padStart(3, '0')}`,
      tarjetas: grupo,
      scad,
      // Lista de quién va en cada bandeja, para la entrega presencial
      lista: grupo.map(t => `N° ${numeroTarjeta(t.id)} - ${t.nombre}`).join('\n')
    })
  }
  return placas
}

// Planilla con todas las tarjetas del lote (se abre con Excel / LibreOffice)
export function listaCsv(tarjetas, placas) {
  const placaDe = new Map()
  placas.forEach(p => p.tarjetas.forEach(t => placaDe.set(t.id, p.nombre)))
  const celda = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
  const filas = tarjetas.map(t =>
    [numeroTarjeta(t.id), t.nombre, t.email ?? '', placaDe.get(t.id) ?? ''].map(celda).join(',')
  )
  return '\ufeff' + ['N°,Nombre,Email,Placa', ...filas].join('\r\n')
}
