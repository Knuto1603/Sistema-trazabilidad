import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

export const knutoGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.hasRole('ROLE_KNUTO')) {
    return true;
  }

  router.navigate(['/app/dashboard']);
  return false;
};
