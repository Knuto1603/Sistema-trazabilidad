import { Routes } from '@angular/router';

export const accountRoutes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./components/account-page/account-page.component').then(m => m.AccountPageComponent)
  }
];
