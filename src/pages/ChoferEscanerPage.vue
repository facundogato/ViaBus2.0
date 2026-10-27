<template>
  <MainLayout>
  <div class="q-pa-md">
    <q-card class="q-mx-auto" style="max-width:420px">
      <q-card-section style="background:#f4b400;color:#060c22">
        <div class="text-h6">Escáner de abordaje</div>
        <div v-if="cargando">Buscando tu viaje…</div>
        <div v-else-if="servicio">{{ servicio.origen }} → {{ servicio.destino }} · {{ servicio.hora }}</div>
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
  </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import MainLayout from '../layouts/MainLayout.vue'
import { llamarApi } from '../services/tarjetasApi'

const servicio = ref(null)
const resultado = ref(null)
const cargando = ref(true)
const camaraActiva = ref(false)
let lector = null
let bloqueado = false

onMounted(async () => {
  try {
    const d = await llamarApi('/api/chofer/servicio-activo.php')
    servicio.value = d.servicio
  } catch (e) {
    resultado.value = { ok: false, mensaje: e.message }
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
    const d = await llamarApi('/api/chofer/abordar.php', {
      metodo: 'POST',
      cuerpo: { card_uid: texto, servicio_id: servicio.value.id }
    })
    resultado.value = { ok: true, mensaje: `✔ ${d.nombre} abordó` }
    navigator.vibrate?.(200)
  } catch (e) {
    resultado.value = { ok: false, mensaje: e.message }
    navigator.vibrate?.([100, 50, 100])
  }
  setTimeout(() => { bloqueado = false }, 2500)
}

onBeforeUnmount(async () => {
  if (lector?.isScanning) await lector.stop()
})
</script>
