import { Routes } from '@angular/router';

export const senasaRoutes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./components/senasa-verificar/senasa-verificar.component').then(m => m.SenasaVerificarComponent)
  }
];
