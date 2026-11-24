
<!-- Página de preguntas frecuentes (ruta /consultas) -->
<template>
  <MainLayout>
    <div class="consultas">

      <!-- ENCABEZADO -->
      <header class="encabezado">
        <h1>Preguntas <span>frecuentes</span></h1>
        <p>
          Encontrá respuestas a tus dudas sobre viajes, reservas y servicios.
        </p>
      </header>

      <!-- BUSCADOR -->
      <section class="tarjeta-buscador">
        <div class="buscador-icono">
          <q-icon name="search" size="24px" />
        </div>

        <div class="buscador-contenido">
          <label for="faq-busqueda">¿Qué necesitás saber?</label>
          <input
            id="faq-busqueda"
            v-model="busqueda"
            type="search"
            autocomplete="off"
            placeholder="Ej.: horarios, equipaje, mascotas..."
          />
          <span class="buscador-ayuda">
            Escribí una palabra o frase para encontrar tu respuesta.
          </span>
        </div>

        <div class="contador-preguntas">
          <span class="contador-numero">{{ visibles.length }}</span>
          <span class="contador-texto">
            {{ busqueda.trim() ? 'Resultados' : 'Preguntas' }}
          </span>
        </div>
      </section>

      <!-- CARGANDO -->
      <div v-if="cargando" class="estado-vacio" role="status">
        <q-icon name="sync" size="38px" class="icono-cargando" />
        <h3>Cargando consultas</h3>
        <p>Estamos preparando las respuestas para vos...</p>
      </div>

      <!-- ERROR -->
      <div v-else-if="fallo" class="estado-vacio estado-error" role="alert">
        <q-icon name="cloud_off" size="42px" />
        <h3>No pudimos cargar las preguntas</h3>
        <p>Ocurrió un problema al conectar con el servidor.</p>

        <button
          type="button"
          class="boton-principal"
          @click="cargar"
        >
          <q-icon name="refresh" size="17px" />
          Reintentar
        </button>
      </div>

      <template v-else>

        <!-- INFORMACIÓN DE RESULTADOS -->
        <div class="barra-seccion">
          <div class="seccion-titulo">
            <span class="seccion-indicador"></span>
            <h2>
              {{ busqueda.trim() ? 'Resultados de búsqueda' : '¿En qué podemos ayudarte?' }}
            </h2>
          </div>

          <span class="texto-resultados" v-if="busqueda.trim()">
            {{ visibles.length }} de {{ preguntas.length }}
          </span>
        </div>

        <!-- SIN RESULTADOS -->
        <div v-if="!visibles.length" class="estado-vacio">
          <q-icon name="search_off" size="44px" />
          <h3>No encontramos resultados</h3>
          <p>
            Probá con otras palabras o consultale directamente a nuestro asistente.
          </p>

          <button
            type="button"
            class="boton-principal"
            @click="abrirChat"
          >
            <q-icon name="chat" size="17px" />
            Consultar al asistente
          </button>
        </div>

        <!-- LISTA DE PREGUNTAS -->
        <div v-else class="lista-preguntas">
          <details
            v-for="(p, index) in visibles"
            :key="p.id"
            class="pregunta-item"
          >
            <summary>
              <span class="pregunta-numero">
                {{ String(index + 1).padStart(2, '0') }}
              </span>

              <span class="pregunta-texto">
                {{ p.pregunta }}
              </span>

              <span class="pregunta-flecha">
                <q-icon name="expand_more" size="22px" />
              </span>
            </summary>

            <div class="respuesta-contenido">
              <div class="respuesta-indicador"></div>
              <p class="respuesta-texto">{{ p.respuesta }}</p>
            </div>
          </details>
        </div>

        <!-- ACCESO AL CHAT -->
        <section class="tarjeta-asistente">
          <div class="asistente-icono">
            <q-icon name="support_agent" size="34px" />
          </div>

          <div class="asistente-info">
            <h3>¿No encontraste lo que buscabas?</h3>
            <p>
              Nuestro asistente puede ayudarte con tus consultas.
              Escribile y encontrá la información que necesitás.
            </p>
          </div>

          <button
            type="button"
            class="boton-principal boton-asistente"
            @click="abrirChat"
          >
            <q-icon name="chat" size="18px" />
            Hablar con el asistente
          </button>
        </section>

      </template>
    </div>
  </MainLayout>
</template>


<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'

const route = useRoute()

const props = defineProps({
  endpoint: {
    type: String,
    default: '/api/ChatBot/consultas.php',
  },
})

const preguntas = ref([])
const busqueda = ref('')
const cargando = ref(true)
const fallo = ref(false)

// Sube la página al inicio suavemente.
function scrollIntoTop() {
  window.scrollTo({
    top: 0,
    left: 0,
    behavior: 'smooth',
  })
}

