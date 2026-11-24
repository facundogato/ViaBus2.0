<!-- Página de perfil del usuario (ruta /perfil) -->
<template>
  <MainLayout>
    <div class="perfil">

      <!-- ENCABEZADO -->
      <header class="encabezado">
        <h1>Mi <span>perfil</span></h1>
        <p>Tus reservas, tu historial y tus datos</p>
      </header>

      <!-- TARJETA DEL USUARIO -->
      <section class="tarjeta-usuario">
        <div class="avatar">{{ inicial }}</div>

        <div class="identidad">
          <h2>{{ usuario?.nombre }} {{ usuario?.apellido }}</h2>
          <p>{{ usuario?.email }}</p>
        </div>

        <div class="estadisticas">
          <div class="estadistica">
            <span class="numero">{{ viajesTotales }}</span>
            <span class="etiqueta">Viajes</span>
          </div>
          <div class="estadistica">
            <span class="numero">${{ totalInvertido }}</span>
            <span class="etiqueta">Invertido</span>
          </div>
        </div>

        <button class="boton-secundario" @click="mostrarEdicion = !mostrarEdicion">
          <q-icon name="edit" size="16px" />
          {{ mostrarEdicion ? 'Cerrar' : 'Editar datos' }}
        </button>
      </section>

      <!-- EDICIÓN -->
      <transition name="desplegar">
        <section v-if="mostrarEdicion" class="panel-edicion">

          <div class="columna">
            <h3>Datos personales</h3>

            <label>Nombre</label>
            <input v-model="formDatos.nombre" type="text" />

            <label>Apellido</label>
            <input v-model="formDatos.apellido" type="text" />

            <label>Correo electrónico</label>
            <input v-model="formDatos.email" type="email" />

            <p v-if="errorDatos" class="mensaje error">{{ errorDatos }}</p>
            <p v-if="okDatos" class="mensaje ok">{{ okDatos }}</p>

            <button class="boton-principal" :disabled="guardandoDatos" @click="guardarDatos">
              {{ guardandoDatos ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>

          <div class="columna">
            <h3>Cambiar contraseña</h3>

            <label>Contraseña actual</label>
            <input v-model="formPassword.actual" type="password" />

            <label>Contraseña nueva</label>
            <input v-model="formPassword.nueva" type="password" />

            <p v-if="errorPassword" class="mensaje error">{{ errorPassword }}</p>
            <p v-if="okPassword" class="mensaje ok">{{ okPassword }}</p>

            <button class="boton-principal" :disabled="guardandoPassword" @click="guardarPassword">
              {{ guardandoPassword ? 'Guardando...' : 'Cambiar contraseña' }}
            </button>
          </div>

        </section>
      </transition>

      <!-- MI TARJETA QR: solicitud con los datos del perfil y seguimiento -->


      <!-- PESTAÑAS -->
      <nav class="pestanas">
        <button
          :class="{ activa: pestana === 'proximos' }"
          @click="pestana = 'proximos'"
        >
          Próximos viajes
          <span class="contador">{{ proximos.length }}</span>
        </button>
        <button
          :class="{ activa: pestana === 'historial' }"
          @click="pestana = 'historial'"
        >
          Historial
          <span class="contador">{{ historial.length }}</span>
        </button>
      </nav>

      <!-- ESTADOS -->
      <p v-if="cargando" class="estado-vacio">Cargando tus reservas...</p>

      <div
        v-else-if="listaActual.length === 0"
        class="estado-vacio"
      >
        <q-icon name="confirmation_number" size="40px" />
        <p>
          {{ pestana === 'proximos'
            ? 'No tenés viajes próximos.'
            : 'Todavía no tenés viajes en tu historial.' }}
        </p>
      </div>

      <!-- LISTA DE RESERVAS -->
      <div v-else class="lista-reservas">
        <article
          v-for="r in listaActual"
          :key="r.id"
          class="reserva"
          :class="{ cancelada: r.estado === 'cancelada' }"
        >
          <div class="reserva-fecha">
            <span class="codigo">#{{ r.id }}</span>
            <span class="dia">{{ formatearFecha(r.fecha_viaje) }}</span>
            <span class="hora">{{ formatearHora(r.hora) }}</span>
          </div>

          <div class="reserva-detalle">
            <h3>{{ r.origen }} <span class="flecha">→</span> {{ r.destino }}</h3>
            <p>{{ r.empresa }} · Bus {{ r.bus || '-' }}</p>
            <p>Asiento {{ r.asiento }} · Andén {{ r.anden || '-' }}</p>
          </div>

          <div class="reserva-acciones">
            <span class="estado" :class="claseEstado(r)">{{ etiquetaEstado(r) }}</span>
            <span class="precio">${{ r.total }}</span>

            <template v-if="pestana === 'proximos' && r.estado !== 'cancelada'">
              <button
                class="boton-cancelar"
                :disabled="!puedeCancelar(r) || cancelando === r.id"
                @click="cancelar(r)"
              >
                {{ cancelando === r.id ? 'Cancelando...' : 'Cancelar' }}
              </button>
              <small v-if="!puedeCancelar(r)" class="nota">
                Faltan menos de {{ PLAZO_HORAS }} hs
              </small>
            </template>
          </div>
        </article>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import TarjetaQrSeccion from 'src/components/tarjeta/TarjetaQrSeccion.vue'
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import { useAuth } from '../services/auth'
import {
  getReservasUsuario,
  cancelarReserva,
  actualizarPerfil,
  cambiarPassword
} from '../services/api'

const router = useRouter()
const { usuario, guardarSesion } = useAuth()

if (!usuario.value) {
  router.push('/login')
}

const inicial = computed(() => usuario.value?.nombre?.charAt(0).toUpperCase() || '?')

// ---- Reservas ----
const PLAZO_HORAS = 3
const reservas = ref([])
const cargando = ref(false)
const pestana = ref('proximos')
const cancelando = ref(null)

async function cargarReservas() {
  if (!usuario.value) return
  cargando.value = true
  try {
    const res = await getReservasUsuario(usuario.value.id)
    reservas.value = Array.isArray(res) ? res : (res?.data ?? [])
  } catch (e) {
    console.error('Error cargando reservas:', e)
    reservas.value = []
  } finally {
    cargando.value = false
  }
}

onMounted(cargarReservas)

// Combina fecha (YYYY-MM-DD) y hora (HH:MM), tolere el formato que venga del servidor
function fechaSalida(r) {
  const fecha = String(r.fecha_viaje).slice(0, 10)
  const hora = String(r.hora).slice(0, 5)
  return new Date(`${fecha}T${hora}:00`)
}

function formatearFecha(fecha) {
  const d = new Date(`${String(fecha).slice(0, 10)}T12:00:00`)
  return d.toLocaleDateString('es-UY', { weekday: 'short', day: '2-digit', month: 'short' })
}

function formatearHora(hora) {
  return String(hora).slice(0, 5)
}

const proximos = computed(() =>
  reservas.value
    .filter(r => r.estado !== 'cancelada' && fechaSalida(r) >= new Date())
    .sort((a, b) => fechaSalida(a) - fechaSalida(b))
)

const historial = computed(() =>
  reservas.value
    .filter(r => r.estado === 'cancelada' || fechaSalida(r) < new Date())
    .sort((a, b) => fechaSalida(b) - fechaSalida(a))
)

const listaActual = computed(() =>
  pestana.value === 'proximos' ? proximos.value : historial.value
)

const viajesTotales = computed(() =>
  reservas.value.filter(r => r.estado !== 'cancelada').length
)

const totalInvertido = computed(() =>
  reservas.value
    .filter(r => r.estado !== 'cancelada')
    .reduce((acc, r) => acc + Number(r.total || 0), 0)
)

function puedeCancelar(r) {
  const horas = (fechaSalida(r).getTime() - Date.now()) / 3_600_000
  return horas >= PLAZO_HORAS
}

function etiquetaEstado(r) {
  if (r.estado === 'cancelada') return 'Cancelado'
  if (pestana.value === 'historial') return 'Completado'
  return r.estado === 'confirmada' ? 'Confirmado' : 'Pendiente'
}

function claseEstado(r) {
  if (r.estado === 'cancelada') return 'rojo'
  if (pestana.value === 'historial') return 'azul'
  return r.estado === 'confirmada' ? 'verde' : 'amarillo'
}

async function cancelar(r) {
  if (!confirm(`¿Cancelar el viaje ${r.origen} → ${r.destino} del ${r.fecha_viaje}?`)) return
  cancelando.value = r.id
  try {
    await cancelarReserva(r.id)
    await cargarReservas()
  } catch (e) {
    alert(e.message)
  } finally {
    cancelando.value = null
  }
}

// ---- Edición de datos ----
const mostrarEdicion = ref(false)
const formDatos = ref({
  nombre: usuario.value?.nombre || '',
  apellido: usuario.value?.apellido || '',
  email: usuario.value?.email || ''
})
const guardandoDatos = ref(false)
const errorDatos = ref('')
const okDatos = ref('')

async function guardarDatos() {
  guardandoDatos.value = true
  errorDatos.value = ''
  okDatos.value = ''
  try {
    const actualizado = await actualizarPerfil(usuario.value.id, formDatos.value)
    guardarSesion(actualizado)
    okDatos.value = 'Datos actualizados correctamente'
  } catch (e) {
    errorDatos.value = e.message
  } finally {
    guardandoDatos.value = false
  }
}

// ---- Cambio de contraseña ----
const formPassword = ref({ actual: '', nueva: '' })
const guardandoPassword = ref(false)
const errorPassword = ref('')
const okPassword = ref('')

async function guardarPassword() {
  guardandoPassword.value = true
  errorPassword.value = ''
  okPassword.value = ''
  try {
    await cambiarPassword(usuario.value.id, formPassword.value)
    okPassword.value = 'Contraseña actualizada'
    formPassword.value = { actual: '', nueva: '' }
  } catch (e) {
    errorPassword.value = e.message
  } finally {
    guardandoPassword.value = false
  }
}
</script>

<style scoped>
.perfil {
  max-width: 1000px;
  margin: 0 auto;
  padding: 10px 20px 60px;
  color: white;
}

/* Encabezado */
.encabezado h1 {
  font-size: 42px;
  font-weight: 600;
  letter-spacing: 2px;
  margin: 0;
}
.encabezado h1 span {
  color: #f4b400;
  font-weight: 700;
}
.encabezado p {
  color:var(--color-texto-suave);
  font-size: 17px;
  letter-spacing: 1px;
  margin: 6px 0 30px;
}

/* Tarjeta usuario */
.tarjeta-usuario {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  border-radius: 16px;
  padding: 28px;
  margin-bottom: 28px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}
.avatar {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: #f4b400;
  color: #060c22;
  font-size: 30px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.identidad {
  flex: 1;
  min-width: 180px;
}
.identidad h2 {
  font-size: 24px;
  letter-spacing: 1px;
  margin: 0 0 4px;
}
.identidad p {
  color: #7b8198;
  font-size: 13px;
  margin: 0;
}
.estadisticas {
  display: flex;
  gap: 32px;
}
.estadistica {
  text-align: center;
}
.numero {
  display: block;
  color: #f4b400;
  font-size: 30px;
  font-weight: 700;
}
.etiqueta {
  color: #7b8198;
  font-size: 11px;
  letter-spacing: 1px;
  text-transform: uppercase;
}

/* Botones */
.boton-secundario {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: 1px solid #1a2138;
  color: #9aa1b8;
  padding: 10px 18px;
  border-radius: 999px;
  cursor: pointer;
  font-size: 13px;
  transition: 0.2s;
}
.boton-secundario:hover {
  color: white;
  border-color: #f4b400;
}
.boton-principal {
  background: #f4b400;
  border: none;
  color: #060c22;
  padding: 12px 20px;
  border-radius: 999px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1px;
  margin-top: 6px;
  transition: 0.2s;
}
.boton-principal:hover:not(:disabled) {
  background: #ffd24d;
}
.boton-principal:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Panel de edición */
.panel-edicion {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  border-radius: 16px;
  padding: 26px;
  margin-bottom: 30px;
}
.columna {
  flex: 1;
  min-width: 260px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.columna h3 {
  font-size: 13px;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: #f4b400;
  margin: 0 0 6px;
}
.columna label {
  color: #7b8198;
  font-size: 11px;
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-top: 4px;
}
.columna input {
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  color: white;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}
.columna input:focus {
  border-color: #f4b400;
}

/* Mensajes */
.mensaje {
  font-size: 13px;
  margin: 4px 0 0;
}
.mensaje.error { color: #ff6b6b; }
.mensaje.ok { color: #00ff99; }

/* Pestañas */
.pestanas {
  display: flex;
  gap: 10px;
  margin-bottom: 22px;
  border-bottom: 1px solid #1a2138;
}
.pestanas button {
  background: transparent;
  border: none;
  color: var(--color-secundario);
  padding: 12px 18px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}
.pestanas button:hover {
  color: white;
}
.pestanas button.activa {
  color: #f4b400;
  border-bottom-color: #f4b400;
}
.contador {
  background: var(--color-sidebar);
  border: 1px solid #1a2138;
  border-radius: 999px;
  padding: 1px 8px;
  font-size: 11px;
}

/* Estados vacíos */
.estado-vacio {
  text-align: center;
  color: var(--color-secundario);
  padding: 50px 20px;
  font-size: 14px;
  border: 1px dashed #1a2138;
  border-radius: 16px;
}
.estado-vacio p {
  margin: 10px 0 0;
}

/* Reservas */
.lista-reservas {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.reserva {
  display: grid;
  grid-template-columns: 150px 1fr auto;
  gap: 24px;
  align-items: center;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  border-left: 3px solid #f4b400;
  border-radius: 14px;
  padding: 22px 24px;
  transition: transform 0.2s, border-color 0.2s;
}
.reserva:hover {
  transform: translateY(-2px);
  border-color: #2a3550;
}
.reserva.cancelada {
  border-left-color: #ff6b6b;
  opacity: 0.7;
}
.reserva-fecha {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.codigo {
  color: #7b8198;
  font-size: 12px;
  letter-spacing: 1px;
}
.dia {
  color: white;
  font-size: 15px;
  font-weight: 600;
  text-transform: capitalize;
}
.hora {
  color: #f4b400;
  font-size: 20px;
  font-weight: 700;
}
.reserva-detalle h3 {
  font-size: 17px;
  margin: 0 0 6px;
  font-weight: 600;
}
.flecha {
  color: #f4b400;
  margin: 0 4px;
}
.reserva-detalle p {
  color: #7b8198;
  font-size: 12px;
  margin: 3px 0 0;
}
.reserva-acciones {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
  min-width: 140px;
}
.estado {
  font-size: 11px;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid;
}
.estado.verde { color: #00ff99; border-color: #00ff99; }
.estado.amarillo { color: #f4b400; border-color: #f4b400; }
.estado.azul { color: #4db8ff; border-color: #4db8ff; }
.estado.rojo { color: #ff6b6b; border-color: #ff6b6b; }
.precio {
  color: #f4b400;
  font-size: 22px;
  font-weight: 700;
}
.boton-cancelar {
  background: transparent;
  border: 1px solid #ff6b6b;
  color: #ff6b6b;
  padding: 6px 14px;
  border-radius: 999px;
  cursor: pointer;
  font-size: 12px;
  transition: 0.2s;
}
.boton-cancelar:hover:not(:disabled) {
  background: #ff6b6b;
  color: #060c22;
}
.boton-cancelar:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.nota {
  color: #7b8198;
  font-size: 11px;
  text-align: right;
}

/* Animación del panel de edición */
.desplegar-enter-active,
.desplegar-leave-active {
  transition: all 0.25s ease;
}
.desplegar-enter-from,
.desplegar-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Responsive */
@media (max-width: 760px) {
  .encabezado h1 { font-size: 30px; }
  .tarjeta-usuario {
    justify-content: center;
    text-align: center;
    padding: 22px;
  }
  .estadisticas { width: 100%; justify-content: space-around; }
  .reserva {
    grid-template-columns: 1fr;
    gap: 14px;
  }
  .reserva-acciones {
    align-items: flex-start;
  }
  .nota { text-align: left; }
}
</style>
