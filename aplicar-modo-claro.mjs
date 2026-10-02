#!/usr/bin/env node
/*
  Reemplaza los colores fijos (#hex) de ViaBus por variables CSS
  (var(--color-...)) para poder tener modo oscuro y modo claro.

  Uso (desde la raíz del proyecto, donde está la carpeta src/):
    node aplicar-modo-claro.mjs --dry-run   -> solo muestra qué cambiaría
    node aplicar-modo-claro.mjs             -> aplica los cambios

  Es seguro correrlo dos veces: lo que ya es var(...) no se vuelve a tocar.
  Cuando termines podés borrar este archivo, no forma parte del proyecto.
*/
import fs from 'node:fs'
import path from 'node:path'

const RAIZ = process.cwd()
const SRC = path.join(RAIZ, 'src')
const DRY = process.argv.includes('--dry-run')

// ---- Familias de colores del tema oscuro actual -------------------------
const PRINCIPAL = new Set(['f4b400', 'fab400'])
const PRINCIPAL_HOVER = new Set(['ffcb2b', 'ffca28'])
const PRINCIPAL_OSCURO = new Set(['e0a300'])
const ERROR = new Set(['ff6b6b', 'ff5b5b'])
const SECUNDARIO = new Set(['7b8198', '858da7', '69718a', '8b92aa', '8b92a8', '707991', '69738c', '68728b'])
const SUAVE = new Set(['b8bfd4', 'c4c8d4', 'c8cce0', 'a8b0c4', '9ca3b8', '9aa4bf', 'e8ecf5', 'ececec', 'e0e0e0'])
const SIDEBAR = new Set(['0b1023', '0c1325', '0a0f22', '0b1528', '0a1028', '07101e', '060c22', '0d1328', '050816'])
const TARJETA = new Set(['0f1629', '101831', '10182c', '0f172c', '111a30', '101a2f', '151d33'])
const BORDE_HOVER = new Set(['1a2138', '252e47', '2a3148', '29324d', '27304c', '242c43', '1d2740', '36415f', '48536f'])
const BLANCO = new Set(['ffffff'])

// En estos archivos el fondo #0b1023 es el de la PÁGINA (no el del sidebar)
const FONDO_PAGINA = new Set(['App.vue', 'MainLayout.vue'])

// Devuelve 'bg', 'border', 'color' u 'other' según la propiedad CSS
function clase(prop) {
  const p = prop.toLowerCase()
  if (p.startsWith('--')) {
    if (/texto|text/.test(p)) return 'color'
    if (/borde|border|line/.test(p)) return 'border'
    return 'bg'
  }
  if (p.startsWith('background') || p.startsWith('fill')) return 'bg'
  if (p.startsWith('border') || p.startsWith('outline')) return 'border'
  if (['color', 'caret-color', 'text-decoration-color', 'stroke'].includes(p)) return 'color'
  return 'other'
}

// Devuelve el var(...) que corresponde a este hex, o null si no se toca
function mapear(h, cls, archivo) {
  if (PRINCIPAL.has(h)) return 'var(--color-principal)'
  if (PRINCIPAL_HOVER.has(h)) return 'var(--color-principal-hover)'
  if (PRINCIPAL_OSCURO.has(h)) return 'var(--color-principal-oscuro)'
  if (ERROR.has(h)) return 'var(--color-error)'
  if (SECUNDARIO.has(h)) return 'var(--color-secundario)'
  if (SUAVE.has(h)) return cls === 'color' ? 'var(--color-texto-suave)' : null
  if (BLANCO.has(h)) return cls === 'color' ? 'var(--color-texto)' : null
  if (SIDEBAR.has(h)) {
    if (cls === 'color') return 'var(--color-texto-sobre-principal)'
    if (cls === 'bg' && h === '0b1023' && FONDO_PAGINA.has(archivo)) return 'var(--color-fondo)'
    return 'var(--color-sidebar)'
  }
  if (TARJETA.has(h)) return 'var(--color-tarjeta)'
  if (BORDE_HOVER.has(h)) return cls === 'bg' ? 'var(--color-hover)' : 'var(--color-borde)'
  return null
}

