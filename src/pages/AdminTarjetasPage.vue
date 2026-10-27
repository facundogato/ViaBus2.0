<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Tarjetas QR</div>

    <q-tabs v-model="tab" align="left" active-color="primary">
      <q-tab name="imprimir" :label="`Por imprimir (${porImprimir.length})`" />
      <q-tab name="entregar" :label="`Para entregar (${paraEntregar.length})`" />
      <q-tab name="activas" :label="`Activas (${activas.length})`" />
      <q-tab name="crear" label="Crear para un usuario" />
    </q-tabs>
    <q-separator />

    <q-tab-panels v-model="tab" animated>
      <!-- 1. Solicitadas -->
      <q-tab-panel name="imprimir">
        <div class="row q-gutter-sm q-mb-md">
          <q-btn label="1. Descargar archivo" icon="download" outline
                 :disable="!porImprimir.length" @click="descargar" />
          <q-btn label="2. Ya las imprimí" color="positive"
                 :disable="!descargadas.length" @click="marcarImpresas" />
        </div>
        <q-table :rows="porImprimir" :columns="cols" row-key="id" flat bordered hide-pagination
                 :pagination="{ rowsPerPage: 0 }" no-data-label="No hay solicitudes pendientes" />
      </q-tab-panel>

      <!-- 2. Impresas esperando retiro -->
      <q-tab-panel name="entregar">
        <q-table :rows="paraEntregar" :columns="cols" row-key="id" flat bordered
                 no-data-label="No hay tarjetas esperando retiro">
          <template #body-cell-acciones="p">
            <q-td :props="p">
              <q-btn dense color="primary" label="Entregar" @click="entregar(p.row)" />
            </q-td>
          </template>
        </q-table>
      </q-tab-panel>

      <!-- 3. Entregadas -->
      <q-tab-panel name="activas">
        <q-table :rows="activas" :columns="cols" row-key="id" flat bordered
                 no-data-label="Todavía no hay tarjetas entregadas">
          <template #body-cell-acciones="p">
            <q-td :props="p">
              <q-btn dense flat color="negative" label="Anular" @click="anular(p.row)" />
            </q-td>
          </template>
        </q-table>
      </q-tab-panel>

      <!-- 4. Crear a mano (gente que no usa la app) -->
      <q-tab-panel name="crear">
        <q-table :rows="sinTarjeta" :columns="colsUsuarios" row-key="id"
                 selection="multiple" v-model:selected="seleccionados" flat bordered
                 no-data-label="Todos los usuarios ya tienen tarjeta">
          <template #top-right>
            <q-btn label="Crear tarjetas" color="primary"
                   :disable="!seleccionados.length" @click="crear" />
          </template>
        </q-table>
      </q-tab-panel>
    </q-tab-panels>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const tab = ref('imprimir')
const tarjetas = ref([])
const sinTarjeta = ref([])
const seleccionados = ref([])
const descargadas = ref([])   // ids incluidos en la última descarga

const cols = [
  { name: 'id', label: 'N°', field: 'id', align: 'left', sortable: true },
  { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left', sortable: true },
  { name: 'email', label: 'Email', field: 'email', align: 'left' },
  { name: 'solicitada_en', label: 'Solicitada', field: r => r.solicitada_en?.slice(0, 10), align: 'left' },
  { name: 'acciones', label: '', field: 'id', align: 'right' }
]
const colsUsuarios = [
  { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
  { name: 'email', label: 'Email', field: 'email', align: 'left' }
]

const porImprimir  = computed(() => tarjetas.value.filter(t => t.estado === 'solicitada'))
const paraEntregar = computed(() => tarjetas.value.filter(t => t.estado === 'impresa'))
const activas      = computed(() => tarjetas.value.filter(t => t.estado === 'entregada'))

const headers = () => ({
  'Content-Type': 'application/json',
  Authorization: 'Bearer ' + localStorage.getItem('token')
})

async function cargar() {
  const r = await fetch('/api/admin/tarjetas.php', { headers: headers() })
  const d = await r.json()
  if (!r.ok) return $q.notify({ type: 'negative', message: d.error })
  tarjetas.value = d.tarjetas
  sinTarjeta.value = d.sin_tarjeta
  seleccionados.value = []
}

async function enviar(accion, ids) {
  const r = await fetch('/api/admin/tarjetas.php', {
    method: 'POST', headers: headers(), body: JSON.stringify({ accion, ids })
  })
  const d = await r.json()
  if (!r.ok) throw new Error(d.error)
  return d
}

async function ejecutar(fn, mensaje) {
  try { const d = await fn(); if (mensaje) $q.notify({ type: 'positive', message: mensaje(d) }); await cargar() }
  catch (e) { $q.notify({ type: 'negative', message: e.message }) }
}

function descargar() {
  const lote = porImprimir.value.map(({ id, card_uid, nombre }) => ({ id, card_uid, nombre }))
  descargadas.value = lote.map(t => t.id)
  const blob = new Blob([JSON.stringify(lote, null, 2)], { type: 'application/json' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'tarjetas-pendientes.json'
  a.click()
  URL.revokeObjectURL(a.href)
}

function marcarImpresas() {
  $q.dialog({ title: 'Confirmar', message: `¿Ya imprimiste las ${descargadas.value.length} tarjetas del archivo?`, cancel: true })
    .onOk(() => ejecutar(async () => {
      const d = await enviar('marcar_impresas', descargadas.value)
      descargadas.value = []
      return d
    }, d => `${d.marcadas} tarjeta(s) lista(s) para entregar`))
}

const crear   = () => ejecutar(() => enviar('crear', seleccionados.value.map(u => u.id)), d => `${d.creadas} tarjeta(s) creada(s)`)
const entregar = (t) => ejecutar(() => enviar('entregar', [t.id]), () => `Entregada a ${t.nombre}`)
const anular  = (t) => $q.dialog({ title: 'Anular', message: `¿Anular la tarjeta de ${t.nombre}?`, cancel: true })
  .onOk(() => ejecutar(() => enviar('anular', [t.id]), () => 'Tarjeta anulada'))

onMounted(cargar)
</script>
