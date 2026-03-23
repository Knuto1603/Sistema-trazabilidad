import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { CampahnaService, CampahnaCreateDto } from '../../services/campahna.service';
import { FrutaService } from '../../services/fruta.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Campaign } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-campanhas',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent],
  templateUrl: './campanhas.component.html'
})
export class CampanhasComponent implements OnInit {
  private campahnaService = inject(CampahnaService);
  private frutaService = inject(FrutaService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  items = signal<Campaign[]>([]);
  pagination = signal<Pagination>({
    page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0
  });
  loading = signal(false);
  saving = signal(false);
  search = signal('');
  currentPage = signal(0);

  showModal = signal(false);
  editing = signal<Campaign | null>(null);
  showConfirm = signal(false);
  deletingId = signal<string | null>(null);

  frutas = signal<{ id: string; name: string }[]>([]);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  form = this.fb.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    descripcion: [''],
    fechaInicio: ['', Validators.required],
    fechaFin: [''],
    frutaId: ['', Validators.required]
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.load();
    this.frutaService.getShared().subscribe(res => {
      if (res.status) this.frutas.set(res.items);
    });
  }

  load(): void {
    this.loading.set(true);
    this.campahnaService.getAll({
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
        this.notification.error('Error al cargar campañas');
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
    this.editing.set(null);
    this.form.reset();
    this.showModal.set(true);
  }

  openEdit(item: Campaign): void {
    this.editing.set(item);
    this.form.patchValue({
      nombre: item.nombre,
      descripcion: item.descripcion ?? '',
      fechaInicio: item.fechaInicio,
      fechaFin: item.fechaFin ?? '',
      frutaId: item.frutaId
    });
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
    const data: CampahnaCreateDto = {
      nombre: raw.nombre!,
      descripcion: raw.descripcion ?? undefined,
      fechaInicio: raw.fechaInicio!,
      fechaFin: raw.fechaFin ?? undefined,
      frutaId: raw.frutaId!
    };

    const current = this.editing();
    const op = current
      ? this.campahnaService.update(current.id, data)
      : this.campahnaService.create(data);

    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(current ? 'Campaña actualizada' : 'Campaña creada');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => {
        this.notification.error('Error al guardar la campaña');
        this.saving.set(false);
      }
    });
  }

  toggleEstado(item: Campaign): void {
    const op = item.isActive
      ? this.campahnaService.disable(item.id)
      : this.campahnaService.enable(item.id);
    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(item.isActive ? 'Campaña deshabilitada' : 'Campaña habilitada');
          this.load();
        }
      },
      error: () => this.notification.error('Error al cambiar estado')
    });
  }

  confirmDelete(id: string): void {
    this.deletingId.set(id);
    this.showConfirm.set(true);
  }

  cancelDelete(): void {
    this.showConfirm.set(false);
    this.deletingId.set(null);
  }

  doDelete(): void {
    const id = this.deletingId();
    if (!id) return;
    this.campahnaService.delete(id).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Campaña eliminada');
          this.load();
        }
        this.cancelDelete();
      },
      error: () => {
        this.notification.error('Error al eliminar la campaña');
        this.cancelDelete();
      }
    });
  }

  hasError(field: string): boolean {
    const control = this.form.get(field);
    return !!(control?.invalid && control?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
