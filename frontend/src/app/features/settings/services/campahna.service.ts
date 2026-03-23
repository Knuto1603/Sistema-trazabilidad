import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { ApiListResponse, ApiResponse, FilterParams } from '@core/models/api.model';
import { Campaign } from '@core/models/core.model';

export interface CampahnaCreateDto {
  nombre: string;
  descripcion?: string;
  fechaInicio: string;
  fechaFin?: string;
  frutaId: string;
}

@Injectable({ providedIn: 'root' })
export class CampahnaService {
  private http = inject(HttpClient);
  private url = `${environment.coreUrl}/campahnas`;

  getAll(filters: FilterParams = {}) {
    let params = new HttpParams();
    if (filters.page !== undefined) params = params.set('page', filters.page);
    if (filters.itemsPerPage) params = params.set('itemsPerPage', filters.itemsPerPage);
    if (filters.search) params = params.set('search', filters.search);
    return this.http.get<ApiListResponse<Campaign>>(`${this.url}/`, { params });
  }

  getShared() {
    return this.http.get<{ status: boolean; items: { id: string; name: string }[] }>(`${this.url}/shared`);
  }

  create(data: CampahnaCreateDto) {
    return this.http.post<ApiResponse<Campaign>>(`${this.url}/`, data);
  }

  update(id: string, data: CampahnaCreateDto) {
    return this.http.put<ApiResponse<Campaign>>(`${this.url}/${id}`, data);
  }

  enable(id: string) {
    return this.http.patch<ApiResponse<Campaign>>(`${this.url}/${id}/enable`, {});
  }

  disable(id: string) {
    return this.http.patch<ApiResponse<Campaign>>(`${this.url}/${id}/disable`, {});
  }

  delete(id: string) {
    return this.http.delete<ApiResponse<null>>(`${this.url}/${id}`);
  }
}
