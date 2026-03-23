import { Routes } from '@angular/router';
import { SettingsComponent } from './settings.component';

export const settingsRoutes: Routes = [
  {
    path: '',
    component: SettingsComponent,
    children: [
      {
        path: 'campanhas',
        loadComponent: () => import('./components/campanhas/campanhas.component').then(m => m.CampanhasComponent)
      },
      {
        path: 'frutas',
        loadComponent: () => import('./components/frutas/frutas.component').then(m => m.FrutasComponent)
      },
      {
        path: 'parametros',
        loadComponent: () => import('./components/parametros/parametros.component').then(m => m.ParametrosComponent)
      },
      {
        path: 'roles',
        loadComponent: () => import('./components/roles/roles.component').then(m => m.RolesComponent)
      },
      { path: '', redirectTo: 'campanhas', pathMatch: 'full' }
    ]
  }
];
