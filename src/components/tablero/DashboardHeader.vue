<!-- Encabezado del tablero. Antes: BloqueTableroSuperior.vue -->
<template>
  <div class="superior">

    <!-- IZQUIERDA, texto plano del tablero. -->
    <div>
      <h1>
        TABLERO DE SERVICIOS
      </h1>

      <p>
        Salidas desde Melo · Estado en tiempo real
      </p>
    </div>

    <!-- espacio para poner el reloj en un futuro -->
    <div class="hora-container">


      <span>

      </span>

    </div>

  </div>
</template>

<script setup>


</script>

<script setup>
</script>

<style scoped>

/* =========================
   ENCABEZADO
   ========================= */

.superior {
  position: relative;

  display: flex;
  justify-content: center;
  align-items: center;

  width: 100%;

  padding: clamp(28px, 5vw, 55px) 20px 30px;

  border-bottom: 1px solid #1a2138;

  box-sizing: border-box;
}


/* =========================
   CONTENIDO CENTRAL
   ========================= */

.encabezado {
  text-align: center;
}


/* =========================
   TÍTULO
   ========================= */

.titulo-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

h1 {
  margin: 0;

  font-family: 'Inter', sans-serif;

  /*
   Se adapta automáticamente:
   celular → más pequeño
   escritorio → más grande
  */
  font-size: clamp(2.5rem, 5vw, 4.5rem);

  font-weight: 800;

  /*
   Un poco menos de espacio que antes.
   Al ser una tipografía gruesa no necesita
   tanto letter-spacing.
  */
  letter-spacing: -0.035em;

  line-height: 1.05;

  color: #ffffff;

  text-transform: uppercase;
}


/* =========================
   LÍNEA AMARILLA
   ========================= */

.acento {
  width: 70px;
  height: 3px;

  margin-top: 16px;

  background: #f4b400;

  border-radius: 10px;

  box-shadow: 0 0 12px rgba(244, 180, 0, 0.25);
}


/* =========================
   SUBTÍTULO
   ========================= */

p {
  margin: 18px 0 0;

  font-family: 'Inter', sans-serif;

  font-size: clamp(0.75rem, 1.2vw, 0.9rem);

  font-weight: 500;

  letter-spacing: 0.08em;

  color: var(--color-texto);
}


/* Punto amarillo */

.separador {
  margin: 0 8px;

  color: #f4b400;

  font-size: 0.8em;
}


/* =========================
   RELOJ
   ========================= */

.hora-container {
  position: absolute;

  right: 20px;
  top: 50%;

  transform: translateY(-50%);

  text-align: right;
}


/* =========================
   CELULAR
   ========================= */

@media (max-width: 600px) {

  .superior {
    padding: 30px 16px 25px;
  }

  h1 {
    /*
      En celular evitamos que el título
      ocupe demasiado espacio.
    */
    font-size: clamp(2.2rem, 11vw, 3rem);

    line-height: 1.05;

    /*
      Permite que "TABLERO DE SERVICIOS" quede bien distribuido
      si la pantalla es angosta.
    */
    max-width: 100%;
  }

  .acento {
    width: 55px;
    height: 3px;

    margin-top: 13px;
  }

  p {
    margin-top: 14px;

    line-height: 1.6;

    /*
      En pantallas chicas permitimos
      que el subtítulo ocupe dos líneas.
    */
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
  }

  .hora-container {
    display: none;
  }

}
</style>
