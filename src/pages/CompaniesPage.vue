<!-- ============================================================
     Página de INFORMACIÓN GENERAL de la terminal (ruta /info).
     Es la página "pura información": le cuenta al visitante qué
     empresas trabajan en la terminal y datos prácticos (horarios,
     ubicación, contacto, servicios). No se crea ni edita nada acá,
     es solo para leer.
============================================================ -->

<template>
<!-- Todo lo de acá adentro es lo que se ve en pantalla -->

  <!-- MainLayout es el "cascarón" de la página: trae el menú y
       el encabezado general. Igual que en las otras páginas. -->
  <MainLayout>

    <!-- =========== PARTE DE ARRIBA: título de la página =========== -->
    <div class="superior">
      <div>
        <!-- El título grande -->
        <h1>INFORMACIÓN GENERAL</h1>
        <!-- Textito explicando de qué va esta página -->
        <p>Todo sobre la Terminal de Ómnibus de Melo y las empresas que operan en ella</p>
      </div>
    </div>

    <!-- =========== SECCIÓN: EMPRESAS =========== -->
    <!-- Título de la sección -->
    <h2 class="seccion-titulo">EMPRESAS QUE OPERAN EN LA TERMINAL</h2>

    <!-- Un mensajito que le avisa al usuario que los logos
         se pueden clickear para ver más info de cada empresa -->
    <p class="seccion-hint">Hacé click en el logo de una empresa para ver su información completa</p>

    <!-- CompanyList es un componente aparte (como una pieza de
         LEGO hecha antes). Él solo se encarga de mostrar la lista
         de empresas con sus logos. Toda su lógica vive en su propio
         archivo, por eso acá solo lo "encajamos" y listo. -->
    <CompanyList />

    <!-- =========== SECCIÓN: INFO PRÁCTICA =========== -->
    <h2 class="seccion-titulo">INFORMACIÓN PRÁCTICA</h2>

    <!-- Una grilla con 4 tarjetas, cada una con un dato útil.
         A diferencia de la lista de empresas, estas tarjetas
         están escritas a mano acá, porque esa info no cambia
         (no se trae de ningún servidor). -->
    <div class="info-grid">

      <!-- Tarjeta 1: horario de atención -->
      <div class="info-card">
        <!-- Ícono de relojito (schedule), en amarillo -->
        <q-icon name="schedule" size="26px" color="amber" />
        <h3>Horario de atención</h3>
        <p>Lunes a domingo, de 06:00 a 23:00</p>
      </div>

      <!-- Tarjeta 2: dónde queda la terminal -->
      <div class="info-card">
        <!-- Ícono de ubicación (chincheta en el mapa) -->
        <q-icon name="place" size="26px" color="amber" />
        <h3>Ubicación</h3>
        <p>Terminal de Ómnibus de Melo, Cerro Largo</p>
      </div>

      <!-- Tarjeta 3: teléfono y mail de contacto -->
      <div class="info-card">
        <!-- Ícono de teléfono -->
        <q-icon name="call" size="26px" color="amber" />
        <h3>Contacto</h3>
        <p>+598 XXXX XXXX · info@viabus.uy</p>
      </div>

      <!-- Tarjeta 4: qué servicios ofrece la terminal -->
      <div class="info-card">
        <!-- Ícono de persona saludando (para "servicios") -->
        <q-icon name="hail" size="26px" color="amber" />
        <h3>Servicios en la terminal</h3>
        <p>Sala de espera, baños, cafetería, wifi gratuito</p>
      </div>

    </div>

  </MainLayout>

</template>


<script setup>
// El diseño general (menú, encabezado), igual que en las
// demás páginas del sitio
import MainLayout from '../layouts/MainLayout.vue'

// El componente que muestra las empresas con sus logos.
// Lo hace todo él solo: pedir las empresas al servidor,
// mostrarlas, etc. Esta página solo lo pone en su lugar.
import CompanyList from '../components/company/CompanyList.vue'
</script>


<style scoped>

/* =========================================================
   ENCABEZADO
   ========================================================= */

.superior {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  padding: clamp(30px, 5vw, 52px) 20px 30px;
  margin-bottom: 35px;
  border-bottom: 1px solid #1a2138;
  box-sizing: border-box;
  text-align: center;
}

