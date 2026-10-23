
<template>
  <q-page class="faq-pagina">
    <div class="faq">
      <h1 class="faq__titulo">Preguntas frecuentes</h1>
      <p class="faq__intro">
        Buscá tu duda o abrí una pregunta para ver la respuesta. Si no la encontrás, escribile al
        asistente desde el botón <strong>Consultas</strong>, abajo a la derecha.
      </p>

      <div class="faq__buscador">
        <label for="faq-busqueda">Buscar en las preguntas</label>
        <input
          id="faq-busqueda"
          v-model="busqueda"
          type="search"
          autocomplete="off"
          placeholder="Ej.: horarios, equipaje, mascotas"
        />
      </div>

      <p v-if="cargando" class="faq__estado" role="status">Cargando preguntas...</p>

      <div v-else-if="fallo" class="faq__estado" role="alert">
        <p>No pudimos cargar las preguntas. Probá de nuevo en un momento.</p>
        <button type="button" class="faq__boton" @click="cargar">Reintentar</button>
      </div>

      <template v-else>
        <p v-if="busqueda.trim()" class="faq__estado" role="status">
          {{ visibles.length }} de {{ preguntas.length }} preguntas
        </p>

        <p v-if="!visibles.length" class="faq__vacio">
          No encontramos preguntas con esa búsqueda. Probá con otra palabra o escribile al asistente.
        </p>

        <div class="faq__lista">
          <details v-for="p in visibles" :key="p.id" class="faq__item">
            <summary>{{ p.pregunta }}</summary>
            <p class="faq__respuesta">{{ p.respuesta }}</p>
          </details>
        </div>
      </template>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  endpoint: { type: String, default: '/api/ChatBot/consultas.php' },
})

const preguntas = ref([])
const busqueda = ref('')
const cargando = ref(true)
const fallo = ref(false)

function normalizar(texto) {
  return texto
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

// Todas las palabras buscadas tienen que aparecer en la pregunta o en la respuesta
const visibles = computed(() => {
  const palabras = normalizar(busqueda.value.trim()).split(/\s+/).filter(Boolean)
  if (!palabras.length) return preguntas.value
  return preguntas.value.filter((p) => {
    const texto = normalizar(p.pregunta + ' ' + p.respuesta)
    return palabras.every((palabra) => texto.includes(palabra))
  })
})

async function cargar() {
  cargando.value = true
  fallo.value = false
  try {
    const res = await fetch(`${props.endpoint}?accion=faq&completa=1`)
    if (!res.ok) throw new Error('HTTP ' + res.status)
    preguntas.value = (await res.json()).preguntas
  } catch {
    fallo.value = true
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
</script>



<style scoped>
/* Los colores de texto y fondo los pone el tema del sitio (oscuro o claro).
   El color de acento es el color principal de Quasar; si no existe, amarillo ViaBus. */
.faq-pagina {
  padding: 1.5rem 1rem 3rem;
}

.faq {
  --faq-acento: var(--q-primary, #f4b400);
  --faq-borde: color-mix(in srgb, currentColor 22%, transparent);
  --faq-fondo: color-mix(in srgb, currentColor 8%, transparent);

  box-sizing: border-box;
  max-width: 46rem;
  margin: 0 auto;
  font-family: inherit;
  color: inherit;
}

.faq__titulo {
  margin: 0 0 0.5rem;
  font-size: 1.75rem;
  line-height: 1.2;
}

.faq__titulo::after {
  content: '';
  display: block;
  width: 3rem;
  height: 3px;
  margin-top: 0.6rem;
  border-radius: 2px;
  background: var(--faq-acento);
}

.faq__intro {
  margin: 1rem 0 1.5rem;
  line-height: 1.5;
  opacity: 0.85;
}

.faq__buscador {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1.25rem;
}

.faq__buscador label {
  font-size: 0.9rem;
  font-weight: 600;
}

.faq__buscador input {
  padding: 0.7rem 0.9rem;
  border: 1px solid var(--faq-borde);
  border-radius: 0.75rem;
  background: var(--faq-fondo);
  color: inherit;
  font: inherit;
}

.faq__buscador input::placeholder {
  color: inherit;
  opacity: 0.55;
}

.faq__estado {
  margin: 0 0 1rem;
  font-size: 0.9rem;
  opacity: 0.85;
}

.faq__vacio {
  margin: 1.5rem 0;
  line-height: 1.5;
}

.faq__lista {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.faq__item {
  border: 1px solid var(--faq-borde);
  border-radius: 0.75rem;
  background: var(--faq-fondo);
}

.faq__item[open] {
  border-color: var(--faq-acento);
}

.faq__item summary {
  padding: 0.9rem 1rem;
  font-weight: 600;
  cursor: pointer;
}

.faq__item summary::marker {
  color: var(--faq-acento);
}

.faq__item[open] summary {
  border-bottom: 1px solid var(--faq-borde);
}

.faq__respuesta {
  margin: 0;
  padding: 0.9rem 1rem 1rem;
  line-height: 1.55;
  white-space: pre-line;
  overflow-wrap: anywhere;
}

.faq__boton {
  padding: 0.55rem 1rem;
  border: 1px solid var(--faq-borde);
  border-radius: 0.75rem;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
}

.faq input:focus-visible,
.faq summary:focus-visible,
.faq__boton:focus-visible {
  outline: 2px solid var(--faq-acento);
  outline-offset: 2px;
}
</style>
