import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { ParametroService } from '../../services/parametro.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Parameter } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-parametros',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent],
  templateUrl: './parametros.component.html'
})
export class ParametrosComponent implements OnInit {
  private parametroService = inject(ParametroService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  items = signal<Parameter[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  saving = signal(false);
  search = signal('');
  currentPage = signal(0);

  showModal = signal(false);
  editing = signal<Parameter | null>(null);
  showConfirm = signal(false);
  deletingId = signal<string | null>(null);

  parents = signal<{ id: string; name: string }[]>([]);
  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  form = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    alias: ['', [Validators.maxLength(6)]],
    value: [''],
    parentId: ['']
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.parametroService.getAll({ page: this.currentPage(), itemsPerPage: 10, search: this.search() }).subscribe({
      next: res => {
        if (res.status) { this.items.set(res.items); this.pagination.set(res.pagination); }
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  loadParents(): void {
    this.parametroService.getParents().subscribe(res => {
      if (res.status) this.parents.set(res.items);
    });
  }

  onSearch(value: string): void {
    this.search.set(value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => { this.currentPage.set(0); this.load(); }, 400);
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.load(); }

  openCreate(): void {
    this.editing.set(null);
    this.form.reset();
    this.loadParents();
    this.showModal.set(true);
  }

  openEdit(item: Parameter): void {
    this.editing.set(item);
    this.form.patchValue({ name: item.name, alias: item.alias, value: item.value as any, parentId: item.parentId ?? '' });
    this.loadParents();
    this.showModal.set(true);
  }

  closeModal(): void { this.showModal.set(false); this.form.reset(); }

  save(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;
    const data: Partial<Parameter> = {
      name: raw.name!,
      alias: raw.alias ?? '',
      value: raw.value !== '' ? raw.value as any : null,
      parentId: raw.parentId || undefined
    };
    const current = this.editing();
    const op = current ? this.parametroService.update(current.id, data) : this.parametroService.create(data);
    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(current ? 'Parámetro actualizado' : 'Parámetro creado');
          this.closeModal(); this.load();
        }
        this.saving.set(false);
      },
      error: () => this.saving.set(false)
    });
  }

  toggleEstado(item: Parameter): void {
    const op = item.isActive ? this.parametroService.disable(item.id) : this.parametroService.enable(item.id);
    op.subscribe(res => {
      if (res.status) {
        this.notification.success(item.isActive ? 'Parámetro deshabilitado' : 'Parámetro habilitado');
        this.load();
      }
    });
  }

  confirmDelete(id: string): void { this.deletingId.set(id); this.showConfirm.set(true); }
  cancelDelete(): void { this.showConfirm.set(false); this.deletingId.set(null); }

  doDelete(): void {
    const id = this.deletingId();
    if (!id) return;
    this.parametroService.delete(id).subscribe(res => {
      if (res.status) { this.notification.success('Parámetro eliminado'); this.load(); }
      this.cancelDelete();
    });
  }

  hasError(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
