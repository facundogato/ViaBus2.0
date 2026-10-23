<!-- Página de reserva de pasajes (ruta /comprar) -->
<template>

  <MainLayout>

    <!-- ENCABEZADO -->
    <div class="superior">
      <div class="superior-contenido">

        <div class="superior-etiqueta">
          <span></span>
          RESERVAS
        </div>

        <h1>Reserva tu pasaje</h1>

        <p>
          Elegí tu destino, servicio y asiento para completar tu reserva.
        </p>

      </div>
    </div>

    <!-- STEPPER -->
    <div class="stepper">
      <div
        v-for="(nombrePaso, i) in pasos"
        :key="i"
        class="paso"
        :class="{ activo: pasoActual === i, completado: pasoActual > i }"
      >
        <span class="numero">{{ i + 1 }}</span>
        <span class="label">{{ nombrePaso }}</span>
        <span v-if="i < pasos.length - 1" class="linea" />
      </div>
    </div>

    <!-- PASO 1: BÚSQUEDA -->
    <div v-if="pasoActual === 0" class="paso-content">

      <h2>BUSCAR SERVICIOS</h2>

      <div class="formulario">

        <div class="campo">
          <label>ORIGEN</label>
          <select v-model="busqueda.origen" disabled>
            <option value="Melo">Melo</option>
          </select>
        </div>

        <div class="campo">
          <label>DESTINO</label>
          <select v-model="busqueda.destino">
            <option v-for="c in ciudades" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>

        <div class="campo">
          <label>FECHA DE VIAJE</label>
          <input type="date" v-model="busqueda.fecha" />
        </div>

        <button class="btn-buscar" :disabled="buscando" @click="buscar">
          <q-icon name="search" size="18px" />
          {{ buscando ? 'BUSCANDO...' : 'BUSCAR' }}
        </button>

      </div>

      <p v-if="errorCiudades || errorBusqueda" class="error-msg">
        {{ errorCiudades || errorBusqueda }}
      </p>

    </div>

    <!-- PASO 2: SERVICIOS -->
    <div v-if="pasoActual === 1" class="paso-content">

      <h2>SELECCIONAR SERVICIO</h2>

      <p v-if="!buscando && serviciosFiltrados.length === 0" class="sin-resultados">
        No se encontraron servicios para ese origen y destino.
      </p>

      <div class="resultados">

        <div
          v-for="s in serviciosFiltrados"
          :key="s.id"
          class="resultado-fila"
          :class="{ seleccionado: servicioSeleccionado?.id === s.id }"
          @click="servicioSeleccionado = s"
          @dblclick="seleccionarYBajarAlBoton(s)"
        >
          <div class="res-hora">{{ s.hora }}</div>
          <div class="res-info">
            <span class="res-empresa">{{ s.empresa }}</span>
            <span class="res-tipo">{{ s.tipo }}</span>
          </div>
          <div class="res-duracion">{{ s.duracion }}</div>
          <div class="res-precio">${{ s.precio }}</div>
          <div class="res-estado">
            <span class="badge-disponible">{{ s.asientos }} asientos</span>
          </div>
        </div>

      </div>

      <div class="acciones">
        <button class="btn-volver" @click="subirAlPaso(0)">Volver</button>
        <button
          ref="botonContinuarServicio"
          class="btn-siguiente"
          :disabled="!servicioSeleccionado"
          @click="subirAlPaso(2)"
        >
          Continuar
        </button>
      </div>

    </div>

    <!-- PASO 3: ASIENTOS -->
    <div v-if="pasoActual === 2" class="paso-content">

      <h2>SELECCIONAR ASIENTO</h2>

      <p v-if="cargandoAsientos" class="sin-resultados">
        Cargando disponibilidad de asientos...
      </p>

      <div class="asientos-grid">
        <div
          v-for="asiento in asientos"
          :key="asiento.numero"
          class="asiento"
          :class="{
            ocupado: asiento.ocupado,
            seleccionado: asientoSeleccionado === asiento.numero
          }"
          @click="seleccionarAsiento(asiento)"
          @dblclick="seleccionarAsientoYBajarAlBoton(asiento)"
        >
          {{ asiento.numero }}
        </div>
      </div>

      <div class="acciones">
        <button class="btn-volver" @click="subirAlPaso(1)">Volver</button>
        <button
          ref="botonContinuarAsiento"
          class="btn-siguiente"
          :disabled="!asientoSeleccionado"
          @click="subirAlPaso(3)"
        >
          Continuar
        </button>
      </div>

    </div>

    <!-- PASO 4: DATOS DEL PASAJERO -->
    <div v-if="pasoActual === 3" class="paso-content">

      <h2>DATOS DEL PASAJERO</h2>

      <div class="formulario">

        <div class="campo">
          <label>NOMBRE</label>
          <input type="text" v-model="pasajero.nombre" />
        </div>

        <div class="campo">
          <label>APELLIDO</label>
          <input type="text" v-model="pasajero.apellido" />
        </div>

        <div class="campo">
          <label>DOCUMENTO (CI)</label>
          <input type="text" v-model="pasajero.documento" />
        </div>

        <div class="campo">
          <label>EMAIL</label>
          <input type="email" v-model="pasajero.email" />
        </div>

        <div class="campo">
          <label>TELÉFONO</label>
          <input type="text" v-model="pasajero.telefono" />
        </div>

      </div>

      <p v-if="errorPasajero" class="error-msg">{{ errorPasajero }}</p>

      <div class="acciones">
        <button class="btn-volver" @click="subirAlPaso(2)">Volver</button>
        <button
          class="btn-siguiente"
          :disabled="!pasajeroValido"
          @click="subirAlPaso(4)"
        >
          Continuar
        </button>
      </div>

    </div>

    <!-- PASO 5: PAGO -->
    <div v-if="pasoActual === 4" class="paso-content">

      <PaymentStep
        :resumen="resumenPago"
        :fase="fasePago"
        :mensaje-error="mensajeErrorPago"
        @volver="subirAlPaso(3)"
        @pagar="iniciarPago"
        @reintentar="iniciarPago"
      />

      <!-- Botón de demo: crea la reserva real, la confirma y avanza -->
      <div v-if="debugMode" class="contenedor-debug">
  <button
    class="btn-debug"
    :disabled="fasePago === 'procesando'"
    @click="avanzarDemo"
  >
    ⏩ Avanzar a Confirmación
  </button>
