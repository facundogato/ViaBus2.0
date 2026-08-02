<!-- Página placeholder del módulo de pagos, aún en integración (ruta /pagos). Antes: PaginaPagos.vue -->
<template>

  <MainLayout>

      <div class="pagos-container">

        <!-- ICONO -->
        <div class="icono-card">
          <q-icon name="credit_card" size="80px" color="amber" />
        </div>

        <!-- TITULO -->
        <h1>PRÓXIMAMENTE</h1>

        <!-- DESCRIPCION -->
        <p>
          El módulo de pagos en línea está en integración activa.
          Podrás gestionar tus transacciones, ver historial y
          administrar métodos de pago desde este panel.
        </p>

        <!-- METODOS -->
        <div class="metodos">

          <span class="metodo">
            <q-icon name="payments" size="18px" />
            Efectivo
          </span>

          <span class="metodo">
            <q-icon name="credit_card" size="18px" />
            Tarjeta de Crédito
          </span>

          <span class="metodo">
            <q-icon name="account_balance_wallet" size="18px" />
            Mercado Pago
          </span>

          <span class="metodo">
            <q-icon name="account_balance" size="18px" />
            Transferencia Bancaria
          </span>

        </div>

        <p class="footer-text">
          INTEGRACIÓN CON PASARELAS CERTIFICADAS · PCI DSS COMPLIANT · SSL/TLS SECURED
        </p>

      </div>

  </MainLayout>

</template>

<script setup>

import MainLayout from '../layouts/MainLayout.vue'

</script>

<style scoped>

.pagos-container{
  text-align:center;
  max-width:500px;
  margin:40px auto;
  padding:60px 40px;
}

.icono-card{
  margin-bottom:30px;
}

h1{
  color:#f4b400;
  font-size:50px;
  letter-spacing:5px;
  margin-bottom:20px;
}

p{
  color:#7b8198;
  font-size:15px;
  line-height:1.7;
  margin-bottom:30px;
}

.metodos{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  justify-content:center;
  margin-bottom:30px;
}

.metodo{
  display:flex;
  align-items:center;
  gap:8px;
  padding:10px 18px;
  border:1px solid #1a2138;
  border-radius:5px;
  color:#7b8198;
  font-size:13px;
  background:#0f1629;
}

.footer-text{
  font-size:10px;
  letter-spacing:1px;
  color:#3a4158;
  margin-bottom:0;
}

</style>
