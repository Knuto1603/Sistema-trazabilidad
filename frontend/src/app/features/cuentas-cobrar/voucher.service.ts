import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse } from '@core/models/api.model';
import { Voucher } from '@core/models/core.model';

export interface VoucherFormDto {
  clienteId?: string;
  numero: string;
  numeroOperacion?: string;
  montoTotal: number;
  fecha: string;
}

@Injectable({ providedIn: 'root' })
export class VoucherService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/vouchers`;

  list(clienteId: string, q: string = '', page: number = 0): Observable<ApiListResponse<Voucher>> {
    return this.http.get<ApiListResponse<Voucher>>(`${this.base}/`, {
      params: { clienteId, q, page: page.toString() }
    });
  }

  create(dto: VoucherFormDto): Observable<ApiResponse<Voucher>> {
    return this.http.post<ApiResponse<Voucher>>(`${this.base}/`, dto);
  }

  update(id: string, dto: Omit<VoucherFormDto, 'clienteId'>): Observable<ApiResponse<Voucher>> {
    return this.http.put<ApiResponse<Voucher>>(`${this.base}/${id}`, dto);
  }

  search(clienteId: string, q: string = ''): Observable<{ status: boolean; items: Voucher[] }> {
    return this.http.get<{ status: boolean; items: Voucher[] }>(`${this.base}/search`, {
      params: { clienteId, q }
    });
  }

  searchTodos(clienteId: string, q: string = ''): Observable<{ status: boolean; items: Voucher[] }> {
    return this.http.get<{ status: boolean; items: Voucher[] }>(`${this.base}/search`, {
      params: { clienteId, q, todos: 'true' }
    });
  }

  getByNumero(clienteId: string, numero: string): Observable<ApiResponse<Voucher>> {
    return this.http.get<ApiResponse<Voucher>>(`${this.base}/by-numero`, {
      params: { clienteId, numero }
    });
  }

  getById(id: string): Observable<ApiResponse<Voucher>> {
    return this.http.get<ApiResponse<Voucher>>(`${this.base}/${id}`);
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`);
  }

  forceDelete(id: string, justificante: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}/force`, {
      body: { justificante }
    });
  }
}
