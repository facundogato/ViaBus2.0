<!-- Sección de estadísticas (andenes, servicios, pasajeros, años) en el home. Antes: BloqueEstadisticas.vue -->
<template>

  <div class="estadisticas">

    <!--
      TARJETAS
    -->

   <StatCard
      numero="7"
      texto="ANDENES"
      icono="directions_bus" />


    <StatCard
    :numero="totalServicios"
    texto="SERVICIOS"
    icono="route" />

    <StatCard
    numero="12K"
    texto="PASAJEROS"
    icono="people" />

    <StatCard
    numero="45"
    texto="AÑOS"
    icono="history" />

  </div>

</template>

<script setup>

import { ref, onMounted } from 'vue'
import StatCard from '../common/StatCard.vue'
import { getTodosServicios } from '../../services/api'

import { supabase } from 'src/boot/supabase'

// Función para cargar los servicios desde Supabase
async function cargarServicios () {
  const { data, error } = await supabase
    .from('servicios')
    .select(`
      *,
      origen:ciudades!origen_id(nombre),
      destino:ciudades!destino_id(nombre),
      empresa:empresas!empresa_id(nombre, logo)
    `)

  if (error) {
    console.error('Error al obtener servicios:', error)
  } else {
    // Asignas los datos a la variable reactiva que usas en tu template
    servicios.value = data
  }
}

</script>

<style scoped>

.estadisticas{

  margin-top:40px;

  display:grid;

  grid-template-columns:repeat(4,1fr);

  gap:20px;

}

@media (max-width: 700px) {
  .estadisticas {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 25px;
  }
}

</style>
