<!-- Página de INICIO de la terminal (ruta /terminal). Es la primera que ve el usuario.
     No tiene lógica propia: solo apila 5 componentes, cada uno con su propia
     lógica adentro (igual que piezas de LEGO ya armadas). -->

<template>

  <!-- Diseño general de la página (menú, encabezado) -->
  <MainLayout>

    <!-- Banner grande con la presentación/bienvenida -->
    <HeroBanner />

    <!-- Números de la terminal (ej: empresas, destinos, servicios) -->
    <StatsSection />

    <!-- Grilla de accesos rápidos (comprar pasaje, horarios, etc.) -->
    <QuickActionsGrid />

    <!-- Mapa con la ubicación de la terminal -->
    <LocationMap />

    <!-- Sección con los medios de pago aceptados -->
    <PaymentMethodsSection />

  </MainLayout>

</template>

<script setup>
// La página solo importa las piezas y las muestra en orden.
// Toda la lógica (pedir datos, botones, etc.) vive adentro de cada componente.

import MainLayout from '../layouts/MainLayout.vue'
import HeroBanner from '../components/home/HeroBanner.vue'
import StatsSection from '../components/home/StatsSection.vue'
import QuickActionsGrid from '../components/home/QuickActionsGrid.vue'
import LocationMap from '../components/home/LocationMap.vue'
import PaymentMethodsSection from '../components/home/PaymentMethodsSection.vue'
</script>

<style scoped>

:deep(.zona-mapa) {
  margin-top: 30px;
}

</style>
