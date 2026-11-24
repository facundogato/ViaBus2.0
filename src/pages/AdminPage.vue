<<!-- Esto es el PANEL DEL ADMINISTRADOR.
     Acá el admin puede: crear viajes nuevos, editar los que ya
     existen y "borrarlos" (en realidad los desactiva).
     Solo entra acá quien tiene rol de admin -->

<template>

<!-- Todo lo que está acá adentro es lo que se VE en la pantalla -->

  <!-- MainLayout es como el "cascarón" de la página: el menú,
       el encabezado, etc. Todo lo demás va adentro de él. -->
  <MainLayout>

    <!-- =========== PARTE DE ARRIBA: título + botón =========== -->
    <div class="superior">
      <div>
        <!-- El título grande de la página -->
        <h1>PANEL DE ADMINISTRADOR</h1>
        <!-- Un texto chiquito explicando para qué sirve -->
        <p>Gestioná los viajes que se ofrecen en la terminal</p>
      </div>

      <!-- Este botón abre el formulario para crear un viaje nuevo.
           El @click significa: "cuando me hagan clic, hacé esto" -->
      <button class="btn-nuevo" @click="abrirFormularioNuevo">
        <!-- El ícono del "+" y el texto del botón -->
        <q-icon name="add" size="18px" />
        NUEVO VIAJE
      </button>
    </div>

    <!-- Si hubo un error general (por ejemplo, falló la conexión con
         el servidor), se muestra este mensaje en rojo. El v-if hace
         que solo aparezca SI hay error, si no, no se ve nada. -->
    <p v-if="errorGeneral" class="error-msg">{{ errorGeneral }}</p>

    <!-- =========== FORMULARIO (para crear o editar) =========== -->
    <!-- Este bloque solo aparece si formularioAbierto es true,
         o sea, si el admin apretó "Nuevo viaje" o el lápiz de editar -->
    <div v-if="formularioAbierto" class="panel-formulario">

      <!-- El título cambia según lo que estemos haciendo:
           si editamos dice "EDITAR VIAJE", si no, "NUEVO VIAJE" -->
      <h2>{{ editando ? 'EDITAR VIAJE' : 'NUEVO VIAJE' }}</h2>

      <div class="formulario">

        <!-- IMPORTANTE: origen, destino y empresa SOLO se eligen
             cuando se crea el viaje. Si estás editando, quedan
             bloqueados (por eso el :disabled="editando") -->

        <!-- Lista desplegable para elegir de dónde sale el bus -->
        <div class="campo">
          <label>ORIGEN</label>
          <select v-model="form.origen" :disabled="editando">
            <!-- Recorre la lista de ciudades y crea una opción por cada una -->
            <option v-for="c in ciudades" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>

        <!-- Lista desplegable para elegir a dónde va el bus -->
        <div class="campo">
          <label>DESTINO</label>
          <select v-model="form.destino" :disabled="editando">
            <option v-for="c in ciudades" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>

        <!-- Lista desplegable para elegir la empresa del bus -->
        <div class="campo">
          <label>EMPRESA</label>
          <select v-model="form.empresa" :disabled="editando">
            <option v-for="e in empresas" :key="e.id" :value="e.nombre">{{ e.nombre }}</option>
          </select>
        </div>

        <!-- Campo para poner la hora de salida (aparece un relojito) -->
        <div class="campo">
          <label>HORA</label>
          <input type="time" v-model="form.hora" />
        </div>

        <!-- Tipo de servicio, por ejemplo "De Línea" -->
        <div class="campo">
          <label>TIPO</label>
          <input type="text" v-model="form.tipo" placeholder="De Linea" />
        </div>

        <!-- Cuánto tarda el viaje, ej: "5h 44m" -->
        <div class="campo">
          <label>DURACIÓN</label>
          <input type="text" v-model="form.duracion" placeholder="5h 44m" />
        </div>

        <!-- Precio del pasaje. El v-model.number hace que lo que
             escribís se guarde como número, no como texto -->
        <div class="campo">
          <label>PRECIO</label>
          <input type="number" v-model.number="form.precio" min="0" step="10" />
        </div>

        <!-- Cantidad de asientos que tiene el bus -->
        <div class="campo">
          <label>ASIENTOS</label>
          <input type="number" v-model.number="form.asientos" min="1" />
        </div>

        <!-- Patente o identificación del bus -->
        <div class="campo">
          <label>BUS</label>
          <input type="text" v-model="form.bus" placeholder="MELMON-1234" />
        </div>

      </div>

      <!-- Si falta completar algo obligatorio, acá se avisa -->
      <p v-if="errorFormulario" class="error-msg">{{ errorFormulario }}</p>

      <!-- Botones del formulario: cancelar o guardar -->
      <div class="acciones">
        <!-- Este botón cierra el formulario sin guardar nada -->
        <button class="btn-volver" @click="cerrarFormulario">Cancelar</button>

        <!-- Este botón guarda. Mientras está guardando se desactiva
             (para que no le den clic 5 veces) y dice "Guardando...".
             El texto cambia según si es nuevo viaje o edición -->
        <button class="btn-siguiente" :disabled="guardando" @click="guardar">
          {{ guardando ? 'Guardando...' : (editando ? 'Guardar cambios' : 'Crear viaje') }}
        </button>
      </div>

    </div>

    <!-- =========== LISTADO DE TODOS LOS VIAJES =========== -->
    <h2 class="titulo-lista">VIAJES ACTIVOS</h2>

    <!-- Tres casos posibles:
         1) Si está cargando, muestra "Cargando viajes..."
         2) Si terminó de cargar y no hay nada, muestra ese aviso
         3) Si hay viajes, muestra la tabla -->
    <p v-if="cargando" class="sin-resultados">Cargando viajes...</p>
    <p v-else-if="servicios.length === 0" class="sin-resultados">No hay viajes cargados todavía.</p>

    <div v-else class="tabla">

      <!-- La fila de encabezado: los nombres de las columnas -->
      <div class="tabla-header">
        <div>Origen → Destino</div>
        <div>Empresa</div>
        <div>Hora</div>
        <div>Tipo</div>
        <div>Precio</div>
        <div>Estado</div>
        <div>Acciones</div>
      </div>

      <!-- Acá se repite una fila por CADA viaje que exista.
           El v-for es como decir "por cada viaje s de la lista, dibujá una fila" -->
      <div v-for="s in servicios" :key="s.id" class="tabla-fila">
        <!-- Muestra "Ciudad A → Ciudad B" -->
        <div>{{ s.origen }} → {{ s.destino }}</div>
        <div>{{ s.empresa }}</div>
        <div>{{ s.hora }}</div>
        <div>{{ s.tipo }}</div>
        <!-- El $ va pegado al precio -->
        <div>${{ s.precio }}</div>
        <!-- StatusBadge es una etiquetita de color que muestra
             si el viaje está activo, lleno, etc. -->
        <div><StatusBadge :tipo="s.estado" /></div>

        <!-- Los dos botones de cada fila: editar y borrar -->
        <div class="fila-acciones">
          <!-- Lápiz: abre el formulario con los datos de ESTE viaje -->
          <button class="btn-icono" title="Editar" @click="abrirFormularioEdicion(s)">
            <q-icon name="edit" size="16px" />
          </button>
          <!-- Tachito: desactiva el viaje (con confirmación antes) -->
          <button class="btn-icono btn-icono-borrar" title="Desactivar" @click="borrar(s)">
            <q-icon name="delete" size="16px" />
          </button>
        </div>
      </div>

    </div>

  </MainLayout>

