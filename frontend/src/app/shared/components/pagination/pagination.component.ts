import { Component, input, output, computed } from '@angular/core';
import { Pagination } from '@core/models/api.model';

@Component({
  selector: 'app-pagination',
  standalone: true,
  template: `
    @if (pagination().totalItems > 0) {
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">

        <div class="flex items-center gap-3">
          @if (pageSizes().length > 0) {
            <select
              [value]="pagination().itemsPerPage"
              (change)="pageSizeChange.emit(+$any($event.target).value)"
              class="px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
              @for (size of pageSizes(); track size) {
                <option [value]="size">{{ size }} por pág.</option>
              }
            </select>
          }
          <p class="text-xs text-slate-500 font-medium">
            {{ pagination().startIndex + 1 }}–{{ pagination().endIndex }}
            <span class="text-slate-400">de</span>
            <span class="font-semibold text-slate-600">{{ pagination().totalItems }}</span>
          </p>
        </div>

        <div class="flex items-center gap-1">
          <button
            (click)="pageChange.emit(pagination().page - 1)"
            [disabled]="pagination().page === 0"
            class="flex items-center gap-1 pl-2.5 pr-3 h-8 text-xs font-semibold rounded-lg border transition-all
                   border-slate-200 text-slate-600 bg-white hover:bg-slate-50 hover:border-slate-300 hover:shadow-sm
                   disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-slate-200 disabled:hover:shadow-none">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
            Ant.
          </button>

          @for (p of pageNumbers(); track $index) {
            @if (p === -1) {
              <span class="w-8 h-8 flex items-center justify-center text-xs text-slate-300 select-none">•••</span>
            } @else {
              <button
                (click)="pageChange.emit(p)"
                class="w-8 h-8 text-xs font-semibold rounded-lg border transition-all"
                [class]="p === pagination().page
                  ? 'bg-blue-600 text-white border-blue-600 shadow-sm shadow-blue-200'
                  : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:shadow-sm'">
                {{ p + 1 }}
              </button>
            }
          }

          <button
            (click)="pageChange.emit(pagination().page + 1)"
            [disabled]="pagination().page >= totalPages() - 1"
            class="flex items-center gap-1 pl-3 pr-2.5 h-8 text-xs font-semibold rounded-lg border transition-all
                   border-slate-200 text-slate-600 bg-white hover:bg-slate-50 hover:border-slate-300 hover:shadow-sm
                   disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-slate-200 disabled:hover:shadow-none">
            Sig.
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

      </div>
    }
  `
})
export class PaginationComponent {
  pagination = input.required<Pagination>();
  pageSizes = input<number[]>([]);
  pageChange = output<number>();
  pageSizeChange = output<number>();

  totalPages = computed(() => Math.ceil(this.pagination().totalItems / this.pagination().itemsPerPage));

  pageNumbers = computed(() => {
    const total = this.totalPages();
    const current = this.pagination().page;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i);

    const pages: number[] = [0];
    const rangeStart = Math.max(1, current - 1);
    const rangeEnd = Math.min(total - 2, current + 1);
    if (rangeStart > 1) pages.push(-1);
    for (let i = rangeStart; i <= rangeEnd; i++) pages.push(i);
    if (rangeEnd < total - 2) pages.push(-1);
    pages.push(total - 1);
    return pages;
  });
}
