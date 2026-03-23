import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, ApiListResponse, FilterParams } from '@core/models/api.model';
import { Producer } from '@core/models/core.model';

export interface ProductorCreateDto {
  codigo: string;
  nombre: string;
  clp?: string;
  mtdCeratitis?: string;
  mtdAnastrepha?: string;
  campahnaId: string;
}

@Injectable({ providedIn: 'root' })
export class ProductorService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/productores`;

  getAll(params: FilterParams): Observable<ApiListResponse<Producer>> {
    return this.http.get<ApiListResponse<Producer>>(`${this.base}/`, { params: params as any });
  }

  getByCampaign(campahnaId: string, params: FilterParams): Observable<ApiListResponse<Producer>> {
    return this.http.get<ApiListResponse<Producer>>(`${this.base}/campahna/${campahnaId}`, { params: params as any });
  }

  getLastCode(): Observable<ApiResponse<null> & { lastCode: string }> {
    return this.http.get<ApiResponse<null> & { lastCode: string }>(`${this.base}/last-code`);
  }

  create(dto: ProductorCreateDto): Observable<ApiResponse<Producer>> {
    return this.http.post<ApiResponse<Producer>>(`${this.base}/`, dto);
  }

  delete(id: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`);
  }

  assignToCampaign(campahnaId: string, productorId: string): Observable<ApiResponse<any>> {
    return this.http.post<ApiResponse<any>>(`${this.base}/campahna/${campahnaId}/assign`, { productorId });
  }

  assignMultipleToCampaign(campahnaId: string, productorIds: string[]): Observable<ApiResponse<any>> {
    return this.http.post<ApiResponse<any>>(`${this.base}/campahna/${campahnaId}/assign-multiple`, { productorIds });
  }

  removeFromCampaign(campahnaId: string, productorId: string): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/campahna/${campahnaId}/remove/${productorId}`);
  }
}