</template>

<script setup>
// ============================================================
// PARTE LÓGICA: acá está "el cerebro" de la página, lo que
// pasa por detrás cuando tocás los botones.
// ============================================================

// Importamos herramientas de Vue: ref sirve para crear variables
// que cuando cambian, la página se actualiza sola.
import { ref, onMounted } from 'vue'

// Importamos el diseño general y la etiqueta de estado
import MainLayout from '../layouts/MainLayout.vue'
import StatusBadge from '../components/common/StatusBadge.vue'

// Importamos las funciones que hablan con el servidor
// (crear, editar, borrar, traer datos, etc.)
import {
  getTodosServicios,   // trae todos los viajes
  getCiudades,         // trae la lista de ciudades
  getEmpresas,         // trae la lista de empresas
  crearServicio,       // crea un viaje nuevo
  actualizarServicio,  // edita un viaje existente
  eliminarServicio     // desactiva un viaje
} from '../services/api'

// ---- Variables que guarda la página ----

// La lista de viajes (empieza vacía)
const servicios = ref([])
// Las ciudades para los desplegables
const ciudades = ref([])
// Las empresas para el desplegable
const empresas = ref([])
// Bandera: ¿estamos cargando datos del servidor? (para mostrar "Cargando...")
const cargando = ref(false)
// Si algo falla a lo grande, el mensaje se guarda acá
const errorGeneral = ref('')

