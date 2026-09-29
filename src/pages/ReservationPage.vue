<!-- Página de reserva de pasajes: búsqueda -> selección de servicio -> asiento -> confirmación (ruta /comprar). Antes: PaginaBoton2.vue -->
<template>

  <MainLayout>

      <!-- SUPERIOR -->
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


      <!-- STEPPER ( paso a paso )-->
      <div class="stepper">
        <div
          v-for="(paso, i) in pasos"
          :key="i"
          class="paso"
          :class="{ activo: pasoActual === i, completado: pasoActual > i }"
        >
          <span class="numero">{{ i + 1 }}</span>
          <span class="label">{{ paso }}</span>
          <span v-if="i < pasos.length - 1" class="linea" />
        </div>
      </div>

      <!-- PASO 1: BUSQUEDA -->
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

        <p v-if="errorCiudades || errorBusqueda" class="error-msg">{{ errorCiudades || errorBusqueda }}</p>

      </div>

      <!-- PASO 2: RESULTADOS -->
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
            <div class="res-precio">{{ s.precio }}</div>
            <div class="res-estado">
              <span class="badge-disponible">{{ s.asientos }} asientos</span>
            </div>
          </div>

        </div>

        <div class="acciones">
          <button class="btn-volver" @click="pasoActual = 0">Volver</button>
          <button ref="botonContinuarServicio" class="btn-siguiente" :disabled="!servicioSeleccionado" @click="pasoActual = 2">
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
            @dblclick="seleccionarAsientoyBajarAlBoton(asiento)"
          >
            {{ asiento.numero }}
          </div>

        </div>

        <div class="acciones">
          <button class="btn-volver" @click="pasoActual--">
            Volver
          </button>

          <button
            ref="botonContinuarServicio"
            class="btn-siguiente"
            :disabled="!asientoSeleccionado"
            @click="pasoActual++"
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
          <button class="btn-volver" @click="pasoActual--">Volver</button>

          <button
            class="btn-siguiente"
            :disabled="!pasajeroValido"
            @click="pasoActual++"
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
          @volver="pasoActual--"
          @pagar="iniciarPago"
          @reintentar="iniciarPago"
        />

        <!-- Botón temporal para debug -->
        <button
          v-if="debugMode"
          class="btn-debug"
          @click="forzarConfirmacion"
        >
          ⏩ Avanzar a Confirmación
        </button>

      </div>

      <!-- PASO 6: CONFIRMACIÓN -->
      <div v-if="pasoActual === 5" class="paso-content">

        <div class="placeholder-paso">

          <q-icon name="check_circle" size="48px" color="positive" />

          <h2>¡RESERVA CONFIRMADA!</h2>

          <p v-if="reservaCreada">
            Reserva N° {{ reservaCreada.id }} —
            {{ servicioSeleccionado?.origen }} →
            {{ servicioSeleccionado?.destino }},
            asiento {{ asientoSeleccionado }},
            total ${{ reservaCreada.total }}
          </p>

          <button class="btn-siguiente" @click="reiniciar">
            Hacer otra reserva
          </button>

          <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px; flex-wrap: wrap;">
            <canvas
              ref="qrCanvas"
              style="background: white; padding: 10px; border-radius: 8px;"
            />
          </div>

          <div style="display: flex; gap: 15px; justify-content: center; margin-top: 15px; flex-wrap: wrap;">

            <button class="btn-secundario" @click="descargarQR">
              📥 Descargar QR
            </button>

            <button class="btn-secundario" @click="reenviarCorreo">
              📧 Reenviar QR al correo
            </button>

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
  getEstadoPago
} from '../services/api'


const { usuario, isAuthenticated } = useAuth()


const pasos = [
  'Búsqueda',
  'Servicio',
  'Asiento',
  'Pasajero',
  'Pago',
  'Confirmación'
]

const pasoActual = ref(0)


// =========================================================
// RECUPERACIÓN DE RESERVA EN PROCESO
// =========================================================