</div>

    </div>

    <!-- PASO 6: CONFIRMACIÓN -->
    <div v-if="pasoActual === 5" class="paso-content">

      <div class="confirmacion">

        <q-icon name="check_circle" size="56px" color="positive" />
        <h2>¡Reserva confirmada!</h2>
        <p class="numero-reserva">Reserva N° {{ reservaCreada?.id }}</p>

        <div class="ticket">
          <div class="ticket-fila">
            <span>Ruta</span>
            <strong>{{ resumenPago.origen || 'Melo' }} → {{ resumenPago.destino }}</strong>
          </div>
          <div class="ticket-fila">
            <span>Fecha</span>
            <strong>{{ resumenPago.fecha }}</strong>
          </div>
          <div class="ticket-fila">
            <span>Hora</span>
            <strong>{{ resumenPago.hora }}</strong>
          </div>
          <div class="ticket-fila">
            <span>Empresa</span>
            <strong>{{ resumenPago.empresa }}</strong>
          </div>
          <div class="ticket-fila">
            <span>Asiento</span>
            <strong>{{ resumenPago.asiento }}</strong>
          </div>
          <div class="ticket-fila">
            <span>Pasajero</span>
            <strong>{{ pasajero.nombre }} {{ pasajero.apellido }}</strong>
          </div>
          <div class="ticket-fila total">
            <span>Total</span>
            <strong>${{ reservaCreada?.total }}</strong>
          </div>
        </div>

        <canvas ref="qrCanvas" class="qr-canvas" />

        <div class="botones-confirmacion">
          <button class="btn-secundario" @click="descargarQR">Descargar QR</button>
          <button class="btn-secundario" @click="reenviarCorreo">Reenviar QR al correo</button>
          <button class="btn-siguiente" @click="reiniciar">Hacer otra reserva</button>
        </div>

      </div>

    </div>

  </MainLayout>

