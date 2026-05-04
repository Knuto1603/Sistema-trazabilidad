import { Component, input, output, inject, signal, effect, OnDestroy } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ArchivoDespacho } from '@core/models/core.model';
import { ArchivoDespachoService } from '@features/facturacion/archivo-despacho.service';

@Component({
  selector: 'app-pdf-viewer',
  standalone: true,
  template: `
    <div class="fixed inset-0 bg-slate-900/70 z-50 flex flex-col backdrop-blur-sm" (click.self)="close.emit()">
      <div class="flex flex-col h-full max-w-5xl w-full mx-auto my-4 sm:my-6 bg-white rounded-2xl shadow-2xl overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-white flex-shrink-0">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 flex-shrink-0 rounded-lg bg-red-50 flex items-center justify-center">
              <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-800 truncate">{{ archivo().nombre }}</p>
              <span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded text-xs font-semibold">{{ archivo().tipoArchivo }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0 ml-3">
            <button (click)="descargar()" [disabled]="loading() || !currentBlob"
               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors disabled:opacity-50">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Descargar
            </button>
            <button (click)="close.emit()"
               class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Visor -->
        <div class="flex-1 bg-slate-100 overflow-hidden relative">
          @if (loading()) {
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-slate-400">
              <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <p class="text-sm">Cargando documento...</p>
            </div>
          } @else if (errorMsg()) {
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-slate-400">
              <svg class="w-12 h-12 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <p class="text-sm font-medium text-red-400">{{ errorMsg() }}</p>
            </div>
          } @else if (blobUrl()) {
            @if (isPdf()) {
              <iframe [src]="blobUrl()!" class="w-full h-full border-0" title="Visor PDF"></iframe>
            } @else {
              <div class="flex flex-col items-center justify-center h-full gap-4 text-slate-400">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium">Vista previa no disponible para este tipo de archivo</p>
                <button (click)="descargar()" class="px-4 py-2 text-sm font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                  Descargar archivo
                </button>
              </div>
            }
          }
        </div>

      </div>
    </div>
  `
})
export class PdfViewerComponent implements OnDestroy {
  private sanitizer = inject(DomSanitizer);
  private archivoService = inject(ArchivoDespachoService);

  archivo = input.required<ArchivoDespacho>();
  close = output<void>();

  loading = signal(true);
  errorMsg = signal('');
  blobUrl = signal<SafeResourceUrl | null>(null);

  currentBlob: Blob | null = null;
  private currentObjectUrl: string | null = null;

  constructor() {
    effect(() => {
      const a = this.archivo();
      if (a?.id) this.loadFile(a.id, a.nombre);
    });
  }

  isPdf(): boolean {
    return this.archivo().nombre.toLowerCase().endsWith('.pdf');
  }

  private loadFile(id: string, nombre: string): void {
    this.loading.set(true);
    this.errorMsg.set('');
    this.revokeCurrentUrl();

    this.archivoService.download(id).subscribe({
      next: blob => {
        this.currentBlob = blob;
        const objectUrl = URL.createObjectURL(blob);
        this.currentObjectUrl = objectUrl;
        this.blobUrl.set(this.sanitizer.bypassSecurityTrustResourceUrl(objectUrl));
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('No se pudo cargar el documento. Verifica que el archivo exista en el servidor.');
        this.loading.set(false);
      }
    });
  }

  descargar(): void {
    if (!this.currentObjectUrl || !this.currentBlob) return;
    const a = document.createElement('a');
    a.href = this.currentObjectUrl;
    a.download = this.archivo().nombre;
    a.click();
  }

  private revokeCurrentUrl(): void {
    if (this.currentObjectUrl) {
      URL.revokeObjectURL(this.currentObjectUrl);
      this.currentObjectUrl = null;
    }
    this.blobUrl.set(null);
    this.currentBlob = null;
  }

  ngOnDestroy(): void {
    this.revokeCurrentUrl();
  }
}