.superior h1 {
  margin: 0;
  color: #ffffff;
  font-family: 'Inter', sans-serif;
  /*
    Responsive:
    móvil → ~38px
    escritorio → hasta 60px
  */
  font-size: clamp(2.4rem, 5vw, 3.75rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.05;
  text-transform: uppercase;
}


/*
  Línea amarilla debajo del título
*/

.superior h1::after {
  content: '';
  display: block;
  width: 65px;
  height: 3px;
  margin: 16px auto 0;
  background: #f4b400;
  border-radius: 20px;
}

/* =========================================================
   SUBTÍTULO
   ========================================================= */

.superior p {
  max-width: 700px;
  margin: 17px auto 0;
  color: #7b8198;
  font-family: 'Inter', sans-serif;
  font-size: clamp(1.125rem, 1.5vw, 1.25rem);
  font-weight: 400;
  letter-spacing: 0.06em;
  line-height: 1.7;
}

/* =========================================================
   TÍTULOS DE SECCIÓN
   ========================================================= */

.seccion-titulo {
  position: relative;
  display: flex;
  align-items: center;
  margin: 42px 0 7px;
  color: #ffffff;
  font-family: 'Inter', sans-serif;
  font-size: 20px;
  font-weight: 700;
  letter-spacing: 0.16em;
  line-height: 1.4;
  text-transform: uppercase;
}

/*
  Punto amarillo de identidad
*/

.seccion-titulo::before {
  content: '';
  width: 7px;
  height: 7px;
  flex-shrink: 0;
  margin-right: 10px;
  background: #f4b400;
  border-radius: 50%;
}

/* =========================================================
   TEXTO DE AYUDA
   ========================================================= */

.seccion-hint {
  margin: 0 0 22px 17px;
  color: #7b8198;
  font-family: 'Inter', sans-serif;
  font-size: 17px;
  font-weight: 400;
  letter-spacing: 0.04em;
  line-height: 1.6;
}

/* =========================================================
   INFORMACIÓN PRÁCTICA
   ========================================================= */

.info-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-top: 20px;
}

/* =========================================================
   TARJETAS
   ========================================================= */

.info-card {
  position: relative;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 26px 20px 24px;
  background: #0f1629;
  border: 1px solid #1a2138;
  border-radius: 11px;
  text-align: center;
  box-sizing: border-box;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    background 0.2s ease;
}

/*
  Línea amarilla superior muy sutil
*/

.info-card::before {
  content: '';
  position: absolute;
  top: -1px;
  left: 50%;
  width: 35px;
  height: 2px;
  transform: translateX(-50%);
  background: #f4b400;
  border-radius: 0 0 5px 5px;
  opacity: 0.8;
}

/*
  Hover elegante.

*/

.info-card:hover {
  transform: translateY(-3px);
  background: #111a30;
  border-color: rgba(244, 180, 0, 0.35);
}

/* =========================================================
   ÍCONOS
   ========================================================= */

.info-card :deep(.q-icon) {
  color: #f4b400 !important;
  opacity: 0.95;
}

/* =========================================================
   TÍTULO DE CADA TARJETA
   ========================================================= */

.info-card h3 {
  margin: 15px 0 8px;
  color: #ffffff;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  line-height: 1.4;
  text-transform: uppercase;
}


/* =========================================================
   TEXTO DE LAS TARJETAS
   ========================================================= */

.info-card p {
  margin: 0;
  color: #7b8198;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 400;
  line-height: 1.7;
  overflow-wrap: anywhere;
}

/* =========================================================
   TABLET, PARA QUE NO SE VEAN TAN CHIQUITAS LAS TARJETAS
   ========================================================= */

@media (max-width: 1000px) {

  .info-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

}

/* =========================================================
   CELULAR
   ========================================================= */

@media (max-width: 600px) {

  .superior {
    padding: 30px 16px 25px;
    margin-bottom: 28px;
  }


  .superior h1 {
    /*
      En celulares dejamos que el título
      sea grande pero no ocupe toda la pantalla.
    */
    font-size: clamp(2.1rem, 10vw, 2.8rem);
    line-height: 1.08;
  }


  .superior h1::after {
    width: 52px;
    height: 3px;
    margin-top: 13px;
  }


  .superior p {
    max-width: 320px;
    margin-top: 14px;
    font-size: 11px;
    line-height: 1.7;
  }


  /* -----------------------------------------
     Secciones
     ----------------------------------------- */

  .seccion-titulo {
    margin-top: 32px;
    font-size: 11px;
    letter-spacing: 0.12em;
    line-height: 1.5;
  }


  .seccion-hint {
    margin-left: 17px;
    font-size: 10px;
    line-height: 1.6;
  }

  /* -----------------------------------------
     Tarjetas
     ----------------------------------------- */

  .info-grid {
    grid-template-columns: 1fr;
    gap: 12px;
    margin-top: 18px;
  }


  .info-card {
    /*
      En celular las hicimos un poco
      más compactas.
    */
    min-height: 0;
    padding: 22px 20px;
  }


  .info-card h3 {
    font-size: 11px;
  }


  .info-card p {
    max-width: 280px;
    font-size: 12px;
  }

}


</style>