</template>

<script setup>

import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import QRcode from 'qrcode'
import { useRoute, useRouter } from 'vue-router'

import MainLayout from '../layouts/MainLayout.vue'
import PaymentStep from '../components/reservation/PaymentStep.vue'
import { useAuth } from '../services/auth'
import {
  getCiudades,
  buscarServicios,
  getAsientosOcupados,
  crearReserva,
  crearPreferenciaPago,
  getEstadoPago,
  confirmarReservaPagada
} from '../services/api'

// ---------------------------------------------------------
// CONFIGURACIÓN
// ---------------------------------------------------------

// 'provisorio'  = confirma la reserva sin cobrar (demo / presentación)
// 'mercadopago' = flujo real de pago (cuando esté configurado)
const MODO_PAGO = 'provisorio'

// true = muestra el botón "Avanzar a Confirmación" (demo)
// false = lo oculta (producción)
const debugMode = ref(true)

// ---------------------------------------------------------
// ESTADO GENERAL
// ---------------------------------------------------------

const { usuario, isAuthenticated } = useAuth()
const route = useRoute()
const router = useRouter()
const $q = useQuasar()

const pasos = ['Búsqueda', 'Servicio', 'Asiento', 'Pasajero', 'Pago', 'Confirmación']
const pasoActual = ref(0)

function subirAlPaso(paso) {
  pasoActual.value = paso
}

// ---------------------------------------------------------
// RESERVA EN PROCESO (sessionStorage)
// ---------------------------------------------------------

const STORAGE_KEY = 'reserva_en_proceso'

function guardarReservaEnProceso() {
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
    pasoActual: pasoActual.value,
    busqueda: busqueda.value,
    servicioSeleccionado: servicioSeleccionado.value,
    asientoSeleccionado: asientoSeleccionado.value,
    pasajero: pasajero.value,
    reservaCreada: reservaCreada.value
  }))
}

function recuperarReservaEnProceso() {
  const guardado = sessionStorage.getItem(STORAGE_KEY)
  if (!guardado) return

  try {
    const estado = JSON.parse(guardado)

    if (estado.busqueda) busqueda.value = estado.busqueda
    if (estado.servicioSeleccionado) servicioSeleccionado.value = estado.servicioSeleccionado
    if (estado.asientoSeleccionado != null) asientoSeleccionado.value = estado.asientoSeleccionado
    if (estado.pasajero) pasajero.value = estado.pasajero
    if (estado.reservaCreada) reservaCreada.value = estado.reservaCreada

    // Nunca restauramos estados transitorios de pago: siempre arrancamos en "resumen"
    fasePago.value = 'resumen'
    mensajeErrorPago.value = ''

    if (typeof estado.pasoActual === 'number') {
      pasoActual.value = estado.pasoActual
    }
  } catch (e) {
    console.error('Error recuperando reserva:', e)
    sessionStorage.removeItem(STORAGE_KEY)
  }
}

// ---------------------------------------------------------
// CIUDADES
// ---------------------------------------------------------

const ciudades = ref([])
const errorCiudades = ref('')

async function cargarCiudades() {
  try {
    const data = await getCiudades()
    ciudades.value = data.map(c => c.nombre)
  } catch (e) {
    errorCiudades.value = e.message
  }
}

// ---------------------------------------------------------
// BÚSQUEDA DE SERVICIOS
// ---------------------------------------------------------

const busqueda = ref({
  origen: 'Melo',
  destino: 'Montevideo',
  fecha: new Date().toISOString().slice(0, 10)
})

const serviciosFiltrados = ref([])
const servicioSeleccionado = ref(null)
const buscando = ref(false)
const errorBusqueda = ref('')
const botonContinuarServicio = ref(null)

async function buscar() {
  servicioSeleccionado.value = null
  buscando.value = true
  errorBusqueda.value = ''

  try {
    serviciosFiltrados.value = await buscarServicios(busqueda.value.destino)
    pasoActual.value = 1
  } catch (e) {
    errorBusqueda.value = e.message
  } finally {
    buscando.value = false
  }
}

