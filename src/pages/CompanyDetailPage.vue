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

/* =========================================================
   PALETA
   ========================================================= */

:host {
  --fondo: #0b1023;
  --panel: #0f1629;
  --borde: #1a2138;
  --texto: #ffffff;
  --texto-suave: #b8bfd4;
  --texto-muted: #7b8198;
  --amarillo: #f4b400;
  --amarillo-hover: #ffcb2b;
}


/* =========================================================
   VOLVER
   ========================================================= */

.volver {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 30px;
  padding: 8px 12px;
  color: #8b92aa;
  text-decoration: none;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.04em;
  border: 1px solid transparent;
  border-radius: 6px;
  transition:
    color 0.2s ease,
    background 0.2s ease,
    border-color 0.2s ease;
}

.volver:hover {
  color: #ffffff;
  background: #0f1629;
  border-color: #1a2138;
}

.volver :deep(.q-icon) {
  color: #f4b400;
}

/* =========================================================
   ESTADOS
   ========================================================= */

.estado-msg {
  padding: 40px 0;
  color: #7b8198;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  letter-spacing: 0.05em;
  text-align: center;
}

.estado-msg.error {
  color: #ff6b6b;
}

/* =========================================================
   ENCABEZADO DE EMPRESA
   ========================================================= */

.encabezado {
  display: flex;
  align-items: center;
  gap: 28px;
  margin-bottom: 32px;
  padding: 0 0 30px;
  border-bottom: 1px solid #1a2138;
}


/* =========================================================
   LOGO
   ========================================================= */

.logo-wrap {
  position: relative;
  width: 128px;
  height: 128px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border-radius: 14px;
  overflow: hidden;
  box-shadow:
    0 12px 30px rgba(0, 0, 0, 0.25);

  /*
    Pequeño borde amarillo muy sutil
  */
  border: 1px solid rgba(244, 180, 0, 0.35);
}

.logo-wrap :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 12px;
}

.logo-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 12px;
  box-sizing: border-box;
  color: #0b1023;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 700;
  text-align: center;
}


/* =========================================================
   INFORMACIÓN DE EMPRESA
   ========================================================= */

.encabezado > div:last-child {
  min-width: 0;
}

/*
  Nombre de empresa
*/

.encabezado h1 {
  margin: 0;
  color: #ffffff;
  font-family: 'Inter', sans-serif;

  /*
    Responsive:
    móvil → ~35px
    escritorio → hasta 52px
  */
  font-size: clamp(2.2rem, 4vw, 3.25rem);
  font-weight: 800;

  /*
    Hace que los títulos grandes se vean
    más elegantes y compactos.
  */
  letter-spacing: -0.04em;
  line-height: 1.05;
  overflow-wrap: anywhere;
}

/*
  Línea/acento debajo del nombre
*/

.encabezado h1::after {
  content: '';
  display: block;
  width: 52px;
  height: 3px;
  margin-top: 14px;
  background: #f4b400;
  border-radius: 20px;
}

/*
  Subtítulo
*/

