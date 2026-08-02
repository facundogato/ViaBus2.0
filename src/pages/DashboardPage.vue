<!-- Tablero de salidas del día, en tiempo real (ruta /tablero). Antes: PaginaTablero.vue -->
<template>

  <MainLayout>

    <DashboardBoard />

  </MainLayout>

</template>

<script setup>

import MainLayout from '../layouts/MainLayout.vue'
import DashboardBoard from '../components/dashboard/DashboardBoard.vue'

</script>
