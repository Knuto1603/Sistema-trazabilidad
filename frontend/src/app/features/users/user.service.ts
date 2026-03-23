import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { ApiListResponse, ApiResponse, FilterParams } from '@core/models/api.model';
import { AppUser } from '@core/models/core.model';

@Injectable({ providedIn: 'root' })
export class UserService {
  private http = inject(HttpClient);
  private url = `${environment.securityUrl}/users`;
  private rolesUrl = `${environment.securityUrl}/user_roles`;

  getAll(filters: FilterParams = {}) {
    let params = new HttpParams();
    if (filters.page !== undefined) params = params.set('page', filters.page);
    if (filters.itemsPerPage) params = params.set('itemsPerPage', filters.itemsPerPage);
    if (filters.search) params = params.set('search', filters.search);
    return this.http.get<ApiListResponse<AppUser>>(`${this.url}/`, { params });
  }

  create(data: { username: string; fullname: string; password: string; roles: string[] }) {
    return this.http.post<ApiResponse<AppUser>>(`${this.url}/`, data);
  }

  update(id: string, data: { username: string; fullname: string; password?: string; roles: string[] }) {
    return this.http.put<ApiResponse<AppUser>>(`${this.url}/${id}`, data);
  }

  enable(id: string) {
    return this.http.patch<ApiResponse<AppUser>>(`${this.url}/${id}/enable`, {});
  }

  disable(id: string) {
    return this.http.patch<ApiResponse<AppUser>>(`${this.url}/${id}/disable`, {});
  }

  delete(id: string) {
    return this.http.delete<ApiResponse<null>>(`${this.url}/${id}`);
  }

  getAvailableRoles() {
    return this.http.get<{ status: boolean; items: { id: string; name: string }[] }>(
      `${this.rolesUrl}/shared`
    );
  }
}