// Esta función trae TODO del servidor: viajes, ciudades y empresas.
// "async" significa que puede tardar un rato (habla con internet).
async function cargarTodo() {
  cargando.value = true        // avisamos "empecé a cargar"
  errorGeneral.value = ''      // borramos errores viejos
  try {
    // Promise.all pide las 3 cosas AL MISMO TIEMPO (más rápido
    // que pedirlas de a una). Espera a que lleguen las tres.
    const [listaServicios, listaCiudades, listaEmpresas] = await Promise.all([
      getTodosServicios(),
      getCiudades(),
      getEmpresas()
    ])
    // Guardamos todo en nuestras variables.
    // De las ciudades solo nos interesa el nombre, por eso el .map
    servicios.value = listaServicios
    ciudades.value = listaCiudades.map(c => c.nombre)
    empresas.value = listaEmpresas
  } catch (e) {
    // Si algo falló, guardamos el error para mostrarlo en pantalla
    errorGeneral.value = e.message
  } finally {
    // Esto SIEMPRE se ejecuta, salga bien o mal: dejamos de cargar
    cargando.value = false
  }
}

// onMounted = "en cuanto la página cargue, hacé esto".
// O sea: apenas abrís el panel, se trae todo automáticamente.
onMounted(cargarTodo)

// ---- Todo lo relacionado al formulario ----

// ¿El formulario está abierto en pantalla?
const formularioAbierto = ref(false)
// Si es null → estamos creando uno nuevo.
// Si tiene un número → es el id del viaje que estamos editando.
const editando = ref(null)
// ¿Estamos guardando en este momento? (para desactivar el botón)
const guardando = ref(false)
// Mensaje de error del formulario (ej: "faltó poner la hora")
const errorFormulario = ref('')

// Esta función devuelve un formulario "en blanco" con valores
// por defecto para no empezar de cero.
function formularioVacio() {
  return {
    origen: ciudades.value[0] || '',      // primera ciudad de la lista
    destino: ciudades.value[1] || '',     // segunda ciudad
    empresa: empresas.value[0]?.nombre || '', // primera empresa
    hora: '',                             // sin hora todavía
    tipo: 'De Linea',                     // valor sugerido
    duracion: '',
    precio: null,
    asientos: 40,                         // asumo un bus de 40
    bus: ''
  }
}

// El formulario en sí, arranca vacío
const form = ref(formularioVacio())

// Se apretó "NUEVO VIAJE": preparamos todo para crear desde cero
function abrirFormularioNuevo() {
  editando.value = null          // no estamos editando nada
  errorFormulario.value = ''     // borramos errores viejos
  form.value = formularioVacio() // formulario limpio
  formularioAbierto.value = true // ¡abrimos el formulario!
}

