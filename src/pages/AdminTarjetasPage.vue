<!-- PANEL DE TARJETAS QR (ruta /tarjetas, solo admin).
     Acá el admin:
       - ve todas las tarjetas separadas por estado (solicitada -> impresa -> entregada -> anulada)
       - cambia el estado A MANO, de a una o varias a la vez (avanzar, retroceder o corregir)
       - descarga los archivos para imprimir en 3D (.zip con placas .scad, QR en .png y la lista)
       - crea tarjetas para gente que no usa la app
     Cada cambio queda guardado en la base de datos (tabla `tarjetas`). -->
<template>
  <MainLayout>

    <div class="superior">
      <div>
        <h1>TARJETAS QR</h1>
        <p>Imprimí, entregá y administrá las tarjetas de abordaje</p>
      </div>
    </div>

    <p v-if="errorGeneral" class="error-msg">{{ errorGeneral }}</p>

    <!-- PESTAÑAS -->
    <nav class="pestanas">
      <button v-for="p in pestanas" :key="p.id" :class="{ activa: tab === p.id }" @click="cambiarTab(p.id)">
        {{ p.titulo }}<span v-if="p.id !== 'crear'" class="cuenta">{{ cantidad(p.id) }}</span>
        <span v-else class="cuenta">{{ sinTarjeta.length }}</span>
      </button>
    </nav>

    <!-- ============ TARJETAS POR ESTADO ============ -->
    <section v-if="tab !== 'crear'" class="panel">

      <p class="ayuda">{{ ayudas[tab] }}</p>

      <div class="barra">
        <label class="check-todos">
          <input type="checkbox" :checked="todasMarcadas" :disabled="!lista.length" @change="marcarTodas($event.target.checked)" />
          Seleccionar todas
        </label>

        <span class="espacio"></span>

        <!-- Pasar las seleccionadas a otro estado (manual) -->
        <select v-model="estadoDestino" :disabled="!seleccion.length">
          <option value="">Pasar seleccionadas a…</option>
          <option v-for="e in ESTADOS" :key="e.id" :value="e.id" :disabled="e.id === tab">{{ e.etiqueta }}</option>
        </select>
        <button class="btn-sec" :disabled="!seleccion.length || !estadoDestino" @click="aplicarEstado(seleccion, estadoDestino)">
          Aplicar
        </button>

        <!-- Archivos para imprimir -->
        <button class="btn-principal" :disabled="!paraDescargar.length || generando" @click="descargarPaquete">
          <q-icon name="download" size="16px" />
          {{ generando ? 'Generando...' : `Descargar paquete 3D (${paraDescargar.length})` }}
        </button>
      </div>

      <label v-if="tab === 'solicitada'" class="opcion">
        <input type="checkbox" v-model="marcarAlDescargar" />
        Pasarlas a «Impresa» después de descargar
      </label>

      <p v-if="cargando" class="vacio">Cargando...</p>
      <p v-else-if="!lista.length" class="vacio">No hay tarjetas en este estado.</p>

      <div v-else class="tabla-scroll">
        <table>
          <thead>
            <tr>
              <th></th><th>N°</th><th>Nombre en la tarjeta</th><th>Email</th>
              <th>Solicitada</th><th>Impresa</th><th>Entregada</th><th>Estado</th><th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in lista" :key="t.id">
              <td><input type="checkbox" :value="t.id" v-model="seleccion" /></td>
              <td class="num">{{ numero(t.id) }}</td>
              <td>
                {{ t.nombre }}
                <button class="icono" title="Editar el nombre impreso" @click="editarNombre(t)">
                  <q-icon name="edit" size="15px" />
                </button>
              </td>
              <td class="suave">{{ t.email }}</td>
              <td class="suave">{{ fecha(t.solicitada_en) }}</td>
              <td class="suave">{{ fecha(t.impresa_en) }}</td>
              <td class="suave">{{ fecha(t.entregada_en) }}</td>
              <td>
                <!-- Cambio manual de estado, de a una -->
                <select :value="t.estado" class="estado" :class="t.estado" @change="aplicarEstado([t.id], $event.target.value, $event)">
                  <option v-for="e in ESTADOS" :key="e.id" :value="e.id">{{ e.etiqueta }}</option>
                </select>
              </td>
              <td>
                <button class="icono" title="Descargar el QR (PNG)" @click="descargarQr(t)">
                  <q-icon name="qr_code_2" size="20px" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ============ CREAR A MANO (gente que no usa la app) ============ -->
    <section v-else class="panel">
      <p class="ayuda">Usuarios registrados que todavía no tienen tarjeta. Se crean en estado «Solicitada».</p>

      <div class="barra">
        <span class="espacio"></span>
        <button class="btn-principal" :disabled="!seleccionCrear.length" @click="crear">
          Crear {{ seleccionCrear.length || '' }} tarjeta(s)
        </button>
      </div>

      <p v-if="!sinTarjeta.length" class="vacio">Todos los usuarios ya tienen tarjeta.</p>
      <div v-else class="tabla-scroll">
        <table>
          <thead><tr><th></th><th>Nombre</th><th>Email</th></tr></thead>
          <tbody>
            <tr v-for="u in sinTarjeta" :key="u.id">
              <td><input type="checkbox" :value="u.id" v-model="seleccionCrear" /></td>
              <td>{{ u.nombre }}</td>
              <td class="suave">{{ u.email }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import MainLayout from '../layouts/MainLayout.vue'
import { llamarApi } from '../services/tarjetasApi'
import { armarPlacas, listaCsv, numeroTarjeta, nombreArchivo } from '../../herramientas/tarjetas/tarjetas-lib.mjs'
// El diseño 3D de la tarjeta (se pega adentro de cada .scad para que el archivo sea autosuficiente)
import plantillaScad from '../../herramientas/tarjetas/tarjeta.scad?raw'

const $q = useQuasar()

const ESTADOS = [
  { id: 'solicitada', etiqueta: 'Solicitada' },
  { id: 'impresa',    etiqueta: 'Impresa (para entregar)' },
  { id: 'entregada',  etiqueta: 'Entregada (activa)' },
  { id: 'anulada',    etiqueta: 'Anulada' }
]
const pestanas = [
  { id: 'solicitada', titulo: 'Por imprimir' },
  { id: 'impresa',    titulo: 'Para entregar' },
  { id: 'entregada',  titulo: 'Activas' },
  { id: 'anulada',    titulo: 'Anuladas' },
  { id: 'crear',      titulo: 'Crear para un usuario' }
]
const ayudas = {
  solicitada: 'Pedidas por los usuarios. Descargá el paquete, imprimilas en 3D y pasalas a «Impresa».',
  impresa: 'Ya impresas, esperando que el usuario las retire en la terminal. Al entregarla, pasala a «Entregada».',
  entregada: 'Tarjetas activas: son las únicas que el escáner del chofer acepta.',
  anulada: 'Tarjetas dadas de baja (perdidas o reemplazadas). Podés reactivarlas si fue un error.'
}

const tab = ref('solicitada')
const tarjetas = ref([])
const sinTarjeta = ref([])
const seleccion = ref([])
const seleccionCrear = ref([])
const estadoDestino = ref('')
const marcarAlDescargar = ref(false)
const cargando = ref(true)
const generando = ref(false)
const errorGeneral = ref('')

const lista = computed(() => tarjetas.value.filter(t => t.estado === tab.value))
const cantidad = (estado) => tarjetas.value.filter(t => t.estado === estado).length
const todasMarcadas = computed(() => lista.value.length > 0 && seleccion.value.length === lista.value.length)
// Si hay tarjetas tildadas se descargan esas; si no, todas las de la pestaña
const paraDescargar = computed(() =>
  seleccion.value.length ? lista.value.filter(t => seleccion.value.includes(t.id)) : lista.value
)

const numero = numeroTarjeta
const fecha = (f) => {
  if (!f) return '—'
  const [a, m, d] = String(f).slice(0, 10).split('-')
  return `${d}/${m}/${a}`
}

function cambiarTab(id) {
  tab.value = id
  seleccion.value = []
  estadoDestino.value = ''
}
function marcarTodas(marcar) {
  seleccion.value = marcar ? lista.value.map(t => t.id) : []
}

async function cargar() {
  try {
    const d = await llamarApi('/api/auth/admin/tarjetas.php')
    tarjetas.value = d.tarjetas
    sinTarjeta.value = d.sin_tarjeta
    errorGeneral.value = ''
  } catch (e) {
    errorGeneral.value = e.message
  } finally {
    cargando.value = false
    seleccion.value = []
    seleccionCrear.value = []
    estadoDestino.value = ''
  }
}

const enviar = (cuerpo) => llamarApi('/api/auth/admin/tarjetas.php', { metodo: 'POST', cuerpo })

// ---- Cambio manual de estado ----
async function cambiarEstado(ids, estado) {
  const d = await enviar({ accion: 'cambiar_estado', ids, estado })
  return d.actualizadas
}

function aplicarEstado(ids, estado, evento) {
  const etiqueta = ESTADOS.find(e => e.id === estado)?.etiqueta
  const ejecutar = async () => {
    try {
      const n = await cambiarEstado(ids, estado)
      $q.notify({ type: 'positive', message: `${n} tarjeta(s) → ${etiqueta}` })
    } catch (e) {
      $q.notify({ type: 'negative', message: e.message })
    }
    await cargar()   // recarga también deja el desplegable de la fila en su valor real
  }
  // Anular es lo más delicado: se pide confirmación
  if (estado === 'anulada') {
    $q.dialog({ title: 'Anular', message: `¿Anular ${ids.length} tarjeta(s)? Dejan de funcionar en el escáner.`, cancel: true, persistent: true })
      .onOk(ejecutar)
      .onCancel(() => { if (evento) cargar() })
  } else {
    ejecutar()
  }
}

function editarNombre(t) {
  $q.dialog({
    title: `Nombre en la tarjeta N° ${numero(t.id)}`,
    message: 'Es lo que se imprime. No cambia el nombre del perfil del usuario.',
    prompt: { model: t.nombre, type: 'text' },
    cancel: true
  }).onOk(async (nombre) => {
    try {
      await enviar({ accion: 'editar_nombre', ids: [t.id], nombre })
      await cargar()
    } catch (e) {
      $q.notify({ type: 'negative', message: e.message })
    }
  })
}

async function crear() {
  try {
    const d = await enviar({ accion: 'crear', ids: seleccionCrear.value })
    $q.notify({ type: 'positive', message: `${d.creadas} tarjeta(s) creada(s)` })
    await cargar()
  } catch (e) {
    $q.notify({ type: 'negative', message: e.message })
  }
}

// ---- Descargas ----
function guardarArchivo(blob, nombre) {
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = nombre
  a.click()
  setTimeout(() => URL.revokeObjectURL(a.href), 1000)
}

// El QR lleva solo el código de la tarjeta (card_uid), igual que lo que lee el escáner
async function descargarQr(t) {
  const QRCode = (await import('qrcode')).default
  const url = await QRCode.toDataURL(t.card_uid, { width: 600, margin: 2, errorCorrectionLevel: 'Q' })
  const blob = await (await fetch(url)).blob()
  guardarArchivo(blob, `${numero(t.id)}-${nombreArchivo(t.nombre)}.png`)
}

// ZIP con: placas .scad (8 tarjetas c/u), lista de entrega, QR sueltos y un LEEME
async function descargarPaquete() {
  generando.value = true
  try {
    const [{ default: JSZip }, { default: QRCode }] = await Promise.all([import('jszip'), import('qrcode')])
    const lote = paraDescargar.value
    const placas = armarPlacas(lote, QRCode, (m) => $q.notify({ type: 'warning', message: m }))

    const zip = new JSZip()
    for (const p of placas) {
      zip.file(`${p.nombre}.scad`, plantillaScad + '\n' + p.scad)
      zip.file(`${p.nombre}.txt`, p.lista)
    }
    zip.file('lista.csv', listaCsv(lote, placas))
    for (const t of lote) {
      const url = await QRCode.toDataURL(t.card_uid, { width: 600, margin: 2, errorCorrectionLevel: 'Q' })
      zip.file(`qr/${numero(t.id)}-${nombreArchivo(t.nombre)}.png`, url.split(',')[1], { base64: true })
    }
    zip.file('LEEME.txt',
      'PAQUETE DE TARJETAS VIABUS\r\n\r\n' +
      '1. Abrí cada placa-XXX.scad con OpenSCAD (gratis, openscad.org).\r\n' +
      '2. Render completo (F6) y exportar STL (F7).\r\n' +
      '3. Cada placa trae 8 tarjetas; placa-XXX.txt dice quién va en cada una.\r\n' +
      '4. lista.csv sirve para la entrega presencial. La carpeta qr/ trae cada QR en PNG.\r\n')

    const blob = await zip.generateAsync({ type: 'blob' })
    guardarArchivo(blob, `tarjetas-${new Date().toISOString().slice(0, 10)}.zip`)

    if (tab.value === 'solicitada' && marcarAlDescargar.value) {
      const n = await cambiarEstado(lote.map(t => t.id), 'impresa')
      $q.notify({ type: 'positive', message: `Paquete descargado. ${n} tarjeta(s) pasaron a «Impresa».` })
      await cargar()
    } else {
      $q.notify({ type: 'positive', message: `Paquete descargado (${lote.length} tarjeta(s)).` })
    }
  } catch (e) {
    $q.notify({ type: 'negative', message: 'No se pudo generar el paquete: ' + e.message })
  } finally {
    generando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.superior {
  padding-bottom: 20px;
  border-bottom: 1px solid #1a2138;
  margin-bottom: 24px;
}
h1 { font-size: 38px; letter-spacing: 3px; color: white; margin: 0; }
.superior p { color: #7b8198; margin-top: 8px; font-size: 13px; letter-spacing: 1px; }
.error-msg { color: #ff6b6b; margin-bottom: 16px; }

.pestanas { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 18px; }
.pestanas button {
  background: transparent; border: 1px solid #1a2138; color: #9aa1b8;
  padding: 10px 18px; border-radius: 999px; cursor: pointer; font-size: 13px; transition: 0.2s;
}
.pestanas button:hover { color: white; border-color: #f4b400; }
.pestanas button.activa { background: #f4b400; border-color: #f4b400; color: #060c22; font-weight: 700; }
.cuenta {
  margin-left: 8px; background: rgba(255, 255, 255, 0.12); border-radius: 999px;
  padding: 1px 8px; font-size: 11px;
}
.pestanas button.activa .cuenta { background: rgba(6, 12, 34, 0.2); }

.panel { background: #0a0f22; border: 1px solid #1a2138; border-radius: 10px; padding: 20px; }
.ayuda { color: #7b8198; font-size: 13px; margin: 0 0 16px; }

.barra { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.espacio { flex: 1; }
.check-todos { color: #9aa1b8; font-size: 13px; display: flex; align-items: center; gap: 8px; }
.opcion { color: #9aa1b8; font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }

select {
  background: #0f1629; border: 1px solid #1a2138; color: white;
  padding: 9px 12px; border-radius: 6px; font-size: 13px;
}
select:disabled { opacity: 0.5; }

.btn-principal {
  background: #f4b400; color: #060c22; border: none; padding: 11px 18px; border-radius: 6px;
  font-weight: 700; font-size: 13px; letter-spacing: 0.5px; cursor: pointer;
  display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;
}
.btn-principal:hover:not(:disabled) { background: #e0a300; }
.btn-sec {
  background: transparent; border: 1px solid #f4b400; color: #f4b400;
  padding: 9px 16px; border-radius: 6px; cursor: pointer; font-size: 13px;
}
.btn-sec:disabled, .btn-principal:disabled { opacity: 0.4; cursor: not-allowed; }

.vacio { color: #7b8198; text-align: center; padding: 28px 0; margin: 0; }

.tabla-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; color: white; }
th {
  text-align: left; color: #7b8198; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase;
  padding: 10px 10px; border-bottom: 1px solid #1a2138; white-space: nowrap;
}
td { padding: 10px; border-bottom: 1px solid #121a30; vertical-align: middle; }
td.num { color: #f4b400; font-weight: 700; font-variant-numeric: tabular-nums; }
td.suave { color: #9aa1b8; white-space: nowrap; }
tr:hover td { background: #0d1429; }

.icono { background: none; border: none; color: #7b8198; cursor: pointer; padding: 4px; vertical-align: middle; }
.icono:hover { color: #f4b400; }

select.estado { font-size: 12px; padding: 6px 8px; }
select.estado.solicitada { border-color: #3b6fb6; }
select.estado.impresa { border-color: #f4b400; }
select.estado.entregada { border-color: #2e7d4f; }
select.estado.anulada { border-color: #8a3a46; color: #ff9a9a; }

input[type='checkbox'] { accent-color: #f4b400; width: 16px; height: 16px; }
</style>
