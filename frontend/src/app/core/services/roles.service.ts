// src/app/core/services/roles.service.ts
import { Injectable, signal, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';
import { tap } from 'rxjs';

export interface Role {
  id: string;
  name: string;
}

export interface RoleResponse {
  status: boolean;
  message: string;
  item: Role;
}

@Injectable({ providedIn: 'root' })
export class RolesService {
  private http = inject(HttpClient);
  private url = `${environment.securityUrl}/user_roles`;

  allRoles = signal<Role[]>([]);

  fetchRoles() {
    return this.http.get<{status: boolean, items: Role[]}>(`${this.url}/shared`)
      .subscribe(res => {
        if (res.status) this.allRoles.set(res.items);
      });
  }

  getRoleNameById(id: string) {
    return this.http.get<RoleResponse>(`${this.url}/${id}`)
      .pipe(
        tap(res => {
          if (res.status) {
            return res.item.name;
          }
          return 'ROLE_UNKNOWN';
        })
      ) ;
  }
}