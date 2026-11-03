<!--
  Paso de pago de la reserva (Mercado Pago).

  Es un componente de presentación: no hace llamadas al backend ni maneja
  la navegación entre pasos, eso lo sigue haciendo ReservationPage.vue
  (que ya es el dueño de "pasoActual", "reservaCreada", etc). Este
  componente solo muestra la fase actual del pago y avisa con eventos
  cuando el usuario quiere pagar, reintentar o volver.

  Props:
    resumen        -> { origen, destino, fecha, hora, empresa, asiento, total }
    fase           -> 'resumen' | 'procesando' | 'verificando' | 'rechazado' | 'pendiente' | 'error'
    mensajeError   -> texto a mostrar cuando fase = 'error' o 'rechazado'

  Eventos:
    volver      -> el usuario quiere ir al paso anterior (solo disponible en fase 'resumen')
    pagar       -> el usuario confirmó que quiere pagar
    reintentar  -> el usuario quiere volver a intentar el pago tras un error/rechazo
-->
<template>

  <div class="paso-pago">

    <!-- FASE: RESUMEN, listo para pagar -->
    <template v-if="fase === 'resumen'">

      <h2>PAGO</h2>

<div class="resumen-reserva">

  <!-- CABECERA -->
  <div class="resumen-header">
    <div>
      <span class="resumen-overline">RESUMEN DE RESERVA</span>
      <h3>Tu viaje</h3>
    </div>

    <q-icon
      name="confirmation_number"
      size="30px"
      class="icon-ticket"
    />
  </div>


  <!-- TRAYECTO -->
  <div class="trayecto">

    <div class="ciudad">
      <span>ORIGEN</span>
      <strong>{{ resumen.origen }}</strong>
    </div>

    <div class="flecha">
      <q-icon name="arrow_forward" size="20px" />
    </div>

    <div class="ciudad destino">
      <span>DESTINO</span>
      <strong>{{ resumen.destino }}</strong>
    </div>

  </div>


  <!-- INFORMACIÓN DEL VIAJE -->
  <div class="datos-viaje">

    <div class="dato">
      <q-icon name="event" />

      <div>
        <span>FECHA</span>
        <strong>{{ resumen.fecha }}</strong>
      </div>
    </div>


    <div class="dato">
      <q-icon name="schedule" />

      <div>
        <span>HORA</span>
        <strong>{{ resumen.hora }}</strong>
      </div>
    </div>


    <div class="dato">
      <q-icon name="directions_bus" />

      <div>
        <span>EMPRESA</span>
        <strong>{{ resumen.empresa }}</strong>
      </div>
    </div>


    <div class="dato">
      <q-icon name="airline_seat_recline_normal" />

      <div>
        <span>ASIENTO</span>
        <strong>{{ resumen.asiento }}</strong>
      </div>
    </div>

  </div>


  <!-- TOTAL -->
  <div class="resumen-total">

    <span>TOTAL A PAGAR</span>

    <strong>
      ${{ resumen.total }}
    </strong>

  </div>

</div>


      <div class="acciones">
        <button class="btn-volver" @click="$emit('volver')">Volver</button>
        <button class="btn-pagar-mp" @click="$emit('pagar')">
          <q-icon name="payment" size="18px" />
          Pagar con Mercado Pago
        </button>

        <button @click="$emit('saltear')">
  Saltear
</button>
      </div>

    </template>

    <!-- FASE: PROCESANDO (creando la reserva y la preferencia de pago) -->
    <div v-else-if="fase === 'procesando'" class="estado-pago">
      <q-spinner color="amber" size="48px" />
      <h2>Preparando tu pago...</h2>
      <p>Te vamos a redirigir a Mercado Pago en un momento.</p>
    </div>

    <!-- FASE: VERIFICANDO (el usuario acaba de volver de Mercado Pago) -->
    <div v-else-if="fase === 'verificando'" class="estado-pago">
      <q-spinner color="amber" size="48px" />
      <h2>Verificando tu pago...</h2>
      <p>Esto puede tardar unos segundos.</p>
    </div>

    <!-- FASE: PENDIENTE (ej. pago en efectivo, todavía no se acreditó) -->
    <div v-else-if="fase === 'pendiente'" class="estado-pago">
      <q-icon name="hourglass_empty" size="48px" color="warning" />
      <h2>Tu pago está pendiente</h2>
      <p>Mercado Pago todavía no confirmó el pago. Apenas se acredite, tu reserva queda confirmada automáticamente.</p>
      <div class="acciones">
        <button class="btn-volver" @click="$emit('reintentar')">Volver a verificar</button>
      </div>
    </div>

    <!-- FASE: RECHAZADO -->
    <div v-else-if="fase === 'rechazado'" class="estado-pago">
      <q-icon name="error_outline" size="48px" color="negative" />
      <h2>El pago fue rechazado</h2>
      <p>{{ mensajeError || 'Mercado Pago rechazó el pago. Podés intentarlo de nuevo.' }}</p>
      <div class="acciones">
        <button class="btn-pagar-mp" @click="$emit('reintentar')">Reintentar pago</button>
      </div>
    </div>

    <!-- FASE: ERROR (de conexión, backend, etc.) -->
    <div v-else-if="fase === 'error'" class="estado-pago">
      <q-icon name="error_outline" size="48px" color="negative" />
      <h2>No pudimos procesar el pago</h2>
      <p>{{ mensajeError || 'Ocurrió un error inesperado.' }}</p>
      <div class="acciones">
        <button class="btn-pagar-mp" @click="$emit('reintentar')">Reintentar</button>
      </div>
    </div>

  </div>

