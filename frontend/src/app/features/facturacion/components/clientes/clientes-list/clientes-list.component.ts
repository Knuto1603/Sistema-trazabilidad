import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { ClienteService, ClienteCreateDto } from '../../../cliente.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Cliente } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'app-clientes-list',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './clientes-list.component.html'
})
export class ClientesListComponent implements OnInit {
  private clienteService = inject(ClienteService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  items = signal<Cliente[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  saving = signal(false);
  rucLoading = signal(false);
  search = signal('');
  currentPage = signal(0);

  showModal = signal(false);
  editingId = signal<string | null>(null);
  showDeleteConfirm = signal(false);
  deletingId = signal<string | null>(null);
  deletingNombre = signal<string | null>(null);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  form = this.fb.group({
    ruc: ['', [Validators.required, Validators.minLength(11), Validators.maxLength(11), Validators.pattern(/^\d{11}$/)]],
    razonSocial: ['', [Validators.required, Validators.maxLength(255)]],
    nombreComercial: [''],
    telefono: [''],
    email: ['', [Validators.email]],
    direccion: [''],
    departamento: [''],
    provincia: [''],
    distrito: [''],
    estado: [''],
    condicion: [''],
    tipoContribuyente: [''],
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.clienteService.getAll({ page: this.currentPage(), itemsPerPage: 10, search: this.search() }).subscribe({
      next: res => {
        if (res.status) { this.items.set(res.items); this.pagination.set(res.pagination); }
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  onSearch(event: Event): void {
    this.search.set((event.target as HTMLInputElement).value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => { this.currentPage.set(0); this.load(); }, 400);
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.load(); }

  openCreateModal(): void {
    this.form.reset();
    this.editingId.set(null);
    this.showModal.set(true);
  }

  openEditModal(item: Cliente): void {
    this.editingId.set(item.id);
    this.form.patchValue({
      ruc: item.ruc,
      razonSocial: item.razonSocial,
      nombreComercial: item.nombreComercial ?? '',
      telefono: item.telefono ?? '',
      email: item.email ?? '',
      direccion: item.direccion ?? '',
      departamento: item.departamento ?? '',
      provincia: item.provincia ?? '',
      distrito: item.distrito ?? '',
      estado: item.estado ?? '',
      condicion: item.condicion ?? '',
      tipoContribuyente: item.tipoContribuyente ?? '',
    });
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.form.reset();
    this.editingId.set(null);
  }

  buscarEnSunat(): void {
    const ruc = this.form.get('ruc')?.value?.trim();
    if (!ruc || ruc.length !== 11) { this.notification.error('Ingrese un RUC de 11 dígitos'); return; }

    this.rucLoading.set(true);
    this.clienteService.searchByRuc(ruc).subscribe({
      next: res => {
        if (res.status && res.item) {
          const d = res.item as any;
          this.form.patchValue({
            razonSocial: d.razonSocial ?? d.nombre ?? '',
            nombreComercial: d.nombreComercial ?? '',
            direccion: d.direccion ?? '',
            departamento: d.departamento ?? '',
            provincia: d.provincia ?? '',
            distrito: d.distrito ?? '',
            estado: d.estado ?? '',
            condicion: d.condicion ?? '',
            tipoContribuyente: d.tipoContribuyente ?? '',
            telefono: Array.isArray(d.telefonos) ? d.telefonos.join(', ') : (d.telefono ?? ''),
          });
          this.notification.success('Datos cargados desde SUNAT');
        } else {
          this.notification.error('RUC no encontrado');
        }
        this.rucLoading.set(false);
      },
      error: () => { this.notification.error('Error al consultar el RUC'); this.rucLoading.set(false); }
    });
  }

  saveCliente(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;

    const dto: ClienteCreateDto = {
      ruc: raw.ruc!,
      razonSocial: raw.razonSocial!,
      nombreComercial: raw.nombreComercial || undefined,
      telefono: raw.telefono || undefined,
      email: raw.email || undefined,
      direccion: raw.direccion || undefined,
      departamento: raw.departamento || undefined,
      provincia: raw.provincia || undefined,
      distrito: raw.distrito || undefined,
      estado: raw.estado || undefined,
      condicion: raw.condicion || undefined,
      tipoContribuyente: raw.tipoContribuyente || undefined,
    };

    const editingId = this.editingId();
    const request = editingId
      ? this.clienteService.update(editingId, dto)
      : this.clienteService.create(dto);

    request.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(editingId ? 'Cliente actualizado' : 'Cliente creado');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => this.saving.set(false)
    });
  }

  confirmDelete(item: Cliente): void {
    this.deletingId.set(item.id);
    this.deletingNombre.set(item.razonSocial);
    this.showDeleteConfirm.set(true);
  }

  cancelDelete(): void {
    this.showDeleteConfirm.set(false);
    this.deletingId.set(null);
    this.deletingNombre.set(null);
  }

  executeDelete(): void {
    const id = this.deletingId();
    if (!id) return;
    this.clienteService.delete(id).subscribe(res => {
      if (res.status) { this.notification.success('Cliente eliminado'); this.load(); }
      this.cancelDelete();
    });
  }

  fieldInvalid(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
