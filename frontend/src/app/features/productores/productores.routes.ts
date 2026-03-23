import { Routes } from '@angular/router';

export const productoresRoutes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./components/productores-list/productores-list.component').then(m => m.ProductoresListComponent)
  }
];
