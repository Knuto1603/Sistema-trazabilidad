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
  operacionId?: string;
  numeroCliente?: number;
  numeroPlanta?: number;
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

  proximoNumero(operacionId?: string): Observable<ApiResponse<{ numeroPlanta: number }>> {
    const params: any = {};
    if (operacionId) params['operacionId'] = operacionId;
    return this.http.get<ApiResponse<{ numeroPlanta: number }>>(`${this.base}/proximo-numero`, { params });
  }

  proximoNumeroCliente(clienteId: string, operacionId?: string): Observable<ApiResponse<{ numeroCliente: number }>> {
    const params: any = { clienteId };
    if (operacionId) params['operacionId'] = operacionId;
    return this.http.get<ApiResponse<{ numeroCliente: number }>>(`${this.base}/proximo-numero-cliente`, { params });
  }

  previewCorreo(id: string): Observable<ApiResponse<{ asunto: string; cuerpo: string; destinatarios: string }>> {
    return this.http.get<ApiResponse<{ asunto: string; cuerpo: string; destinatarios: string }>>(`${this.base}/${id}/preview-correo`);
  }

  enviarCorreo(id: string, dto: { asunto: string; cuerpo: string; destinatarios: string; archivosIds: string[] }): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(`${this.base}/${id}/enviar-correo`, dto);
  }
}
