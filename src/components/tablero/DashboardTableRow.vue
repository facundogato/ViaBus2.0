<!-- Fila de la tabla de servicios del tablero. Antes: BloqueTableroFila.vue -->
<template>

  <div class="fila">

    <!-- HORA -->
    <div class="col-hora">
      <span class="hora-servicio"><b>HORA - </b>{{ servicio.hora }}</span>
    </div>

    <!-- DESTINO (todos los servicios salen desde Melo) -->
    <div class="col-destino">
      <span class="destino"><b>DESTINO - </b> <p>{{ servicio.destino }}</p></span>
    </div>

    <!-- ANDEN -->
    <div class="col-anden">
      <span class="anden"><b>ANDEN - </b>{{ servicio.anden || '—' }}</span>
    </div>

    <!-- N° BUS -->
    <div class="col-bus">
       <span class="bus"> <b>N° BUS - </b> <p class="buss">{{ servicio.bus }}</p> </span>
    </div>

    <!-- EMPRESA -->
    <div class="col-empresa">
      <span class="empresa"><b>EMPRESA - </b>{{ servicio.empresa }}</span>
    </div>

    <!-- TIPO -->
    <div class="col-tipo">
      <span class="tipo"><b>TIPO - </b>{{ servicio.tipo }}</span>
    </div>

    <!-- ESTADO -->
    <div class="col-estado">
      <StatusBadge :tipo="servicio.estado" />

      <button
        v-if="editable"
        class="btn-demora"
        :class="{ activo: servicio.demorado }"
        :title="servicio.demorado ? 'Quitar demora' : 'Marcar demora'"
        @click="$emit('toggle-demora', { id: servicio.id, demorado: !servicio.demorado })"
      >
        <q-icon name="schedule" size="14px" />
      </button>
    </div>

  </div>

</template>

<script setup>

import StatusBadge from '../common/StatusBadge.vue'

defineProps({
  servicio: Object,
  editable: { type: Boolean, default: false }
})

defineEmits(['toggle-demora'])

</script>

<style scoped>

.fila{
  display:grid;
  grid-template-columns:80px 140px 80px 120px 100px 120px 1fr;
  align-items:center;
  padding:20px 25px;
  border-bottom:1px solid #1a2138;
  transition:0.2s;
  gap:0px;

}

.col-bus

.fila b{
  color:#7b8198;
  font-size:12px;
  letter-spacing:1px;

}


.fila:hover{
  background:#0f1629;
}

.hora-servicio{
  color:#f4b400;
  font-size:18px;
  font-weight:bold;
  letter-spacing:1px;
}

.destino{
  color:white;
  font-size:16px;
  font-weight:600;
  display:block;
}

.anden{
  color:#00ffd0;
  font-size:22px;
  font-weight:bold;
  letter-spacing:1px;
}

.bus{
  background:#1a2138;
  color:white;
  padding:4px 7px;
  border-radius:3px;
  font-size:13px;
  letter-spacing:1px;
  display:block;
}

.buss {
  
  font-weight: bold;
  
}

.col-empresa span,
.col-tipo span{
  color:#7b8198;
  font-size:13px;
  letter-spacing:1px;
}

.col-estado{
  display:flex;
  align-items:center;
  gap:10px;
}

.btn-demora{
  background:transparent;
  border:1px solid #1a2138;
  color:#7b8198;
  border-radius:4px;
  width:26px;
  height:26px;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:0.2s;
}

.btn-demora:hover{
  border-color:#ff5b5b;
  color:#ff5b5b;
}
.stepper {
    overflow-x: auto;
    padding-bottom: 5px;
  }

.btn-demora.activo{
  background:#ff5b5b;
  border-color:#ff5b5b;
  color:white;
}

@media (max-width: 700px) {
  .fila {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 15px;
  }

  .fila span {
    width: 100%;
  }

  .fila b {
    display: inline-block;
    width: 70px;
    color: #7b8198;
    font-size: 9px;
  }
}

</style>
