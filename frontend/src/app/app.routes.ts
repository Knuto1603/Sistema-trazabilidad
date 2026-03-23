import { Routes } from '@angular/router';
import { LayoutComponent } from './layouts/layout/layout.component';
import { authGuard } from '@core/guards/auth.guard';
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
            { path: 'trazabilidad', redirectTo: 'dashboard', pathMatch: 'full' },
            { path: 'lotes', redirectTo: 'dashboard', pathMatch: 'full' },
            { path: 'users', redirectTo: 'dashboard', pathMatch: 'full' },
            { path: 'settings', redirectTo: 'dashboard', pathMatch: 'full' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },
    { path: '', redirectTo: 'auth/login', pathMatch: 'full' }
];