import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';
import { ApiResponse } from '@core/models/api.model';
import { AppUser, UserSmtpConfig } from '@core/models/core.model';

export interface UpdateMeDto {
  fullname?: string;
  password?: string;
  passwordConfirm?: string;
}

export interface SaveSmtpDto {
  smtpEmail: string;
  smtpPassword?: string;
  displayName?: string | null;
  firmaNombre?: string | null;
  firmaCargo?: string | null;
  firmaEmpresa?: string | null;
  ccEmails?: string | null;
}

@Injectable({ providedIn: 'root' })
export class AccountService {
  private http = inject(HttpClient);
  private secUrl = `${environment.securityUrl}/security`;
  private coreUrl = `${environment.coreUrl}/smtp-config`;

  getMe() {
    return this.http.get<ApiResponse<AppUser>>(`${this.secUrl}/me`);
  }

  updateMe(data: UpdateMeDto) {
    return this.http.put<ApiResponse<AppUser>>(`${this.secUrl}/me`, data);
  }

  getSmtpConfig() {
    return this.http.get<ApiResponse<UserSmtpConfig | null>>(`${this.coreUrl}/me`);
  }

  saveSmtpConfig(data: SaveSmtpDto) {
    return this.http.post<ApiResponse<UserSmtpConfig>>(`${this.coreUrl}/me`, data);
  }

  clearSmtpConfig() {
    return this.http.delete<ApiResponse<null>>(`${this.coreUrl}/me`);
  }
}
