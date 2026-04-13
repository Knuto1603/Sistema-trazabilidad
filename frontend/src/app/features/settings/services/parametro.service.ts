import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { ApiListResponse, ApiResponse, FilterParams } from '@core/models/api.model';
import { Parameter } from '@core/models/core.model';

export interface ParametroCreateDto {
  name: string;
  alias: string;
  value: number | string | null;
  parentId?: string | null;
}

@Injectable({ providedIn: 'root' })
export class ParametroService {
  private http = inject(HttpClient);
  private url = `${environment.coreUrl}/parametros`;

  getAll(filters: FilterParams = {}) {
    let params = new HttpParams();
    if (filters.page !== undefined) params = params.set('page', filters.page);
    if (filters.itemsPerPage) params = params.set('itemsPerPage', filters.itemsPerPage);
    if (filters.search) params = params.set('search', filters.search);
    return this.http.get<ApiListResponse<Parameter>>(`${this.url}/`, { params });
  }

  getParents() {
    return this.http.get<{ status: boolean; items: { id: string; name: string }[] }>(`${this.url}/parents`);
  }

  create(data: ParametroCreateDto) {
    return this.http.post<ApiResponse<Parameter>>(`${this.url}/`, data);
  }

  update(id: string, data: ParametroCreateDto) {
    return this.http.put<ApiResponse<Parameter>>(`${this.url}/${id}`, data);
  }

  enable(id: string) {
    return this.http.patch<ApiResponse<Parameter>>(`${this.url}/${id}/enable`, {});
  }

  disable(id: string) {
    return this.http.patch<ApiResponse<Parameter>>(`${this.url}/${id}/disable`, {});
  }

  delete(id: string) {
    return this.http.delete<ApiResponse<null>>(`${this.url}/${id}`);
  }

  getByParentAlias(alias: string) {
    return this.http.get<{ status: boolean; items: string[] }>(`${this.url}/by-parent-alias/${alias}`);
  }
}
