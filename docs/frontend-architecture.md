# Frontend Architecture - Trazabilidad Frontend

## Project Location
`E:\InterFruit\sistema-trazabilidad3\trazabilidad-frontend\`

## Tech Stack
- **Framework:** Angular 19.2 (standalone components, no NgModules)
- **Language:** TypeScript 5.7
- **Reactive:** RxJS 7.8, Angular Signals
- **Styling:** Tailwind CSS 4.1 (via PostCSS)
- **Testing:** Karma + Jasmine
- **Build:** Angular CLI 19.2

## Project Structure
```
trazabilidad-frontend/
├── src/
│   ├── app/
│   │   ├── app.component.ts          # Root component
│   │   ├── app.config.ts             # Providers (HTTP, router, interceptors)
│   │   ├── app.routes.ts             # Root routes (lazy loading)
│   │   ├── core/                     # Singleton services & infrastructure
│   │   │   ├── constants/
│   │   │   │   └── menu.config.ts    # Sidebar menu with role-based access
│   │   │   ├── guards/
│   │   │   │   └── auth.guard.ts     # Functional guard (checks localStorage token)
│   │   │   ├── interceptors/
│   │   │   │   └── auth.interceptor.ts  # Adds Bearer token + X-Campahna-Id
│   │   │   ├── models/
│   │   │   │   ├── auth.model.ts     # User, LoginResponse
│   │   │   │   ├── api.model.ts      # Pagination, ApiResponse, SharedItem, FilterParams
│   │   │   │   └── core.model.ts     # ALL business entity interfaces
│   │   │   └── services/
│   │   │       ├── auth.service.ts       # Login, logout, hasRole(), session
│   │   │       ├── campaign.service.ts   # Active campaign context management
│   │   │       ├── notification.service.ts  # Toast notifications (signal-based)
│   │   │       └── roles.service.ts      # Role fetching
│   │   ├── features/                 # Feature modules (lazy loaded)
│   │   │   ├── auth/                 # Authentication
│   │   │   │   └── components/login/
│   │   │   ├── dashboard/            # Dashboard
│   │   │   │   ├── dashboard.routes.ts
│   │   │   │   └── components/home/
│   │   │   ├── maestros/             # Master data (Areas, Clientes, Productores)
│   │   │   │   ├── maestros.component.ts    # Tab container with sub-nav
│   │   │   │   ├── maestros.routes.ts
│   │   │   │   ├── components/
│   │   │   │   │   ├── areas/areas.component.ts
│   │   │   │   │   ├── clientes/clientes.component.ts
│   │   │   │   │   └── productores/productores.component.ts
│   │   │   │   └── services/
│   │   │   │       ├── area.service.ts
│   │   │   │       ├── cliente.service.ts
│   │   │   │       └── productor.service.ts
│   │   │   ├── lotes/                # Batch management
│   │   │   │   ├── lotes.routes.ts
│   │   │   │   ├── components/
│   │   │   │   │   ├── lote-list/
│   │   │   │   │   ├── lote-form/
│   │   │   │   │   └── lote-detail/  # Includes Recepcion + Descartes inline
│   │   │   │   └── services/
│   │   │   │       ├── lote.service.ts
│   │   │   │       ├── recepcion.service.ts
│   │   │   │       ├── descarte-lote.service.ts
│   │   │   │       └── parametro.service.ts  # With in-memory cache
│   │   │   ├── trazabilidad/         # Traceability (Calibrado + ProcesoLote)
│   │   │   │   ├── trazabilidad.routes.ts
│   │   │   │   ├── components/
│   │   │   │   │   ├── trazabilidad-list/
│   │   │   │   │   └── trazabilidad-lote/  # Calibrado + Proceso inline
│   │   │   │   └── services/
│   │   │   │       ├── calibrado.service.ts
│   │   │   │       └── proceso-lote.service.ts
│   │   │   ├── camaras/              # Cold storage
│   │   │   │   ├── camaras.routes.ts
│   │   │   │   ├── components/camaras-list/
│   │   │   │   └── services/pallet-camara.service.ts
│   │   │   ├── despacho/             # Dispatch
│   │   │   │   ├── despacho.routes.ts
│   │   │   │   ├── components/
│   │   │   │   │   ├── despacho-list/
│   │   │   │   │   ├── despacho-form/
│   │   │   │   │   └── despacho-detail/
│   │   │   │   └── services/despacho.service.ts
│   │   │   ├── users/                # User management
│   │   │   │   ├── users.routes.ts
│   │   │   │   ├── components/user-list/
│   │   │   │   └── services/user.service.ts
│   │   │   └── settings/             # Settings (Campañas, Frutas, Parámetros)
│   │   │       ├── settings.component.ts    # Tab container with sub-nav
│   │   │       ├── settings.routes.ts
│   │   │       ├── components/
│   │   │       │   ├── campanhas/campanhas.component.ts
│   │   │       │   ├── frutas/frutas.component.ts
│   │   │       │   └── parametros/parametros.component.ts
│   │   │       └── services/
│   │   │           ├── campahna.service.ts
│   │   │           ├── fruta.service.ts
│   │   │           └── parametro-admin.service.ts
│   │   ├── layouts/
│   │   │   ├── layout/layout.component.ts           # Main app (sidebar, header, footer)
│   │   │   └── empty-layout/empty-layout.component.ts  # Auth pages
│   │   └── shared/
│   │       ├── loading-screen/loading-screen.component.ts
│   │       └── components/
│   │           ├── notification/notification.component.ts
│   │           ├── confirm-dialog/confirm-dialog.component.ts
│   │           ├── page-header/page-header.component.ts
│   │           └── data-table/data-table.component.ts
│   ├── environments/
│   │   ├── environment.ts
│   │   └── environment.development.ts
│   └── styles.css              # Global styles + Tailwind import
├── angular.json
├── tsconfig.json               # Path aliases
└── package.json
```

## Statistics
- **28** component files
- **19** service files
- **9** route files
- **3** model files
- **5** shared components
- **1** guard, **1** interceptor

---

## Routing Architecture

### Root Routes (app.routes.ts)
```
/ → redirect to /auth/login
/auth (EmptyLayoutComponent)
  /auth/login → LoginComponent (lazy)
