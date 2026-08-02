# ViaBus — Backend PHP + SQLite

Backend REST en PHP para el sistema de reservas de pasajes. Usa **SQLite**
(un solo archivo, sin necesidad de instalar ni configurar un servidor MySQL).

---

## Estructura

```
viabus-backend/
├── router.php            ← router para el servidor embebido de PHP
├── config/
│   ├── database.php       ← conexión PDO a SQLite (auto-inicializa la DB)
│   └── helpers.php        ← CORS, JSON, utilidades
├── api/
│   ├── servicios.php      ← CRUD de viajes
│   ├── reservas.php       ← CRUD de reservas + asientos ocupados
│   ├── ciudades.php       ← listado de ciudades
│   ├── empresas.php       ← listado de empresas (con descripción y logo)
│   └── pasajeros.php      ← CRUD de pasajeros
├── data/                  ← acá se crea viabus.sqlite (se ignora en git)
└── sql/
    └── schema.sql         ← tablas + los 214 servicios reales del frontend
```

---

## Cómo correrlo

No hace falta instalar ni configurar nada de bases de datos: la primera vez
que se llama a cualquier endpoint, `config/database.php` crea automáticamente
`data/viabus.sqlite` a partir de `sql/schema.sql`.

Solo se necesita PHP con la extensión `pdo_sqlite` (viene incluida en la
mayoría de las instalaciones de PHP 8+):

```bash
php -S localhost:8000 -t viabus-backend viabus-backend/router.php
```

O, más simple, usar el script `dev.sh` de la raíz del proyecto, que levanta
el backend y el frontend juntos (ver el README principal).

### Reiniciar los datos

Si se quiere volver a los datos originales (por ejemplo, después de crear
reservas de prueba), simplemente borrar el archivo y se regenera solo:

```bash
rm viabus-backend/data/viabus.sqlite
```

---

## Endpoints

### Ciudades
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/ciudades.php` | Lista todas las ciudades |

### Empresas
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/empresas.php` | Lista todas las empresas (nombre, logo, descripción) |

### Servicios (viajes)
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/servicios.php` | Todos los servicios activos |
| GET | `/api/servicios.php?id=5` | Servicio por ID |
| GET | `/api/servicios.php?origen=Melo&destino=Montevideo` | Buscar por ruta |
| POST | `/api/servicios.php` | Crear servicio |
| PUT | `/api/servicios.php?id=5` | Editar servicio |
| DELETE | `/api/servicios.php?id=5` | Desactivar servicio |

#### POST body (crear servicio):
```json
{
  "origen": "Melo",
  "destino": "Montevideo",
  "empresa": "Ega",
  "hora": "07:00",
  "tipo": "De Linea",
  "duracion": "5h 30m",
  "precio": 450.00,
  "asientos": 40,
  "anden": "A-01",
  "bus": "MELMON-1234",
  "estado": "horario"
}
```

### Reservas
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/reservas.php` | Todas las reservas |
| GET | `/api/reservas.php?id=3` | Reserva por ID |
| GET | `/api/reservas.php?servicio_id=5&fecha=2026-08-15` | Asientos ocupados |
| POST | `/api/reservas.php` | Crear reserva |
| PUT | `/api/reservas.php?id=3` | Cambiar estado |
| DELETE | `/api/reservas.php?id=3` | Cancelar reserva |

#### POST body (crear reserva):
```json
{
  "servicio_id": 5,
  "fecha_viaje": "2026-08-15",
  "asiento": 12,
  "pasajero": {
    "nombre": "Juan",
    "apellido": "García",
    "documento": "12345678",
    "email": "juan@email.com",
    "telefono": "099123456"
  }
}
```
El campo `total` es opcional; si no se envía, se usa el precio del servicio.

#### PUT body (cambiar estado):
```json
{ "estado": "confirmada" }
```
Estados válidos: `pendiente` | `confirmada` | `cancelada`

#### Respuesta asientos ocupados:
```json
{ "asientos_ocupados": [5, 12, 23] }
```

### Pasajeros
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/pasajeros.php` | Todos los pasajeros |
| GET | `/api/pasajeros.php?id=3` | Por ID |
| GET | `/api/pasajeros.php?documento=12345678` | Por documento (CI) |
| POST | `/api/pasajeros.php` | Crear pasajero |
| PUT | `/api/pasajeros.php?id=3` | Editar pasajero |

---

## Conexión con el frontend

El frontend (Quasar) ya está conectado: usa `src/services/api.js`, que llama
a rutas relativas (`/api/servicios.php`, etc.). En desarrollo, `quasar.config.js`
redirige automáticamente `/api/*` hacia `http://localhost:8000` (ver
`devServer.proxy`), así que no hay que hardcodear ninguna URL.

En producción, hay que publicar la carpeta `viabus-backend/` en el mismo
dominio que el frontend, bajo la ruta `/api`, o ajustar `BASE` en
`src/services/api.js` para que apunte a la URL del backend.

---

## Notas

- Los precios, duraciones, asientos, andenes, buses y estados se calcularon
  a partir de los 214 servicios reales que estaban hardcodeados originalmente
  en el frontend (hoy `src/pages/ReservationPage.vue`), usando distancias
  aproximadas por ruta.
  Son valores realistas para que la app funcione de punta a punta, no datos
  oficiales de las empresas.
- Los borrados son lógicos (campo `activo` / estado `cancelada`), no se
  elimina nada de la base.
- La tabla `reservas` tiene una restricción `UNIQUE` que impide duplicar
  asiento + servicio + fecha.