// Se apretó el lápiz de algún viaje: abrimos el formulario
// PERO relleno con los datos de ese viaje
function abrirFormularioEdicion(servicio) {
  editando.value = servicio.id   // recordamos cuál viaje estamos editando
  errorFormulario.value = ''
  // Copiamos cada dato del viaje al formulario
  form.value = {
    origen: servicio.origen,
    destino: servicio.destino,
    empresa: servicio.empresa,
    hora: servicio.hora,
    tipo: servicio.tipo,
    duracion: servicio.duracion,
    precio: servicio.precio,
    asientos: servicio.asientos,
    bus: servicio.bus
  }
  formularioAbierto.value = true
}

// Botón "Cancelar": solo cerramos el formulario sin guardar nada
function cerrarFormulario() {
  formularioAbierto.value = false
}

// Se apretó "Guardar": acá pasa la magia (o los errores 😅)
async function guardar() {
  // Primera revisión: la hora y el tipo son obligatorios.
  // Si falta alguno, mostramos el aviso y no seguimos.
  if (!form.value.hora || !form.value.tipo) {
    errorFormulario.value = 'Hora y tipo son obligatorios'
    return
  }

  guardando.value = true       // "estoy guardando" (botón desactivado)
  errorFormulario.value = ''   // borramos errores viejos
  try {
    // ¿Estamos EDITANDO o CREANDO? Depends del valor de editando
    if (editando.value) {
      // EDITANDO: por regla del negocio, al editar un viaje que ya
      // existe NO se puede cambiar origen/destino/empresa.
      // Solo extraemos los campos que sí se pueden tocar...
      const { hora, tipo, duracion, precio, asientos, bus } = form.value
      // ...y mandamos solo esos al servidor
      await actualizarServicio(editando.value, { hora, tipo, duracion, precio, asientos, bus })
    } else {
      // CREANDO: acá sí hace falta que origen, destino y empresa
      // estén completos. Si falta algo, aviso y me voy.
      if (!form.value.origen || !form.value.destino || !form.value.empresa) {
        errorFormulario.value = 'Origen, destino y empresa son obligatorios'
        guardando.value = false
        return
      }
      // Todo bien: mandamos el formulario completo para crear el viaje
      await crearServicio(form.value)
    }
    // Si salió todo bien: cerramos el formulario y recargamos la
    // lista para que el viaje nuevo/editado aparezca al toque.
    formularioAbierto.value = false
    await cargarTodo()
  } catch (e) {
    // Si el servidor rechazó algo, mostramos su mensaje
    errorFormulario.value = e.message
  } finally {
    guardando.value = false // dejamos de "guardando" siempre
  }
}

// Se apretó el tachito de un viaje: lo desactivamos
async function borrar(servicio) {
  // Primero preguntamos "¿estás seguro?" con un mensajito.
  // Si dice que no, no hacemos nada (el return corta todo).
  if (!confirm(`¿Desactivar el viaje servicio.origen→{servicio.origen} →servicio.origen→{servicio.destino} (${servicio.hora})?`)) return
  try {
    // Le avisamos al servidor que desactive ese viaje
    await eliminarServicio(servicio.id)
    // Recargamos la lista para que ya no aparezca
    await cargarTodo()
  } catch (e) {
    // Si falló, mostramos el error general arriba
    errorGeneral.value = e.message
  }
}
</script>

<style scoped>

.superior{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  padding-bottom:20px;
  border-bottom:1px solid #1a2138;
  margin-bottom:30px;
}

h1{
  font-size:38px;
  letter-spacing:3px;
  color:white;
}

.superior p{
  color:#7b8198;
  margin-top:8px;
  font-size:13px;
  letter-spacing:1px;
}

.btn-nuevo{
  background:#f4b400;
  color:black;
  border:none;
  padding:12px 22px;
  font-weight:bold;
  font-size:13px;
  letter-spacing:1px;
  cursor:pointer;
  display:flex;
  align-items:center;
  gap:8px;
  border-radius:4px;
  transition:0.2s;
}

.btn-nuevo:hover{
  background:#e0a300;
}

/* FORMULARIO */

