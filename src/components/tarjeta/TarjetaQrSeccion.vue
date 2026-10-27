<!-- Sección "Mi tarjeta ViaBus" del perfil (ruta /perfil).
     Permite solicitar la tarjeta QR con los datos del perfil y seguir su estado:
       solicitada -> impresa (lista para retirar en la terminal) -> entregada (activa)
     El código QR en sí nunca se muestra acá: va solo en la tarjeta física. -->
<template>
  <section class="tarjeta-qr">

    <header class="cabecera">
      <h2>Mi tarjeta <span>ViaBus</span></h2>
      <p>Subí al bus escaneando tu tarjeta, sin mostrar el pasaje en el celular.</p>
    </header>

    <p v-if="cargando" class="aviso">Cargando tu tarjeta...</p>
    <p v-else-if="errorCarga" class="aviso error">{{ errorCarga }}</p>

    <div v-else class="contenido">

      <!-- Así queda la tarjeta impresa (el QR real se genera al imprimirla) -->
      <div class="plastico" :class="{ apagada: !tarjeta }">
        <div class="panel-qr"><q-icon name="qr_code_2" size="76px" /></div>
        <div class="datos">
          <span class="marca">VIABUS</span>
          <span class="nombre">{{ lineasNombre[0] }}</span>
          <span class="nombre">{{ lineasNombre[1] }}</span>
          <span v-if="tarjeta" class="numero">N° {{ numero }}</span>
        </div>
      </div>

      <div class="detalle">

        <!-- SIN TARJETA: solicitar con los datos del perfil -->
        <template v-if="!tarjeta">
          <h3>Solicitá tu tarjeta</h3>
          <ul class="datos-perfil">
            <li><span>Nombre en la tarjeta</span><strong>{{ perfil.nombre || '—' }}</strong></li>
            <li><span>Correo</span><strong>{{ perfil.email || '—' }}</strong></li>
          </ul>
          <p class="nota">
            Se imprime con el nombre de tu perfil. Si no es correcto, corregilo primero en
            «Editar datos». La tarjeta se retira en la terminal con tu documento.
          </p>
          <button class="boton-principal" :disabled="enviando || !perfil.nombre" @click="confirmarSolicitud">
            <q-icon name="credit_card" size="16px" />
            {{ enviando ? 'Solicitando...' : 'Solicitar con estos datos' }}
          </button>
        </template>

        <!-- CON TARJETA: seguimiento -->
        <template v-else>
          <ol class="pasos">
            <li :class="claseDePaso(1)"><span class="punto">1</span>Solicitada</li>
            <li :class="claseDePaso(2)"><span class="punto">2</span>Lista para retirar</li>
            <li :class="claseDePaso(3)"><span class="punto">3</span>Activa</li>
          </ol>

          <p class="mensaje" v-if="tarjeta.estado === 'solicitada'">
            Recibimos tu solicitud el {{ fecha(tarjeta.solicitada_en) }}. Estamos preparando tu tarjeta.
          </p>
          <p class="mensaje ok" v-else-if="tarjeta.estado === 'impresa'">
            ¡Tu tarjeta está lista! Retirala en la terminal con tu documento.
          </p>
          <template v-else>
            <p class="mensaje ok">
              Tu tarjeta está activa. Te la entregaron el {{ fecha(tarjeta.entregada_en) }}.
            </p>
            <button class="boton-peligro" @click="confirmarPerdida">Perdí mi tarjeta</button>
          </template>
        </template>

      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useAuth } from '../../services/auth'
import { llamarApi } from '../../services/tarjetasApi'
import { partirNombre, numeroTarjeta } from '../../../herramientas/tarjetas/tarjetas-lib.mjs'

const $q = useQuasar()
const { usuario } = useAuth()

const tarjeta = ref(null)
const perfil = ref({ nombre: '', email: '' })   // datos del perfil que se imprimen
const cargando = ref(true)
const enviando = ref(false)
const errorCarga = ref('')

// Si ya hay tarjeta se muestra el nombre que quedó guardado; si no, el actual del perfil
const nombreMostrado = computed(() => tarjeta.value?.nombre_impreso || perfil.value.nombre)
const lineasNombre = computed(() => nombreMostrado.value ? partirNombre(nombreMostrado.value) : ['TU NOMBRE', ''])
const numero = computed(() => tarjeta.value ? numeroTarjeta(tarjeta.value.id) : '')

const ORDEN = { solicitada: 1, impresa: 2, entregada: 3 }
function claseDePaso(n) {
  const actual = ORDEN[tarjeta.value?.estado] ?? 0
  return { hecho: n < actual, actual: n === actual }
}

const fecha = (f) => f ? new Date(String(f).replace(' ', 'T')).toLocaleDateString('es-UY') : ''

async function cargar() {
  try {
    const d = await llamarApi('/api/tarjetas/mia.php')
    tarjeta.value = d.tarjeta
    perfil.value = d.perfil
    errorCarga.value = ''
  } catch (e) {
    errorCarga.value = e.message
  } finally {
    cargando.value = false
  }
}

