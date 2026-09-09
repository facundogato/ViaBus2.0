<!--
  ============================================================
  FICHA DE EMPRESA (ruta /empresas/:id)
  Esto es la página donde ves TODA la info de UNA empresa.

  El truco copado: es UNA sola página para las 7 empresas.
  ¿Cómo? El ":id" de la ruta es como un hueco: si entrás a
  /empresas/1 muestra la empresa 1, si entrás a /empresas/5
  muestra la empresa 5. Misma página, distinto contenido.
  Así, si mañana agregan una empresa nueva en la base de datos,
  no hay que tocar NADA del frontend: la página ya existe.
  ============================================================
-->

<template>

  <!-- El "cascarón" de siempre: menú y encabezado general -->
  <MainLayout>

    <!-- Link de "volver atrás" que te manda a la página de info
         general. router-link es como un <a> pero de Vue: navega
         sin recargar toda la página -->
    <router-link to="/info" class="volver">
      <q-icon name="arrow_back" size="16px" />
      Info general
    </router-link>

    <!-- Acá empiezan los 3 posibles estados de la página:
         1) cargando, 2) error, 3) todo bien (mostrar empresa) -->

    <!-- 1) Mientras se trae la info del servidor, aparece esto -->
    <p v-if="cargando" class="estado-msg">Cargando información de la empresa...</p>

    <!-- 2) Si algo falló (ej: no existe esa empresa), se muestra
            el mensaje de error en rojo -->
    <div v-else-if="error" class="estado-msg error">
      {{ error }}
    </div>

    <!-- 3) Si todo salió bien Y hay empresa cargada, se muestra
            TODO lo de abajo (la ficha completa) -->
    <template v-else-if="empresa">

      <!-- =========== ENCABEZADO: logo + nombre =========== -->
      <div class="encabezado">
        <div class="logo-wrap">
          <!-- Si la empresa tiene logo, se muestra la imagen.
               q-img es el componente de imágenes de Quasar -->
          <q-img v-if="logo" :src="logo" :alt="empresa.nombre" />
          <!-- Si NO tiene logo, se dibuja un reemplazo: un cajita
               blanca con el nombre de la empresa escrito.
               (El v-else significa "si no pasó lo de arriba...") -->
          <div v-else class="logo-placeholder">{{ empresa.nombre }}</div>
        </div>

        <div>
          <!-- El nombre grande de la empresa -->
          <h1>{{ empresa.nombre }}</h1>
          <!-- Subtítulo fijo de debajo -->
          <p class="subtitulo">Empresa de transporte de pasajeros</p>
        </div>
      </div>

      <!-- La grilla con las 3 tarjetas de contenido -->
      <div class="grilla">

        <!-- =========== TARJETA 1: DESCRIPCIÓN =========== -->
        <div class="tarjeta descripcion">
          <h2>SOBRE LA EMPRESA</h2>
          <!-- Se muestra la descripción de la empresa. Si no tiene
               ninguna cargada, se muestra ese texto por defecto.
               (El || significa: "usa lo de la izquierda, y si está
               vacío, usa lo de la derecha") -->
          <p>{{ empresa.descripcion || 'Todavía no hay una descripción cargada para esta empresa.' }}</p>

          <!-- Botón amarillo que te lleva a la página de compra,
               para ver los horarios de ESTA empresa y sacar pasaje -->
          <router-link to="/comprar" class="btn-comprar">
            <q-icon name="receipt" size="18px" />
            Ver horarios y comprar pasaje
          </router-link>
        </div>

        <!-- =========== TARJETA 2: CONTACTO =========== -->
        <div class="tarjeta">
          <h2>CONTACTO</h2>

          <!-- Si la empresa tiene AL MENOS un dato de contacto,
               se muestra la lista. Si no, el mensajito de abajo -->
          <div v-if="tieneContacto" class="lista-contacto">

            <!-- Teléfono: solo aparece SI tiene teléfono.
                 El href con "tel:" hace que en el celular, al
                 tocarlo, te marque directo. El .replace borra
                 los espacios del número (los "tel:" no los aceptan) -->
            <a v-if="empresa.telefono" :href="`tel:${empresa.telefono.replace(/\s/g, '')}`" class="fila-contacto">
              <q-icon name="call" size="18px" />
              <span>{{ empresa.telefono }}</span>
            </a>

            <!-- Email: al tocarlo se abre tu programa de correo
                 con el destinatario ya puesto (mailto:) -->
            <a v-if="empresa.email" :href="`mailto:${empresa.email}`" class="fila-contacto">
              <q-icon name="mail" size="18px" />
              <span>{{ empresa.email }}</span>
            </a>

            <!-- Sitio web: abre la página de la empresa en una
                 pestaña nueva (target="_blank"). La dirección la
                 arregla urlSitioWeb (explicado más abajo) -->
            <a v-if="empresa.sitio_web" :href="urlSitioWeb" target="_blank" rel="noopener" class="fila-contacto">
              <q-icon name="language" size="18px" />
              <span>{{ empresa.sitio_web }}</span>
            </a>

            <!-- Dirección: esto no es un link, es solo texto
                 (no podés "clickear" una dirección acá) -->
            <div v-if="empresa.direccion" class="fila-contacto">
              <q-icon name="place" size="18px" />
              <span>{{ empresa.direccion }}</span>
            </div>

          </div>
          <!-- Si la empresa no cargó ningún contacto, este aviso -->
          <p v-else class="sin-datos">
            Esta empresa todavía no cargó su información de contacto.
          </p>
        </div>

        <!-- =========== TARJETA 3: RUTAS =========== -->
        <div class="tarjeta rutas">
          <h2>RUTAS QUE OPERA</h2>

          <!-- Si la empresa tiene rutas, se lista una por una.
               El v-for recorre la lista y dibuja una fila por ruta -->
          <div v-if="empresa.rutas.length > 0" class="lista-rutas">
            <div v-for="(r, i) in empresa.rutas" :key="i" class="fila-ruta">
              <!-- El trayecto: "Origen → Destino" (con flechita) -->
              <span class="trayecto">{{ r.origen }} <q-icon name="arrow_forward" size="14px" /> {{ r.destino }}</span>
              <!-- Cuántos servicios hace por día. Y acá un detalle
                   fino: si es 1, dice "servicio/día" (singular), si
                   son más, "servicios/día" (plural) -->
              <span class="frecuencia">{{ r.frecuencias }} {{ r.frecuencias === 1 ? 'servicio/día' : 'servicios/día' }}</span>
            </div>
          </div>
          <!-- Si no tiene rutas activas, este aviso -->
          <p v-else class="sin-datos">
            Esta empresa no tiene servicios activos en este momento.
          </p>
        </div>

      </div>

    </template>

  </MainLayout>

</template>

<script setup>
// ============================================================
// PARTE LÓGICA: el cerebro de la ficha de empresa.
// Su trabajo: fijarse qué empresa pidió ver el usuario (por el
// número de la ruta), pedirle esa info al servidor y mostrarla.
// ============================================================

// Herramientas de Vue:
// - ref: variables que al cambiar actualizan la pantalla
// - computed: valores que se calculan solos a partir de otros
// - onMounted: "cuando la página cargue, hacé esto"
// - watch: "estoy atento a que algo cambie, y cuando cambie, hacé esto"
import { ref, computed, onMounted, watch } from 'vue'
// useRoute nos deja leer la dirección de la página actual
// (para sacar el número de empresa que quiere ver el usuario)
import { useRoute } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
// La función que le pide al servidor la info de UNA empresa
import { getEmpresa } from '../services/api'

// ---- Los logos ----
// import.meta.glob busca TODOS los archivos que estén en la carpeta
// ../assets/empresas/ y los mete en la variable "logos" de una.
// Es como decir: "traeme todas las fotos de logos que haya ahí".
// (eager: true = que los cargue todos de una, de entrada)
const logos = import.meta.glob('../assets/empresas/*', { eager: true, import: 'default' })

// Esta función busca el logo de una empresa por el nombre del
// archivo. Ej: si la empresa dice que su logo es "ega.png",
// recorre la carpeta hasta encontrarlo y devuelve la foto.
// Si no encuentra nada (o no había nombre), devuelve null.
function logoDe(nombreArchivo) {
  if (!nombreArchivo) return null
  const entry = Object.entries(logos).find(([path]) => path.endsWith('/' + nombreArchivo))
  return entry ? entry[1] : null
}

// La ruta actual (la dirección de la página que estás viendo)
const route = useRoute()

// ---- Variables de estado ----
// La empresa que se está mostrando (empieza vacía)
const empresa = ref(null)
// ¿Estamos cargando? (para el "Cargando información...")
const cargando = ref(false)
// Si algo falla, el mensaje se guarda acá
const error = ref('')

// El logo listo para mostrar: si hay empresa, busca su logo;
// si no hay empresa todavía, da null (y por eso se muestra
// el reemplazo con el nombre)
const logo = computed(() => empresa.value ? logoDe(empresa.value.logo) : null)

// ¿Tiene la empresa AL MENOS un dato de contacto?
// Revisa teléfono, email, sitio web y dirección. Si hay al
// menos uno, responde verdadero. (!!"algo" = convertirlo a
// verdadero/falso de una)
const tieneContacto = computed(() => {
  if (!empresa.value) return false
  const e = empresa.value
  return !!(e.telefono || e.email || e.sitio_web || e.direccion)
})

// Arregla el link del sitio web. En la base de datos el sitio se
// guarda como "www.ega.com.uy" (sin el https://). Pero si ponés
// eso directo en un link, el navegador se lo cree un archivo local
// y no funciona. Entonces acá le agregamos el https:// adelante.
// (Si ya lo tuviera, se deja como está)
const urlSitioWeb = computed(() => {
  const web = empresa.value?.sitio_web
  if (!web) return ''
  return web.startsWith('http') ? web : `https://${web}`
})

// Esta función pide al servidor la info de UNA empresa según su id.
// Es async porque tarda (habla con internet).
async function cargarEmpresa(id) {
  cargando.value = true   // avisamos "cargando..."
  error.value = ''        // borramos errores viejos
  empresa.value = null    // limpiamos la empresa anterior
  try {
    // Le pedimos la empresa al servidor y la guardamos
    empresa.value = await getEmpresa(id)
  } catch (e) {
    // Si falló (ej: no existe ese id), guardamos el mensaje
    error.value = e.message
  } finally {
    // Termine como termine, dejamos de "cargando"
    cargando.value = false
  }
}

// Cuando la página carga por primera vez, mira el número que está
// en la dirección (route.params.id) y carga esa empresa.
// Ej: estás en /empresas/3 → carga la empresa con id 3.
onMounted(() => cargarEmpresa(route.params.id))

// DETALLE IMPORTANTE: si el usuario salta de una ficha a otra
// SIN recargar la página (por ejemplo desde el menú lateral),
// Vue reutiliza esta misma página (no la "apaga y prende"), así
// que onMounted NO se vuelve a ejecutar. Por eso el watch queda
// mirando el número de la ruta: en cuanto cambia, vuelve a pedir
// los datos de la empresa nueva. Sin esto, verías la empresa vieja.
watch(() => route.params.id, (nuevoId) => cargarEmpresa(nuevoId))
</script>


<style scoped>

.volver {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #7b8198;
  text-decoration: none;
  font-size: 12px;
  letter-spacing: 1px;
  margin-bottom: 25px;
  transition: color 0.2s;
}

.volver:hover {
  color: #f4b400;
}

.estado-msg {
  color: #7b8198;
  font-size: 13px;
  letter-spacing: 1px;
}

.estado-msg.error {
  color: #ff6b6b;
}

.encabezado {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 30px;
  padding-bottom: 25px;
  border-bottom: 1px solid #1a2138;
}

.logo-wrap {
  width: 110px;
  height: 110px;
  background: white;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-wrap :deep(img) {
  object-fit: contain;
}

.logo-placeholder {
  color: #0b1023;
  font-weight: bold;
  text-align: center;
  padding: 8px;
  font-size: 12px;
}

.encabezado h1 {
  color: white;
  font-size: 30px;
  letter-spacing: 1px;
}

.subtitulo {
  color: #7b8198;
  font-size: 12px;
  letter-spacing: 1px;
  margin-top: 4px;
}

.grilla {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.tarjeta {
  background: #0f1629;
  border: 1px solid #1a2138;
  border-radius: 10px;
  padding: 24px;
  grid-column: 2;
}

.tarjeta.descripcion {
  grid-row: 1 / 3;
  grid-column: 1;
}

.tarjeta h2 {
  color: white;
  font-size: 13px;
  letter-spacing: 2px;
  margin-bottom: 16px;
}

.tarjeta.descripcion p {
  color: #b8bfd4;
  font-size: 14px;
  line-height: 1.8;
}

.btn-comprar {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 24px;
  padding: 12px 22px;
  background: #f4b400;
  color: #0b1023;
  text-decoration: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: bold;
  letter-spacing: 1px;
  transition: background 0.2s;
}

.btn-comprar:hover {
  background: #ffcb2b;
}

.sin-datos {
  color: #7b8198;
  font-size: 13px;
  line-height: 1.6;
}

.lista-contacto {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.fila-contacto {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #b8bfd4;
  font-size: 13px;
  text-decoration: none;
  transition: color 0.2s;
}

a.fila-contacto {
  cursor: pointer;
}

a.fila-contacto:hover {
  color: #f4b400;
}

.fila-contacto .q-icon {
  color: #f4b400;
}

.lista-rutas {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.fila-ruta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #1a2138;
  font-size: 13px;
}

.fila-ruta:last-child {
  border-bottom: none;
}

.trayecto {
  color: white;
  display: flex;
  align-items: center;
  gap: 6px;
}

.trayecto .q-icon {
  color: #7b8198;
}

.frecuencia {
  color: #7b8198;
  font-size: 11px;
  letter-spacing: 1px;
}

@media (max-width: 900px) {
  .grilla {
    grid-template-columns: 1fr;
  }
  .tarjeta,
  .tarjeta.descripcion {
    grid-column: 1;
    grid-row: auto;
  }
  .encabezado {
    flex-direction: column;
    text-align: center;
  }
}

</style>
