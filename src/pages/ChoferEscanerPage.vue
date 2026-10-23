<template>
  <q-page class="q-pa-md" style="background:#060c22">
    <q-card class="q-mx-auto" style="max-width:420px">
      <q-card-section style="background:#f4b400;color:#060c22">
        <div class="text-h6">Escáner de abordaje</div>
        <div v-if="servicio">{{ servicio.origen }} → {{ servicio.destino }}</div>
        <div v-else>No tenés un viaje activo ahora</div>
      </q-card-section>

      <q-card-section>
        <div id="lector" style="width:100%"></div>
        <q-btn
          v-if="!camaraActiva && servicio"
          label="Activar cámara"
          color="warning" text-color="dark"
          class="full-width q-mt-sm"
          @click="iniciarCamara"
        />
      </q-card-section>

      <q-card-section v-if="resultado">
        <q-banner :class="resultado.ok ? 'bg-positive' : 'bg-negative'" class="text-white">
          {{ resultado.mensaje }}
        </q-banner>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onBeforeUnmount, onMounted } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'

const servicio = ref(null)
const resultado = ref(null)
const camaraActiva = ref(false)
let lector = null
let bloqueado = false

onMounted(async () => {
  // Usá el mismo helper/headers de auth que en el resto de tus llamadas
  const r = await fetch('/api/chofer/servicio-activo.php', { credentials: 'include' })
  servicio.value = (await r.json()).servicio
})

async function iniciarCamara() {
  lector = new Html5Qrcode('lector')
  await lector.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: 250 },
    alEscanear,
    () => {}
  )
  camaraActiva.value = true
}

async function alEscanear(texto) {
  if (bloqueado) return
  bloqueado = true                       // evita lecturas repetidas del mismo QR
  try {
    const r = await fetch('/api/chofer/abordar.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ card_uid: texto, servicio_id: servicio.value.id })
    })
    const data = await r.json()
    resultado.value = r.ok
      ? { ok: true, mensaje: `✔ ${data.nombre} abordó` }
      : { ok: false, mensaje: data.message }
    navigator.vibrate?.(r.ok ? 200 : [100, 50, 100])
  } catch {
    resultado.value = { ok: false, mensaje: 'Error de conexión' }
  }
  setTimeout(() => { bloqueado = false }, 2500)
}

onBeforeUnmount(async () => {
  if (lector?.isScanning) await lector.stop()
})

const headers = () => ({
  'Content-Type': 'application/json',
  Authorization: 'Bearer ' + localStorage.getItem('token')
})

// en onMounted:
const r = await fetch('/api/chofer/servicio-activo.php', { headers: headers() })
servicio.value = (await r.json()).servicio

// en alEscanear:
const r = await fetch('/api/chofer/abordar.php', {
  method: 'POST',
  headers: headers(),
  body: JSON.stringify({ card_uid: texto, servicio_id: servicio.value.id })
})
const data = await r.json()
resultado.value = r.ok
  ? { ok: true, mensaje: `✔ ${data.nombre} abordó` }
  : { ok: false, mensaje: data.error || data.message }
</script>
