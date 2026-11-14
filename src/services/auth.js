// Manejo de sesión del lado del cliente.
//
// Al iniciar sesión, el backend (api/auth/login.php) devuelve los datos del
// usuario y un TOKEN. Acá guardamos las dos cosas en localStorage:
//   - viabus_usuario: los datos (nombre, rol, etc.) para mostrar/ocultar cosas
//   - token:          se manda en la cabecera "Authorization: Bearer ..." a las
//                     APIs protegidas (tarjetas, panel de tarjetas, escáner del chofer)

import { ref, computed } from 'vue'

const STORAGE_KEY = 'viabus_usuario'
const TOKEN_KEY = 'token'

function cargarUsuarioGuardado() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

// Estado compartido entre todos los componentes que llamen a useAuth().
const usuario = ref(cargarUsuarioGuardado())

// Token de la sesión actual (o null si no hay)
export function obtenerToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function useAuth() {
  const isAuthenticated = computed(() => usuario.value !== null)
  const isAdmin = computed(() => usuario.value?.rol === 'admin')
  const isChofer = computed(() => usuario.value?.rol === 'chofer')

  // El token es opcional: al editar el perfil se vuelve a llamar sin token
  // y se conserva el que ya estaba guardado.
  function guardarSesion(datosUsuario, token) {
    usuario.value = datosUsuario
    localStorage.setItem(STORAGE_KEY, JSON.stringify(datosUsuario))
    if (token) localStorage.setItem(TOKEN_KEY, token)
  }

  function cerrarSesion() {
    usuario.value = null
    localStorage.removeItem(STORAGE_KEY)
    localStorage.removeItem(TOKEN_KEY)
  }

  return { usuario, isAuthenticated, isAdmin, isChofer, guardarSesion, cerrarSesion }
}