</template>

<script setup>
defineProps({
  resumen: { type: Object, required: true },
  fase: { type: String, default: 'resumen' },
  mensajeError: { type: String, default: '' }
})

defineEmits(['volver', 'pagar', 'reintentar'])
</script>

<style scoped>

/* =========================================================
   RESUMEN DE RESERVA
========================================================= */

.resumen-reserva {
  width: 100%;
  max-width: 680px;
  margin-bottom: 28px;
  background: #0f1629;
  border: 1px solid #1d2740;
  border-radius: 12px;
  overflow: hidden;
  box-shadow:
    0 12px 35px rgba(0, 0, 0, 0.18);
}


/* =========================================================
   CABECERA
========================================================= */

.resumen-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #1a2138;
}

.resumen-header > div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.resumen-overline {
  color: #f4b400;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 2px;
}

.resumen-header h3 {
  margin: 0;
  color: white;
  font-size: 19px;
  font-weight: 600;
  letter-spacing: -0.3px;
}

.icon-ticket {
  color: #f4b400;
  opacity: 0.9;
}


/* =========================================================
   TRAYECTO
========================================================= */

.trayecto {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  padding: 27px 24px;
  background:
    linear-gradient(
      135deg,
      rgba(244, 180, 0, 0.04),
      transparent 60%
    );
}

.ciudad {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.ciudad span {
  color: #69738c;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.ciudad strong {
  color: white;
  font-size: 21px;
  font-weight: 650;
  letter-spacing: -0.4px;
}

.ciudad.destino {
  text-align: right;
}


.flecha {
  width: 42px;
  height: 42px;
  margin: 0 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f4b400;
  background: rgba(244, 180, 0, 0.07);
  border: 1px solid rgba(244, 180, 0, 0.2);
  border-radius: 50%;
}


/* =========================================================
   DATOS DEL VIAJE
========================================================= */

.datos-viaje {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  border-top: 1px solid #1a2138;
  border-bottom: 1px solid #1a2138;
}

.dato {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px;
  border-right: 1px solid #1a2138;
}

.dato:last-child {
  border-right: none;
}

.dato > .q-icon {
  color: #f4b400;
  font-size: 18px;
  flex-shrink: 0;
}

.dato > div {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.dato span {
  color: #68728b;
  font-size: 8px;
  font-weight: 700;
  letter-spacing: 1.2px;
}

.dato strong {
  color: white;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* =========================================================
   TOTAL
========================================================= */

.resumen-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: #0c1325;
}

.resumen-total span {
  color: #858da7;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.resumen-total strong {
  color: #00e6bd;
  font-size: 26px;
  font-weight: 750;
  letter-spacing: -0.5px;
}


.acciones{
  display:flex;
  gap:12px;
}

.btn-volver{
  padding:12px 24px;
  background:transparent;
  border:1px solid #1a2138;
  color:#7b8198;
  border-radius:6px;
  cursor:pointer;
  font-size:13px;
  letter-spacing:1px;
  transition:0.2s;
}

.btn-volver:hover{
  border-color:#f4b400;
  color:white;
}

.btn-pagar-mp{
  padding:12px 24px;
  background:#00b1ea;
  border:none;
  color:white;
  border-radius:6px;
  cursor:pointer;
  font-size:13px;
  font-weight:bold;
  letter-spacing:1px;
  display:flex;
  align-items:center;
  gap:8px;
  transition:0.2s;
}

.btn-pagar-mp:hover{
  background:#0090c2;
}

/* Estados intermedios (procesando / verificando / pendiente / rechazado / error) */
.paso-pago > h2 {
  color: #ffffff;
  font-size: 22px;
  font-weight: 650;
  letter-spacing: 3px;
  margin: 0 0 25px;
  text-transform: uppercase;
}


.estado-pago{
  max-width:480px;
  padding:50px 30px;
  text-align:center;
  background:#0f1629;
  border:1px solid #1a2138;
  border-radius:10px;
}

.estado-pago h2{
  color:white;
  font-size:18px;
  letter-spacing:1px;
  margin:18px 0 10px;
}

.estado-pago p{
  color:#7b8198;
  font-size:13px;
  line-height:1.6;
  margin-bottom:20px;
}

.estado-pago .acciones{
  justify-content:center;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 650px) {

  .resumen-reserva {
    border-radius: 10px;
  }


  /* CABECERA */

  .resumen-header {
    padding: 17px 18px;
  }

  .resumen-header h3 {
    font-size: 17px;
  }


  /* TRAYECTO */

  .trayecto {
    padding: 22px 18px;
  }

  .ciudad strong {
    font-size: 17px;
  }

  .flecha {
    width: 35px;
    height: 35px;
    margin: 0 10px;
  }


  /* DATOS */

  .datos-viaje {
    grid-template-columns: repeat(2, 1fr);
  }

  .dato {
    padding: 15px;
    border-right: 1px solid #1a2138;
    border-bottom: 1px solid #1a2138;
  }

  .dato:nth-child(2n) {
    border-right: none;
  }

  .dato:nth-child(3),
  .dato:nth-child(4) {
    border-bottom: none;
  }


  /* TOTAL */

  .resumen-total {
    padding: 18px;
  }

  .resumen-total strong {
    font-size: 23px;
  }
}


@media (max-width: 380px) {

  .ciudad strong {
    font-size: 15px;
  }

  .flecha {
    margin: 0 6px;
  }

  .dato {
    padding: 13px 11px;
  }

  .dato > .q-icon {
    display: none;
  }

}

</style>
