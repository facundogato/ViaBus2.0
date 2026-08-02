# ViaBus (quasar-project)

Plataforma de gestión de terminal de ómnibus para Melo, Uruguay. Frontend en
Vue 3 + Quasar, backend en PHP + SQLite.

Toda la información que muestra la app (ciudades, empresas, servicios,
asientos disponibles, reservas) sale del backend — no hay datos
hardcodeados en el frontend.

## Instalar dependencias

```bash
pnpm install
# o
npm install
```

Se necesita además **PHP 8+ con la extensión `pdo_sqlite`** (viene incluida
en la mayoría de las instalaciones de PHP). No hace falta instalar MySQL ni
ninguna otra base de datos: se genera sola en `viabus-backend/data/viabus.sqlite`
la primera vez que se usa.

## Levantar todo en desarrollo

```bash
./dev.sh
```

Esto arranca:
- el backend PHP en `http://localhost:8000`
- el frontend de Quasar en `http://localhost:9000`

El frontend está configurado para redirigir automáticamente las llamadas a
`/api/*` hacia el backend (ver `devServer.proxy` en `quasar.config.js`), así
que no hay que tocar nada más.

### Levantar cada parte por separado

```bash
# Backend
php -S localhost:8000 -t viabus-backend viabus-backend/router.php

# Frontend (en otra terminal)
pnpm dev
```

## Build para producción

```bash
pnpm build
```

Para producción, hay que publicar `viabus-backend/` en el mismo dominio que
el frontend bajo la ruta `/api` (ver `viabus-backend/README.md`).

## Estructura

```
src/
├── assets/              # imágenes, fuentes, hojas de estilo globales
├── components/
│   ├── common/          # piezas reutilizables en cualquier parte de la app
│   │   (NavMenuItem, LogoutButton, StatCard, StatusBadge, LiveClock)
│   ├── layout/           # AppSidebar (el menú lateral fijo)
│   ├── home/             # HeroBanner, StatsSection, QuickActionsGrid, LocationMap
│   ├── company/          # CompanyList
│   └── dashboard/        # DashboardBoard y sus piezas internas (Header, Metrics,
│                          # MetricCard, Tabs, Table, TableRow)
├── layouts/
│   └── MainLayout.vue    # sidebar + área de contenido; lo usan todas las
│                          # páginas salvo NotFoundPage
├── pages/                # una página por ruta (ver tabla de rutas abajo)
├── router/index.js       # definición de rutas
├── services/api.js       # cliente HTTP hacia el backend (un export por endpoint)
└── css/, boot/           # scaffolding estándar de Quasar

viabus-backend/
├── router.php            # router del servidor embebido de PHP
├── config/               # conexión PDO (database.php) y helpers de CORS/JSON
├── api/                  # un archivo por recurso (ciudades, empresas,
│                          # servicios, reservas, pasajeros, tablero)
├── sql/schema.sql         # tablas + datos de ejemplo
└── data/viabus.sqlite     # se genera solo, no se versiona
```

### Rutas actuales

| Ruta | Página | Componente |
|------|--------|------------|
| `/terminal` | Inicio / presentación | `pages/HomePage.vue` |
| `/tablero` | Tablero de salidas en vivo | `pages/DashboardPage.vue` |
| `/comprar` | Reserva de pasajes (multi-paso) | `pages/ReservationPage.vue` |
| `/pagos` | Placeholder de pagos | `pages/PaymentsPage.vue` |
| `/perfil` | Perfil / historial del usuario | `pages/ProfilePage.vue` |
| `/info` | Info general y empresas | `pages/CompaniesPage.vue` |
| `/login` | Inicio de sesión | `pages/LoginPage.vue` |
| `/:catchAll` | 404 | `pages/NotFoundPage.vue` |

### Convención de nombres

- **Páginas** (`src/pages/`): `NombreDescriptivoPage.vue`, PascalCase.
- **Componentes**: PascalCase, nombre = qué hace (`CompanyList`, no `Boton2`).
- **Carpeta de componentes**: por dominio/feature (`home/`, `dashboard/`,
  `company/`), y `common/` solo para piezas realmente genéricas usadas en
  más de un dominio.
- **Variables/props**: en español, igual que el resto del código (backend
  y frontend son consistentemente en español) — lo importante es que sean
  descriptivas, no el idioma.
- **CSS**: clases en `kebab-case` dentro de `<style scoped>` de cada
  componente; evitar estilos globales salvo en `assets/styles/`.

### Cómo agregar una página nueva

1. Crear `src/pages/MiNuevaPage.vue`. Envolver el contenido en `<MainLayout>`
   (a menos que sea una pantalla especial como el 404) para heredar el
   sidebar y el padding estándar.
2. Agregar la ruta en `src/router/index.js`.
3. Si necesita aparecer en el menú, agregar un `<NavMenuItem>` en
   `src/components/layout/AppSidebar.vue`.

### Cómo agregar un endpoint nuevo en el backend

1. Crear `viabus-backend/api/mi_recurso.php`, siguiendo el patrón de los
   existentes: `require '../config/database.php'` y `../config/helpers.php`,
   switch por `$_SERVER['REQUEST_METHOD']`, consultas siempre con PDO
   *prepared statements*.
2. Si el recurso necesita una tabla nueva, agregarla en `sql/schema.sql`
   (borrar `data/viabus.sqlite` en desarrollo para regenerarla con el
   esquema nuevo).
3. Agregar la función correspondiente en `src/services/api.js` (un
   `export function` por operación, mismo estilo que las existentes).

### Cómo agregar una funcionalidad más grande

El proyecto está pensado para poder sumar, sin reorganizar de nuevo:
compra de pasajes real, selección de asientos, historial de reservas,
roles de administrador/empleado/chofer, gestión de empresas/terminales/
horarios, notificaciones y reportes. La convención a seguir:

- Nueva sección de UI → nueva carpeta en `src/components/<dominio>/` +
  su página en `src/pages/`.
- Nuevo recurso de datos → nueva tabla en `schema.sql` + su archivo en
  `viabus-backend/api/` + sus funciones en `services/api.js`.
- No se necesitan patrones adicionales (Singleton, Repository, DI, etc.)
  para esto — cada endpoint PHP ya es, en sí mismo, la capa de acceso a
  datos, y cada componente Vue ya es la unidad de reutilización de UI.

## Nota sobre la base de datos

El proyecto usa **SQLite** (un solo archivo, sin servidor que instalar ni
configurar) en vez de MySQL. Es una decisión deliberada del equipo, documentada
en `viabus-backend/README.md`, para que cualquiera pueda levantar el proyecto
sin instalar un motor de base de datos aparte. Si el objetivo académico
requiere específicamente MySQL, es un cambio de infraestructura (no de
organización de archivos) que conviene evaluar aparte, ya que implica
adaptar `config/database.php` y las consultas que dependan de sintaxis
específica de SQLite.


## Customizar la configuración

Ver [Configuring quasar.config.js](https://v2.quasar.dev/quasar-cli-vite/quasar-config-js).