function seleccionarYBajarAlBoton(servicio) {
  servicioSeleccionado.value = servicio
  nextTick(() => {
    botonContinuarServicio.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })
}

// ---------------------------------------------------------
// ASIENTOS
// ---------------------------------------------------------

const asientoSeleccionado = ref(null)
const asientosOcupados = ref([])
const cargandoAsientos = ref(false)
const restaurandoReserva = ref(false)
const botonContinuarAsiento = ref(null)

const asientos = computed(() => {
  const total = Number(servicioSeleccionado.value?.asientos) || 40
  return Array.from({ length: total }, (_, i) => {
    const numero = i + 1
    return { numero, ocupado: asientosOcupados.value.includes(numero) }
  })
})

async function cargarAsientosOcupados() {
  if (!servicioSeleccionado.value) return
  cargandoAsientos.value = true
  try {
    const data = await getAsientosOcupados(servicioSeleccionado.value.id, busqueda.value.fecha)
    asientosOcupados.value = data.asientos_ocupados || []
  } catch {
    asientosOcupados.value = []
  } finally {
    cargandoAsientos.value = false
  }
}

function seleccionarAsiento(asiento) {
  if (asiento.ocupado) return
  asientoSeleccionado.value = asiento.numero
}

function seleccionarAsientoYBajarAlBoton(asiento) {
  if (asiento.ocupado) return
  asientoSeleccionado.value = asiento.numero
  nextTick(() => {
    botonContinuarAsiento.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })
}

watch(pasoActual, (nuevo) => {
  if (nuevo === 2) {
    if (!restaurandoReserva.value) asientoSeleccionado.value = null
    cargarAsientosOcupados()
  }
})

watch(pasoActual, async () => {
  await nextTick()
  document.querySelector('.superior')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
})

// ---------------------------------------------------------
// DATOS DEL PASAJERO
// ---------------------------------------------------------

const pasajero = ref({
  nombre: '',
  apellido: '',
  documento: '',
  email: '',
  telefono: ''
})

const errorPasajero = ref('')

const pasajeroValido = computed(() =>
  pasajero.value.nombre.trim() &&
  pasajero.value.apellido.trim() &&
  pasajero.value.documento.trim()
)

// ---------------------------------------------------------
// PAGO
// ---------------------------------------------------------

const reservaCreada = ref(null)
const fasePago = ref('resumen')
const mensajeErrorPago = ref('')

const resumenPago = computed(() => ({
  origen: servicioSeleccionado.value?.origen || 'Melo',
  destino: servicioSeleccionado.value?.destino,
  fecha: busqueda.value.fecha,
  hora: servicioSeleccionado.value?.hora,
  empresa: servicioSeleccionado.value?.empresa,
  asiento: asientoSeleccionado.value,
  total: reservaCreada.value?.total ?? servicioSeleccionado.value?.precio
}))

// Si el usuario llega al pago sin estar logueado, guardamos y mandamos al login
watch(pasoActual, (nuevo) => {
  if (nuevo === 4 && !isAuthenticated.value) {
    guardarReservaEnProceso()
    router.push({ path: '/login', query: { redirect: '/comprar' } })
  }
})

async function iniciarPago() {
  if (!servicioSeleccionado.value || !asientoSeleccionado.value) return

  fasePago.value = 'procesando'
  mensajeErrorPago.value = ''

  try {
    // 1. Crear la reserva real en la base (solo si todavía no existe)
    if (!reservaCreada.value) {
      reservaCreada.value = await crearReserva({
        servicio_id: servicioSeleccionado.value.id,
        fecha_viaje: busqueda.value.fecha,
        asiento: asientoSeleccionado.value,
        total: servicioSeleccionado.value.precio,
        pasajero: pasajero.value,
        usuario_id: usuario.value?.id
      })
    }

    // 2. Modo provisorio: confirmar directamente (con una pausa para que se vea el proceso)
    if (MODO_PAGO === 'provisorio') {
      await new Promise(resolve => setTimeout(resolve, 2500))
      await confirmarReservaPagada(reservaCreada.value.id)
      fasePago.value = 'resumen'
      pasoActual.value = 5
      return
    }

    // 3. Modo Mercado Pago: ir al checkout
    const preferencia = await crearPreferenciaPago(reservaCreada.value.id)
    window.location.href = preferencia.init_point

  } catch (e) {
    fasePago.value = 'error'
    mensajeErrorPago.value = e.message
  }
}

