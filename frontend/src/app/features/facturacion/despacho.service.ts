import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse, FilterParams } from '@core/models/api.model';
import { Despacho } from '@core/models/core.model';

export interface DespachoCreateDto {
  sede: string;
  contenedor?: string;
  observaciones?: string;
  clienteId: string;
  frutaId: string;
}

@Injectable({ providedIn: 'root' })
export class DespachoService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/despachos`;

  getAll(params: FilterParams & { clienteId?: string; frutaId?: string; sede?: string }): Observable<ApiListResponse<Despacho>> {
    return this.http.get<ApiListResponse<Despacho>>(`${this.base}/`, { params: params as any });
  }

  getById(id: string): Observable<ApiResponse<Despacho>> {
    return this.http.get<ApiResponse<Despacho>>(`${this.base}/${id}`);
  }

  create(dto: DespachoCreateDto): Observable<ApiResponse<Despacho>> {
    return this.http.post<ApiResponse<Despacho>>(`${this.base}/`, dto);
  }

  update(id: string, dto: Partial<DespachoCreateDto>): Observable<ApiResponse<Despacho>> {
    return this.http.put<ApiResponse<Despacho>>(`${this.base}/${id}`, dto);
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`);
  }
}
