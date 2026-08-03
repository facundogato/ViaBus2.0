<!-- Tarjeta de métrica coloreada del tablero (embarcando/en horario/demorados). Antes: TarjetaMetrica.vue -->
<template>

  <q-card
    class="tarjeta"
    :style="{
      borderTop:'3px solid ' + color
    }"
  >

    <q-card-section>

      <!-- TITULO -->
      <p class="titulo">
        {{ titulo }}
      </p>

      <!-- NUMERO -->
      <h1
        :style="{
          color:color
        }"
      >
        {{ numero }}
      </h1>

      <!-- SUB -->
      <span>
        {{ subtitulo }}
      </span>

    </q-card-section>

  </q-card>

</template>

<script setup>

defineProps({

  titulo:String,
  numero:String,
  subtitulo:String,
  color:String

})

</script>

<style scoped>

.tarjeta{

  background:#0b1023;

  border:1px solid #1a2138;

  color:white;

}

.titulo{

  color:#7b8198;

  letter-spacing:3px;

  font-size:12px;

}

h1{

  font-size:55px;

  margin:10px 0;

}

span{

  color:#7b8198;

}

</style>