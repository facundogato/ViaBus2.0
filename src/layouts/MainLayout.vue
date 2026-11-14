<!--
  Layout principal de la app: sidebar + área de contenido + footer.
  Se usa en todas las páginas excepto NotFoundPage (pantalla completa sin sidebar).

  El estado "abierto/cerrado" del sidebar vive acá (no en AppSidebar) porque
  es este layout el que necesita reaccionar a ese estado para dejar de
  reservar los 240px de margen cuando el sidebar está oculto. Antes ese
  margen quedaba fijo siempre, así que al cerrar el sidebar quedaba un
  espacio vacío del mismo ancho, sin aprovechar la pantalla.

  La estructura general es en columna (sidebar+contenido arriba, footer
  abajo) para que el footer siempre quede al pie ocupando el 100% del
  ancho, sin importar el estado del sidebar.
-->
<template>
  <div class="layout">

    <AppSidebar v-model="sidebarAbierta" />

    <!-- Columna principal: se corre/ensancha según el sidebar.
         Adentro van el contenido y el footer, así ambos reaccionan igual. -->
    <div class="principal" :class="{ 'principal--full': !sidebarAbierta }">
     <div class="contenido">
  <TopBar />

  <div class="contenido-pagina">
    <slot />
  </div>
</div>

      <Appfooter />
      <ChatConsultas endpoint="/api/ChatBot/consultas.php" />
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppSidebar from './AppSidebar.vue'
import TopBar from './TopBar.vue'
import Appfooter from './Appfooter.vue'
import ChatConsultas from './ChatConsultas.vue'
// En pantallas grandes el sidebar empuja el contenido, así que arranca
// abierto. En pantallas chicas es un panel flotante que se superpone,
// así que arranca cerrado (igual que antes).
const sidebarAbierta = ref(false)

onMounted(() => {
  sidebarAbierta.value = window.innerWidth > 700
})
</script>

<style scoped>

.layout{
  background: var(--color-fondo);
  min-height: 100dvh;
}

/* Columna que reserva el espacio del sidebar. Es el único lugar donde
   se calcula el margen, así contenido y footer siempre quedan alineados. */
.principal{
  display: flex;
  flex-direction: column;     /* contenido arriba, footer abajo */
  min-height: 100dvh;         /* para que el footer quede siempre al pie */
  margin-left: 240px;
  width: calc(100% - 240px);
  transition: margin-left 0.3s, width 0.3s;
}

/* Sidebar cerrado: la columna (contenido + footer) ocupa el 100% */
.principal--full{
  margin-left: 0;
  width: 100%;
}

/* El padding queda solo en el contenido; el footer usa el suyo propio */
.contenido{
  flex: 1;                    /* empuja el footer hacia abajo */
  padding: 40px;
}

@media(max-width:700px){

  /* En celular el sidebar flota encima, no reserva espacio */
  .principal{
    margin-left: 0;
    width: 100%;
  }

  .contenido{
    padding: 10px;
  }

}


/* =====================================
   TRANSICIONES ENTRE PÁGINAS
===================================== */

.pagina-enter-from {
  opacity: 0;
  transform: translateY(18px);
}

.pagina-enter-active {
  transition:
    opacity 0.4s ease,
    transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

.pagina-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.pagina-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.pagina-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.pagina-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Accesibilidad */
@media (prefers-reduced-motion: reduce) {
  .pagina-enter-active,
  .pagina-leave-active {
    transition: none;
  }
}


.contenido-pagina {
  animation: entrada-contenido 0.5s ease both;
}

@keyframes entrada-contenido {
  from {
    opacity: 0;
    transform: translateY(15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .contenido-pagina {
    animation: none;
  }
}
</style>
