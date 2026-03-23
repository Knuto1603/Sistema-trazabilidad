import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NotificationService, Notification } from '@core/services/notification.service';

@Component({
  selector: 'app-notification',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-80">
      @for (n of notificationService.notifications(); track n.id) {
        <div [class]="getClass(n.type)"
             class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg border animate-in slide-in-from-right duration-300">
          <div class="shrink-0 mt-0.5">
            @switch (n.type) {
              @case ('success') { <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> }
              @case ('error') { <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> }
              @case ('warning') { <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> }
              @default { <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> }
            }
          </div>
          <p class="text-sm font-medium text-slate-800 flex-1">{{ n.message }}</p>
          <button (click)="notificationService.remove(n.id)" class="shrink-0 text-slate-400 hover:text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      }
    </div>
  `
})
export class NotificationComponent {
  notificationService = inject(NotificationService);

  getClass(type: string): string {
    const map: Record<string, string> = {
      success: 'bg-emerald-50 border-emerald-200',
      error: 'bg-red-50 border-red-200',
      warning: 'bg-amber-50 border-amber-200',
      info: 'bg-blue-50 border-blue-200',
    };
    return map[type] ?? map['info'];
  }
}
