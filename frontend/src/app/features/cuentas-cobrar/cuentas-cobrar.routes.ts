import { Routes } from '@angular/router';

export const cuentasCobrarRoutes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./components/cuentas-cobrar-list/cuentas-cobrar-list.component')
        .then(m => m.CuentasCobrarListComponent),
  },
  {
    path: 'vouchers',
    loadComponent: () =>
      import('./components/vouchers/vouchers.component')
        .then(m => m.VouchersComponent),
  },
];
