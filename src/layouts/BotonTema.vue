<!-- Botón del sidebar para cambiar entre modo oscuro y modo claro -->
<template>
  <button class="boton-tema" @click="alternarTema">
    <q-icon :name="esClaro ? 'dark_mode' : 'light_mode'" size="20px" />
    <span>{{ esClaro ? 'Modo oscuro' : 'Modo claro' }}</span>
  </button>
</template>

<script setup>
import { useTema } from '../services/tema'

const { esClaro, alternarTema } = useTema()
</script>

<style scoped>
.boton-tema{
  width:100%;
  padding:14px 25px;
  display:flex;
  align-items:center;
  gap:12px;
  background:transparent;
  border:none;
  border-left:3px solid transparent;
  color:var(--color-texto);
  font-size:14px;
  text-align:left;
  cursor:pointer;
  transition:0.3s;
}

.boton-tema:hover{
  background:var(--color-hover);
  border-left:3px solid var(--color-principal);
}
</style>
