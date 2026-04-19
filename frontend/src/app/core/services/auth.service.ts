import { Injectable, signal, inject, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LoginResponse, User } from '../models/auth.model';
import { forkJoin, map, of, take, tap } from 'rxjs';
import { environment } from '@env/environment.development';
import { RolesService } from './roles.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  private rolesService = inject(RolesService);

  private apiUrl = environment.securityUrl;

  #currentUser = signal<User | null>(null);

  currentUser = this.#currentUser.asReadonly();

  isAuthenticated = computed(() => !!this.#currentUser());

  login(credentials: any, rememberMe = true) {
    return this.http.post<LoginResponse>(`${this.apiUrl}/login_check`, credentials).pipe(
      tap(res => {
        if (res.status) {
          const storage = rememberMe ? localStorage : sessionStorage;
          storage.setItem('token', res.token);
          storage.setItem('user', JSON.stringify(res.user));
          this.#currentUser.set(res.user);
        }
      })
    );
  }

  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('user');
    this.#currentUser.set(null);
  }

  constructor() {
    this.checkSession();
  }

  private checkSession() {
    const token = localStorage.getItem('token') ?? sessionStorage.getItem('token');
    const savedUser = localStorage.getItem('user') ?? sessionStorage.getItem('user');

    if (token && savedUser) {
      this.#currentUser.set(JSON.parse(savedUser));
    }
  }

  hasRole(roleName: string): boolean {
    const userRoles = this.#currentUser()?.roles || [];
    return userRoles.includes(roleName);
  }
}