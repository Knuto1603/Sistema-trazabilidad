import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';

export interface SenasaVerificarResponse {
  success: boolean;
  data?: any;
  error?: string;
}

@Injectable({ providedIn: 'root' })
export class SenasaService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/senasa`;

  verificar(codigo: string, fecha: string): Observable<SenasaVerificarResponse> {
    return this.http.post<SenasaVerificarResponse>(`${this.base}/verificar`, { codigo, fecha });
  }
}
