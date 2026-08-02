<!-- Orquesta el tablero completo: carga datos, refresca cada 30s y compone header/metricas/tabs/tabla. Antes: BloqueTableroContenedor.vue -->
<template>

  <div class="contenedor-tablero">

    <!-- SUPERIOR -->
    <DashboardHeader />

    <!-- METRICAS -->
    <DashboardMetrics :data="tablero" />

    <p v-if="error" class="tablero-error">{{ error }}</p>

    <!-- TABS -->
    <DashboardTabs v-model="tabActivo" />

    <!-- TABLA -->
    <DashboardTable
      :servicios="serviciosVisibles"
      :modo="tabActivo"
      :cargando="cargando && !tablero"
      @toggle-demora="alternarDemora"
    />

  </div>

</template>

<script setup>

import { ref, computed, onMounted, onUnmounted } from 'vue'
import DashboardHeader from './DashboardHeader.vue'
import DashboardMetrics from './DashboardMetrics.vue'
import DashboardTabs from './DashboardTabs.vue'
import DashboardTable from './DashboardTable.vue'
import { getTablero, marcarDemora } from '../../services/api'

const tablero = ref(null)
const cargando = ref(false)
const error = ref('')
const tabActivo = ref('proximos')

let intervalo = null

async function cargarTablero() {
  cargando.value = true
  try {
    tablero.value = await getTablero()
    error.value = ''
  } catch (e) {
    error.value = e.message
  } finally {
    cargando.value = false
  }
}

const serviciosVisibles = computed(() => {
  if (!tablero.value) return []
  return tabActivo.value === 'proximos' ? tablero.value.proximos : tablero.value.salidos
})

async function alternarDemora({ id, demorado }) {
  try {
    await marcarDemora(id, demorado)
    await cargarTablero()
  } catch (e) {
    error.value = e.message
  }
}

onMounted(() => {
  cargarTablero()
  // El tablero depende de la hora actual (qué salió, qué está por salir,
  // cuántos andenes están activos), así que se refresca solo.
  intervalo = setInterval(cargarTablero, 30000)
})

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo)
})

</script>

<style scoped>

.contenedor-tablero{

  width:100%;
  max-width: 1000px;
  margin: 0 auto;
  display: flex;
  flex-direction:column;

  gap:30px;

}

.tablero-error{
  color:#ff6b6b;
  font-size:13px;
  letter-spacing:0.5px;
}

</style>
