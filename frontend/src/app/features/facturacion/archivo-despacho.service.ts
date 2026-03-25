import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse } from '@core/models/api.model';
import { ArchivoDespacho } from '@core/models/core.model';

@Injectable({ providedIn: 'root' })
export class ArchivoDespachoService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/archivos-despacho`;

  upload(
    despachoId: string,
    tipoArchivo: string,
    file: File,
    facturaId?: string,
  ): Observable<ApiResponse<ArchivoDespacho>> {
    const formData = new FormData();
    formData.append('despachoId', despachoId);
    formData.append('tipoArchivo', tipoArchivo);
    formData.append('archivo', file);
    if (facturaId) {
      formData.append('facturaId', facturaId);
    }
    return this.http.post<ApiResponse<ArchivoDespacho>>(`${this.base}/upload`, formData);
  }

  getByDespacho(despachoId: string): Observable<{ status: boolean; items: ArchivoDespacho[] }> {
    return this.http.get<{ status: boolean; items: ArchivoDespacho[] }>(`${this.base}/by-despacho/${despachoId}`);
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`);
  }
}
