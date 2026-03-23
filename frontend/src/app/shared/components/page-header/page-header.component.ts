import { Component, input } from '@angular/core';
import { RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';

export interface Breadcrumb { label: string; link?: string; }

@Component({
  selector: 'app-page-header',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="mb-6">
      @if (breadcrumbs().length > 0) {
        <nav class="flex items-center gap-1 text-xs text-slate-500 mb-2 font-medium">
          @for (crumb of breadcrumbs(); track crumb.label; let last = $last) {
            @if (crumb.link) {
              <a [routerLink]="crumb.link" class="hover:text-blue-600 transition-colors">{{ crumb.label }}</a>
            } @else {
              <span [class.text-slate-800]="last" [class.font-semibold]="last">{{ crumb.label }}</span>
            }
            @if (!last) { <span>/</span> }
          }
        </nav>
      }
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">{{ title() }}</h1>
          @if (subtitle()) {
            <p class="text-sm text-slate-500 mt-1">{{ subtitle() }}</p>
          }
        </div>
        <ng-content></ng-content>
      </div>
    </div>
  `
})
export class PageHeaderComponent {
  title = input.required<string>();
  subtitle = input<string>('');
  breadcrumbs = input<Breadcrumb[]>([]);
}
