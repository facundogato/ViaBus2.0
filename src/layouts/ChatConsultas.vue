<!--
  ChatConsultas.vue
  Asistente automático de consultas: el cliente elige una pregunta frecuente
  o escribe la suya, y recibe la respuesta al instante (sin persona de por medio).

  Uso (en el layout principal, para que aparezca en todas las páginas):
    <ChatConsultas endpoint="/api/consultas.php" />

  Las preguntas y respuestas viven en el servidor (consultas_faq.php).
-->
<script setup>
import { ref, nextTick, watch, onMounted } from 'vue'

const props = defineProps({
  // URL de consultas.php, sin parámetros
  endpoint: { type: String, default: '/api/ChatBot/consultas.php' },
  titulo: { type: String, default: 'Asistente' },
  saludo: {
    type: String,
    default: 'Hola, soy el asistente automático. Elegí una pregunta frecuente o escribí la tuya.',
  },
})

const abierto = ref(false)
const texto = ref('')
const enviando = ref(false)
const preguntas = ref([])
const mostrarPreguntas = ref(true)
const lista = ref(null)
const campo = ref(null)
const mensajes = ref([{ id: 0, rol: 'bot', texto: props.saludo }])

let contador = 1

function irAlFinal() {
  nextTick(() => {
    if (lista.value) lista.value.scrollTop = lista.value.scrollHeight
  })
}

async function api(accion, cuerpo) {
  const res = await fetch(
    `${props.endpoint}?accion=${accion}`,
    cuerpo
      ? {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(cuerpo),
        }
      : undefined
  )
  const datos = await res.json().catch(() => ({}))
  if (!res.ok) {
    const error = new Error(datos.error || 'Error ' + res.status)
    error.status = res.status
    throw error
  }
  return datos
}

async function preguntar(cuerpo, visible) {
  if (enviando.value) return
  mensajes.value.push({ id: contador++, rol: 'cliente', texto: visible })
  enviando.value = true
  mostrarPreguntas.value = false
  irAlFinal()

  try {
    const datos = await api('preguntar', cuerpo)
    mensajes.value.push({ id: contador++, rol: 'bot', texto: datos.respuesta })
    // Si no entendió, vuelve a mostrar las preguntas frecuentes para que elija
    if (!datos.entendida) mostrarPreguntas.value = true
  } catch (error) {
    mensajes.value.push({
      id: contador++,
      rol: 'error',
      texto: error.status
        ? error.message
        : 'No pudimos conectarnos. Revisá tu conexión y probá de nuevo.',
    })
  } finally {
    enviando.value = false
    irAlFinal()
    nextTick(() => campo.value?.focus())
  }
}

function enviarTexto() {
  const limpio = texto.value.trim()
  if (!limpio) return
  texto.value = ''
  preguntar({ mensaje: limpio }, limpio)
}

function elegir(pregunta) {
  preguntar({ id: pregunta.id }, pregunta.pregunta)
}

watch(abierto, (valor) => {
  if (!valor) return
  nextTick(() => campo.value?.focus())
  irAlFinal()
})

onMounted(async () => {
  try {
    preguntas.value = (await api('faq')).preguntas
  } catch {
    /* sin lista de preguntas: igual se puede escribir */
  }
})
</script>

<template>
  <div class="chat">
    <section
      v-show="abierto"
      id="chat-panel"
      class="chat__panel"
      role="dialog"
      aria-label="Asistente de consultas"
      @keydown.esc="abierto = false"
    >
      <header class="chat__cabecera">
        <h2 class="chat__titulo">{{ titulo }}</h2>
        <button class="chat__cerrar" type="button" aria-label="Cerrar chat" @click="abierto = false">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </button>
      </header>

      <div ref="lista" class="chat__mensajes" role="log" aria-live="polite">
        <p
          v-for="m in mensajes"
          :key="m.id"
          class="chat__burbuja"
          :class="'chat__burbuja--' + m.rol"
        >{{ m.texto }}</p>
      </div>

      <div v-if="preguntas.length" class="chat__preguntas">
        <button
          class="chat__alternar"
          type="button"
          aria-controls="chat-preguntas"
          :aria-expanded="mostrarPreguntas"
          @click="mostrarPreguntas = !mostrarPreguntas"
        >
          Preguntas frecuentes
          <svg
            class="chat__flecha"
            :class="{ 'chat__flecha--abierta': mostrarPreguntas }"
            viewBox="0 0 24 24"
            width="16"
            height="16"
            aria-hidden="true"
          >
            <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <ul v-show="mostrarPreguntas" id="chat-preguntas" class="chat__opciones">
          <li v-for="p in preguntas" :key="p.id">
            <button type="button" :disabled="enviando" @click="elegir(p)">{{ p.pregunta }}</button>
          </li>
        </ul>
      </div>

      <form class="chat__form" @submit.prevent="enviarTexto">
        <textarea
          ref="campo"
          v-model="texto"
          rows="1"
          maxlength="300"
          placeholder="Escribí tu consulta"
          aria-label="Tu consulta"
          @keydown.enter.exact.prevent="enviarTexto"
        ></textarea>
        <button class="chat__enviar" type="submit" :disabled="!texto.trim() || enviando">Enviar</button>
      </form>
    </section>

    <button
      class="chat__lanzador"
      type="button"
      aria-controls="chat-panel"
      :aria-expanded="abierto"
      @click="abierto = !abierto"
    >
      <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
        <path
          d="M4 5h16v11H9l-5 4V5z"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linejoin="round"
        />
      </svg>
      {{ abierto ? 'Cerrar' : 'Consultas' }}
    </button>
  </div>
