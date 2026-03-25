import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DespachoService, DespachoCreateDto } from '../../../despacho.service';
import { ClienteService } from '../../../cliente.service';
import { FrutaService } from '@features/settings/services/fruta.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Despacho, Cliente, Fruit } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'app-despachos-list',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './despachos-list.component.html'
})
export class DespachosListComponent implements OnInit {
  private despachoService = inject(DespachoService);
  private clienteService = inject(ClienteService);
  private frutaService = inject(FrutaService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private router = inject(Router);
  private fb = inject(FormBuilder);

  items = signal<Despacho[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  saving = signal(false);
  search = signal('');
  currentPage = signal(0);
  sefiltro = signal<string>('');

  clientes = signal<Cliente[]>([]);
  frutas = signal<{ id: string; nombre: string }[]>([]);

  showModal = signal(false);
  showDeleteConfirm = signal(false);
  deletingId = signal<string | null>(null);
  deletingLabel = signal<string | null>(null);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  form = this.fb.group({
    clienteId: ['', Validators.required],
    frutaId: ['', Validators.required],
    sede: ['', Validators.required],
    contenedor: [''],
    observaciones: [''],
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  readonly SEDES = ['SULLANA', 'TAMBOGRANDE', 'GENERAL'];

  ngOnInit(): void {
    this.loadClientes();
    this.loadFrutas();
    this.load();
  }

  loadClientes(): void {
    this.clienteService.getAll({ page: 0, itemsPerPage: 200 }).subscribe(res => {
      if (res.status) this.clientes.set(res.items);
    });
  }

  loadFrutas(): void {
    this.frutaService.getAll({ page: 0, itemsPerPage: 100 }).subscribe(res => {
      if (res.status) this.frutas.set(res.items.map(f => ({ id: f.id, nombre: f.nombre })));
    });
  }

  load(): void {
    this.loading.set(true);
    const params: any = { page: this.currentPage(), itemsPerPage: 10, search: this.search() };
    if (this.sefiltro()) params['sede'] = this.sefiltro();

    this.despachoService.getAll(params).subscribe({
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

  onSedeFilter(sede: string): void {
    this.sefiltro.set(sede);
    this.currentPage.set(0);
    this.load();
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.load(); }

  openCreateModal(): void {
    this.form.reset();
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.form.reset();
  }

  saveDespacho(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;

    const dto: DespachoCreateDto = {
      clienteId: raw.clienteId!,
      frutaId: raw.frutaId!,
      sede: raw.sede!,
      contenedor: raw.contenedor || undefined,
      observaciones: raw.observaciones || undefined,
    };

    this.despachoService.create(dto).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Despacho creado exitosamente');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => this.saving.set(false)
    });
  }

  verDetalle(item: Despacho): void {
    this.router.navigate(['/app/facturacion/despachos', item.id]);
  }

  confirmDelete(item: Despacho): void {
    this.deletingId.set(item.id);
    this.deletingLabel.set(`Despacho #${item.numeroPlanta ?? item.numeroCliente}`);
    this.showDeleteConfirm.set(true);
  }

  cancelDelete(): void {
    this.showDeleteConfirm.set(false);
    this.deletingId.set(null);
    this.deletingLabel.set(null);
  }

  executeDelete(): void {
    const id = this.deletingId();
    if (!id) return;
    this.despachoService.delete(id).subscribe(res => {
      if (res.status) { this.notification.success('Despacho eliminado'); this.load(); }
      this.cancelDelete();
    });
  }

  fieldInvalid(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
