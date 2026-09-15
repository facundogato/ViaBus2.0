```vue
<template>

  <MainLayout>

    <div class="verificacion-page">
      <q-card flat class="verificacion-card">
        <div v-if="cargando">
          <q-spinner size="60px" color="white"/>
          <h2> Verificando tu correo... </h2>
          <p> Un momento, estamos confirmando tu cuenta. </p>
        </div>

        <div v-else-if="exito">
          <div class="icono-exito">
            ✓
          </div>
          <h2>
            ¡Correo confirmado!
          </h2>
          <p>
            Tu cuenta de ViaBus fue confirmada correctamente.
          </p>

          <q-btn
            rounded
            outline
            color="white"
            label="Iniciar sesión"
            class="login-btn"
            @click="irAlLogin"
          />
        </div>

        <div v-else>
          <div class="icono-error">
            ✕
          </div>
          <h2>
            No pudimos confirmar tu correo
          </h2>
          <p>
            {{ mensaje }}
          </p>

          <q-btn
            rounded
            outline
            color="white"
            label="Volver al inicio de sesión"
            class="login-btn"
            @click="irAlLogin"
          />

        </div>
      </q-card>
    </div>
  </MainLayout>
</template>


<script setup>

import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'


// Importamos la función que agregamos anteriormente a api.js
import { verificarCorreo } from '../services/api'


const route = useRoute()
const router = useRouter()

// Estados de la página
const cargando = ref(true)
const exito = ref(false)
const mensaje = ref('')



async function confirmarCorreo() {

  const token = route.query.token

  // Si alguien entra manualmente a /verificar-correo
  // sin proporcionar un token, mostramos un error

  if (typeof token !== 'string' || !token) {
    mensaje.value =
      'No se encontró el código de verificación.'
    cargando.value = false

    return
  }


  try {
    // Enviamos el token al backend.
    await verificarCorreo(token)

    // Si el backend responde correctamente,
    // mostramos la pantalla de éxito.
    exito.value = true
  } catch (e) {

    // Si el token es inválido, expiró, etc.,
    // mostramos el mensaje recibido
    mensaje.value =
      e.message || 'No se pudo confirmar el correo.'
  } finally {
    cargando.value = false

  }
}

function irAlLogin() {

  router.push('/login')

}


// Ejecutamos la verificación automáticamente al montar
// el componente

onMounted(() => {
  confirmarCorreo()

})

</script>


<style scoped>


.verificacion-page {
  min-height: 100vh;
  background: #060c22;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 30px;
}

.verificacion-card {

  width: 500px;
  max-width: 100%;
  padding: 50px 40px;
  background: #0a1028;
  border: 3px solid white;
  border-radius: 30px;
  color: white;
  text-align: center;

}

h2 {

  font-size: 2rem;
  margin: 25px 0 15px;

}

p {
  font-size: 1rem;
  color: #d0d4e0;
  margin-bottom: 30px;

}


.icono-exito {

  width: 80px;
  height: 80px;
  margin: auto;
  border: 3px solid #00ff99;
  border-radius: 50%;
  color: #00ff99;
  font-size: 50px;
  display: flex;
  align-items: center;
  justify-content: center;

}

.icono-error {
  width: 80px;
  height: 80px;
  margin: auto;
  border: 3px solid #ff6b6b;
  border-radius: 50%;
  color: #ff6b6b;
  font-size: 40px;
  display: flex;
  align-items: center;
  justify-content: center;

}

.login-btn {

  width: 250px;
  font-size: 1.1rem;

}


@media (max-width: 600px) {

  .verificacion-card {
    padding: 40px 25px;
  }

  h2 {
    font-size: 1.6rem;
  }

  .login-btn {
    width: 100%;
  }

}

</style>
