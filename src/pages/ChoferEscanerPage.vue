<template>
  <q-page class="q-pa-md" style="background:#060c22">
    <q-card class="q-mx-auto" style="max-width:420px">
      <q-card-section style="background:#f4b400;color:#060c22">
        <div class="text-h6">Escáner de abordaje</div>
        <div v-if="cargando">Buscando tu viaje…</div>
        <div v-else-if="servicio">{{ servicio.origen }} → {{ servicio.destino }}</div>
        <div v-else>No tenés un viaje activo ahora</div>
      </q-card-section>

      <q-card-section v-if="servicio">
        <div id="lector" style="width:100%"></div>
        <q-btn v-if="!camaraActiva" label="Activar cámara" color="warning" text-color="dark"
               size="lg" class="full-width" @click="iniciarCamara" />
      </q-card-section>

      <q-card-section v-if="resultado">
        <q-banner :class="resultado.ok ? 'bg-positive' : 'bg-negative'" class="text-white text-h6">
          {{ resultado.mensaje }}
        </q-banner>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'

const servicio = ref(null)
const resultado = ref(null)
const cargando = ref(true)
const camaraActiva = ref(false)
let lector = null
let bloqueado = false

const headers = () => ({
  'Content-Type': 'application/json',
  Authorization: 'Bearer ' + localStorage.getItem('token')
})

onMounted(async () => {
  try {
    const r = await fetch('/api/chofer/servicio-activo.php', { headers: headers() })
    const d = await r.json()
    if (!r.ok) resultado.value = { ok: false, mensaje: d.error }
    else servicio.value = d.servicio
  } catch {
    resultado.value = { ok: false, mensaje: 'Sin conexión' }
  } finally {
    cargando.value = false
  }
})

async function iniciarCamara() {
  lector = new Html5Qrcode('lector')
  await lector.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, alEscanear, () => {})
  camaraActiva.value = true
}

async function alEscanear(texto) {
  if (bloqueado) return
  bloqueado = true
  try {
    const r = await fetch('/api/chofer/abordar.php', {
      method: 'POST',
      headers: headers(),
      body: JSON.stringify({ card_uid: texto, servicio_id: servicio.value.id })
    })
    const d = await r.json()
    resultado.value = r.ok
      ? { ok: true, mensaje: `✔ ${d.nombre} abordó` }
      : { ok: false, mensaje: d.error || d.message }
    navigator.vibrate?.(r.ok ? 200 : [100, 50, 100])
  } catch {
    resultado.value = { ok: false, mensaje: 'Error de conexión' }
  }
  setTimeout(() => { bloqueado = false }, 2500)
}

onBeforeUnmount(async () => {
  if (lector?.isScanning) await lector.stop()
})
</script>