const STORAGE_KEY = 'reserva_en_proceso'


function guardarReservaEnProceso() {

  const estado = {
    pasoActual: pasoActual.value,

    busqueda: busqueda.value,

    servicioSeleccionado: servicioSeleccionado.value,

    asientoSeleccionado: asientoSeleccionado.value,

    pasajero: pasajero.value,

    reservaCreada: reservaCreada.value,

    fasePago: fasePago.value,

    mensajeErrorPago: mensajeErrorPago.value
  }

  sessionStorage.setItem(
    STORAGE_KEY,
    JSON.stringify(estado)
  )
}


function recuperarReservaEnProceso() {

  const guardado = sessionStorage.getItem(STORAGE_KEY)

  if (!guardado) {
    return false
  }

  try {

    const estado = JSON.parse(guardado)


    if (estado.busqueda) {
      busqueda.value = estado.busqueda
    }


    if (estado.servicioSeleccionado) {
      servicioSeleccionado.value = estado.servicioSeleccionado
    }


    if (
      estado.asientoSeleccionado !== undefined &&
      estado.asientoSeleccionado !== null
    ) {
      asientoSeleccionado.value = estado.asientoSeleccionado
    }


    if (estado.pasajero) {
      pasajero.value = estado.pasajero
    }


    if (estado.reservaCreada) {
      reservaCreada.value = estado.reservaCreada
    }


    if (estado.fasePago) {
      fasePago.value = estado.fasePago
    }


    if (estado.mensajeErrorPago) {
      mensajeErrorPago.value = estado.mensajeErrorPago
    }


    if (typeof estado.pasoActual === 'number') {
      pasoActual.value = estado.pasoActual
    }


    return true

  } catch (error) {

    console.error(
      'Error recuperando reserva:',
      error
    )

    sessionStorage.removeItem(STORAGE_KEY)

    return false
  }
}


// =========================================================
// CIUDADES
// =========================================================

const ciudades = ref([])
const cargandoCiudades = ref(false)
const errorCiudades = ref('')


async function cargarCiudades() {

  cargandoCiudades.value = true
  errorCiudades.value = ''

  try {

    const data = await getCiudades()

    ciudades.value = data.map(
      c => c.nombre
    )

  } catch (e) {

    errorCiudades.value = e.message

  } finally {

    cargandoCiudades.value = false

  }
}


// =========================================================
// BÚSQUEDA
// =========================================================

const busqueda = ref({
  destino: 'Montevideo',  // ← IMPORTANTE: un destino válido
  fecha: new Date().toISOString().slice(0, 10), // ← hoy
})

const servicioSeleccionado = ref(null)

const botonContinuarServicio = ref(null)


function subiralPaso(paso) {

  pasoActual.value = paso

  nextTick(() => {

    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    })

  })

}


function seleccionarAsientoyBajarAlBoton(asiento) {

  if (asiento.ocupado) {
    return
  }

  asientoSeleccionado.value = asiento.numero

  nextTick(() => {

    if (botonContinuarServicio.value) {

      botonContinuarServicio.value.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      })

    }

  })

}


function seleccionarYBajarAlBoton(servicio) {

  servicioSeleccionado.value = servicio

  nextTick(() => {

    if (botonContinuarServicio.value) {

      botonContinuarServicio.value.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      })

    }

  })

}


// =========================================================
// SERVICIOS
// =========================================================

const serviciosFiltrados = ref([])

const buscando = ref(false)

const errorBusqueda = ref('')


async function buscar() {

  servicioSeleccionado.value = null

  buscando.value = true

  errorBusqueda.value = ''

  try {

    serviciosFiltrados.value =
      await buscarServicios(
    busqueda.value.destino,
    busqueda.value.fecha
 )

    pasoActual.value = 1

  } catch (e) {

    errorBusqueda.value = e.message

  } finally {

    buscando.value = false

  }

}


// =========================================================
// ASIENTOS
// =========================================================

const asientoSeleccionado = ref(null)

