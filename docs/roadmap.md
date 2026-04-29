# Roadmap - Pendientes, Mejoras y Correcciones

## Estado actual por modulo

| Modulo | Backend | Frontend | Estado |
|--------|---------|----------|--------|
| Auth/Login | COMPLETO | COMPLETO | Production ready |
| Users CRUD | COMPLETO | COMPLETO (list, create, enable/disable) | Funcional |
| Roles CRUD | COMPLETO | No hay UI dedicada | Parcial |
| Campanas | COMPLETO | COMPLETO (CRUD, enable/disable, modal) | Funcional |
| Frutas | COMPLETO | COMPLETO (create, enable/disable, modal) | Funcional |
| Parametros | COMPLETO | COMPLETO (CRUD, enable/disable, modal) | Funcional |
| Areas | COMPLETO | COMPLETO (CRUD, campaign assign, enable/disable) | Funcional |
| Clientes | COMPLETO | COMPLETO (CRUD, campaign assign, enable/disable) | Funcional |
| Productores | COMPLETO | PARCIAL (list, campaign assign, NO create/edit) | Falta CRUD |
| Lotes | COMPLETO | COMPLETO (list, form, detail) | Funcional |
| Recepcion | COMPLETO | COMPLETO (create, edit inline en lote-detail) | Funcional |
| Descartes | COMPLETO | COMPLETO (CRUD inline en lote-detail) | Funcional |
| Calibrado | COMPLETO | COMPLETO (create, edit inline en trazabilidad-lote) | Funcional |
| ProcesoLote | COMPLETO | COMPLETO (create, edit inline en trazabilidad-lote) | Funcional |
| Camaras | COMPLETO | COMPLETO (list, change estado) | Funcional |
| Despacho | COMPLETO | COMPLETO (list, form, detail) | Funcional |
| SENASA | COMPLETO | No hay UI | Sin frontend |
| Documento | COMPLETO | No hay UI | Sin frontend |
| BlockChain | Parcial? | No hay UI | Experimental |
| Dashboard | - | PLACEHOLDER (sin datos) | Vacio |
| Responsive | - | COMPLETO (7 componentes) | Aplicado |
| Admin DELETE | COMPLETO | COMPLETO (2 componentes) | Funcional |

---

## PRIORIDAD ALTA - Funcionalidad faltante

### 1. Dashboard con datos reales
**Estado:** El HomeComponent es un placeholder sin metricas.
**Tareas:**
- [ ] Backend: Crear endpoint `/api/dashboard/stats` que retorne metricas (total lotes, lotes por estado, recepciones del mes, despachos recientes, etc.)
- [ ] Frontend: Disenar dashboard con cards de metricas, graficos de lotes por estado, tabla de actividad reciente
- [ ] Agregar graficos (considerar libreria como Chart.js o ng2-charts)

### 2. Productores - CRUD completo en frontend
**Estado:** Solo tiene listado y asignacion a campana. No hay formulario de creacion/edicion.
**Tareas:**
- [ ] Frontend: Agregar formulario create/edit (codigo, nombre, clp, mtdCeratitis, mtdAnastrepha, productor)
- [ ] Frontend: Agregar boton de edicion en la tabla
- [ ] Frontend: Agregar toggle enable/disable
- [ ] Frontend: Usar `getLastCode()` para sugerir siguiente codigo

### 3. Frutas - Edicion faltante
**Estado:** Solo tiene crear y enable/disable. No se puede editar una fruta existente.
**Tareas:**
- [ ] Backend: Verificar si existe UpdateFrutaService (puede que falte)
- [ ] Frontend: Agregar funcion de edicion al modal de frutas

### 4. Roles - UI de administracion
**Estado:** Backend completo, frontend no tiene componente.
**Tareas:**
- [ ] Frontend: Crear componente de gestion de roles (posiblemente dentro de Settings o Users)
- [ ] Frontend: CRUD de roles con nombre y alias
- [ ] Frontend: Asignacion de roles a usuarios

---

## PRIORIDAD MEDIA - Mejoras importantes

### 5. Manejo de errores HTTP global
**Estado:** Cada componente maneja errores individualmente. No hay interceptor de errores.
**Tareas:**
- [ ] Crear `errorInterceptor` que maneje 401 (redirect a login), 403 (notificacion de acceso denegado), 500 (error generico)
- [ ] Centralizar logout automatico cuando el token expira

### 6. Confirmacion de eliminacion en mas componentes
**Estado:** Solo areas y clientes tienen ConfirmDialogComponent. Otros usan `confirm()` nativo o no tienen confirmacion.
**Tareas:**
- [ ] lote-detail: Reemplazar `confirm()` nativo en deleteDescarte con ConfirmDialogComponent
- [ ] Agregar confirmacion para remove de campana (areas, clientes, productores)

### 7. Paginacion consistente
**Estado:** Algunos componentes tienen paginacion con numeros de pagina (campanhas, frutas, parametros), otros solo Anterior/Siguiente (areas, clientes, productores).
**Tareas:**
- [ ] Unificar estilo de paginacion, posiblemente crear componente SharedPaginationComponent
- [ ] Considerar componente compartido para evitar duplicacion

### 8. Validacion de formularios
**Estado:** Componentes de settings usan ReactiveFormsModule con Validators. Maestros usan NgModel sin validacion frontend.
**Tareas:**
- [ ] Agregar validacion a formularios de areas (nombre requerido)
- [ ] Agregar validacion a formularios de clientes (codigo, razonSocial requeridos)
- [ ] Mostrar mensajes de error en campos invalidos
- [ ] Deshabilitar boton guardar cuando form es invalido

