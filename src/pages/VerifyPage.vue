<!-- Página de verificación de email (ruta /verificar) -->
<template>

  <MainLayout>

    <!-- Contenedor de verificación centrado en la pantalla -->
    <div class="verify-page flex flex-center">

      <!-- ===================== TARJETA VERIFICACIÓN ===================== -->
      <q-card flat class="verify-card">

        <!-- Logo -->
        <div class="text-center q-mb-lg">
          <img
            :src="logo"
            alt="Logo"
            class="logo-small"
          >
        </div>

        <!-- Título -->
        <h3 class="title">
          Verifica tu
          <span class="brand">Email</span>
        </h3>

        <!-- Email mostrado -->
        <div class="email-display">
          {{ emailMostrado }}
        </div>

        <!-- PASO 1: Solicitar código -->
        <div v-if="paso === 1" class="paso-section">
          <p class="paso-titulo">Paso 1: Solicitar código</p>
          <q-btn
            rounded
            outline
            color="white"
            label="Enviar código de verificación"
            class="verify-btn"
            :disable="cargando"
            @click="solicitudCodigo"
          />
          <p v-if="mensajeReintento" class="reintento-msg">
            {{ mensajeReintento }}
          </p>
        </div>

        <!-- PASO 2: Ingresar código -->
        <div v-if="paso === 2" class="paso-section">
          <p class="paso-titulo">Paso 2: Ingresa el código (6 dígitos)</p>

          <q-input
            v-model="codigo"
            outlined
            dark
            label="Código"
            maxlength="6"
            inputmode="numeric"
            class="q-mb-md codigo-input"
            :disable="cargando"
            @keyup.enter="verificarCodigo"
          />

          <div class="button-group">
            <q-btn
              rounded
              outline
              color="white"
              :label="cargando ? 'Verificando...' : 'Verificar'"
              class="verify-btn"
              :disable="cargando || codigo.length !== 6"
              @click="verificarCodigo"
            />
            <q-btn
              rounded
              outline
              color="white"
              label="Reenviar"
              class="verify-btn"
              :disable="cargando"
              @click="reenviarCodigo"
            />
          </div>
        </div>

        <!-- Mensaje de error -->
        <p v-if="error" class="error-msg">{{ error }}</p>

        <!-- Mensaje de éxito -->
        <p v-if="exito" class="ok-msg">{{ exito }}</p>

        <!-- Cargando -->
        <p v-if="cargando" class="loading-msg">Procesando...</p>

        <!-- Link para ir a login -->
        <div class="text-center q-mt-lg">
          <router-link to="/login" class="back-link">← Volver a login</router-link>
        </div>

      </q-card>

    </div>

  </MainLayout>

</template>

<script setup>

import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import logo from '../assets/logo1.jpg'
import { sendVerificationCode, verifyCode, resendCode } from '../services/api'

const route = useRoute()
const router = useRouter()

// Estado del formulario
const paso = ref(1)
const codigo = ref('')
const cargando = ref(false)
const error = ref('')
const exito = ref('')
const mensajeReintento = ref('')
const contador = ref(0)

// Datos del usuario
let userId = null
let email = null

const emailMostrado = computed(() => {
  if (email) {
    // Ocultar parcialmente el email: usuario@doma...
    const [usuario, dominio] = email.split('@')
    return `${usuario}@${dominio.substring(0, 3)}...`
  }
  return 'Cargando...'
})

// Cargar datos al montar
onMounted(() => {
  email = route.query.email || localStorage.getItem('email_pendiente')
  userId = parseInt(
    route.query.user_id || localStorage.getItem('user_id_pendiente') || 0
  )

  if (!userId || !email) {
    error.value = 'Error: datos de usuario faltantes. Por favor regístrate primero.'
    setTimeout(() => {
      router.push('/registro')
    }, 2000)
  }
})

async function solicitudCodigo() {
  if (!email) return

  cargando.value = true
  error.value = ''
  exito.value = ''
  mensajeReintento.value = ''

  try {
    await sendVerificationCode(email)
    exito.value = '✓ Código enviado a tu correo'
    paso.value = 2
    contador.value = 60
    iniciarContador()
  } catch (e) {
    error.value = e.message || 'Error al enviar el código'
  } finally {
    cargando.value = false
  }
}