/app (LayoutComponent + authGuard)
  /app/dashboard    → HomeComponent (lazy)
  /app/maestros     → MaestrosComponent (tabs: areas, clientes, productores)
  /app/lotes        → LoteListComponent, LoteFormComponent, LoteDetailComponent
  /app/trazabilidad → TrazabilidadListComponent, TrazabilidadLoteComponent
  /app/camaras      → CamarasListComponent
  /app/despacho     → DespachoListComponent, DespachoFormComponent, DespachoDetailComponent
  /app/users        → UserListComponent
  /app/settings     → SettingsComponent (tabs: campanhas, frutas, parametros)
  /app → redirect to dashboard
```

All feature routes use lazy loading via `loadChildren`.

---

## Auth Flow
1. **Login:** POST `securityUrl/login_check` with `{username, password}`
2. **Response:** `{token, status, user: {id, username, fullname, roles, avatar}}`
3. **Store:** `localStorage.setItem('token', token)` + `localStorage.setItem('user', JSON.stringify(user))`
4. **Interceptor:** Adds `Authorization: Bearer ${token}` + `X-Campahna-Id` to all requests
5. **Guard:** Functional `authGuard` checks `localStorage.getItem('token')`
6. **Logout:** Clear localStorage, redirect to `/auth/login`

### Role-Based Access
- **ROLE_USER:** Dashboard only
- **KNUTO_ROLE:** Operations (Lotes, Trazabilidad, Camaras, Despacho)
- **ROLE_ADMIN:** Full access (+ Maestros, Usuarios, Configuracion)

Frontend uses `authService.hasRole('ROLE_ADMIN')` to conditionally show delete buttons.

---

## Campaign Context System
- `CampaignService` manages active campaign via signals
- Fetches campaigns from `/campahnas/shared`
- Auto-selects first campaign or restores from `localStorage`
- `authInterceptor` adds `X-Campahna-Id` header to every request
- Backend uses ContextTrait to filter entities by campaign

---

## Core Services

### AuthService (`@core/services/auth.service.ts`)
- Signals: `#currentUser` (writable), `currentUser` (readonly), `isAuthenticated` (computed)
- Methods: `login()`, `logout()`, `checkSession()`, `hasRole(roleName)`

### CampaignService (`@core/services/campaign.service.ts`)
- Signals: `#campaigns`, `#activeCampaign`
- Computed: `activeCampaignId()`
- Loads shared campaigns, persists selection in localStorage

### NotificationService (`@core/services/notification.service.ts`)
- Signal-based toast notification list
- Auto-dismiss after 4000ms
- Methods: `success()`, `error()`, `info()`, `warning()`, `remove(id)`

### RolesService (`@core/services/roles.service.ts`)
- Signals: `allRoles`
- Methods: `fetchRoles()`, `getRoleNameById()`

---

## Data Models (core.model.ts)

### Master Data
- `Campaign`: id, nombre, descripcion, fechaInicio, fechaFin, frutaId, frutaNombre, isActive
- `Fruit`: id, nombre, codigo, isActive
- `Producer`: id, codigo, nombre, clp, mtdCeratitis, mtdAnastrepha, productor, isActive
- `Client`: id, codigo, razonSocial, abreviacion, isActive
- `Area`: id, nombre, tipo, tipoLabel, requiereCal, requiereTratamiento, isActive
- `Parameter`: id, name, alias, value, parentId, parentName, parentAlias, isActive

### Bridge Entities
- `ProducerCampaign`: id, productorId, productorCodigo, productorNombre, fechaIngreso
- `ClientCampaign`: id, clienteId, clienteCodigo, clienteRazonSocial, clienteAbreviacion, fechaIngreso
- `AreaCampaign`: id, areaId, areaNombre, areaTipo, areaTipoLabel, areaRequiereCal, areaRequiereTratamiento, fechaApertura