const asientosOcupados = ref([])

const cargandoAsientos = ref(false)


const asientos = computed(() => {

  const total =
    Number(
      servicioSeleccionado.value?.asientos
    ) || 40

  return Array.from(
    { length: total },
    (_, i) => {

      const numero = i + 1

      return {
        numero,
        ocupado:
          asientosOcupados.value.includes(numero)
      }

    }
  )

})


async function cargarAsientosOcupados() {

  if (!servicioSeleccionado.value) {
    return
  }

  cargandoAsientos.value = true

  try {

    const data =
      await getAsientosOcupados(
        servicioSeleccionado.value.id,
        busqueda.value.fecha
      )

    asientosOcupados.value =
      data.asientos_ocupados || []

  } catch {

    asientosOcupados.value = []

  } finally {

    cargandoAsientos.value = false

  }

}


// Se usa para saber si estamos restaurando una reserva.
// De esta forma no borramos el asiento recuperado.
const restaurandoReserva = ref(false)


watch(pasoActual, (nuevo, anterior) => {

  if (nuevo === 2) {

    if (!restaurandoReserva.value) {
      asientoSeleccionado.value = null
    }

    cargarAsientosOcupados()

  }

})


watch(pasoActual, async () => {

  await nextTick()

  const contenido =
    document.querySelector('.superior')

  contenido?.scrollIntoView({
    behavior: 'smooth',
    block: 'start'
  })

})


function seleccionarAsiento(asiento) {

  if (asiento.ocupado) {
    return
  }

  asientoSeleccionado.value =
    asiento.numero

}


// =========================================================
// DATOS DEL PASAJERO
// =========================================================

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


// =========================================================
// PAGO
// =========================================================

const route = useRoute()

const router = useRouter()


const reservaCreada = ref(null)


const fasePago = ref('resumen')

const mensajeErrorPago = ref('')


const resumenPago = computed(() => ({

  origen:
    servicioSeleccionado.value?.origen,

  destino:
    servicioSeleccionado.value?.destino,

  fecha:
    busqueda.value.fecha,

  hora:
    servicioSeleccionado.value?.hora,

  empresa:
    servicioSeleccionado.value?.empresa,

  asiento:
    asientoSeleccionado.value,

  total:
    reservaCreada.value?.total ??
    servicioSeleccionado.value?.precio

}))


// =========================================================
// LOGIN
// =========================================================

// Si el usuario llega al paso de pago sin estar autenticado,
// primero guardamos toda la reserva y después lo mandamos al login.

watch(pasoActual, (nuevo) => {

  if (
    nuevo === 4 &&
    !isAuthenticated.value
  ) {

    guardarReservaEnProceso()

    router.push({
      path: '/login',
      query: {
        redirect: '/comprar'
      }
    })

  }

})


// =========================================================
// INICIAR PAGO
// =========================================================

async function iniciarPago() {

  if (
    !servicioSeleccionado.value ||
    !asientoSeleccionado.value
  ) {
    return
  }

  fasePago.value = 'procesando'

  mensajeErrorPago.value = ''


  try {

    // 1. Crear reserva si todavía no existe.

    if (!reservaCreada.value) {

      const data =
        await crearReserva({

          servicio_id:
            servicioSeleccionado.value.id,

          fecha_viaje:
            busqueda.value.fecha,

          asiento:
            asientoSeleccionado.value,

          total:
            servicioSeleccionado.value.precio,

          pasajero:
            pasajero.value,

          usuario_id:
            usuario.value?.id

        })

      reservaCreada.value = data

    }


    // 2. Crear preferencia de Mercado Pago.

    const preferencia =
      await crearPreferenciaPago(
        reservaCreada.value.id
      )


    // 3. Ir a Mercado Pago.

    window.location.href =
      preferencia.init_point


  } catch (e) {

    fasePago.value = 'error'

    mensajeErrorPago.value =
      e.message

  }

}


// =========================================================
// VERIFICAR PAGO
// =========================================================