// Normaliza el texto para buscar sin distinguir
// mayúsculas, minúsculas ni acentos.
function normalizar(texto) {
  return String(texto ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

// Todas las palabras buscadas deben aparecer
// en la pregunta o en la respuesta.
const visibles = computed(() => {
  const palabras = normalizar(busqueda.value.trim())
    .split(/\s+/)
    .filter(Boolean)

  if (!palabras.length) return preguntas.value

  return preguntas.value.filter((p) => {
    const texto = normalizar(
      `${p.pregunta} ${p.respuesta}`
    )

    return palabras.every((palabra) =>
      texto.includes(palabra)
    )
  })
})

// Carga las preguntas frecuentes desde el servidor.
async function cargar() {
  cargando.value = true
  fallo.value = false

  try {
    const res = await fetch(
      `${props.endpoint}?accion=faq&completa=1`
    )

    if (!res.ok) {
      throw new Error('HTTP ' + res.status)
    }

    const datos = await res.json()

    if (!Array.isArray(datos.preguntas)) {
      throw new Error('Respuesta inválida')
    }

    preguntas.value = datos.preguntas
  } catch (error) {
    console.error(
      'Error cargando preguntas frecuentes:',
      error
    )

    fallo.value = true
  } finally {
    cargando.value = false
  }
}

// Abre el chat flotante existente.
function abrirChat() {
  window.dispatchEvent(
    new CustomEvent('viabus:abrir-chat')
  )
}

// Al entrar al componente, sube al inicio y carga las preguntas.
onMounted(async () => {
  scrollIntoTop()
  await cargar()
})

// Detecta cambios de ruta y vuelve a subir al inicio.
// Útil cuando el componente permanece montado entre rutas.
watch(
  () => route.fullPath,
  async (nuevaRuta, rutaAnterior) => {
    if (nuevaRuta !== rutaAnterior) {
      await nextTick()
      scrollIntoTop()
    }
  }
)
</script>

<style scoped>
.consultas {
  box-sizing: border-box;
  max-width: 1000px;
  margin: 0 auto;
  padding: 10px 20px 60px;
  color: white;
}

/* ENCABEZADO */
.encabezado h1 {
  font-size: 42px;
  font-weight: 600;
  letter-spacing: 2px;
  line-height: 1.2;
  margin: 0;
}

.encabezado h1 span {
  color: #f4b400;
  font-weight: 700;
}

.encabezado p {
  color: var(--color-texto-suave, #9aa1b8);
  font-size: 17px;
  letter-spacing: 0.3px;
  line-height: 1.6;
  margin: 8px 0 30px;
  max-width: 720px;
}

/* BUSCADOR */
.tarjeta-buscador {
  display: flex;
  align-items: center;
  gap: 20px;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 34px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.buscador-icono {
  width: 54px;
  height: 54px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(244, 180, 0, 0.12);
  color: #f4b400;
}

.buscador-contenido {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.buscador-contenido label {
  color: #f4b400;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.buscador-contenido input {
  width: 100%;
  box-sizing: border-box;
  background: var(--color-tarjeta);
  border: 1px solid #1a2138;
  border-radius: 10px;
  color: white;
  padding: 13px 15px;
  font-family: inherit;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.buscador-contenido input::placeholder {
  color: var(--color-texto-suave, #9aa1b8);
}

.buscador-contenido input:focus {
  border-color: #f4b400;
  box-shadow: 0 0 0 3px rgba(244, 180, 0, 0.08);
}

.buscador-ayuda {
  color: var(--color-texto-suave, #9aa1b8);
  font-size: 12px;
  line-height: 1.5;
}

.contador-preguntas {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-width: 95px;
  padding-left: 20px;
  border-left: 1px solid #1a2138;
}

.contador-numero {
  color: #f4b400;
  font-size: 32px;
  font-weight: 700;
  line-height: 1.1;
}

.contador-texto {
  color: var(--color-texto-suave, #9aa1b8);
  font-size: 10px;
  letter-spacing: 1px;
  text-transform: uppercase;
}

/* BARRA DE SECCIÓN */
.barra-seccion {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 18px;
}

.seccion-titulo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.seccion-indicador {
  display: block;
  width: 4px;
  height: 25px;
  flex-shrink: 0;
  background: #f4b400;
  border-radius: 999px;
}

.seccion-titulo h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  letter-spacing: 0.4px;
}

.texto-resultados {
  color: #7b8198;
  font-size: 12px;
  white-space: nowrap;
}

/* LISTA DE PREGUNTAS */
.lista-preguntas {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pregunta-item {
  overflow: hidden;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  border-left: 3px solid #1a2138;
  border-radius: 13px;
  transition: border-color 0.2s, transform 0.2s, background 0.2s;
}

.pregunta-item:hover {
  border-color: #2a3550;
  border-left-color: #f4b400;
  transform: translateY(-1px);
}

.pregunta-item[open] {
  border-color: #2a3550;
  border-left-color: #f4b400;
  background: var(--color-tarjeta, #0a0f22);
}

.pregunta-item summary {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 22px;
  cursor: pointer;
  list-style: none;
  user-select: none;
}

.pregunta-item summary::-webkit-details-marker {
  display: none;
}

.pregunta-numero {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  border: 1px solid #28314a;
  border-radius: 10px;
  color: #f4b400;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.5px;
  transition: background 0.2s, color 0.2s;
}

.pregunta-item[open] .pregunta-numero {
  background: #f4b400;
  border-color: #f4b400;
  color: #060c22;
}

.pregunta-texto {
  flex: 1;
  min-width: 0;
  color: #e6e9f2;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.6;
}

.pregunta-flecha {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 50%;
  color: #7b8198;
  transition: transform 0.25s, color 0.2s, background 0.2s;
}

.pregunta-item[open] .pregunta-flecha {
  transform: rotate(180deg);
  color: #f4b400;
  background: rgba(244, 180, 0, 0.08);
}

.respuesta-contenido {
  display: flex;
  align-items: stretch;
  gap: 16px;
  padding: 0 24px 23px 74px;
  animation: aparecer 0.2s ease;
}

.respuesta-indicador {
  width: 2px;
  flex-shrink: 0;
  border-radius: 999px;
  background: rgba(244, 180, 0, 0.45);
}

.respuesta-texto {
  flex: 1;
  min-width: 0;
  color: #9aa1b8;
  font-size: 13px;
  line-height: 1.8;
  white-space: pre-line;
  overflow-wrap: anywhere;
  margin: 0;
}

/* ESTADOS VACÍOS Y ERROR */
.estado-vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  color: #7b8198;
  padding: 45px 24px;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px dashed #1a2138;
  border-radius: 16px;
}

.estado-vacio > .q-icon {
  color: #f4b400;
  margin-bottom: 12px;
}

.estado-vacio h3 {
  color: white;
  font-size: 17px;
  font-weight: 600;
  margin: 0 0 8px;
}

.estado-vacio p {
  max-width: 480px;
  font-size: 13px;
  line-height: 1.7;
  margin: 0 0 18px;
}

.estado-error > .q-icon {
  color: #ff6b6b;
}

.icono-cargando {
  animation: girar 1.2s linear infinite;
}

/* TARJETA DEL ASISTENTE */
.tarjeta-asistente {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-top: 32px;
  padding: 25px;
  background: var(--color-tarjeta, #0a0f22);
  border: 1px solid #1a2138;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
}

.asistente-icono {
  width: 60px;
  height: 60px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: var(--color-accent, #f4b400);
  color: #060c22;
}

.asistente-info {
  flex: 1;
  min-width: 0;
}

.asistente-info h3 {
  color: white;
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 7px;
}

.asistente-info p {
  color: var(--color-texto-suave, #9aa1b8);
  font-size: 12px;
  line-height: 1.7;
  margin: 0;
}

/* BOTONES */
.boton-principal {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--color-accent, #f4b400);
  border: 1px solid #f4b400;
  border-radius: 999px;
  color: #060c22;
  padding: 11px 20px;
  font-family: inherit;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.4px;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
}

.boton-principal:hover {
  background: #ffd24d;
  border-color: #ffd24d;
  box-shadow: 0 5px 18px rgba(244, 180, 0, 0.14);
  transform: translateY(-1px);
}

.boton-principal:active {
  transform: translateY(0);
}

.boton-principal:focus-visible {
  outline: 2px solid #f4b400;
  outline-offset: 4px;
}

.boton-asistente {
  flex-shrink: 0;
}

/* ANIMACIONES */
@keyframes aparecer {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes girar {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* RESPONSIVE */
@media (max-width: 760px) {
  .consultas {
    padding: 10px 16px 40px;
  }

  .encabezado h1 {
    font-size: 30px;
    letter-spacing: 1px;
  }

  .encabezado p {
    font-size: 14px;
    margin-bottom: 24px;
  }

  .tarjeta-buscador {
    flex-wrap: wrap;
    gap: 14px;
    padding: 20px;
    margin-bottom: 28px;
  }

  .buscador-icono {
    width: 46px;
    height: 46px;
  }

  .buscador-contenido {
    flex: 1;
    min-width: 180px;
  }

  .contador-preguntas {
    flex-direction: row;
    justify-content: flex-start;
    gap: 10px;
    width: 100%;
    padding: 13px 0 0;
    border-left: none;
    border-top: 1px solid #1a2138;
  }

  .contador-numero {
    font-size: 23px;
  }

  .barra-seccion {
    align-items: flex-start;
  }

  .seccion-titulo h2 {
    font-size: 15px;
  }

  .pregunta-item summary {
    gap: 12px;
    padding: 16px 14px;
  }

  .pregunta-numero {
    width: 31px;
    height: 31px;
    font-size: 11px;
  }

  .pregunta-texto {
    font-size: 13px;
  }

  .pregunta-flecha {
    width: 27px;
    height: 27px;
  }

  .respuesta-contenido {
    gap: 12px;
    padding: 0 17px 20px 57px;
  }

  .respuesta-texto {
    font-size: 12px;
  }

  .tarjeta-asistente {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    padding: 22px;
  }

  .asistente-icono {
    width: 50px;
    height: 50px;
  }

  .asistente-info h3 {
    font-size: 15px;
  }

  .boton-asistente {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .consultas *,
  .consultas *::before,
  .consultas *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
