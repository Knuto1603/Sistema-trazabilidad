import { Injectable, signal, inject, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LoginResponse, User } from '../models/auth.model';
import { tap } from 'rxjs';
import { environment } from '@env/environment.development';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);

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
          this.#currentUser.set(this.decodeUser(res.token));
        }
      })
    );
  }

  logout() {
    localStorage.removeItem('token');
    sessionStorage.removeItem('token');
    this.#currentUser.set(null);
  }

  constructor() {
    this.checkSession();
  }

  private checkSession() {
    const token = localStorage.getItem('token') ?? sessionStorage.getItem('token');
    if (token) {
      this.#currentUser.set(this.decodeUser(token));
    }
  }

  private decodeUser(token: string): User | null {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return {
        id: payload['id'],
        username: payload['username'],
        fullname: payload['fullname'] ?? '',
        roles: payload['roles'] ?? [],
        modules: payload['modules'] ?? [],
        avatar: payload['avatar'] ?? null,
      };
    } catch {
      return null;
    }
  }

  hasRole(roleName: string): boolean {
    const userRoles = this.#currentUser()?.roles || [];
    return userRoles.includes(roleName);
  }

  hasModule(moduleKey: string): boolean {
    const modules = this.#currentUser()?.modules || [];
    return modules.includes(moduleKey);
  }
}