const $q = useQuasar()


async function verificarPago(reservaId) {

  pasoActual.value = 4

  fasePago.value = 'verificando'


  try {

    const pago =
      await getEstadoPago(reservaId)


    reservaCreada.value = {
      id: reservaId,
      total: pago.monto
    }


    if (pago.estado === 'pagado') {

      pasoActual.value = 5

    } else if (
      pago.estado === 'rechazado' ||
      pago.estado === 'cancelado'
    ) {

      fasePago.value = 'rechazado'

    } else {

      fasePago.value = 'pendiente'

    }


  } catch (e) {

    fasePago.value = 'error'

    mensajeErrorPago.value =
      e.message

  }

}


// =========================================================
// INICIALIZACIÓN
// =========================================================

onMounted(() => {

  cargarCiudades()


  // Recuperamos la reserva guardada antes del login.
  restaurandoReserva.value = true

  recuperarReservaEnProceso()

  restaurandoReserva.value = false


  // Si volvimos desde Mercado Pago,
  // verificamos el estado real de la reserva.

  if (route.query.reserva_id) {

    const reservaId =
      Number(route.query.reserva_id)

    verificarPago(reservaId)


    // Limpiamos la URL.

    router.replace({
      path: '/comprar'
    })

  }

})


// =========================================================
// REINICIAR
// =========================================================

function reiniciar() {

  // MUY IMPORTANTE:
  // borramos la reserva guardada para que
  // una nueva reserva empiece desde cero.

  sessionStorage.removeItem(
    STORAGE_KEY
  )


  pasoActual.value = 0

  servicioSeleccionado.value = null

  asientoSeleccionado.value = null

  asientosOcupados.value = []

  reservaCreada.value = null

  fasePago.value = 'resumen'

  mensajeErrorPago.value = ''

  pasajero.value = {
    nombre: '',
    apellido: '',
    documento: '',
    email: '',
    telefono: ''
  }

}


// =========================================================
// SIMULACIÓN DE PAGO
// =========================================================

async function simularPagoExitoso() {

  if (
    !servicioSeleccionado.value ||
    !asientoSeleccionado.value
  ) {

    alert(
      'Debes seleccionar servicio y asiento primero.'
    )

    return
  }


  if (!reservaCreada.value) {

    try {

      const data =
        await crearReserva({

          servicio_id:
            servicioSeleccionado.value.id,

          fecha_viaje:
            busqueda.value.fecha,

          asiento:
            asientoSeleccionado.value,

          total:
            servicioSeleccionado.value.precio,

          pasajero:
            pasajero.value,

          usuario_id:
            usuario.value?.id

        })

      reservaCreada.value = data

    } catch (e) {

      console.error(
        'Error creando reserva simulada:',
        e
      )

      alert(
        'Error al crear reserva simulada.'
      )

      return

    }

  }


  pasoActual.value = 5

}


// =========================================================
// QR
// =========================================================

const qrCanvas = ref(null)


watch(
  [pasoActual, reservaCreada],
  async ([nuevoPaso, reserva]) => {

    if (
      nuevoPaso === 5 &&
      reserva &&
      servicioSeleccionado.value
    ) {

      await nextTick()


      if (qrCanvas.value) {

        try {

          const texto =
            `Reserva N° ${reserva.id} - ` +
            `${servicioSeleccionado.value.origen} → ` +
            `${servicioSeleccionado.value.destino}, ` +
            `${busqueda.value.fecha}, ` +
            `asiento ${asientoSeleccionado.value}, ` +
            `total $${reserva.total}`


          await QRcode.toCanvas(
            qrCanvas.value,
            texto,
            {
              width: 200
            }
          )


          const qrBase64 =
            qrCanvas.value.toDataURL(
              'image/png'
            )


          await enviarQRPorCorreo(
            reserva.id,
            pasajero.value.email,
            qrBase64
          )


        } catch (err) {

          console.error(
            'Error generando QR:',
            err
          )

        }

      }

    }

  }
)


