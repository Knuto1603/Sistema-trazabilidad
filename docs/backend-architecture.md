# Backend Architecture - Sistema Trazabilidad

## Project Location
`E:\InterFruit\sistema-trazabilidad3\sistema-trazabilidad\`

## Tech Stack
- **Framework:** Symfony 7.2.* (PHP >= 8.2)
- **ORM:** Doctrine ORM 3.4, DBAL 3.9
- **Auth:** Lexik JWT Authentication Bundle 3.1
- **CORS:** Nelmio CORS Bundle 2.5
- **Images:** Liip Imagine Bundle 2.13
- **Spreadsheets:** carloschininin/php-spreadsheet (custom)
- **HTTP Scraping:** Symfony HttpClient, DomCrawler, CssSelector
- **DB:** MySQL/MariaDB (2 databases)

## Structure
```
sistema-trazabilidad/
├── apps/
│   ├── security/          # Auth & user management (port 8000)
│   │   ├── src/Controller/    # SecurityApi, UserApi, UserRoleApi
│   │   ├── src/Entity/        # User, UserRole
│   │   ├── src/Service/       # User CRUD, Role CRUD services
│   │   ├── src/Repository/
│   │   ├── src/EventListener/JWT/
│   │   │   ├── AuthenticationSuccessListener.php
│   │   │   └── CreatedListener.php  # Adds UUID to JWT, roles included
│   │   └── src/Command/       # CLI: CreateAdmin, InitializeRoles
│   └── core/              # Business logic (port 8001)
│       ├── src/Controller/    # 15 API controllers
│       ├── src/Entity/        # 19 entities (includes ContextTrait)
│       ├── src/Service/       # 19 service directories
│       └── src/Repository/
├── src/shared/            # Shared infrastructure
│   ├── Api/               # AbstractSerializerApi, DtoSerializer, Paginator
│   ├── Cache/
│   ├── Controller/
│   ├── Doctrine/          # DoctrineEntityRepository, DoctrinePaginator, UidType
│   ├── Entity/            # EntityTrait (UUID, timestamps, isActive, enable/disable)
│   ├── EventListener/     # ExceptionListener, JWT/
│   ├── Exception/         # DomainException, MissingParameterException, NotFoundException
│   ├── Http/              # HttpClient, HttpClientCache
│   ├── Repository/        # PaginatorInterface, RepositoryInterface
│   ├── Service/           # FilterService, Helper, ImageService, TextCleaner
│   │   ├── Dto/           # FilterDto, PaginationDto, SortingDto
│   │   ├── Filter/        # PaginationFilter, SearchTextFilter
│   │   ├── Sorting/       # SortByRequestField
│   │   └── Transformer/   # DtoTransformer base
│   ├── Symfony/
│   └── Validator/         # Uid, UidValidator (custom UUID validation)
├── config/                # Symfony config, JWT keys
├── HTTP/                  # API test collection (Bruno format)
└── .env
```

## Databases
- `sistema_trazabilidad_security` (port 3306) - Users & roles
- `sistema_trazabilidad_core` (port 3306) - All business entities

---

## ALL ENTITIES

### Security App (2 entities)
| Entity | Table | Key Fields |
|--------|-------|------------|
| **User** | `security_user` | uuid, username, password, fullName, gender, photo, isActive. ManyToMany→UserRole |
| **UserRole** | `security_user_role` | uuid, name, alias, isActive |

### Core App - Maestros (8 entities)
| Entity | Table | Key Fields |
|--------|-------|------------|
| **Campahna** | `core_campahna` | uuid, nombre, descripcion, fechaInicio, fechaFin, fruta_id(FK) |
| **Fruta** | `core_fruta` | uuid, nombre, codigo(5 chars) |
| **Productor** | `core_productor` | uuid, codigo(4), nombre, clp, mtdCeratitis, mtdAnastrepha, productor |
| **ProductorCampahna** | `core_productor_campahna` | productor_id(FK), campahna_id(FK), fechaIngreso. UNIQUE |
| **Cliente** | `core_cliente` | uuid, codigo(10), razonSocial(150), abreviacion(20) |
| **ClienteCampahna** | `core_cliente_campahna` | cliente_id(FK), campahna_id(FK), fechaIngreso. UNIQUE |
| **Area** | `core_area` | uuid, nombre(100), tipo(M/A), requiereCal, requiereTratamiento |
| **AreaCampahna** | `core_area_campahna` | area_id(FK), campahna_id(FK), fechaApertura. UNIQUE |
| **Parametro** | `core_parametro` | uuid, name, alias, value(DECIMAL 12,4), parent_id(FK self) |

### Core App - Trazabilidad (10 entities)
| Entity | Table | Key Fields |
|--------|-------|------------|
| **Lote** | `core_lote` | uuid, codigo(20), estado, campahna_id, productorCampahna_id, clienteCampahna_id, areaCampahna_id, variedad_id(Param), tipoCaja_id(Param). Uses ContextTrait. UNIQUE(codigo, campahna_id) |
| **Recepcion** | `core_recepcion` | lote_id(OneToOne), fecha, guia, nJabas, pesoBruto, pesoTarima, pesoJaba, pesoNeto, pesoPromedio, observaciones |
| **DescarteLote** | `core_descarte_lote` | lote_id(FK), tipoDescarte_id(Param), peso, porcentaje, observaciones |
| **Calibrado** | `core_calibrado` | lote_id(OneToOne), fecha, pesoTotal, descarte, pesoExportable, observaciones. OneToMany→CalibreDetalle |
| **CalibreDetalle** | `core_calibre_detalle` | calibrado_id(FK), calibre_id(Param), cantidad, peso |
| **ProcesoLote** | `core_proceso_lote` | lote_id(ManyToOne), fecha, juliano, semana, pedido, expediente, categoria_id(Param), calibre_id(Param), nCajas, nPalet, cajasPorPallet, etiqueta_id(Param), presentacion_id(Param), pesoNeto, codTrazabilidad(auto), observaciones. OneToOne→PalletCamara |
| **PalletCamara** | `core_pallet_camara` | procesoLote_id(OneToOne), fechaIngreso, estado(Stock/Despachado), nDiasEstadia, pedido, observaciones |
| **Despacho** | `core_despacho` | codigo(auto ITFP-MANGO-XX-XXX), fecha, horaCarga, semana, areaCampahna_id, clienteCampahna_id, campahna_id, nDespachoCliente, nDespachoPlanta, cantPallets, totalCajas, destinoPais, contenedor, observaciones. OneToMany→DespachoPallet |
| **DespachoPallet** | `core_despacho_pallet` | despacho_id(FK), palletCamara_id(FK), posicion(I/D), orden |

### ContextTrait
Shared trait for entities that need campaign context. Adds automatic campaign filtering via the `X-Campahna-Id` header.

---

## ALL CONTROLLERS (18 total)

### Security App (3 controllers)
| Controller | Base Route | Purpose |
|-----------|------------|---------|
| SecurityApi | `/security` | Login, token validation, current user |
| UserApi | `/users` | User CRUD + enable/disable + download |
| UserRoleApi | `/user_roles` | Role CRUD + shared list |

### Core App (15 controllers)
| Controller | Base Route | Key Endpoints | Admin DELETE |
|-----------|------------|---------------|-------------|
| CampahnaApi | `/campahnas` | CRUD, filter_advanced, download, shared | Yes |
| FrutaApi | `/frutas` | Create, enable/disable, download, shared | - |
| ProductorApi | `/productores` | CRUD, campaign assign/remove/multi, last-code | Yes |
| ClienteApi | `/clientes` | CRUD, campaign assign/remove/multi, shared | Yes |
| AreaApi | `/areas` | CRUD, campaign assign/remove/multi, shared | Yes |
| ParametroApi | `/parametros` | CRUD, filter_advanced, download, parents, shared, by-parent-fruta | Yes |
| LoteApi | `/lotes` | CRUD, enable/disable, next-code, by-campahna | Yes |
| RecepcionApi | `/recepciones` | Create, update, get-by-lote | - |
| CalibradoApi | `/calibrados` | Create, update, get-by-lote | - |
| ProcesoLoteApi | `/procesos-lote` | Create, update, list, by-lote | - |
| PalletCamaraApi | `/pallets-camara` | Create, update, list, change-estado | - |
| DespachoApi | `/despachos` | Create, update, list, view | - |
| DescarteLoteApi | `/descartes` | Create, update, delete, by-lote | Yes |
| DocumentoApi | `/documentos` | Consulta documento | - |
| SenasaController | `/senasa` | Verificar con SENASA (web scraping) | - |

### Security: `#[IsGranted('ROLE_ADMIN')]`
Applied to DELETE methods in: AreaApi, ClienteApi, ProductorApi, CampahnaApi, ParametroApi, LoteApi, DescarteLoteApi.

