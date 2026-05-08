import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { ApiListResponse, ApiResponse, FilterParams } from '@core/models/api.model';
import { Fruit } from '@core/models/core.model';
import { RefDataService } from '@core/services/ref-data.service';

export interface FrutaCreateDto {
  codigo: string;
  nombre: string;
}

@Injectable({ providedIn: 'root' })
export class FrutaService {
  private http = inject(HttpClient);
  private refData = inject(RefDataService);
  private url = `${environment.coreUrl}/frutas`;

  static readonly CACHE_SHARED = 'frutas:shared';

  getAll(filters: FilterParams = {}) {
    let params = new HttpParams();
    if (filters.page !== undefined) params = params.set('page', filters.page);
    if (filters.itemsPerPage) params = params.set('itemsPerPage', filters.itemsPerPage);
    if (filters.search) params = params.set('search', filters.search);
    return this.http.get<ApiListResponse<Fruit>>(`${this.url}/`, { params });
  }

  getShared() {
    return this.refData.getOrFetch(
      FrutaService.CACHE_SHARED,
      () => this.http.get<{ status: boolean; items: { id: string; nombre: string }[] }>(`${this.url}/shared`)
    );
  }

  create(data: FrutaCreateDto) {
    return this.http.post<ApiResponse<Fruit>>(`${this.url}/`, data);
  }

  enable(id: string) {
    return this.http.patch<ApiResponse<Fruit>>(`${this.url}/${id}/enable`, {});
  }

  disable(id: string) {
    return this.http.patch<ApiResponse<Fruit>>(`${this.url}/${id}/disable`, {});
  }
}
