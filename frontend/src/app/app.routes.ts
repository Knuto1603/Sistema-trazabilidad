import { Routes } from '@angular/router';
import { LayoutComponent } from './layouts/layout/layout.component';
import { authGuard } from '@core/guards/auth.guard';
import { knutoGuard } from '@core/guards/knuto.guard';
import { EmptyLayoutComponent } from './layouts/empty-layout/empty-layout.component';

export const routes: Routes = [
  {
    path: 'auth',
    component: EmptyLayoutComponent,
    children: [
      {
        path: 'login',
        loadComponent: () => import('./features/auth/components/login/login.component').then(m => m.LoginComponent)
      }
    ]
  },
  {
    path: 'app',
    component: LayoutComponent,
    canActivate: [authGuard],
    children: [
      {
        path: 'dashboard',
        loadChildren: () => import('@features/dashboard/dashboard.routes').then(m => m.dashboardRoutes)
      },
      {
        path: 'settings',
        loadChildren: () => import('@features/settings/settings.routes').then(m => m.settingsRoutes)
      },
      {
        path: 'users',
        loadChildren: () => import('@features/users/users.routes').then(m => m.usersRoutes)
      },
      {
        path: 'productores',
        loadChildren: () => import('@features/productores/productores.routes').then(m => m.productoresRoutes)
      },
      {
        path: 'senasa',
        loadChildren: () => import('@features/senasa/senasa.routes').then(m => m.senasaRoutes)
      },
      {
        path: 'facturacion',
        loadChildren: () => import('@features/facturacion/facturacion.routes').then(m => m.facturacionRoutes)
      },
      {
        path: 'dev',
        canActivate: [knutoGuard],
        loadChildren: () => import('@features/dev/dev.routes').then(m => m.devRoutes)
      },
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      { path: '**', redirectTo: 'dashboard' }
    ]
  },
  { path: '', redirectTo: 'auth/login', pathMatch: 'full' },
  { path: '**', redirectTo: 'auth/login' }
];
