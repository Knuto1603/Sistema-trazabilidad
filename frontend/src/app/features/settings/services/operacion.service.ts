import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse } from '@core/models/api.model';
import { Operacion } from '@core/models/core.model';

export interface OperacionCreateDto {
  nombre: string;
  sede: 'SULLANA' | 'TAMBOGRANDE' | 'GENERAL';
}

@Injectable({ providedIn: 'root' })
export class OperacionService {
  private http = inject(HttpClient);
  private url = `${environment.coreUrl}/operaciones`;

  getAll(sede?: string): Observable<{ status: boolean; items: Operacion[] }> {
    const params: any = {};
    if (sede) params['sede'] = sede;
    return this.http.get<{ status: boolean; items: Operacion[] }>(`${this.url}/`, { params });
  }

  create(dto: OperacionCreateDto): Observable<ApiResponse<Operacion>> {
    return this.http.post<ApiResponse<Operacion>>(`${this.url}/`, dto);
  }

  update(id: string, dto: OperacionCreateDto): Observable<ApiResponse<Operacion>> {
    return this.http.put<ApiResponse<Operacion>>(`${this.url}/${id}`, dto);
  }

  enable(id: string): Observable<ApiResponse<Operacion>> {
    return this.http.patch<ApiResponse<Operacion>>(`${this.url}/${id}/enable`, {});
  }

  disable(id: string): Observable<ApiResponse<Operacion>> {
    return this.http.patch<ApiResponse<Operacion>>(`${this.url}/${id}/disable`, {});
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.url}/${id}`);
  }
}
