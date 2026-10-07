<!-- Página de inicio de sesión (ruta /login) -->
<template>

  <MainLayout>

    <!-- Contenedor del login centrado en la pantalla -->
    <div class="login-page flex flex-center">

      <!-- ===================== TARJETA LOGIN ===================== -->
      <q-card flat class="login-card">
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
          Inicia sesión en
          <span class="brand">ViaBus</span>
        </h3>

        <!-- Campo para ingresar el correo -->
        <q-input
          v-model="loginForm.email"
          outlined
          dark
          type="email"
          label="Correo electrónico"
          color="white"
          class="q-mb-md"
          :disable="cargando"
          @keyup.enter="hacerLogin"
        />

        <!-- Campo para ingresar la contraseña -->
        <q-input
          v-model="loginForm.password"
          outlined
          dark
          label="Contraseña"
          type="password"
          color="white"
          :disable="cargando"
          @keyup.enter="hacerLogin"
        />

        <!-- Mensaje de error -->
        <p v-if="error" class="error-msg">{{ error }}</p>

        <!-- Mensaje si email no verificado -->
        <p v-if="mensajeVerificacion" class="info-msg">{{ mensajeVerificacion }}</p>

        <!-- Botón para iniciar sesión -->
        <div class="text-center q-mt-xl">
          <q-btn
            rounded
            outline
            color="white"
            :label="cargando ? 'Ingresando...' : 'Iniciar Sesión'"
            :disable="cargando"
            class="login-btn"
            @click="hacerLogin"
          />
        </div>

        <!-- Link para registrarse -->
        <div class="text-center q-mt-lg">
          <p class="signup-link">
            ¿No tienes cuenta?
            <router-link to="/registro">Regístrate aquí</router-link>
          </p>
        </div>

      </q-card>

    </div>

  </MainLayout>

</template>

<script setup>

import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import logo from '../assets/logo1.jpg'
import { login } from '../services/api'
import { useAuth } from '../services/auth'

const route = useRoute()
const router = useRouter()
const { guardarSesion } = useAuth()

// Si llegamos acá desde una ruta protegida (ej. el paso de pago),
// después de loguearnos volvemos a esa misma ruta.
function irLuegoDeAutenticar() {
  const destino = typeof route.query.redirect === 'string' ? route.query.redirect : '/terminal'
  router.push(destino)
}

// ---- Login ----
const loginForm = ref({ email: '', password: '' })
const cargando = ref(false)
const error = ref('')
const mensajeVerificacion = ref('')

async function hacerLogin() {
  if (!loginForm.value.email || !loginForm.value.password) {
    error.value = 'Completá correo y contraseña'
    return
  }
  cargando.value = true
  error.value = ''
  mensajeVerificacion.value = ''
  try {
    const usuario = await login(loginForm.value.email, loginForm.value.password)
    guardarSesion(usuario.user)
    irLuegoDeAutenticar()
  } catch (e) {
    // Si el error menciona verificación de email, ofrecemos redirigir a verificar
    if (e.message.includes('verificar') || e.message.includes('EMAIL_NOT_VERIFIED')) {
      mensajeVerificacion.value = 'Debes verificar tu email. Redirigiendo...'
      setTimeout(() => {
        router.push({ path: '/verificar', query: { email: loginForm.value.email } })
      }, 1500)
    } else {
      error.value = e.message
    }
  } finally {
    cargando.value = false
  }
}

</script>

<style scoped>

/* Fondo de la pantalla del login */
.login-page {
  background: transparent;
  width: 100%;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

/* Tarjeta del login */
.login-card {
  background: var(--color-sidebar);
  border: 3px solid white;
  border-radius: 30px;
  color: white;
  width: 100%;
  max-width: 520px;
  padding: 40px;
}

/* Título del login */
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

/* Tamaño del logo pequeño */
.logo-small {
  width: 120px;
  border-radius: 12px;
}

/* Redondea los bordes de los QInput de Quasar */
:deep(.q-field__control) {
  border-radius: 18px;
}

/* Tamaño del botón */
.login-btn {
  width: 250px;
  font-size: 1.2rem;
}

/* Mensajes de estado */
.error-msg {
  color: #ff6b6b;
  font-size: 0.85rem;
  margin-top: 12px;
  text-align: center;
}

.info-msg {
  color: #4db8ff;
  font-size: 0.85rem;
  margin-top: 12px;
  text-align: center;
  font-weight: 500;
}

/* Link para registro */
.signup-link {
  font-size: 0.9rem;
  color: #ccc;
}

.signup-link a {
  color: #f4b400;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
}

.signup-link a:hover {
  color: #ffd700;
  text-decoration: underline;
}

@media (max-width: 700px) {
  .login-page {
    padding: 20px;
    min-height: auto;
  }

  .login-card {
    width: 100%;
    padding: 30px 25px;
  }

  .title {
    font-size: 1.8rem;
  }

  .login-btn {
    width: 100%;
  }

  .logo-small {
    width: 100px;
  }
}

</style>
