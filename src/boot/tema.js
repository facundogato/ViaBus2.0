// Boot file: se ejecuta antes de mostrar la app, así el tema guardado
// se aplica de entrada y no hay un parpadeo oscuro -> claro.
import { iniciarTema } from '../services/tema'

export default () => {
  iniciarTema()
}
