import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { NotificationService } from '../services/notification.service';
import { AuthService } from '../services/auth.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const notification = inject(NotificationService);
  const router = inject(Router);
  const auth = inject(AuthService);

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      // No manejar errores del login
      if (req.url.includes('login_check')) return throwError(() => err);

      if (err.status === 0) {
        notification.error('Sin conexión con el servidor.');
      } else if (err.status === 401) {
        auth.logout();
        router.navigate(['/auth/login']);
      } else if (err.status === 403) {
        notification.error('No tienes permisos para realizar esta acción.');
      } else if (err.status >= 500) {
        notification.error('Error interno del servidor. Inténtalo más tarde.');
      } else if (err.status >= 400) {
        const msg = err.error?.message || 'Error en la solicitud.';
        notification.error(msg);
      }
      return throwError(() => err);
    })
  );
};