// Botón de demo: hace lo mismo que el pago real (crear reserva y confirmarla),
// pero sin Mercado Pago. Si algo falla, muestra el motivo en pantalla.
async function avanzarDemo() {
  if (!servicioSeleccionado.value) {
    $q.notify({ type: 'warning', message: 'Falta elegir el servicio. Volvé al paso 2.' })
    return
  }
  if (!asientoSeleccionado.value) {
    $q.notify({ type: 'warning', message: 'Falta elegir el asiento. Volvé al paso 3.' })
    return
  }
  if (!pasajeroValido.value) {
    $q.notify({ type: 'warning', message: 'Faltan datos del pasajero. Volvé al paso 4.' })
    return
  }
  if (!usuario.value?.id) {
    $q.notify({ type: 'negative', message: 'Tenés que iniciar sesión para reservar.' })
    return
  }

  fasePago.value = 'procesando'
  mensajeErrorPago.value = ''

  try {
    // 1. Crear la reserva real con tu usuario_id (si todavía no existe)
    if (!reservaCreada.value) {
      reservaCreada.value = await crearReserva({
        servicio_id: servicioSeleccionado.value.id,
        fecha_viaje: busqueda.value.fecha,
        asiento: asientoSeleccionado.value,
        total: servicioSeleccionado.value.precio,
        pasajero: pasajero.value,
        usuario_id: usuario.value.id
      })
    }

    // 2. Simular el tiempo de procesamiento del pago
    await new Promise(resolve => setTimeout(resolve, 2000))

    // 3. Marcar la reserva como confirmada y pagada
    await confirmarReservaPagada(reservaCreada.value.id)

    // 4. Ir a la confirmación (genera el QR con el id real)
    fasePago.value = 'resumen'
    subirAlPaso(5)

  } catch (e) {
    console.error('Error en avanzarDemo:', e)
    fasePago.value = 'error'
    mensajeErrorPago.value = e.message
    $q.notify({ type: 'negative', message: 'No se pudo confirmar: ' + e.message })
  }
}

// Vuelta desde Mercado Pago (solo se usa en modo mercadopago)
async function verificarPago(reservaId) {
  pasoActual.value = 4
  fasePago.value = 'verificando'

  try {
    const pago = await getEstadoPago(reservaId)
    reservaCreada.value = { id: reservaId, total: pago.monto }

    if (pago.estado === 'pagado') {
      pasoActual.value = 5
    } else if (pago.estado === 'rechazado' || pago.estado === 'cancelado') {
      fasePago.value = 'rechazado'
    } else {
      fasePago.value = 'pendiente'
    }
  } catch (e) {
    fasePago.value = 'error'
    mensajeErrorPago.value = e.message
  }
}

// ---------------------------------------------------------
// QR
// ---------------------------------------------------------

const qrCanvas = ref(null)

watch(
  [pasoActual, reservaCreada],
  async ([nuevoPaso, reserva]) => {
    if (nuevoPaso !== 5 || !reserva || !servicioSeleccionado.value) return

    await nextTick()
    if (!qrCanvas.value) return

    try {
      const texto =
        `Reserva N° ${reserva.id} - ` +
        `${resumenPago.value.origen} → ${servicioSeleccionado.value.destino}, ` +
        `${busqueda.value.fecha}, ` +
        `asiento ${asientoSeleccionado.value}, ` +
        `total $${reserva.total}`

      await QRcode.toCanvas(qrCanvas.value, texto, { width: 200 })

      const qrBase64 = qrCanvas.value.toDataURL('image/png')
      await enviarQRPorCorreo(reserva.id, pasajero.value.email, qrBase64)
    } catch (err) {
      console.error('Error generando QR:', err)
    }
  }
)