### Trazabilidad
- `Lote`: id, codigo, estado, campahnaNombre, productorCodigo/Nombre, clienteRazonSocial/Abreviacion, areaNombre/Tipo, frutaNombre, variedadNombre, isActive
- `Recepcion`: id, loteId, fecha, guia, nJabas, pesoBruto, pesoTarima, pesoJaba, pesoNeto, pesoPromedio, observaciones
- `DescarteLote`: id, loteId, tipoDescarteId, tipoDescarteNombre, peso, porcentaje, observaciones
- `Calibrado`: id, loteId, fecha, pesoTotal, descarte, pesoExportable, observaciones, detalles[]
- `CalibreDetalle`: id, calibradoId, calibreId, calibreNombre, cantidad, peso
- `ProcesoLote`: id, loteId, fecha, juliano, semana, pedido, expediente, categoriaId/Nombre, calibreId/Nombre, nCajas, nPalet, cajasPorPallet, etiquetaId/Nombre, presentacionId/Nombre, pesoNeto, codTrazabilidad, observaciones
- `PalletCamara`: id, procesoLoteId, fechaIngreso, estado, nDiasEstadia, pedido, observaciones, + proceso data
- `Despacho`: id, codigo, fecha, horaCarga, semana, areaNombre, clienteRazonSocial, nDespachoCliente, nDespachoPlanta, cantPallets, totalCajas, destinoPais, contenedor, observaciones, pallets[]
- `DespachoPallet`: id, despachoId, palletCamaraId, posicion, orden, + pallet data

---

## API Response Models (api.model.ts)
```typescript
interface Pagination {
  page: number;
  itemsPerPage: number;
  count: number;
  totalItems: number;
  startIndex: number;
  endIndex: number;
}

interface ApiResponse<T> { status: boolean; message?: string; item?: T; }
interface ApiListResponse<T> { status: boolean; items: T[]; pagination: Pagination; }
interface SharedItem { id: string; name: string; }
interface FilterParams { page?: number; itemsPerPage?: number; search?: string; sort?: string; direction?: string; }
```

---

## Responsive Design Patterns Applied
- **Tables:** Wrapped in `<div class="overflow-x-auto">` for horizontal scroll on mobile
- **Search + Button bars:** `flex flex-col sm:flex-row items-stretch sm:items-center gap-3`
- **Campaign section headers:** `flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3`
- **Selector + Assign button:** `flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto`
- **Search inputs:** `w-full sm:w-64` (not fixed width)
- **Form grids:** `grid-cols-1 sm:grid-cols-2 md:grid-cols-4`
- **Date grids (modal):** `grid-cols-1 sm:grid-cols-2`

Applied to: areas, clientes, productores, campanhas, frutas, parametros, lote-detail.

---

## Shared Components

| Component | Purpose |
|-----------|---------|
| `LoadingScreenComponent` | Full-screen loading indicator |
| `NotificationComponent` | Toast notifications display |
| `ConfirmDialogComponent` | Confirmation modal dialog |
| `PageHeaderComponent` | Page title & breadcrumb |
| `DataTableComponent` | Reusable data table |

---

## Styling
- Tailwind CSS 4.1 with `@import "tailwindcss"` in styles.css
- Color scheme: Blue primary, Slate neutrals, Emerald success, Amber warning, Red danger
- All component styles use inline Tailwind classes (no separate CSS files)
- Custom animations defined in styles.css

## API Consumption Patterns

### Parallel calls with forkJoin
When a component needs multiple independent API calls, use `forkJoin` instead of sequential subscribes:
```typescript
import { forkJoin } from 'rxjs';

// GOOD: All calls execute in parallel
forkJoin({
  productores: this.productorService.getByCampaign(campId),
  clientes: this.clienteService.getByCampaign(campId),
  areas: this.areaService.getByCampaign(campId),
}).subscribe({
  next: (res) => {
    this.productores.set(res.productores.items.map(...));
    this.clientes.set(res.clientes.items.map(...));
    this.areas.set(res.areas.items.map(...));
  }
});
```
Applied to: lote-form, despacho-form, lote-detail, trazabilidad-lote.

### Service-level caching
`ParametroService` caches `getShared()` and `getByParentAndFruta()` responses in memory.
Call `clearCache()` after creating/updating parameters.

### Pattern for dependent + independent calls
When some calls depend on a previous result and others don't:
```typescript
// Load lote first (dependency), then parallel load related data
this.loteService.getById(id).subscribe({
  next: (res) => {
    this.lote.set(res.item);
    // Now load independent data in parallel
    forkJoin({ recepcion: ..., descartes: ..., parametros: ... }).subscribe(...);
  }
});
```

---

## Dev Commands
```bash
npm start      # ng serve (port 4200)
npm run build  # production build
npm test       # karma + jasmine
```
