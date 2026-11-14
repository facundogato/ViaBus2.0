<!-- Página de registro (ruta /registro) -->
<template>

  <MainLayout>

    <!-- Contenedor del registro centrado en la pantalla -->
    <div class="register-page flex flex-center">

      <!-- ===================== TARJETA REGISTRO ===================== -->
      <q-card flat class="register-card">

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
          Crea tu cuenta en
          <span class="brand">ViaBus</span>
        </h3>

        <!-- Nombres -->
        <q-input
          v-model="form.nombre"
          outlined
          dark
          label="Nombre"
          color="white"
          class="q-mb-md"
          :disable="cargando"
        />

        <q-input
          v-model="form.apellido"
          outlined
          dark
          label="Apellido"
          color="white"
          class="q-mb-md"
          :disable="cargando"
        />

        <!-- Correo -->
        <q-input
          v-model="form.email"
          outlined
          dark
          type="email"
          label="Correo electrónico"
          color="white"
          class="q-mb-md"
          :disable="cargando"
        />

        <!-- Contraseña -->
        <q-input
          v-model="form.password"
          outlined
          dark
          label="Contraseña (mín. 6 caracteres)"
          type="password"
          color="white"
          class="q-mb-md"
          :disable="cargando"
          @keyup.enter="hacerRegistro"
        />

        <!-- Mensaje de error -->
        <p v-if="error" class="error-msg">{{ error }}</p>

        <!-- Mensaje de éxito -->
        <p v-if="mensaje" class="ok-msg">{{ mensaje }}</p>

        <!-- Botón para registrarse -->
        <div class="text-center q-mt-xl">
          <q-btn
            rounded
            outline
            color="white"
            :label="cargando ? 'Creando cuenta...' : 'Registrarse'"
            :disable="cargando"
            class="register-btn"
            @click="hacerRegistro"
          />
        </div>

        <!-- Link para login -->
        <div class="text-center q-mt-lg">
          <p class="login-link">
            ¿Ya tienes cuenta?
            <router-link to="/login">Inicia sesión aquí</router-link>
          </p>
        </div>

      </q-card>

    </div>

  </MainLayout>

</template>

<script setup>

import { ref } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import logo from '../assets/logo1.jpg'
import { registrarse } from '../services/api'

const router = useRouter()

const form = ref({
  nombre: '',
  apellido: '',
  email: '',
  password: ''
})

const cargando = ref(false)
const error = ref('')
const mensaje = ref('')

async function hacerRegistro() {
  // Validaciones
  if (!form.value.nombre.trim()) {
    error.value = 'El nombre es obligatorio'
    return
  }
  if (!form.value.apellido.trim()) {
    error.value = 'El apellido es obligatorio'
    return
  }
  if (!form.value.email.trim()) {
    error.value = 'El correo es obligatorio'
    return
  }
  if (!form.value.password || form.value.password.length < 6) {
    error.value = 'La contraseña debe tener al menos 6 caracteres'
    return
  }

  cargando.value = true
  error.value = ''
  mensaje.value = ''

  try {
    const respuesta = await registrarse({
      nombre: form.value.nombre.trim(),
      apellido: form.value.apellido.trim(),
      email: form.value.email.trim(),
      password: form.value.password
    })

    // Guardar datos para la verificación
    localStorage.setItem('user_id_pendiente', respuesta.user_id)
    localStorage.setItem('email_pendiente', form.value.email.trim())

    mensaje.value = '¡Cuenta creada! Redirigiendo a verificación...'

    // Redirigir a verificación
    setTimeout(() => {
      router.push({
        path: '/verificar',
        query: { email: form.value.email.trim() }
      })
    }, 1500)

  } catch (e) {
    error.value = e.message || 'Error al registrarse'
  } finally {
    cargando.value = false
  }
}

</script>

<style scoped>

/* Fondo de la pantalla del registro */
.register-page {
  background: transparent;
  width: 100%;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

/* Tarjeta del registro */
.register-card {
  background: var(--color-sidebar);
  border: 3px solid white;
  border-radius: 30px;
  color: white;
  width: 100%;
  max-width: 520px;
  padding: 40px;
}

/* Título del registro */
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
.register-btn {
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

.ok-msg {
  color: #00ff99;
  font-size: 0.85rem;
  margin-top: 12px;
  text-align: center;
  font-weight: 500;
}

/* Link para login */
.login-link {
  font-size: 0.9rem;
  color: #ccc;
}

.login-link a {
  color: #f4b400;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
}

.login-link a:hover {
  color: #ffd700;
  text-decoration: underline;
}

@media (max-width: 700px) {
  .register-page {
    padding: 20px;
    min-height: auto;
  }

  .register-card {
    width: 100%;
    padding: 30px 25px;
  }

  .title {
    font-size: 1.8rem;
  }

  .register-btn {
    width: 100%;
  }

  .logo-small {
    width: 100px;
  }
}

</style>
