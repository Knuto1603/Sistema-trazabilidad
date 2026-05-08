import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { ApiResponse } from '@core/models/api.model';
import { UserSmtpConfig } from '@core/models/core.model';

@Injectable({ providedIn: 'root' })
export class UserSmtpConfigService {
  private http = inject(HttpClient);
  private url = `${environment.coreUrl}/smtp-config`;

  get(userUuid: string) {
    return this.http.get<ApiResponse<UserSmtpConfig | null>>(`${this.url}/${userUuid}`);
  }

  save(userUuid: string, data: { smtpEmail: string; smtpPassword?: string }) {
    return this.http.post<ApiResponse<UserSmtpConfig>>(`${this.url}/${userUuid}`, data);
  }

  clear(userUuid: string) {
    return this.http.delete<ApiResponse<null>>(`${this.url}/${userUuid}`);
  }
}
