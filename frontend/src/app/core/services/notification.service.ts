import { Injectable, signal } from '@angular/core';

export type NotificationType = 'success' | 'error' | 'info' | 'warning';

export interface Notification {
  id: number; type: NotificationType; message: string;
}

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private counter = 0;
  notifications = signal<Notification[]>([]);

  private add(type: NotificationType, message: string) {
    const id = ++this.counter;
    this.notifications.update(n => [...n, { id, type, message }]);
    setTimeout(() => this.remove(id), 4000);
  }

  success(message: string) { this.add('success', message); }
  error(message: string) { this.add('error', message); }
  info(message: string) { this.add('info', message); }
  warning(message: string) { this.add('warning', message); }
  remove(id: number) { this.notifications.update(n => n.filter(x => x.id !== id)); }
}
