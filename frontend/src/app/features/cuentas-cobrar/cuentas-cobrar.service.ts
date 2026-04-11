import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse, FilterParams } from '@core/models/api.model';
import { CuentaCobrar, PagoFactura } from '@core/models/core.model';

export interface CuentasCobrarParams extends FilterParams {
  sede?: string;
  operacionId?: string;
  clienteId?: string;
  estado?: string;
}

export interface CreatePagoDto {
  facturaId: string;
  montoAplicado: number;
  voucherNumero: string;
  voucherNumeroOperacion?: string;
  voucherMontoTotal?: number;
  voucherFecha?: string;
}

export interface UpdatePagoDto {
  montoAplicado: number;
  voucherNumeroOperacion?: string;
}

export interface DeletePagoDto {
  justificante: string;
}

@Injectable({ providedIn: 'root' })
export class CuentasCobrarService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/cuentas-cobrar`;
  private pagosBase = `${environment.coreUrl}/pagos-factura`;

  getAll(params: CuentasCobrarParams): Observable<ApiListResponse<CuentaCobrar>> {
    return this.http.get<ApiListResponse<CuentaCobrar>>(`${this.base}/`, { params: params as any });
  }

  getPagosByFactura(facturaId: string): Observable<{ status: boolean; items: PagoFactura[] }> {
    return this.http.get<{ status: boolean; items: PagoFactura[] }>(
      `${this.pagosBase}/by-factura/${facturaId}`
    );
  }

  createPago(dto: CreatePagoDto): Observable<ApiResponse<PagoFactura>> {
    return this.http.post<ApiResponse<PagoFactura>>(`${this.pagosBase}/`, dto);
  }

  updatePago(id: string, dto: UpdatePagoDto): Observable<ApiResponse<PagoFactura>> {
    return this.http.put<ApiResponse<PagoFactura>>(`${this.pagosBase}/${id}`, dto);
  }

  deletePago(id: string, dto: DeletePagoDto): Observable<ApiResponse<PagoFactura>> {
    return this.http.delete<ApiResponse<PagoFactura>>(`${this.pagosBase}/${id}`, { body: dto });
  }
}
