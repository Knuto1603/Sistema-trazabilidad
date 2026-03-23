import { Component, input, output, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Pagination } from '@core/models/api.model';

@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [CommonModule],
  template: `
    @if (pagination().totalItems > 0) {
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">
        <p class="text-xs text-slate-500 font-medium">
          Pág. {{ pagination().page + 1 }} de {{ totalPages() }} ({{ pagination().totalItems }} registros)
        </p>
        <div class="flex items-center gap-1">
          <button
            (click)="pageChange.emit(pagination().page - 1)"
            [disabled]="pagination().page === 0"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 border-slate-200 text-slate-600">
            Anterior
          </button>
          @for (p of pageNumbers(); track p) {
            <button
              (click)="pageChange.emit(p)"
              [class]="p === pagination().page ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
              class="w-8 h-8 text-xs font-semibold rounded-lg border transition-colors">
              {{ p + 1 }}
            </button>
          }
          <button
            (click)="pageChange.emit(pagination().page + 1)"
            [disabled]="pagination().page >= totalPages() - 1"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 border-slate-200 text-slate-600">
            Siguiente
          </button>
        </div>
      </div>
    }
  `
})
export class PaginationComponent {
  pagination = input.required<Pagination>();
  pageChange = output<number>();

  totalPages = computed(() => Math.ceil(this.pagination().totalItems / this.pagination().itemsPerPage));

  pageNumbers = computed(() => {
    const total = this.totalPages();
    const current = this.pagination().page;
    const pages: number[] = [];
    const start = Math.max(0, current - 2);
    const end = Math.min(total - 1, current + 2);
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
  });
}