</template>

<style scoped>
/* Cambiá estos colores por los de tu página */
.chat {
  --chat-fondo: #0c1433;
  --chat-superficie: #18224d;
  --chat-texto: #f2f4fa;
  --chat-acento: #f4b400;
  --chat-sobre-acento: #1a1400;
  --chat-error: #ff9d94;

  position: fixed;
  right: 1rem;
  bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.75rem;
  font-family: inherit;
  font-size: 1rem;
  color: var(--chat-texto);
}

/* Botón flotante */
.chat__lanzador {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.1rem;
  border: 0;
  border-radius: 999px;
  background: var(--chat-acento);
  color: var(--chat-sobre-acento);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* Panel */
.chat__panel {
  display: flex;
  flex-direction: column;
  width: min(22rem, calc(100vw - 2rem));
  height: min(34rem, calc(100dvh - 7rem));
  overflow: hidden;
  border-radius: 1rem;
  background: var(--chat-fondo);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.chat__cabecera {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.chat__titulo {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 600;
}

.chat__cerrar {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.chat__cerrar:hover {
  background: var(--chat-superficie);
}

/* Mensajes */
.chat__mensajes {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 6rem;
  padding: 1rem;
  overflow-y: auto;
}

.chat__burbuja {
  max-width: 85%;
  margin: 0;
  padding: 0.6rem 0.85rem;
  border-radius: 1rem;
  line-height: 1.4;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.chat__burbuja--cliente {
  align-self: flex-end;
  border-bottom-right-radius: 0.25rem;
  background: var(--chat-acento);
  color: var(--chat-sobre-acento);
}

.chat__burbuja--bot {
  align-self: flex-start;
  border-bottom-left-radius: 0.25rem;
  background: var(--chat-superficie);
}

.chat__burbuja--error {
  align-self: flex-start;
  border: 1px solid var(--chat-error);
  background: transparent;
  color: var(--chat-error);
}

/* Preguntas frecuentes */
.chat__preguntas {
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.chat__alternar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.6rem 1rem;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
}

.chat__flecha {
  transition: transform 0.15s;
}

.chat__flecha--abierta {
  transform: rotate(180deg);
}

.chat__opciones {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  max-height: 10rem;
  margin: 0;
  padding: 0 1rem 0.75rem;
  overflow-y: auto;
  list-style: none;
}

.chat__opciones button {
  width: 100%;
  padding: 0.45rem 0.8rem;
  border: 1px solid var(--chat-acento);
  border-radius: 0.75rem;
  background: transparent;
  color: var(--chat-texto);
  font: inherit;
  font-size: 0.9rem;
  text-align: left;
  cursor: pointer;
}

.chat__opciones button:hover:not(:disabled) {
  background: var(--chat-superficie);
}

.chat__opciones button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Formulario */
.chat__form {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
  padding: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.chat__form textarea {
  flex: 1;
  max-height: 6rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 0.75rem;
  background: var(--chat-superficie);
  color: var(--chat-texto);
  font: inherit;
  resize: none;
}

.chat__form textarea::placeholder {
  color: var(--chat-texto);
  opacity: 0.55;
}

.chat__enviar {
  padding: 0.6rem 0.9rem;
  border: 0;
  border-radius: 0.75rem;
  background: var(--chat-acento);
  color: var(--chat-sobre-acento);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.chat__enviar:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* Foco visible para teclado */
.chat button:focus-visible,
.chat textarea:focus-visible {
  outline: 2px solid var(--chat-acento);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .chat__flecha {
    transition: none;
  }
}
</style>
