<!--
  Layout principal de la app: sidebar + área de contenido.
  Se usa en todas las páginas excepto NotFoundPage (pantalla completa sin sidebar).

  El estado "abierto/cerrado" del sidebar vive acá (no en AppSidebar) porque
  es este layout el que necesita reaccionar a ese estado para dejar de
  reservar los 240px de margen cuando el sidebar está oculto. Antes ese
  margen quedaba fijo siempre, así que al cerrar el sidebar quedaba un
  espacio vacío del mismo ancho, sin aprovechar la pantalla.
-->
<template>
  <div class="layout">

    <AppSidebar v-model="sidebarAbierta" />

    <div class="contenido" :class="{ 'contenido--full': !sidebarAbierta }">
      <slot />
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppSidebar from '../components/layout/AppSidebar.vue'

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
  display:flex;
  background:#0b1023;
  min-height:100dvh;
}

.contenido{
  margin-left:240px;
  width:calc(100% - 240px);
  padding:40px;
  transition: margin-left 0.3s, width 0.3s;
}

/* Sidebar cerrado: el contenido pasa a ocupar el 100% del ancho */
.contenido--full{
  margin-left:0;
  width:100%;
}

@media(max-width:700px){

  .layout{
    display:block;
  }

  .contenido{
    margin-left:0;
    width:100%;
    padding:10px;
    min-height:100vh;
  }

}

</style>
