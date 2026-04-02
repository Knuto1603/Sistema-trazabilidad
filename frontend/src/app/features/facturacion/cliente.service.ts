import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse, FilterParams } from '@core/models/api.model';
import { Cliente } from '@core/models/core.model';

export interface ClienteCreateDto {
  ruc: string;
  razonSocial: string;
  nombreComercial?: string;
  direccion?: string;
  departamento?: string;
  provincia?: string;
  distrito?: string;
  estado?: string;
  condicion?: string;
  tipoContribuyente?: string;
  telefono?: string;
  email?: string;
  emailDestinatarios?: string;
}

@Injectable({ providedIn: 'root' })
export class ClienteService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/clientes`;

  getAll(params: FilterParams): Observable<ApiListResponse<Cliente>> {
    return this.http.get<ApiListResponse<Cliente>>(`${this.base}/`, { params: params as any });
  }

  getById(id: string): Observable<ApiResponse<Cliente>> {
    return this.http.get<ApiResponse<Cliente>>(`${this.base}/${id}`);
  }

  create(dto: ClienteCreateDto): Observable<ApiResponse<Cliente>> {
    return this.http.post<ApiResponse<Cliente>>(`${this.base}/`, dto);
  }

  update(id: string, dto: ClienteCreateDto): Observable<ApiResponse<Cliente>> {
    return this.http.put<ApiResponse<Cliente>>(`${this.base}/${id}`, dto);
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`);
  }

  enable(id: string): Observable<ApiResponse<Cliente>> {
    return this.http.patch<ApiResponse<Cliente>>(`${this.base}/${id}/enable`, {});
  }

  disable(id: string): Observable<ApiResponse<Cliente>> {
    return this.http.patch<ApiResponse<Cliente>>(`${this.base}/${id}/disable`, {});
  }

  searchByRuc(ruc: string): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(`${this.base}/search-ruc/${ruc}`);
  }
}