async function verificarCodigo() {
  if (codigo.value.length !== 6 || !userId) return

  cargando.value = true
  error.value = ''
  exito.value = ''

  try {
    await verifyCode(userId, codigo.value)
    exito.value = '✓ ¡Email verificado correctamente!'

    // Limpiar localStorage
    localStorage.removeItem('user_id_pendiente')
    localStorage.removeItem('email_pendiente')

    // Redirigir a login
    setTimeout(() => {
      router.push('/login')
    }, 1500)
  } catch (e) {
    error.value = e.message || 'Código incorrecto'
  } finally {
    cargando.value = false
  }
}

async function reenviarCodigo() {
  if (!userId) return

  cargando.value = true
  error.value = ''
  exito.value = ''

  try {
    await resendCode(userId)
    exito.value = '✓ Nuevo código enviado'
    codigo.value = ''
    contador.value = 60
    iniciarContador()
  } catch (e) {
    error.value = e.message || 'Error al reenviar el código'
  } finally {
    cargando.value = false
  }
}

function iniciarContador() {
  const intervalo = setInterval(() => {
    contador.value--
    if (contador.value <= 0) {
      clearInterval(intervalo)
      mensajeReintento.value = ''
    } else {
      mensajeReintento.value = `Puedes reenviar en ${contador.value}s`
    }
  }, 1000)
}

</script>

<style scoped>

/* Fondo de la pantalla */
.verify-page {
  background: #060c22;
  width: 100%;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

/* Tarjeta de verificación */
.verify-card {
  background: #0a1028;
  border: 3px solid white;
  border-radius: 30px;
  color: white;
  width: 100%;
  max-width: 520px;
  padding: 40px;
}

/* Título */
.title {
  font-size: 2.5rem;
  font-weight: 300;
  margin-bottom: 35px;
  text-align: center;
}

/* Color del nombre de la aplicación */
.brand {
  color: #f4b400;
  font-weight: bold;
}

/* Logo */
.logo-small {
  width: 120px;
  border-radius: 12px;
}

/* Display de email */
.email-display {
  background: #0f1530;
  border: 1px solid #2a3a5a;
  border-radius: 12px;
  padding: 12px;
  text-align: center;
  font-size: 0.9rem;
  color: #b0c4de;
  margin-bottom: 30px;
}

/* Sección de paso */
.paso-section {
  margin: 20px 0;
}

.paso-titulo {
  font-size: 0.95rem;
  color: #ccc;
  margin-bottom: 15px;
  font-weight: 500;
}

/* Input del código */
.codigo-input {
  text-align: center;
}

:deep(.codigo-input .q-field__control) {
  font-size: 1.5rem;
  letter-spacing: 6px;
  border-radius: 18px;
}

/* Grupo de botones */
.button-group {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

/* Botones */
.verify-btn {
  width: 200px;
  font-size: 1rem;
}

/* Redondea los bordes de los QInput de Quasar */
:deep(.q-field__control) {
  border-radius: 18px;
}

/* Mensajes de estado */
.error-msg {
  color: #ff6b6b;
  font-size: 0.85rem;
  margin-top: 12px;
  text-align: center;
}

.ok-msg {
  color: #00ff99;
  font-size: 0.85rem;
  margin-top: 12px;
  text-align: center;
  font-weight: 500;
}

.loading-msg {
  color: #4db8ff;
  font-size: 0.85rem;
  margin-top: 12px;
  text-align: center;
  font-weight: 500;
}

.reintento-msg {
  color: #888;
  font-size: 0.8rem;
  text-align: center;
  margin-top: 10px;
  font-style: italic;
}

/* Link para volver */
.back-link {
  color: #f4b400;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
  font-size: 0.9rem;
}

.back-link:hover {
  color: #ffd700;
  text-decoration: underline;
}

@media (max-width: 700px) {
  .verify-page {
    padding: 20px;
    min-height: auto;
  }

  .verify-card {
    width: 100%;
    padding: 30px 25px;
  }

  .title {
    font-size: 1.8rem;
  }

  .verify-btn {
    width: 100%;
  }

  .button-group {
    flex-direction: column;
  }

  .logo-small {
    width: 100px;
  }
}

</style>
