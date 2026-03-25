import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse, FilterParams } from '@core/models/api.model';
import { Factura } from '@core/models/core.model';

export interface FacturaCreateDto {
  tipoDocumento: string;
  serie: string;
  correlativo: string;
  numeroGuia?: string;
  fechaEmision: string;
  moneda: string;
  detalle?: string;
  kgCaja?: number;
  unidadMedida?: string;
  cajas?: number;
  cantidad?: number;
  valorUnitario?: number;
  importe?: number;
  igv?: number;
  total?: number;
  tipoCambio?: number;
  tipoServicio?: string;
  tipoOperacion?: string;
  contenedor?: string;
  destino?: string;
  despachoId: string;
}

@Injectable({ providedIn: 'root' })
export class FacturaService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/facturas`;

  getAll(params: FilterParams & { despachoId?: string; tipoDocumento?: string }): Observable<ApiListResponse<Factura>> {
    return this.http.get<ApiListResponse<Factura>>(`${this.base}/`, { params: params as any });
  }

  getById(id: string): Observable<ApiResponse<Factura>> {
    return this.http.get<ApiResponse<Factura>>(`${this.base}/${id}`);
  }

  getByDespacho(despachoId: string): Observable<{ status: boolean; items: Factura[] }> {
    return this.http.get<{ status: boolean; items: Factura[] }>(`${this.base}/by-despacho/${despachoId}`);
  }

  create(dto: FacturaCreateDto): Observable<ApiResponse<Factura>> {
    return this.http.post<ApiResponse<Factura>>(`${this.base}/`, dto);
  }

  update(id: string, dto: Partial<FacturaCreateDto>): Observable<ApiResponse<Factura>> {
    return this.http.put<ApiResponse<Factura>>(`${this.base}/${id}`, dto);
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`);
  }

  anular(id: string): Observable<ApiResponse<Factura>> {
    return this.http.patch<ApiResponse<Factura>>(`${this.base}/${id}/anular`, {});
  }

  parseXml(file: File): Observable<ApiResponse<any>> {
    const formData = new FormData();
    formData.append('archivo', file);
    return this.http.post<ApiResponse<any>>(`${this.base}/parse-xml`, formData);
  }

  exportReporte(search?: string): void {
    let url = `${this.base}/export-reporte`;
    if (search) url += `?search=${encodeURIComponent(search)}`;
    window.open(url, '_blank');
  }
}