function confirmarSolicitud() {
  $q.dialog({
    title: 'Confirmar solicitud',
    message: `Se va a imprimir una tarjeta a nombre de «${perfil.value.nombre}». ¿Confirmás?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    enviando.value = true
    try {
      await llamarApi('/api/tarjetas/mia.php', { metodo: 'POST', cuerpo: { accion: 'solicitar' } })
      $q.notify({ type: 'positive', message: 'Solicitud enviada' })
      await cargar()
    } catch (e) {
      $q.notify({ type: 'negative', message: e.message })
    } finally {
      enviando.value = false
    }
  })
}

function confirmarPerdida() {
  $q.dialog({
    title: 'Anular tarjeta',
    message: 'Tu tarjeta actual dejará de funcionar. Después podés solicitar una nueva. ¿Confirmás?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await llamarApi('/api/tarjetas/mia.php', { metodo: 'POST', cuerpo: { accion: 'reportar_perdida' } })
      await cargar()
    } catch (e) {
      $q.notify({ type: 'negative', message: e.message })
    }
  })
}

onMounted(cargar)

// Si edita su nombre/correo en el perfil, la vista previa se actualiza
watch(() => [usuario.value?.nombre, usuario.value?.apellido, usuario.value?.email], cargar)
</script>

<style scoped>
.tarjeta-qr {
  background: #0a0f22;
  border: 1px solid #1a2138;
  border-radius: 16px;
  padding: 26px 28px;
  margin-bottom: 28px;
}
.cabecera h2 { font-size: 22px; letter-spacing: 1px; margin: 0 0 4px; color: white; }
.cabecera h2 span { color: #f4b400; }
.cabecera p { color: #7b8198; font-size: 13px; margin: 0 0 22px; }

.aviso { color: #7b8198; margin: 0; }
.aviso.error { color: #ff6b6b; }

.contenido { display: flex; gap: 28px; align-items: center; flex-wrap: wrap; }

/* Réplica de la tarjeta física (85,6 x 54 mm) */
.plastico {
  width: 300px;
  aspect-ratio: 85.6 / 54;
  background: #060c22;
  border: 1px solid #1a2138;
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}
.plastico.apagada { opacity: 0.75; }
.panel-qr {
  width: 100px;
  height: 100px;
  background: #f4b400;
  color: #060c22;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.datos { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.marca { color: #f4b400; font-weight: 800; letter-spacing: 2px; font-size: 15px; margin-bottom: 6px; }
.nombre { color: #f4b400; font-weight: 700; font-size: 12px; line-height: 1.25; word-break: break-word; }
.numero { color: #f4b400; font-size: 10px; margin-top: 8px; }

.detalle { flex: 1; min-width: 240px; }
.detalle h3 { margin: 0 0 12px; font-size: 16px; letter-spacing: 1px; color: white; }

.datos-perfil { list-style: none; padding: 0; margin: 0 0 12px; }
.datos-perfil li {
  display: flex; justify-content: space-between; gap: 16px;
  padding: 9px 0; border-bottom: 1px solid #1a2138; font-size: 14px;
}
.datos-perfil span { color: #7b8198; }
.datos-perfil strong { color: white; text-align: right; }
.nota { color: #7b8198; font-size: 12.5px; line-height: 1.5; margin: 0 0 14px; }

.boton-principal {
  display: inline-flex; align-items: center; gap: 8px;
  background: #f4b400; border: none; color: #060c22;
  padding: 12px 22px; border-radius: 999px; cursor: pointer;
  font-size: 13px; font-weight: 700; letter-spacing: 1px; transition: 0.2s;
}
.boton-principal:hover:not(:disabled) { background: #e0a300; }
.boton-principal:disabled { opacity: 0.45; cursor: not-allowed; }
.boton-peligro {
  background: transparent; border: 1px solid #5c2a35; color: #ff6b6b;
  padding: 9px 18px; border-radius: 999px; cursor: pointer; font-size: 12.5px; transition: 0.2s;
}
.boton-peligro:hover { background: #2a1219; }

/* Pasos de seguimiento */
.pasos { list-style: none; display: flex; gap: 8px; padding: 0; margin: 0 0 16px; flex-wrap: wrap; }
.pasos li {
  display: flex; align-items: center; gap: 8px;
  color: #7b8198; font-size: 12.5px;
  background: #0f1629; border: 1px solid #1a2138; border-radius: 999px; padding: 6px 14px 6px 6px;
}
.punto {
  width: 22px; height: 22px; border-radius: 50%; background: #1a2138; color: #9aa1b8;
  display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;
}
.pasos li.hecho { color: #9aa1b8; }
.pasos li.hecho .punto { background: #2e7d4f; color: white; }
.pasos li.actual { color: white; border-color: #f4b400; }
.pasos li.actual .punto { background: #f4b400; color: #060c22; }

.mensaje { color: #cfd4e6; font-size: 14px; margin: 0 0 12px; line-height: 1.5; }
.mensaje.ok { color: #6fd39a; }

@media (max-width: 640px) {
  .tarjeta-qr { padding: 20px 16px; }
  .plastico { width: 100%; max-width: 320px; }
}
</style>
