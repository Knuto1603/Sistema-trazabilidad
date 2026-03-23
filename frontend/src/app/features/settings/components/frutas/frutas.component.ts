import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { FrutaService, FrutaCreateDto } from '../../services/fruta.service';
import { NotificationService } from '@core/services/notification.service';
import { Fruit } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';

@Component({
  selector: 'app-frutas',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent],
  templateUrl: './frutas.component.html'
})
export class FrutasComponent implements OnInit {
  private frutaService = inject(FrutaService);
  private notification = inject(NotificationService);
  private fb = inject(FormBuilder);

  items = signal<Fruit[]>([]);
  pagination = signal<Pagination>({
    page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0
  });
  loading = signal(false);
  saving = signal(false);
  search = signal('');
  currentPage = signal(0);

  showModal = signal(false);

  form = this.fb.group({
    codigo: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(5)]],
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]]
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  skeletonRows = [1, 2, 3, 4, 5];

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.frutaService.getAll({
      page: this.currentPage(),
      itemsPerPage: 10,
      search: this.search()
    }).subscribe({
      next: res => {
        if (res.status) {
          this.items.set(res.items);
          this.pagination.set(res.pagination);
        }
        this.loading.set(false);
      },
      error: () => {
        this.notification.error('Error al cargar frutas');
        this.loading.set(false);
      }
    });
  }

  onSearch(value: string): void {
    this.search.set(value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => {
      this.currentPage.set(0);
      this.load();
    }, 400);
  }

  onPageChange(page: number): void {
    this.currentPage.set(page);
    this.load();
  }

  openCreate(): void {
    this.form.reset();
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.form.reset();
  }

  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.saving.set(true);

    const raw = this.form.value;
    const data: FrutaCreateDto = {
      codigo: raw.codigo!,
      nombre: raw.nombre!
    };

    this.frutaService.create(data).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Fruta creada');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => {
        this.notification.error('Error al crear la fruta');
        this.saving.set(false);
      }
    });
  }

  toggleEstado(item: Fruit): void {
    const op = item.isActive
      ? this.frutaService.disable(item.id)
      : this.frutaService.enable(item.id);
    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(item.isActive ? 'Fruta deshabilitada' : 'Fruta habilitada');
          this.load();
        }
      },
      error: () => this.notification.error('Error al cambiar estado')
    });
  }

  hasError(field: string): boolean {
    const control = this.form.get(field);
    return !!(control?.invalid && control?.touched);
  }
}