### 9. Busqueda con debounce consistente
**Estado:** Maestros tienen debounce manual (setTimeout 400ms). Settings llaman onSearch() directamente sin debounce.
**Tareas:**
- [ ] Unificar patron de busqueda: agregar debounce a campanhas, frutas, parametros
- [ ] Considerar operador `debounceTime` de RxJS en lugar de setTimeout manual

### 10. Loading states en tablas de campana
**Estado:** Las tablas de asignacion a campana (areas, clientes, productores) no muestran estado de carga.
**Tareas:**
- [ ] Agregar signal `loadingCampaign` para las tablas de campana
- [ ] Mostrar "Cargando..." mientras se cargan datos de campana

---

## PRIORIDAD BAJA - Nice to have

### 11. Exportacion/Download en frontend
**Estado:** Backend tiene endpoints de download para campanas, frutas, parametros y users. Frontend no los usa.
**Tareas:**
- [ ] Agregar botones de exportar/descargar en las vistas correspondientes
- [ ] Manejar descarga de archivos (Excel/CSV)

### 12. SENASA - UI de verificacion
**Estado:** Backend tiene endpoint POST `/api/senasa/verificar`. Sin UI.
**Tareas:**
- [ ] Crear componente de verificacion SENASA (input codigo + fecha, boton verificar, resultado)
- [ ] Integrarlo en el flujo de lotes o como herramienta independiente

### 13. Documentos - UI de consulta
**Estado:** Backend tiene ConsultaDocumentoService. Sin UI.
**Tareas:**
- [ ] Crear componente de consulta de documentos
- [ ] Integrarlo donde sea necesario

### 14. Filtros avanzados en frontend
**Estado:** Backend tiene `filter_advanced` para campanas y parametros. Frontend solo usa busqueda basica.
**Tareas:**
- [ ] Agregar panel de filtros avanzados para campanas (por fruta, por fecha, por estado)
- [ ] Agregar filtros para parametros (por padre, por estado)

### 15. Perfil de usuario
**Estado:** No hay pagina de perfil de usuario.
**Tareas:**
- [ ] Crear pagina de perfil (ver datos, cambiar contrasena, foto)
- [ ] Agregar enlace desde el dropdown del header

### 16. Tema oscuro (Dark mode)
**Tareas:**
- [ ] Implementar toggle de tema oscuro
- [ ] Tailwind CSS 4 soporta `dark:` variant nativamente

### 17. Notificaciones push / tiempo real
**Tareas:**
- [ ] Considerar Mercure (Symfony) o WebSockets para actualizaciones en tiempo real
- [ ] Notificar cuando un lote cambia de estado, nuevo despacho, etc.

### 18. Tests
**Estado:** Solo archivos .spec.ts por defecto de Angular CLI, sin tests reales.
**Tareas:**
- [ ] Backend: Agregar tests unitarios para servicios criticos
- [ ] Frontend: Agregar tests para servicios (auth, campaign)
- [ ] Frontend: Agregar tests para componentes principales

---

## DEUDA TECNICA

### 19. Warnings del build
El `ng build` produce 4 warnings preexistentes:
- `NG8107`: Optional chain innecesario en `despacho-detail.component.ts:85` y `trazabilidad-lote.component.ts:57`
- `TS-998113`: `LoadingScreenComponent` importado pero no usado en `EmptyLayoutComponent`
- CSS: 39 rules skipped due to selector errors (Tailwind CSS 4 `&` syntax)

### 20. Productor view endpoint ineficiente
`ProductorApi::view()` carga TODOS los productores y filtra en PHP. Deberia usar `ofId()` directo.

### 21. DataTableComponent sin uso aparente
Existe `shared/components/data-table/data-table.component.ts` pero los componentes usan tablas inline. Evaluar si usarlo o eliminarlo.

### 22. Consistencia en patron de servicios
- Settings usa servicios separados (CampahnaAdminService, FrutaAdminService, ParametroAdminService)
- Maestros usa servicios diferentes (AreaService, ClienteService, ProductorService)
- Core tiene CampaignService (readonly, para context)
- Considerar unificar nombres y patron

### 23. Guard mejorado
El authGuard actual solo verifica que exista un token en localStorage, no valida si esta expirado.
- [ ] Decodificar JWT y verificar expiracion
- [ ] Refresh token si esta proximo a expirar

### 24. Seguridad - IsGranted en mas endpoints
Actualmente `#[IsGranted('ROLE_ADMIN')]` solo esta en DELETE. Considerar:
- [ ] Proteger CREATE/UPDATE en entidades maestras (solo ROLE_ADMIN)
- [ ] Proteger endpoints de users/roles (solo ROLE_ADMIN)
- [ ] Lotes y trazabilidad accesibles por KNUTO_ROLE y ROLE_ADMIN

---

## MEJORAS DE UX

### 25. Tabla de lotes - mostrar mas info
- [ ] Agregar columnas de estado visual (badges de color por estado)
- [ ] Agregar filtro por estado (RECEPCION, CALIBRADO, PROCESO, CAMARA, DESPACHADO)

### 26. Breadcrumbs consistentes
- [ ] Solo lote-detail tiene breadcrumb. Agregar a despacho-detail, trazabilidad-lote

### 27. Empty states mejorados
- [ ] Reemplazar textos planos "No hay datos" con ilustraciones SVG y CTA

### 28. Confirmacion al salir de formularios sin guardar
- [ ] Implementar canDeactivate guard para formularios con cambios pendientes

### 29. Skeleton loaders
- [ ] Reemplazar "Cargando..." con skeleton loaders para mejor UX percibida
