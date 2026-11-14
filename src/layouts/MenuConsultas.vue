<!--
  ChatConsultas.vue
  Chat flotante para que los clientes envíen consultas.

  Uso:
    <ChatConsultas endpoint="/api/consultas" :sugerencias="['Horarios', 'Precios']" />

  Contrato con el backend (lo que el componente envía y espera):
    POST  endpoint
    Body  { "mensaje": "texto del cliente", "historial": [{ "rol": "cliente|sistema", "texto": "..." }] }
    Resp  { "respuesta": "texto que se muestra en el chat" }
-->
<script setup>
import { ref, nextTick, watch } from 'vue'

const props = defineProps({
  endpoint: { type: String, default: '/api/ChatBot/consultas' },
  titulo: { type: String, default: 'Consultas' },
  saludo: { type: String, default: 'Hola, ¿en qué podemos ayudarte?' },
  sugerencias: { type: Array, default: () => [] },
})

const abierto = ref(false)
const texto = ref('')
const enviando = ref(false)
const lista = ref(null)
const campo = ref(null)

let siguienteId = 1
const mensajes = ref([{ id: 0, rol: 'sistema', texto: props.saludo }])

function irAlFinal() {
  nextTick(() => {
    if (lista.value) lista.value.scrollTop = lista.value.scrollHeight
  })
}

watch(abierto, (valor) => {
  if (!valor) return
  nextTick(() => campo.value?.focus())
  irAlFinal()
})

async function enviar(contenido = texto.value) {
  const limpio = String(contenido).trim()
  if (!limpio || enviando.value) return

  // Historial previo (sin incluir el mensaje que se está enviando ni los errores)
  const historial = mensajes.value
    .filter((m) => m.rol !== 'error')
    .slice(-10)
    .map(({ rol, texto }) => ({ rol, texto }))

  mensajes.value.push({ id: siguienteId++, rol: 'cliente', texto: limpio })
  texto.value = ''
  enviando.value = true
  irAlFinal()

  try {
    const res = await fetch(props.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ mensaje: limpio, historial }),
    })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const datos = await res.json()
    mensajes.value.push({ id: siguienteId++, rol: 'sistema', texto: datos.respuesta })
  } catch (error) {
    mensajes.value.push({
      id: siguienteId++,
      rol: 'error',
      texto: 'No pudimos enviar tu consulta. Revisá tu conexión y probá de nuevo.',
    })
  } finally {
    enviando.value = false
    irAlFinal()
    nextTick(() => campo.value?.focus())
  }
}
</script>

<template>
  <div class="chat">
    <section
      v-show="abierto"
      id="chat-panel"
      class="chat__panel"
      role="dialog"
      aria-label="Chat de consultas"
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

        <p v-if="enviando" class="chat__burbuja chat__burbuja--sistema chat__escribiendo" role="status" aria-label="Escribiendo">
          <span></span><span></span><span></span>
        </p>
      </div>

      <div v-if="sugerencias.length && mensajes.length === 1" class="chat__sugerencias">
        <button v-for="s in sugerencias" :key="s" type="button" @click="enviar(s)">{{ s }}</button>
      </div>

      <form class="chat__form" @submit.prevent="enviar()">
        <textarea
          ref="campo"
          v-model="texto"
          rows="1"
          maxlength="500"
          placeholder="Escribí tu consulta"
          aria-label="Tu consulta"
          @keydown.enter.exact.prevent="enviar()"
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
  height: min(32rem, calc(100dvh - 7rem));
  overflow: hidden;
  border-radius: 1rem;
  background: var(--chat-fondo);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.chat__cabecera {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.25rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.chat__titulo {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  font-family: inherit;
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

.chat__burbuja--sistema {
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

/* Indicador "escribiendo" */
.chat__escribiendo {
  display: flex;
  gap: 0.3rem;
  padding: 0.85rem 1rem;
}

.chat__escribiendo span {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 50%;
  background: var(--chat-texto);
  opacity: 0.5;
  animation: chat-pulso 1.2s infinite ease-in-out;
}

.chat__escribiendo span:nth-child(2) { animation-delay: 0.15s; }
.chat__escribiendo span:nth-child(3) { animation-delay: 0.3s; }

@keyframes chat-pulso {
  0%, 80%, 100% { opacity: 0.3; }
  40% { opacity: 1; }
}

/* Sugerencias rápidas */
.chat__sugerencias {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding: 0 1rem 0.75rem;
}

.chat__sugerencias button {
  padding: 0.4rem 0.8rem;
  border: 1px solid var(--chat-acento);
  border-radius: 999px;
  background: transparent;
  color: var(--chat-texto);
  font: inherit;
  font-size: 0.9rem;
  cursor: pointer;
}

.chat__sugerencias button:hover {
  background: var(--chat-superficie);
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
  .chat__escribiendo span {
    animation: none;
    opacity: 0.7;
  }
}
</style>
