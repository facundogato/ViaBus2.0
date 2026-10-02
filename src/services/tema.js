// Manejo del tema (oscuro / claro) de ViaBus.
// El tema se guarda en localStorage y se aplica agregando o quitando la clase
// "modo-claro" en <body>. Los colores de cada tema están en src/css/app.css.

import { ref } from 'vue'

const CLAVE = 'tema'

// true = modo claro, false = modo oscuro (el de siempre)
const esClaro = ref(false)

function aplicar() {
  document.body.classList.toggle('modo-claro', esClaro.value)
}

// Se llama una sola vez al iniciar la app (ver src/boot/tema.js)
export function iniciarTema() {
  try {
    esClaro.value = localStorage.getItem(CLAVE) === 'claro'
  } catch {
    esClaro.value = false
  }
  aplicar()
}

// Se usa desde los componentes: const { esClaro, alternarTema } = useTema()
export function useTema() {
  function alternarTema() {
    esClaro.value = !esClaro.value
    try {
      localStorage.setItem(CLAVE, esClaro.value ? 'claro' : 'oscuro')
    } catch {
      /* si el navegador bloquea localStorage, igual cambia el tema */
    }
    aplicar()
  }

  return { esClaro, alternarTema }
}