---

## ALL SERVICE DIRECTORIES (19 in core)

| Service Directory | Files | Purpose |
|------------------|-------|---------|
| Area | CRUD + ChangeState + Shared + Dto | Area management |
| AreaCampahna | Assign, GetBy, Remove | Area-Campaign bridge |
| BlockChain | (experimental) | Blockchain integration |
| Calibrado | Create, Update, GetByLote + Dto | Calibration records |
| Campahna | CRUD + ChangeState + Download + Filter + Shared + Dto | Campaign management |
| Cliente | CRUD + ChangeState + Shared + Dto | Client management |
| ClienteCampahna | Assign, GetBy, Remove | Client-Campaign bridge |
| Contexto | Context handling | Campaign context resolution |
| DescarteLote | Create, Update, Delete, GetByLote + Dto | Batch waste/discard |
| Despacho | Create, Update, Get, GetList + Dto | Dispatch management |
| Documento | ConsultaDocumento | Document lookup |
| Fruta | Create + ChangeState + Download + Shared + Dto | Fruit management |
| Lote | CRUD + ChangeState + NextCode + Filter + Dto | Batch management |
| PalletCamara | Create, Update, GetList, ChangeEstado + Dto | Cold storage pallets |
| Parametro | CRUD + ChangeState + Download + Filter + Parents + Shared + ByParentFruta + Dto | Parameter management |
| ProcesoLote | Create, Update, GetByLote, GetList + Dto | Batch processing |
| Productor | Create + AssignToCampahna + GetBy + Remove + LastCode + Dto | Producer management |
| Recepcion | Create, Update, GetByLote + Dto | Reception records |
| SenasaScraping | Web scraping | SENASA external verification |

---

## JWT Configuration

### CreatedListener
Adds user UUID to JWT payload. Roles are now included in the token (previously removed with `unset($payload['roles'])`).

### AuthenticationSuccessListener
Returns `{token, status: true, user: {uuid, username, fullName, roles}}` on successful login.

---

## Response Format
```json
// Success with item
{"status": true, "message": "...", "item": {...}}

// Success with list
{"status": true, "items": [...], "pagination": {"page": 0, "itemsPerPage": 5, "count": 5, "totalItems": 42, "startIndex": 1, "endIndex": 5}}

// Error
{"status": false, "message": "...", "exception": "exception_type"}
```

## CORS
- Allowed origins: localhost, 127.0.0.1, 192.168.*.*, 10.*.*.*
- Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
- Headers: Content-Type, Authorization
