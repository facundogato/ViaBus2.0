<!-- Tabla de servicios (próximos o ya salidos) del tablero. Antes: BloqueTableroTabla.vue -->
<template>

  <div class="tabla">

    <!-- ENCABEZADO -->
    <div class="encabezado">
      <span></span>
      <span>DESTINO</span>
      <span>ANDÉN</span>
      <span>N° BUS</span>
      <span>EMPRESA</span>
      <span>TIPO</span>
      <span>ESTADO</span>
    </div>

    <p v-if="cargando" class="estado-msg">Cargando servicios...</p>
    <p v-else-if="servicios.length === 0" class="estado-msg">
      {{ modo === 'proximos' ? 'No quedan más salidas por hoy.' : 'Todavía no salió ningún servicio hoy.' }}
    </p>

    <!-- FILAS -->
    <DashboardTableRow
      v-for="servicio in servicios"
      :key="servicio.id"
      :servicio="servicio"
      :editable="modo === 'proximos'"
      @toggle-demora="$emit('toggle-demora', $event)"
    />

  </div>

</template>

<script setup>

import DashboardTableRow from './DashboardTableRow.vue'

defineProps({
  servicios: { type: Array, default: () => [] },
  modo: { type: String, default: 'proximos' },
  cargando: { type: Boolean, default: false }
})

defineEmits(['toggle-demora'])

</script>

<style scoped>

.tabla{
  border:1px solid #1a2138;
  border-radius:6px;
  overflow:hidden;
  margin-left: 20px;
}

.encabezado{
  display:grid;
  grid-template-columns:100px 200px 90px 110px 160px 160px 1fr;
  padding:12px 25px;
  background:#0f1629;
  gap:10px;
}

.encabezado span{
  color:#7b8198;
  font-size:10px;
  letter-spacing:2px;
}

.estado-msg{
  padding:20px 25px;
  color:#7b8198;
  font-size:12px;
  letter-spacing:1px;
}

@media (max-width: 700px) {
  .tabla {
    margin-left: 0;
    overflow-x: auto;
    overflow-y: hidden;
  }

  /* Las columnas no se apilan (son datos de una fila real de salida):
     se deja el ancho fijo y se scrollea horizontalmente en vez de romper
     el layout. Las filas (DashboardTableRow.vue) usan el mismo ancho
     mínimo para que header y filas queden alineados al scrollear. */
  .encabezado {
    min-width: 800px;
  }
}

</style>
