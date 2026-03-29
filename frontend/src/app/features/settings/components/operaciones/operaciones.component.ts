import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { OperacionService, OperacionCreateDto } from '../../services/operacion.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Operacion } from '@core/models/core.model';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-operaciones',
  standalone: true,
  imports: [ReactiveFormsModule, ConfirmDialogComponent],
  templateUrl: './operaciones.component.html',
})
export class OperacionesComponent implements OnInit {
  private operacionService = inject(OperacionService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  items = signal<Operacion[]>([]);
  loading = signal(false);
  saving = signal(false);
  filtroSede = signal<string>('');

  showModal = signal(false);
  editing = signal<Operacion | null>(null);
  showConfirm = signal(false);
  deletingId = signal<string | null>(null);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  readonly SEDES = ['SULLANA', 'TAMBOGRANDE', 'GENERAL'];

  form = this.fb.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    sede: ['', Validators.required],
  });

  itemsFiltrados = computed(() => {
    const sede = this.filtroSede();
    if (!sede) return this.items();
    return this.items().filter(o => o.sede === sede);
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.operacionService.getAll().subscribe({
      next: res => {
        if (res.status) this.items.set(res.items);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  openCreate(): void {
    this.editing.set(null);
    this.form.reset();
    this.showModal.set(true);
  }

  openEdit(item: Operacion): void {
    this.editing.set(item);
    this.form.patchValue({ nombre: item.nombre, sede: item.sede });
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.form.reset();
    this.editing.set(null);
  }

  save(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;
    const dto: OperacionCreateDto = {
      nombre: raw.nombre!,
      sede: raw.sede! as any,
    };

    const current = this.editing();
    const op = current
      ? this.operacionService.update(current.id, dto)
      : this.operacionService.create(dto);

    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(current ? 'Operación actualizada' : 'Operación creada');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => { this.notification.error('Error al guardar'); this.saving.set(false); },
    });
  }

  toggleEstado(item: Operacion): void {
    const op = item.isActive
      ? this.operacionService.disable(item.id)
      : this.operacionService.enable(item.id);
    op.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(item.isActive ? 'Operación deshabilitada' : 'Operación habilitada');
          this.load();
        }
      },
      error: () => this.notification.error('Error al cambiar estado'),
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
    this.operacionService.delete(id).subscribe({
      next: res => {
        if (res.status) { this.notification.success('Operación eliminada'); this.load(); }
        this.cancelDelete();
      },
      error: () => { this.notification.error('Error al eliminar'); this.cancelDelete(); },
    });
  }

  hasError(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
