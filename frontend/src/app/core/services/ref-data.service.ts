import { Injectable } from '@angular/core';
import { Observable, shareReplay } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class RefDataService {
  private cache = new Map<string, Observable<unknown>>();

  getOrFetch<T>(key: string, factory: () => Observable<T>): Observable<T> {
    if (!this.cache.has(key)) {
      this.cache.set(key, factory().pipe(shareReplay(1)));
    }
    return this.cache.get(key) as Observable<T>;
  }

  invalidate(...keys: string[]): void {
    keys.forEach(k => this.cache.delete(k));
  }

  invalidatePrefix(prefix: string): void {
    for (const key of this.cache.keys()) {
      if (key.startsWith(prefix)) this.cache.delete(key);
    }
  }
}
