import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

export const moduleGuard = (moduleKey: string): CanActivateFn => () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.hasModule(moduleKey)) {
    return true;
  }

  router.navigate(['/app/dashboard']);
  return false;
};
