import { Component, input, output, computed } from '@angular/core';
import { environment } from '@env/environment';

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
              <p class="text-sm font-semibold text-slate-800 truncate">{{ nombre() }}</p>
              <p class="text-xs text-slate-400 font-mono truncate">{{ ruta() }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0 ml-3">
            <a [href]="fileUrl()" [download]="nombre()" target="_blank"
               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Descargar
            </a>
            <a [href]="fileUrl()" target="_blank" rel="noopener"
               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
              Abrir
            </a>
            <button (click)="close.emit()"
               class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Visor -->
        <div class="flex-1 bg-slate-100 overflow-hidden">
          @if (isPdf()) {
            <iframe
              [src]="fileUrl()"
              class="w-full h-full border-0"
              title="Visor PDF">
            </iframe>
          } @else {
            <div class="flex flex-col items-center justify-center h-full gap-4 text-slate-400">
              <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <p class="text-sm font-medium">Este archivo no es un PDF</p>
              <a [href]="fileUrl()" [download]="nombre()"
                 class="px-4 py-2 text-sm font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                Descargar archivo
              </a>
            </div>
          }
        </div>

      </div>
    </div>
  `
})
export class PdfViewerComponent {
  ruta = input.required<string>();
  nombre = input<string>('documento.pdf');
  close = output<void>();

  fileUrl = computed(() => {
    const base = environment.coreUrl.replace('/api', '');
    return `${base}/${this.ruta()}`;
  });

  isPdf = computed(() => this.ruta().toLowerCase().endsWith('.pdf'));
}
