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

.superior {
  padding-bottom: 20px;
  border-bottom: 1px solid #1a2138;
  margin-bottom: 30px;
}

h1 {
  font-size: 45px;
  letter-spacing: 3px;
  color: white;
}

.superior p {
  color: #7b8198;
  margin-top: 8px;
  font-size: 13px;
  letter-spacing: 1px;
}

.seccion-titulo {
  color: white;
  font-size: 16px;
  letter-spacing: 3px;
  margin: 40px 0 6px;
}

.seccion-hint {
  color: #7b8198;
  font-size: 12px;
  letter-spacing: 1px;
  margin-bottom: 20px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.info-card {
  background: #0f1629;
  border: 1px solid #1a2138;
  border-radius: 10px;
  padding: 24px 20px;
  text-align: center;
  transition: transform 0.25s, border-color 0.25s;
}

.info-card:hover {
  transform: translateY(-5px);
  border-color: rgba(244, 180, 0, 0.4);
}

.info-card h3 {
  color: white;
  font-size: 14px;
  letter-spacing: 1px;
  margin: 14px 0 8px;
}

.info-card p {
  color: #7b8198;
  font-size: 13px;
  line-height: 1.5;
}

@media (max-width: 900px) {
  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}

</style>