const HEX = /#([0-9a-fA-F]{6})\b/g
const PROP = /(--[\w-]+|[a-zA-Z-]+)\s*[:=]/g
const RGBA = /rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9.]+)\s*\)/g

function procesar(texto, archivo) {
  let cambios = 0
  let carry = 'color' // propiedad de la línea anterior (por si el valor está en otra línea)

  const lineas = texto.split('\n').map((linea) => {
    // propiedades de esta línea, con la posición donde termina cada una
    const props = [...linea.matchAll(PROP)].map((m) => [m.index + m[0].length, m[1]])

    const nueva = linea.replace(HEX, (completo, hex, pos) => {
      const previas = props.filter(([fin]) => fin <= pos)
      const prop = previas.length ? previas[previas.length - 1][1] : carry
      const reemplazo = mapear(hex.toLowerCase(), clase(prop), archivo)
      if (reemplazo === null) return completo
      cambios++
      return reemplazo
    })

    if (props.length) carry = props[props.length - 1][1]
    return nueva
  })

  const final = lineas.join('\n').replace(RGBA, (completo, r, g, b, a) => {
    if (r === '244' && g === '180' && b === '0') {
      cambios++
      return `rgba(var(--color-principal-rgb), ${a})`
    }
    if (r === '11' && g === '16' && b === '35') {
      cambios++
      return `rgba(var(--color-sidebar-rgb), ${a})`
    }
    return completo
  })

  return [final, cambios]
}

// Edita un archivo si encuentra el texto exacto; si no, avisa
function parchear(ruta, viejo, nuevo, descripcion) {
  const p = path.join(RAIZ, ruta)
  if (!fs.existsSync(p)) {
    console.log(`  [!] No existe ${ruta}: ${descripcion} -> hacelo a mano`)
    return
  }
  const t = fs.readFileSync(p, 'utf-8')
  if (t.includes(nuevo)) {
    console.log(`  [=] ${ruta}: ya estaba (${descripcion})`)
    return
  }
  if (!t.includes(viejo)) {
    console.log(`  [!] ${ruta}: no encontré el punto de inserción (${descripcion}) -> hacelo a mano`)
    return
  }
  if (!DRY) fs.writeFileSync(p, t.replace(viejo, nuevo), 'utf-8')
  console.log(`  [ok] ${ruta}: ${descripcion}`)
}

// Lista todos los .vue y .css dentro de src/
function listar(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const ruta = path.join(dir, e.name)
    if (e.isDirectory()) return listar(ruta)
    return /\.(vue|css)$/.test(e.name) ? [ruta] : []
  })
}

function main() {
  if (!fs.existsSync(SRC)) {
    console.error('Corré este script desde la raíz del proyecto (donde está la carpeta src/).')
    process.exit(1)
  }

  console.log('== Colores -> variables ==')
  const ignorar = new Set(['variables.css', 'global.css', 'app.css']) // no se tocan
  let total = 0
  for (const f of listar(SRC).sort()) {
    const nombre = path.basename(f)
    if (ignorar.has(nombre)) continue
    const original = fs.readFileSync(f, 'utf-8')
    const [nuevo, n] = procesar(original, nombre)
    if (n) {
      console.log(`  ${String(n).padStart(3)} cambios  ${path.relative(RAIZ, f)}`)
      total += n
      if (!DRY) fs.writeFileSync(f, nuevo, 'utf-8')
    }
  }
  console.log(`  TOTAL: ${total} reemplazos${DRY ? ' (simulación, no se escribió nada)' : ''}`)

  console.log('== Conexión del tema ==')
  parchear('quasar.config.js', "'supabase'\n", "'supabase',\n      'tema'\n", "boot: agrega 'tema'")
  parchear(
    'src/layouts/AppSidebar.vue',
    "import LogoutButton from './LogoutButton.vue'\n",
    "import LogoutButton from './LogoutButton.vue'\nimport BotonTema from './BotonTema.vue'\n",
    'import de BotonTema'
  )
  parchear(
    'src/layouts/AppSidebar.vue',
    '    <!-- SPACER -->',
    '    <!-- CAMBIO DE TEMA (oscuro / claro) -->\n    <BotonTema />\n\n    <!-- SPACER -->',
    'agrega <BotonTema /> al sidebar'
  )
}

main()