.panel-formulario{
  border:1px solid #1a2138;
  border-radius:6px;
  padding:25px;
  margin-bottom:35px;
  background: var(--color-tarjeta, #0a0f22);
}

h2{
  color: var(--color-texto-suave, #cfd2e0);
  font-size:16px;
  letter-spacing:2px;
  margin-bottom:25px;
}

.titulo-lista{
  margin-top:10px;
}

.formulario{
  display:flex;
  gap:15px;
  align-items:flex-end;
  flex-wrap:wrap;
}

.campo{
  background: var(--color-tarjeta, #0a0f22);
  display:flex;
  flex-direction:column;
  gap:8px;
}

.campo label{
  color: var(--color-texto-suave, #cfd2e0);
  font-size:11px;
  letter-spacing:2px;
}

.campo select,
.campo input{
  background: var(--color-tarjeta, #0a0f22);
  border:1px solid #1a2138;
  color:white;
  padding:12px 15px;
  border-radius:4px;
  font-size:14px;
  min-width:160px;
}

.campo select:disabled{
  opacity:0.5;
  cursor:not-allowed;
}

.acciones{
  display:flex;
  gap:15px;
  margin-top:25px;
}

.btn-volver{
  background: var(--color-tarjeta);
  border:1px solid #1a2138;
  color:#7b8198;
  padding:12px 25px;
  cursor:pointer;
  font-size:13px;
  letter-spacing:1px;
  border-radius:4px;
  transition:0.2s;
}

.btn-volver:hover{
  border-color:#7b8198;
  color:white;
}

.btn-siguiente{
  background:#f4b400;
  border:none;
  color:black;
  padding:12px 30px;
  cursor:pointer;
  font-size:13px;
  font-weight:bold;
  letter-spacing:1px;
  border-radius:4px;
  transition:0.2s;
}

.btn-siguiente:disabled{
  opacity:0.4;
  cursor:not-allowed;
}

.btn-siguiente:not(:disabled):hover{
  background:#e0a300;
}

/* TABLA */

.tabla{
  border:1px solid #1a2138;
  border-radius:6px;
  overflow:hidden;
}

.tabla-header,
.tabla-fila{
  display:grid;
  grid-template-columns:1.6fr 1fr 0.7fr 1fr 0.7fr 1fr 0.8fr;
  align-items:center;
  padding:14px 20px;
  gap:10px;
}

.tabla-header{
  background: var(--color-tarjeta, #0a0f22);
  color:#7b8198;
  font-size:11px;
  letter-spacing:1px;
}

.tabla-fila{
  border-top:1px solid #1a2138;
  color:white;
  font-size:13px;
}

.fila-acciones{
  display:flex;
  gap:8px;
}

.btn-icono{
  background: var(--color-sidebar, #0a0f22);
  border:1px solid #1a2138;
  color:#7b8198;
  width:30px;
  height:30px;
  border-radius:4px;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  transition:0.2s;
}

.btn-icono:hover{
  color:white;
  border-color:#7b8198;
}

.btn-icono-borrar:hover{
  color:#ff6b6b;
  border-color:#ff6b6b;
}

/* ESTADOS / ERRORES */

.error-msg{
  color:#ff6b6b;
  font-size:13px;
  margin-top:15px;
  margin-bottom: 15px;
  letter-spacing:0.5px;
}

.sin-resultados{
  color:#7b8198;
  font-size:13px;
  margin-bottom:20px;
  letter-spacing:0.5px;
}

@media (max-width: 900px) {
  .superior {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  h1 {
    font-size: 26px;
  }

  .formulario {
    flex-direction: column;
    align-items: stretch;
  }

  .campo select,
  .campo input {
    min-width: 0;
    width: 100%;
  }

  /* La tabla no se apila (rompería las columnas de datos): se hace
     scrollable horizontalmente para no desbordar la pantalla. */
  .tabla {
    overflow-x: auto;
  }

  .tabla-header,
  .tabla-fila {
    grid-template-columns: 160px 120px 70px 100px 80px 110px 90px;
    min-width: 700px;
  }
}

</style>