// =========================================================
// DEBUG
// =========================================================

const debugMode = ref(true)


function forzarConfirmacion() {

  if (!servicioSeleccionado.value) {

    servicioSeleccionado.value = {

      id: 999,

      origen: 'Melo',

      destino: 'Montevideo',

      hora: '08:00',

      empresa: 'COPSA',

      tipo: 'Semicama',

      duracion: '6h',

      precio: 1200,

      asientos: 40

    }


    busqueda.value.origen = 'Melo'

    busqueda.value.destino = 'Montevideo'

    busqueda.value.fecha =
      new Date()
        .toISOString()
        .slice(0, 10)

  }


  if (!asientoSeleccionado.value) {
    asientoSeleccionado.value = 1
  }


  if (!reservaCreada.value) {

    reservaCreada.value = {

      id: Date.now(),

      total:
        servicioSeleccionado.value.precio

    }

  }


  pasoActual.value = 5

}


// =========================================================
// ENVIAR QR POR CORREO
// =========================================================

async function enviarQRPorCorreo(
  reservaId,
  email,
  qrBase64
) {

  if (!email) {

    $q?.notify?.({

      type: 'warning',

      message:
        'No hay email registrado para enviar el QR.'

    })

    return false

  }


  try {

    const response =
      await fetch(
        `/api/reservas/enviar-qr.php?reserva_id=${reservaId}`,
        {
          method: 'POST',

          headers: {
            'Content-Type':
              'application/json'
          },

          body: JSON.stringify({
            email,
            qrBase64
          })
        }
      )


    if (!response.ok) {

      const errorText =
        await response.text()

      throw new Error(
        errorText ||
        'Error del servidor'
      )

    }


    const data =
      await response.json()


    $q?.notify?.({

      type: 'positive',

      message:
        '✅ QR enviado a tu correo electrónico.'

    })


    return true


  } catch (error) {

    console.error(
      'Error enviando QR por correo:',
      error
    )


    $q?.notify?.({

      type: 'negative',

      message:
        '❌ No se pudo enviar el QR. Puedes descargarlo manualmente.'

    })


    return false

  }

}


// =========================================================
// DESCARGAR QR
// =========================================================

function descargarQR() {

  if (!qrCanvas.value) {
    return
  }


  const link =
    document.createElement('a')


  link.download =
    `reserva-${reservaCreada.value?.id || 'qr'}.png`


  link.href =
    qrCanvas.value.toDataURL(
      'image/png'
    )


  link.click()

}


// =========================================================
// REENVIAR QR
// =========================================================

async function reenviarCorreo() {

  if (!reservaCreada.value) {
    return
  }

  if (!qrCanvas.value) {
    return
  }


  const qrBase64 =
    qrCanvas.value.toDataURL(
      'image/png'
    )


  await enviarQRPorCorreo(
    reservaCreada.value.id,
    pasajero.value.email,
    qrBase64
  )

}


// =========================================================
// GUARDADO AUTOMÁTICO
// =========================================================

// Cada cambio importante de la reserva se guarda.
// Así, si el usuario abandona la página o va al login,
// puede recuperar el proceso.

watch(
  [
    pasoActual,
    busqueda,
    servicioSeleccionado,
    asientoSeleccionado,
    pasajero,
    reservaCreada,
    fasePago,
    mensajeErrorPago
  ],
  () => {

    guardarReservaEnProceso()

  },
  {
    deep: true
  }
)

</script>

<style scoped>

/* parte de los asientos */

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

/* saltar la columna del pasillo */

.asiento:nth-child(4n + 3) {
  grid-column: 4;
}

.asiento:nth-child(4n + 4) {
  grid-column: 5;
}

/*fin */


/* =========================================================
   ENCABEZADO
========================================================= */

.superior {
  padding: 10px 0 30px;
  margin-bottom: 32px;
  border-bottom: 1px solid #1a2138;
}

.superior-contenido {
  max-width: 850px;
}


