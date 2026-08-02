<!-- Muestra la hora actual y se actualiza cada segundo. Antes: BloqueTableroHora.vue -->
<template>
  <span class="hora">{{ hora }}</span>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const hora = ref('')
let intervalo = null

onMounted(() => {
  hora.value = new Date().toLocaleTimeString()

  intervalo = setInterval(() => {
    hora.value = new Date().toLocaleTimeString()
  }, 1000)
})

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo)
})

</script>
