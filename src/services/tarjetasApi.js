// Llamadas a las APIs protegidas (tarjetas y escáner).
// Agrega solo el token de sesión y convierte los errores del backend en excepciones
// con un mensaje legible (e.message), para mostrarlo directo en pantalla.

import { obtenerToken } from './auth'

export async function llamarApi(ruta, { metodo = 'GET', cuerpo } = {}) {
  let respuesta
  try {
    respuesta = await fetch(ruta, {
      method: metodo,
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + (obtenerToken() || '')
      },
      body: cuerpo ? JSON.stringify(cuerpo) : undefined
    })
  } catch {
    throw new Error('Sin conexión con el servidor')
  }

  const datos = await respuesta.json().catch(() => ({}))
  if (!respuesta.ok) {
    if (respuesta.status === 401) {
      throw new Error('Tu sesión venció. Volvé a iniciar sesión.')
    }
    throw new Error(datos.error || datos.message || 'Error del servidor')
  }
  return datos
}
