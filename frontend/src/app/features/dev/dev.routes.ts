import { Routes } from '@angular/router';

export const devRoutes: Routes = [
  {
    path: '',
    loadComponent: () => import('./components/dev-panel/dev-panel.component').then(m => m.DevPanelComponent)
  }
];