/* Pequeña etiqueta superior */

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


/* Título */

.superior h1 {
  margin: 0;
  color: #ffffff;
  font-size: clamp(38px, 5vw, 58px);
  line-height: 1.05;
  font-weight: 700;
  letter-spacing: -1.5px;
}


/* Descripción */

.superior p {
  margin: 14px 0 0;
  color: #858da7;
  font-size: 20px;
  line-height: 1.6;
  letter-spacing: 0.3px;
  max-width: 650px;
}


/* =========================================================
   STEPPER
========================================================= */

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
  color: #69718a;
  background: #0b1023;
  font-size: 17px;
  font-weight: 700;
  transition: all 0.25s ease;
}


/* Paso actual */

.paso.activo .numero {
  background: #f4b400;
  border-color: #f4b400;
  color: #0b1023;
  box-shadow:
    0 0 0 5px rgba(244, 180, 0, 0.08);
}


/* Pasos terminados */

.paso.completado .numero {
  background: #00d99a;
  border-color: #00d99a;
  color: #07101e;
  box-shadow:
    0 0 0 4px rgba(0, 217, 154, 0.06);
}

.label {
  color: #69718a;
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


/* FORMULARIO */

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
  color: #7b8198;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.8px;
}

.campo select,
.campo input {
  width: 100%;
  min-width: 200px;
  box-sizing: border-box;
  background: #0c1325;
  border: 1px solid #252e47;
  color: #ffffff;
  padding: 13px 15px;
  border-radius: 7px;
  font-size: 14px;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.campo select:hover,
.campo input:hover {
  border-color: #36415f;
}

.campo select:focus,
.campo input:focus {
  border-color: #f4b400;
  box-shadow:
    0 0 0 3px rgba(244, 180, 0, 0.08);
  background: #0f172c;
}

.btn-buscar {
  min-height: 45px;
  background: #f4b400;
  color: #0b1023;
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
  transition:
    transform 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;
}

.btn-buscar:hover {
  background: #ffca28;
  transform: translateY(-1px);
  box-shadow:
    0 8px 20px rgba(244, 180, 0, 0.15);
}

.btn-buscar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}


/* RESULTADOS */

.resultados {
  display: flex;
  flex-direction: column;
  border: 1px solid #1a2138;
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 22px;
  background: #0b1023;
}

.resultado-fila {
  display: grid;
  grid-template-columns:
    90px
    minmax(150px, 1.5fr)
    1fr
    120px
    130px;

  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #1a2138;
  cursor: pointer;

  transition:
    background 0.2s ease,
    border-color 0.2s ease;
}

.resultado-fila:last-child {
  border-bottom: none;
}

.resultado-fila:hover {
  background: #10182c;
}

.resultado-fila.seleccionado {
  background: #101a2f;
  box-shadow:
    inset 3px 0 0 #f4b400;
}

.res-hora {
  color: #f4b400;
  font-size: 23px;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.res-empresa {
  display: block;
  color: #ffffff;
  font-size: 14px;
  font-weight: 650;
}

.res-tipo {
  display: block;
  color: #707991;
  font-size: 11px;
  margin-top: 4px;
  letter-spacing: 0.8px;
}

.res-duracion {
  color: #858da7;
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
  background: rgba(0, 255, 153, 0.04);
  color: #00e6a0;
  padding: 6px 10px;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
  border: 1px solid rgba(0, 255, 153, 0.35);
}


/* ACCIONES */

.acciones {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 25px;
  padding: 0;
}

.btn-volver,
.btn-siguiente {
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
  background: transparent;
  border: 1px solid #252e47;
  color: #858da7;
}

.btn-volver:hover {
  border-color: #48536f;
  color: white;
  background: #0f1629;
}

.btn-siguiente {
  background: #f4b400;
  border: 1px solid #f4b400;
  color: #0b1023;
}

.btn-siguiente:hover:not(:disabled) {
  background: #ffca28;
  transform: translateY(-1px);
  box-shadow:
    0 7px 18px rgba(244, 180, 0, 0.15);
}

.btn-siguiente:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}


