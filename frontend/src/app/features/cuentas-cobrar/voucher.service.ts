import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse } from '@core/models/api.model';
import { Voucher } from '@core/models/core.model';

@Injectable({ providedIn: 'root' })
export class VoucherService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/vouchers`;

  search(clienteId: string, q: string = ''): Observable<{ status: boolean; items: Voucher[] }> {
    return this.http.get<{ status: boolean; items: Voucher[] }>(`${this.base}/search`, {
      params: { clienteId, q }
    });
  }

  getByNumero(clienteId: string, numero: string): Observable<ApiResponse<Voucher>> {
    return this.http.get<ApiResponse<Voucher>>(`${this.base}/by-numero`, {
      params: { clienteId, numero }
    });
  }
}
