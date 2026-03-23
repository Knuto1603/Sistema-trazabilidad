import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { ApiListResponse, ApiResponse, FilterParams } from '@core/models/api.model';
import { UserRole } from '@core/models/core.model';

export interface RoleCreateDto {
  name: string;
  alias: string;
}

@Injectable({ providedIn: 'root' })
export class RoleService {
  private http = inject(HttpClient);
  private url = `${environment.securityUrl}/user_roles`;

  getAll(filters: FilterParams = {}) {
    let params = new HttpParams();
    if (filters.page !== undefined) params = params.set('page', filters.page);
    if (filters.itemsPerPage) params = params.set('itemsPerPage', filters.itemsPerPage);
    if (filters.search) params = params.set('search', filters.search);
    return this.http.get<ApiListResponse<UserRole>>(`${this.url}/`, { params });
  }

  create(data: RoleCreateDto) {
    return this.http.post<ApiResponse<UserRole>>(`${this.url}/`, data);
  }

  update(id: string, data: RoleCreateDto) {
    return this.http.put<ApiResponse<UserRole>>(`${this.url}/${id}`, data);
  }

  enable(id: string) {
    return this.http.patch<ApiResponse<UserRole>>(`${this.url}/${id}/enable`, {});
  }

  disable(id: string) {
    return this.http.patch<ApiResponse<UserRole>>(`${this.url}/${id}/disable`, {});
  }

  delete(id: string) {
    return this.http.delete<ApiResponse<null>>(`${this.url}/${id}`);
  }
}