async function enviarQRPorCorreo(reservaId, email, qrBase64) {
  if (!email) {
    $q?.notify?.({ type: 'warning', message: 'No hay email registrado para enviar el QR.' })
    return false
  }

  try {
    const response = await fetch(`/api/reservas/enviar-qr.php?reserva_id=${reservaId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, qrBase64 })
    })

    if (!response.ok) {
      throw new Error((await response.text()) || 'Error del servidor')
    }

    $q?.notify?.({ type: 'positive', message: '✅ QR enviado a tu correo electrónico.' })
    return true
  } catch (error) {
    console.error('Error enviando QR por correo:', error)
    $q?.notify?.({
      type: 'negative',
      message: '❌ No se pudo enviar el QR. Puedes descargarlo manualmente.'
    })
    return false
  }
}

function descargarQR() {
  if (!qrCanvas.value) return
  const link = document.createElement('a')
  link.download = `reserva-${reservaCreada.value?.id || 'qr'}.png`
  link.href = qrCanvas.value.toDataURL('image/png')
  link.click()
}

async function reenviarCorreo() {
  if (!reservaCreada.value || !qrCanvas.value) return
  const qrBase64 = qrCanvas.value.toDataURL('image/png')
  await enviarQRPorCorreo(reservaCreada.value.id, pasajero.value.email, qrBase64)
}

// ---------------------------------------------------------
// REINICIAR
// ---------------------------------------------------------

function reiniciar() {
  sessionStorage.removeItem(STORAGE_KEY)

  pasoActual.value = 0
  servicioSeleccionado.value = null
  asientoSeleccionado.value = null
  asientosOcupados.value = []
  reservaCreada.value = null
  fasePago.value = 'resumen'
  mensajeErrorPago.value = ''
  pasajero.value = { nombre: '', apellido: '', documento: '', email: '', telefono: '' }
}

// ---------------------------------------------------------
// GUARDADO AUTOMÁTICO
// ---------------------------------------------------------

watch(
  [pasoActual, busqueda, servicioSeleccionado, asientoSeleccionado, pasajero, reservaCreada],
  () => guardarReservaEnProceso(),
  { deep: true }
)

// ---------------------------------------------------------
// INICIALIZACIÓN
// ---------------------------------------------------------

onMounted(() => {
  cargarCiudades()

  restaurandoReserva.value = true
  recuperarReservaEnProceso()
  restaurandoReserva.value = false

  // Volvimos desde Mercado Pago: verificamos el estado real
  if (route.query.reserva_id) {
    verificarPago(Number(route.query.reserva_id))
    router.replace({ path: '/comprar' })
  }
})

</script>

<style scoped>

/* ENCABEZADO */

.superior {
  padding: 10px 0 30px;
  margin-bottom: 32px;
  border-bottom: 1px solid #1a2138;
}

.superior-contenido {
  max-width: 850px;
}

.superior-etiqueta {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #f4b400;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 3px;
  margin-bottom: 12px;
}

.superior-etiqueta span {
  width: 22px;
  height: 2px;
  background: #f4b400;
  display: block;
}

.superior h1 {
  margin: 0;
  color: #ffffff;
  font-size: clamp(38px, 5vw, 58px);
  line-height: 1.05;
  font-weight: 700;
  letter-spacing: -1.5px;
}

.superior p {
  margin: 14px 0 0;
  color: var(--color-texto-suave);
  font-size: 20px;
  line-height: 1.6;
  max-width: 650px;
}

/* STEPPER */

.stepper {
  display: flex;
  align-items: center;
  width: 100%;
  margin-bottom: 42px;
  overflow-x: auto;
  scrollbar-width: none;
}

.stepper::-webkit-scrollbar {
  display: none;
}

.paso {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.numero {
  width: 34px;
  height: 34px;
  border: 1px solid #29324d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-texto-suave);
  background: var(--color-primary);
  font-size: 17px;
  font-weight: 700;
  transition: all 0.25s ease;
}

.paso.activo .numero {
  background: var(--color-principal-oscuro);
  border-color: var(--color-principal);
  color: var(--color-texto-sobre-principal);
  box-shadow: 0 0 0 5px rgba(244, 180, 0, 0.08);
}

.paso.completado .numero {
  background: var(--color-success);
  border-color: var(--color-success);
  color: var(--color-texto-sobre-success);
}

.label {
  color: var(--color-texto-suave);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 1.2px;
  margin-left: 9px;
  white-space: nowrap;
}

.paso.activo .label {
  color: #ffffff;
}

.paso.completado .label {
  color: #a8b0c4;
}

.linea {
  width: 55px;
  height: 1px;
  background: #242c43;
  margin: 0 14px;
  display: block;
}

/* CONTENIDO DE PASOS */

.paso-content h2 {
  color: #ffffff;
  font-size: 27px;
  font-weight: 650;
  letter-spacing: -0.3px;
  margin: 0 0 24px;
}

.formulario {
  display: flex;
  gap: 15px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.campo label {
  color: var(--color-texto-suave);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.8px;
}

.campo select,
.campo input {
  width: 100%;
  min-width: 200px;
  box-sizing: border-box;
  background: var(--color-sidebar);
  border: 1px solid #252e47;
  color: #ffffff;
  padding: 13px 15px;
  border-radius: 7px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.campo select:focus,
.campo input:focus {
  border-color: #f4b400;
  box-shadow: 0 0 0 3px rgba(244, 180, 0, 0.08);
}

.btn-buscar {
  min-height: 45px;
  background: var(--color-principal);
  color: var(--color-texto-sobre-principal);
  border: none;
  border-radius: 7px;
  padding: 0 28px;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: transform 0.2s ease, background 0.2s ease;
}

.btn-buscar:hover {
  background: var(--color-principal-hover);
  transform: translateY(-1px);
}

.btn-buscar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* RESULTADOS */

.resultados {
  display: flex;
  flex-direction: column;
  border: 1px solid #1a2138;
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 22px;
  background: var(--color-tarjeta);
}

.resultado-fila {
  display: grid;
  grid-template-columns: 90px minmax(150px, 1.5fr) 1fr 120px 130px;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #1a2138;
  cursor: pointer;
  transition: background 0.2s ease;
}

.resultado-fila:last-child {
  border-bottom: none;
}

.resultado-fila:hover {
  background: var(--color-sidebar);
}

.resultado-fila.seleccionado {
  background: var(--color-sidebar);
  box-shadow: inset 3px 0 0 var(--color-principal);
}

.res-hora {
  color: #f4b400;
  font-size: 23px;
  font-weight: 700;
}

.res-empresa {
  display: block;
  color: #ffffff;
  font-size: 14px;
  font-weight: 650;
}

.res-tipo {
  display: block;
  color: var(--color-texto-suave);
  font-size: 11px;
  margin-top: 4px;
  letter-spacing: 0.8px;
}

.res-duracion {
  color: var(--color-texto-suave);
  font-size: 13px;
}

.res-precio {
  color: #00e6bd;
  font-size: 19px;
  font-weight: 700;
}

.badge-disponible {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  background: var(--color-texto-sobre-principal);
  color: #00e6a0;
  padding: 6px 10px;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
  border: 1px solid rgba(0, 255, 153, 0.35);
}

/* ASIENTOS */

.asientos-grid {
  display: grid;
  grid-template-columns: 70px 70px 30px 70px 70px;
  gap: 15px;
  justify-content: center;
}

.asiento {
  width: 70px;
  height: 70px;
  border-radius: 12px;
  background: #ececec;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-weight: bold;
  transition: 0.2s;
  border: 2px solid transparent;
}

.asiento:hover {
  transform: scale(1.05);
}

.asiento.ocupado {
  background: #ff6b6b;
  color: white;
  cursor: not-allowed;
}

.asiento.seleccionado {
  background: #2ecc71;
  color: white;
  border-color: #27ae60;
}

.asiento:nth-child(4n + 3) {
  grid-column: 4;
}

.asiento:nth-child(4n + 4) {
  grid-column: 5;
}

/* ACCIONES */

.acciones {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 25px;
}

.btn-volver,
.btn-siguiente,
.btn-secundario {
  min-height: 44px;
  border-radius: 7px;
  padding: 0 24px;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-volver {
  background: var(--color-sidebar);
  border: 1px solid #252e47;
  color: var(--color-texto);
}

.btn-volver:hover {
  border-color: #48536f;
  color: white;
}

.btn-siguiente {
  background: var(--color-principal);
  border: 1px solid var(--color-principal);
  color: var(--color-texto-sobre-principal);
}

.btn-siguiente:hover:not(:disabled) {
  background: var(--color-principal-hover);
  transform: translateY(-1px);
}

.btn-siguiente:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.btn-secundario {
  background: transparent;
  border: 1px solid #252e47;
  color: var(--color-texto);
}

.btn-secundario:hover {
  border-color: #f4b400;
  color: white;
}

/* ESTADOS */

.error-msg {
  color: #ff6b6b;
  font-size: 13px;
  margin-top: 15px;
}

.sin-resultados {
  color: #7b8198;
  font-size: 13px;
  margin-bottom: 20px;
}

/* CONFIRMACIÓN */

.confirmacion {
  max-width: 560px;
  margin: 0 auto;
  text-align: center;
  color: white;
  padding: 20px 0 40px;
}

.confirmacion h2 {
  font-size: 28px;
  margin: 14px 0 6px;
}

.numero-reserva {
  color: #f4b400;
  letter-spacing: 2px;
  font-weight: 700;
  margin-bottom: 26px;
}

.ticket {
  text-align: left;
  background: #0f1629;
  border: 1px solid #1a2138;
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 26px;
}

.ticket-fila {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 20px;
  border-bottom: 1px solid #1a2138;
  font-size: 14px;
}

.ticket-fila:last-child {
  border-bottom: none;
}

.ticket-fila span {
  color: #7b8198;
  letter-spacing: 1px;
  text-transform: uppercase;
  font-size: 12px;
}

.ticket-fila.total strong {
  color: #00ffd0;
  font-size: 18px;
}

.qr-canvas {
  background: white;
  padding: 10px;
  border-radius: 10px;
  margin: 0 auto 26px;
}

.botones-confirmacion {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}

/* BOTÓN DEBUG */

.contenedor-debug {
  display: flex;
  justify-content: center;
  margin-top: 25px;
}

.btn-debug {
  background: #ff6b35;
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 50px;
  font-weight: bold;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
  transition: 0.2s;
}

.btn-debug:hover:not(:disabled) {
  transform: scale(1.05);
  background: #e55a2b;
}

.btn-debug:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* RESPONSIVE */

@media (max-width: 700px) {
  .superior h1 {
    font-size: 36px;
  }

  .superior p {
    font-size: 13px;
  }

  .label {
    display: none;
  }

  .linea {
    width: 20px;
    margin: 0 6px;
  }

  .formulario {
    flex-direction: column;
    align-items: stretch;
  }

  .campo select,
  .campo input {
    min-width: 0;
  }

  .btn-buscar {
    width: 100%;
    min-height: 48px;
  }

  .resultado-fila {
    grid-template-columns: 1fr auto;
    gap: 12px;
    padding: 18px;
  }

  .res-hora { grid-column: 1; grid-row: 1; }
  .res-info { grid-column: 1; grid-row: 2; }
  .res-duracion { grid-column: 2; grid-row: 1; text-align: right; }
  .res-precio { grid-column: 2; grid-row: 2; text-align: right; }
  .res-estado { grid-column: 1 / -1; grid-row: 3; }

  .badge-disponible {
    width: 100%;
    justify-content: center;
    box-sizing: border-box;
  }

  .acciones {
    flex-direction: column-reverse;
  }

  .btn-volver,
  .btn-siguiente,
  .btn-secundario {
    width: 100%;
  }

  .asientos-grid {
    grid-template-columns: repeat(2, 57px) 24px repeat(2, 57px);
    gap: 10px;
  }

  .asiento {
    width: 57px;
    height: 57px;
    border-radius: 9px;
  }

  .botones-confirmacion {
    flex-direction: column;
  }
}

</style>
