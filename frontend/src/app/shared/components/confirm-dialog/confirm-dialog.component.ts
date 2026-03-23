import { Component, input, output } from '@angular/core';

@Component({
  selector: 'app-confirm-dialog',
  standalone: true,
  template: `
    <div class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md p-6 animate-in zoom-in duration-200">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-800">{{ title() }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ message() }}</p>
          </div>
        </div>
        <div class="flex gap-3 justify-end">
          <button (click)="cancelled.emit()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
            Cancelar
          </button>
          <button (click)="confirmed.emit()" class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">
            {{ confirmLabel() }}
          </button>
        </div>
      </div>
    </div>
  `
})
export class ConfirmDialogComponent {
  title = input<string>('¿Confirmar acción?');
  message = input<string>('Esta acción no se puede deshacer.');
  confirmLabel = input<string>('Eliminar');
  confirmed = output<void>();
  cancelled = output<void>();
}
