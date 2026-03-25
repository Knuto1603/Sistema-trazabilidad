import { Routes } from '@angular/router';

export const facturacionRoutes: Routes = [
  {
    path: 'clientes',
    loadComponent: () => import('./components/clientes/clientes-list/clientes-list.component').then(m => m.ClientesListComponent)
  },
  {
    path: 'despachos',
    loadComponent: () => import('./components/despachos/despachos-list/despachos-list.component').then(m => m.DespachosListComponent)
  },
  {
    path: 'despachos/:id',
    loadComponent: () => import('./components/despacho-detail/despacho-detail.component').then(m => m.DespachoDetailComponent)
  },
  {
    path: 'tipo-cambio',
    loadComponent: () => import('./components/tipo-cambio/tipo-cambio-list/tipo-cambio-list.component').then(m => m.TipoCambioListComponent)
  },
  { path: '', redirectTo: 'despachos', pathMatch: 'full' }
];