.subtitulo {
  margin: 14px 0 0;
  color: #7b8198;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}


/* =========================================================
   GRILLA
   ========================================================= */

.grilla {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
  gap: 18px;
  width: 100%;
}


/* =========================================================
   TARJETAS
   ========================================================= */

.tarjeta {
  position: relative;
  min-width: 0;
  background: #0f1629;
  border: 1px solid #1a2138;
  border-radius: 12px;
  padding: 26px;
  box-sizing: border-box;
  transition:
    border-color 0.2s ease,
    transform 0.2s ease;
}

/*
  Una pequeña línea amarilla en la parte superior
  de la tarjeta principal
*/

.tarjeta.descripcion {
  grid-row: 1 / 3;
  grid-column: 1;
  border-top: 2px solid #f4b400;
}

/*
  Hover muy sutil.
  No conviene exagerarlo en un tablero institucional.
*/

.tarjeta:hover {
  border-color: #27304c;
}

/* =========================================================
   TÍTULOS DE LAS TARJETAS
   ========================================================= */

.tarjeta h2 {
  margin: 0 0 18px;
  color: #ffffff;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

/*
  Pequeño detalle amarillo antes del título
*/

.tarjeta h2::before {
  content: '';
  display: inline-block;
  width: 6px;
  height: 6px;
  margin-right: 9px;
  vertical-align: 1px;
  background: #f4b400;
  border-radius: 50%;
}

/* =========================================================
   DESCRIPCIÓN
   ========================================================= */

.tarjeta.descripcion p {
  margin: 0;
  color: #b8bfd4;
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  font-weight: 400;
  line-height: 1.85;
}

/* =========================================================
   BOTÓN COMPRAR
   ========================================================= */

.btn-comprar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  margin-top: 28px;
  min-height: 46px;
  padding: 0 20px;
  box-sizing: border-box;
  background: #f4b400;
  color: #0b1023;
  text-decoration: none;
  border-radius: 7px;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.05em;
  transition:
    background 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.btn-comprar:hover {
  background: #ffcb2b;
  transform: translateY(-1px);
  box-shadow:
    0 7px 20px rgba(244, 180, 0, 0.18);
}

.btn-comprar :deep(.q-icon) {
  font-size: 18px;
}

/* =========================================================
   CONTACTO
   ========================================================= */

.lista-contacto {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.fila-contacto {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: 12px;
  padding: 10px 8px;
  color: #b8bfd4;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  text-decoration: none;
  border-radius: 6px;
  transition:
    color 0.2s ease,
    background 0.2s ease;
}

.fila-contacto:hover {
  color: #ffffff;
  background: rgba(244, 180, 0, 0.04);
}

.fila-contacto .q-icon {
  flex-shrink: 0;
  color: #f4b400;
}

/*
  Evita que emails o URLs rompan
  el diseño en celulares.
*/

.fila-contacto span {
  min-width: 0;
  overflow-wrap: anywhere;
}

/* =========================================================
   SIN DATOS
   ========================================================= */

.sin-datos {
  margin: 0;
  color: #7b8198;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  line-height: 1.7;
}

/* =========================================================
   RUTAS
   ========================================================= */

.lista-rutas {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.fila-ruta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  padding: 12px 0;
  border-bottom: 1px solid #1a2138;
  font-family: 'Inter', sans-serif;
}


.fila-ruta:last-child {
  border-bottom: none;
}

/*
  Trayecto
*/

.trayecto {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: 7px;
  color: #ffffff;
  font-size: 13px;
  font-weight: 500;
}


.trayecto .q-icon {
  flex-shrink: 0;
  color: #7b8198;
}

/*
  Frecuencia
*/

.frecuencia {
  flex-shrink: 0;
  color: #7b8198;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;

  white-space: nowrap;
}

/* =========================================================
   TABLET (adaptacion nueva)
   ========================================================= */

@media (max-width: 900px) {

  .grilla {
    grid-template-columns: 1fr;
  }

  .tarjeta,
  .tarjeta.descripcion {
    grid-column: 1;
    grid-row: auto;
  }

}


/* =========================================================
   CELULAR
   ========================================================= */

@media (max-width: 600px) {

  /*
    Reducimos el espacio general
  */

  .volver {
    margin-bottom: 22px;
  }


  /*
    Encabezado vertical
  */

  .encabezado {
    flex-direction: column;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 24px;
    text-align: center;
  }


  /*
    Logo más chico en celular
  */

  .logo-wrap {
    width: 100px;
    height: 100px;
    border-radius: 12px;
  }


  .logo-wrap :deep(img) {
    padding: 9px;
  }

  /*
    Nombre de empresa
  */

  .encabezado h1 {
    font-size: clamp(2rem, 10vw, 2.7rem);
    line-height: 1.05;
    letter-spacing: -0.04em;
  }

  /*
    Centramos el detalle amarillo
  */

  .encabezado h1::after {
    margin-left: auto;
    margin-right: auto;
  }


  .subtitulo {
    margin-top: 12px;
    font-size: 10px;
    letter-spacing: 0.08em;
    line-height: 1.5;
  }

  /*
    Tarjetas
  */

  .tarjeta {
    padding: 20px;
    border-radius: 10px;
  }

  .tarjeta h2 {
    margin-bottom: 15px;
  }

  /*
    Descripción
  */

  .tarjeta.descripcion p {
    font-size: 14px;
    line-height: 1.75;
  }

  /*
    Botón ocupa todo el ancho.
    Esto es mucho más cómodo con el dedo.
  */

  .btn-comprar {
    width: 100%;
    margin-top: 24px;
    min-height: 48px;
  }

  /*
    Contacto
  */

  .fila-contacto {
    padding: 11px 6px;
    font-size: 12px;
  }


  /*
    Rutas:
    permitimos que la frecuencia baje
    debajo del trayecto si falta espacio.
  */

  .fila-ruta {
    align-items: flex-start;
    flex-direction: column;
    gap: 5px;
    padding: 13px 0;
  }


  .trayecto {
    font-size: 13px;
  }


  .frecuencia {
    margin-left: 21px;

    font-size: 9px;
  }

}


</style>
