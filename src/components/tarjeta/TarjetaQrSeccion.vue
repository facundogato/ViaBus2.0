<template>
  <q-card flat bordered class="q-mt-md">
    <q-card-section>
      <div class="text-h6">Mi tarjeta ViaBus</div>
      <div class="text-body2 text-grey-7">Para subir al bus sin mostrar el pasaje en el celular.</div>
    </q-card-section>

    <q-card-section v-if="cargando"><q-spinner /></q-card-section>

    <q-card-section v-else-if="!tarjeta">
      <q-btn label="Solicitar mi tarjeta" color="primary" icon="credit_card" :loading="enviando" @click="solicitar" />
    </q-card-section>

    <q-card-section v-else>
      <q-banner v-if="tarjeta.estado === 'solicitada'" class="bg-blue-1">
        <template #avatar><q-icon name="hourglass_top" /></template>
        Recibimos tu solicitud. Estamos preparando tu tarjeta.
      </q-banner>
      <q-banner v-else-if="tarjeta.estado === 'impresa'" class="bg-green-1">
        <template #avatar><q-icon name="check_circle" color="positive" /></template>
        ¡Tu tarjeta está lista! Retirala en la terminal con tu documento.
      </q-banner>
      <template v-else>
        <q-banner class="bg-green-1">
          <template #avatar><q-icon name="verified" color="positive" /></template>
          Tu tarjeta está activa. La entregaron el {{ fecha(tarjeta.entregada_en) }}.
        </q-banner>
        <q-btn flat color="negative" label="Perdí mi tarjeta" class="q-mt-sm" @click="perdida" />
      </template>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const tarjeta = ref(null)
const cargando = ref(true)
const enviando = ref(false)

const headers = () => ({
  'Content-Type': 'application/json',
  Authorization: 'Bearer ' + localStorage.getItem('token')
})
const fecha = (f) => new Date(f.replace(' ', 'T')).toLocaleDateString('es-UY')

async function cargar() {
  const r = await fetch('/api/tarjetas/mia.php', { headers: headers() })
  tarjeta.value = (await r.json()).tarjeta
  cargando.value = false
}

async function accion(nombre) {
  const r = await fetch('/api/tarjetas/mia.php', {
    method: 'POST', headers: headers(), body: JSON.stringify({ accion: nombre })
  })
  const d = await r.json()
  if (!r.ok) throw new Error(d.error)
}

async function solicitar() {
  enviando.value = true
  try { await accion('solicitar'); await cargar() }
  catch (e) { $q.notify({ type: 'negative', message: e.message }) }
  finally { enviando.value = false }
}

function perdida() {
  $q.dialog({
    title: 'Anular tarjeta',
    message: 'Tu tarjeta actual dejará de funcionar. ¿Confirmás?',
    cancel: true
  }).onOk(async () => {
    try { await accion('reportar_perdida'); await cargar() }
    catch (e) { $q.notify({ type: 'negative', message: e.message }) }
  })
}

onMounted(cargar)
</script>
