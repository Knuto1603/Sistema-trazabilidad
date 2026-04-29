# Sistema Trazabilidad - Project Memory

## Project Overview
Agricultural traceability system for InterFruits. Tracks fruit from producer reception through calibration, processing, cold storage, and dispatch. Two projects in `E:\InterFruit\sistema-trazabilidad3\`:
- **Backend**: `sistema-trazabilidad/` - Symfony 7.2 (PHP 8.2+), multi-app architecture
- **Frontend**: `trazabilidad-frontend/` - Angular 19.2, standalone components, Tailwind CSS 4.1

## Detailed Documentation
- [Backend Architecture & APIs](backend-architecture.md)
- [Backend Patterns - Guia para crear entidades](backend-patterns.md)
- [Frontend Architecture](frontend-architecture.md)
- [Roadmap - Pendientes y mejoras](roadmap.md)

## Quick Reference

### Backend Stack
- Symfony 7.2, PHP 8.2+, Doctrine ORM 3.4, MySQL/MariaDB (2 DBs)
- JWT Auth (Lexik JWT Bundle), CORS (Nelmio)
- Multi-app: `apps/security/` (port 8000) + `apps/core/` (port 8001)
- Shared code: `src/shared/` (DTOs, traits, listeners, base classes)
- 15 core controllers, 19 entities, 19 service directories

### Frontend Stack
- Angular 19.2, TypeScript 5.7, RxJS 7.8, Tailwind CSS 4.1
- Standalone components (28 total), Angular Signals for state
- Path aliases: `@env/*`, `@core/*`, `@shared/*`, `@features/*`
- 19 services, 9 route files, 5 shared components

### Security & Auth
- JWT token includes roles (unset removed from CreatedListener)
- `#[IsGranted('ROLE_ADMIN')]` on all DELETE endpoints (hard delete)
- Frontend: `authService.hasRole('ROLE_ADMIN')` hides delete buttons
- Roles: ROLE_ADMIN (full), KNUTO_ROLE (operations), ROLE_USER (dashboard)
- Interceptor adds `Authorization: Bearer` + `X-Campahna-Id` headers

### Key Patterns
- All entities use UUID (ULID-based, Base58 22 chars), soft delete via `isActive`
- Standard response: `{status, message, item/items, pagination}`
- Service layer pattern (logic in services, not controllers)
- DTO pattern: input validation + output transformation
- Campaign context: active campaign stored in localStorage, sent via header

### Responsive Design
- All tables wrapped in `overflow-x-auto`
- Search+button bars: `flex flex-col sm:flex-row items-stretch sm:items-center gap-3`
- Campaign sections: responsive header + selector
- Search inputs: `w-full sm:w-64`
- Grids: `grid-cols-1 sm:grid-cols-2 md:grid-cols-4`

### API Base URLs (dev)
- Security: `https://127.0.0.1:8000/api`
- Core: `https://127.0.0.1:8001/api`
