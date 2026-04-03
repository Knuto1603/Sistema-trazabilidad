import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse } from '@core/models/api.model';

export interface DevInfo {
  php: string;
  symfony_env: string;
  os: string;
  server_time: string;
  timezone: string;
  memory_usage: number;
  memory_peak: number;
  memory_limit: string;
  disk_free_gb: number;
  disk_total_gb: number;
  uploads_size_mb: number;
  cache_size_mb: number;
  mailer_ok: boolean;
  stats: {
    despachos: number;
    clientes: number;
    facturas: number;
    archivos: number;
  };
}

export interface DevHealth {
  db: { ok: boolean; msg: string };
  mailer: { ok: boolean; msg: string };
  uploads: { ok: boolean; msg: string };
  cache: { ok: boolean; msg: string };
}

export interface DevMigraciones {
  ejecutadas: number;
  en_disco: number;
  pendientes: string[];
}

export interface CorreoTestDto {
  destinatario: string;
  asunto?: string;
  cuerpo?: string;
  archivos?: File[];
}

@Injectable({ providedIn: 'root' })
export class DevService {
  private http = inject(HttpClient);
  private base = `${environment.coreUrl}/dev`;

  getInfo(): Observable<ApiResponse<DevInfo>> {
    return this.http.get<ApiResponse<DevInfo>>(`${this.base}/info`);
  }

  getHealth(): Observable<ApiResponse<DevHealth>> {
    return this.http.get<ApiResponse<DevHealth>>(`${this.base}/health`);
  }

  clearCache(): Observable<ApiResponse<{ output: string }>> {
    return this.http.post<ApiResponse<{ output: string }>>(`${this.base}/cache/clear`, {});
  }

  getMigraciones(): Observable<ApiResponse<DevMigraciones>> {
    return this.http.get<ApiResponse<DevMigraciones>>(`${this.base}/migraciones`);
  }

  testCorreo(dto: CorreoTestDto): Observable<ApiResponse<null>> {
    const form = new FormData();
    form.append('destinatario', dto.destinatario);
    if (dto.asunto) form.append('asunto', dto.asunto);
    if (dto.cuerpo) form.append('cuerpo', dto.cuerpo);
    (dto.archivos ?? []).forEach(f => form.append('archivos[]', f, f.name));
    return this.http.post<ApiResponse<null>>(`${this.base}/correo/test`, form);
  }
}