/* PLACEHOLDER */

.placeholder-paso {
  text-align: center;
  padding: 60px;
  color: #7b8198;
}

.placeholder-paso h2 {
  color: white;
  font-size: 25px;
  margin: 20px 0 10px;
}


/* ESTADOS / ERRORES */

.error-msg {
  color: #ff6b6b;
  font-size: 13px;
  margin-top: 15px;
  letter-spacing: 0.5px;
}

.sin-resultados {
  color: #7b8198;
  font-size: 13px;
  margin-bottom: 20px;
  letter-spacing: 0.5px;
}


/* RESUMEN DE PAGO */

.resumen-reserva {
  border: 1px solid #1a2138;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 25px;
  max-width: 480px;
}

.resumen-fila {
  display: flex;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid #1a2138;
  font-size: 13px;
}

.resumen-fila:last-child {
  border-bottom: none;
}

.resumen-fila span {
  color: #7b8198;
  letter-spacing: 1px;
}

.resumen-fila strong {
  color: white;
}

.resumen-total {
  background: #0f1629;
}

.resumen-total strong {
  color: #00ffd0;
  font-size: 18px;
}


@media (max-width: 700px) {

  .superior {
    padding-top: 5px;
    padding-bottom: 24px;
    margin-bottom: 25px;
  }

  .superior h1 {
    font-size: 36px;
    line-height: 1.08;
    letter-spacing: -1px;
  }

  .superior p {
    font-size: 13px;
    max-width: 100%;
  }

  .superior-etiqueta {
    font-size: 9px;
    letter-spacing: 2px;
  }


  /* Stepper */

  .stepper {
    margin-bottom: 30px;
    padding-bottom: 5px;
  }

  .numero {
    width: 31px;
    height: 31px;
    font-size: 11px;
  }

  .label {
    display: none;
  }

  .linea {
    width: 20px;
    margin: 0 6px;
  }


  /* Formularios */

  .formulario {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .campo {
    width: 100%;
  }

  .campo select,
  .campo input {
    width: 100%;
    min-width: 0;
  }

  .btn-buscar {
    width: 100%;
    min-height: 48px;
  }


  /* Resultados */

  .resultado-fila {
    grid-template-columns: 1fr auto;
    gap: 12px;
    padding: 18px;
  }

  .res-hora {
    grid-column: 1;
    grid-row: 1;
  }

  .res-info {
    grid-column: 1;
    grid-row: 2;
  }

  .res-duracion {
    grid-column: 2;
    grid-row: 1;
    text-align: right;
  }

  .res-precio {
    grid-column: 2;
    grid-row: 2;
    text-align: right;
  }

  .res-estado {
    grid-column: 1 / -1;
    grid-row: 3;
  }

  .badge-disponible {
    width: 100%;
    justify-content: center;
    box-sizing: border-box;
  }


  /* Acciones */

  .acciones {
    flex-direction: column-reverse;
    width: 100%;
  }

  .btn-volver,
  .btn-siguiente {
    width: 100%;
  }


  /* Asientos */

  .asientos-grid {
    grid-template-columns:
      repeat(2, 57px)
      24px
      repeat(2, 57px);

    gap: 10px;
  }

  .asiento {
    width: 57px;
    height: 57px;
    margin-left: 0;
    border-radius: 9px;
  }


  /* Confirmación */

  .placeholder-paso {
    padding: 35px 15px;
  }

}


/* Botón debug flotante */

.btn-debug {
  position: fixed;
  bottom: 30px;
  right: 30px;
  background: #ff6b35;
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 50px;
  font-weight: bold;
  font-size: 14px;
  cursor: pointer;
  box-shadow:
    0 4px 12px rgba(255, 107, 53, 0.4);
  z-index: 999;
  transition: 0.2s;
}

.btn-debug:hover {
  transform: scale(1.05);
  background: #e55a2b;
}

</style>
