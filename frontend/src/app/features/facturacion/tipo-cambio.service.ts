import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse, FilterParams } from '@core/models/api.model';
import { TipoCambio } from '@core/models/core.model';

export interface TipoCambioDto {
  fecha: string;
  compra: number;
  venta: number;
}

@Injectable({ providedIn: 'root' })
export class TipoCambioService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/tipos-cambio`;

  getAll(params: FilterParams): Observable<ApiListResponse<TipoCambio>> {
    return this.http.get<ApiListResponse<TipoCambio>>(`${this.base}/`, { params: params as any });
  }

  getByFecha(fecha: string): Observable<ApiResponse<TipoCambio>> {
    return this.http.get<ApiResponse<TipoCambio>>(`${this.base}/by-fecha/${fecha}`);
  }

  createOrUpdate(dto: TipoCambioDto): Observable<ApiResponse<TipoCambio>> {
    return this.http.post<ApiResponse<TipoCambio>>(`${this.base}/`, dto);
  }

  scrapeFromSunat(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(`${this.base}/scrape-sunat`);
  }

  importarAnio(): Observable<ApiResponse<any>> {
    return this.http.post<ApiResponse<any>>(`${this.base}/importar-anio`, {});
  }
}
