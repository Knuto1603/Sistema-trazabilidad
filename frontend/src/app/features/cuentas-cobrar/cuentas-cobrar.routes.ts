import { Routes } from '@angular/router';

export const cuentasCobrarRoutes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./components/cuentas-cobrar-list/cuentas-cobrar-list.component')
        .then(m => m.CuentasCobrarListComponent),
  },
];
