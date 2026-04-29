import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { RoleService } from '../../services/role.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { UserRole } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { SYSTEM_MODULES, getModulesByGroup, ModuleDefinition } from '@core/constants/modules.config';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-roles',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent],
  templateUrl: './roles.component.html'
})
export class RolesComponent implements OnInit {
  private roleService = inject(RoleService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  items = signal<UserRole[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  saving = signal(false);
  search = signal('');
  currentPage = signal(0);

  showModal = signal(false);
  editing = signal<UserRole | null>(null);
  showConfirm = signal(false);
  deletingId = signal<string | null>(null);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  moduleGroups = getModulesByGroup();
  allModuleKeys = SYSTEM_MODULES.map(m => m.key);

  // Módulos seleccionados en el modal (array local, no en el formulario reactivo)
  selectedModules = signal<string[]>([]);

  form = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
    alias: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.roleService.getAll({ page: this.currentPage(), itemsPerPage: 10, search: this.search() }).subscribe({
      next: res => {
        if (res.status) { this.items.set(res.items); this.pagination.set(res.pagination); }
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
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
    this.selectedModules.set([]);
    this.showModal.set(true);
  }

  openEdit(item: UserRole): void {
    this.editing.set(item);
    this.form.patchValue({ name: item.name, alias: item.alias });
    this.selectedModules.set([...(item.modules ?? [])]);
    this.showModal.set(true);
  }

  closeModal(): void { this.showModal.set(false); this.form.reset(); this.selectedModules.set([]); }

  isModuleSelected(key: string): boolean {
    return this.selectedModules().includes(key);
  }

  toggleModule(key: string, checked: boolean): void {
    const current = this.selectedModules();
    if (checked) {
      this.selectedModules.set([...current, key]);
    } else {
      this.selectedModules.set(current.filter(k => k !== key));
    }
  }

  selectAllModules(): void {
    this.selectedModules.set([...this.allModuleKeys]);
  }

  clearAllModules(): void {
    this.selectedModules.set([]);
  }

  get selectedCount(): number { return this.selectedModules().length; }
  get totalModules(): number { return this.allModuleKeys.length; }

  save(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const { name, alias } = this.form.value as { name: string; alias: string };
    const modules = this.selectedModules();
    const current = this.editing();
    const op = current
      ? this.roleService.update(current.id, { name, alias, modules })
      : this.roleService.create({ name, alias, modules });

    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(current ? 'Rol actualizado' : 'Rol creado');
          this.closeModal(); this.load();
        }
        this.saving.set(false);
      },
      error: () => this.saving.set(false)
    });
  }

  toggleEstado(item: UserRole): void {
    const op = item.isActive ? this.roleService.disable(item.id) : this.roleService.enable(item.id);
    op.subscribe(res => {
      if (res.status) {
        this.notification.success(item.isActive ? 'Rol deshabilitado' : 'Rol habilitado');
        this.load();
      }
    });
  }

  confirmDelete(id: string): void { this.deletingId.set(id); this.showConfirm.set(true); }
  cancelDelete(): void { this.showConfirm.set(false); this.deletingId.set(null); }

  doDelete(): void {
    const id = this.deletingId();
    if (!id) return;
    this.roleService.delete(id).subscribe(res => {
      if (res.status) { this.notification.success('Rol eliminado'); this.load(); }
      this.cancelDelete();
    });
  }

  getModuleLabel(key: string): string {
    return SYSTEM_MODULES.find(m => m.key === key)?.label ?? key;
  }

  hasError(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
