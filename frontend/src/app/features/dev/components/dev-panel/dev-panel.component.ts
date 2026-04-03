import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DevService, DevInfo, DevHealth, DevMigraciones } from '../../dev.service';
import { NotificationService } from '@core/services/notification.service';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

type Tab = 'estado' | 'correo' | 'migraciones';

@Component({
  selector: 'app-dev-panel',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeaderComponent],
  templateUrl: './dev-panel.component.html',
})
export class DevPanelComponent implements OnInit {
  private devService = inject(DevService);
  private notif = inject(NotificationService);

  activeTab = signal<Tab>('estado');

  // Estado del servidor
  info = signal<DevInfo | null>(null);
  health = signal<DevHealth | null>(null);
  loadingInfo = signal(false);
  loadingHealth = signal(false);
  clearingCache = signal(false);

  // Correo test
  correoDestinatario = signal('');
  correoAsunto = signal('TEST - Sistema Trazabilidad');
  correoCuerpo = signal('Correo de prueba enviado desde el Panel Developer.\n\nSi recibiste este mensaje, el sistema de correo está funcionando correctamente.');
  correoArchivos = signal<File[]>([]);
  enviandoCorreo = signal(false);

  // Migraciones
  migraciones = signal<DevMigraciones | null>(null);
  loadingMig = signal(false);

  ngOnInit() {
    this.loadEstado();
  }

  setTab(tab: string) {
    this.activeTab.set(tab as Tab);
    if (tab === 'estado' && !this.info()) this.loadEstado();
    if (tab === 'migraciones' && !this.migraciones()) this.loadMigraciones();
  }

  loadEstado() {
    this.loadingInfo.set(true);
    this.devService.getInfo().subscribe({
      next: res => { this.info.set(res.item ?? null); this.loadingInfo.set(false); },
      error: () => { this.notif.error('No se pudo obtener info del servidor'); this.loadingInfo.set(false); }
    });

    this.loadingHealth.set(true);
    this.devService.getHealth().subscribe({
      next: res => { this.health.set(res.item ?? null); this.loadingHealth.set(false); },
      error: () => { this.loadingHealth.set(false); }
    });
  }

  refreshEstado() {
    this.info.set(null);
    this.health.set(null);
    this.loadEstado();
  }

  clearCache() {
    this.clearingCache.set(true);
    this.devService.clearCache().subscribe({
      next: res => {
        this.notif.success(res.message ?? 'Cache limpiado correctamente');
        this.clearingCache.set(false);
      },
      error: err => {
        this.notif.error(err?.error?.message ?? 'Error al limpiar cache');
        this.clearingCache.set(false);
      }
    });
  }

  enviarCorreoTest() {
    const dest = this.correoDestinatario().trim();
    if (!dest) { this.notif.warning('Ingresa un destinatario'); return; }

    this.enviandoCorreo.set(true);
    this.devService.testCorreo({
      destinatario: dest,
      asunto: this.correoAsunto().trim() || undefined,
      cuerpo: this.correoCuerpo().trim() || undefined,
      archivos: this.correoArchivos(),
    }).subscribe({
      next: res => {
        this.notif.success(res.message ?? 'Correo enviado correctamente');
        this.correoArchivos.set([]);
        this.enviandoCorreo.set(false);
      },
      error: err => {
        this.notif.error(err?.error?.message ?? 'Error al enviar correo');
        this.enviandoCorreo.set(false);
      }
    });
  }

  loadMigraciones() {
    this.loadingMig.set(true);
    this.devService.getMigraciones().subscribe({
      next: res => { this.migraciones.set(res.item ?? null); this.loadingMig.set(false); },
      error: () => { this.notif.error('No se pudo obtener migraciones'); this.loadingMig.set(false); }
    });
  }

  refreshMigraciones() {
    this.migraciones.set(null);
    this.loadMigraciones();
  }

  onArchivosSeleccionados(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.correoArchivos.set(input.files ? Array.from(input.files) : []);
  }

  removeArchivo(index: number): void {
    this.correoArchivos.update(files => files.filter((_, i) => i !== index));
  }

  diskUsedPercent(info: DevInfo): number {
    if (!info.disk_total_gb) return 0;
    return Math.round(((info.disk_total_gb - info.disk_free_gb) / info.disk_total_gb) * 100);
  }
}
