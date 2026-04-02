import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DespachoService, DespachoCreateDto } from '../../../despacho.service';
import { ClienteService, ClienteCreateDto } from '../../../cliente.service';
import { FrutaService } from '@features/settings/services/fruta.service';
import { OperacionService } from '@features/settings/services/operacion.service';
import { CampaignService } from '@core/services/campaign.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Despacho, Cliente, Operacion } from '@core/models/core.model';
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
  private operacionService = inject(OperacionService);
  private campaignService = inject(CampaignService);
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
  operaciones = signal<Operacion[]>([]);
  proximoNumeroPlanta = signal<number | null>(null);
  proximoNumeroCliente = signal<number | null>(null);
  loadingNumero = signal(false);
  loadingNumeroCliente = signal(false);

  showModal = signal(false);
  editingId = signal<string | null>(null);
  showDeleteConfirm = signal(false);
  deletingId = signal<string | null>(null);
  deletingLabel = signal<string | null>(null);

  showNewClientePanel = signal(false);
  savingCliente = signal(false);
  rucLoadingCliente = signal(false);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  form = this.fb.group({
    clienteId: ['', Validators.required],
    frutaId: ['', Validators.required],
    sede: ['', Validators.required],
    operacionId: ['' as string],
    contenedor: [''],
    observaciones: [''],
    numeroPlanta: [null as number | null],
    numeroCliente: [null as number | null],
  });

  newClienteRuc = signal('');
  newClienteRazonSocial = signal('');

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
    this.editingId.set(null);
    this.form.reset();
    this.proximoNumeroPlanta.set(null);
    this.proximoNumeroCliente.set(null);
    this.newClienteRuc.set('');
    this.newClienteRazonSocial.set('');
    this.showNewClientePanel.set(false);
    const sede = this.campaignService.activeCampaign()?.sede ?? undefined;
    this.loadOperacionesBySede(sede);
    this.showModal.set(true);
  }

  openEditModal(item: Despacho): void {
    this.editingId.set(item.id);
    this.form.patchValue({
      clienteId: item.clienteId,
      frutaId: item.frutaId,
      sede: item.sede,
      operacionId: item.operacionId ?? '',
      contenedor: item.contenedor ?? '',
      observaciones: item.observaciones ?? '',
      numeroPlanta: item.numeroPlanta ?? null,
      numeroCliente: item.numeroCliente ?? null,
    });
    this.proximoNumeroPlanta.set(null);
    this.proximoNumeroCliente.set(null);
    this.showNewClientePanel.set(false);
    this.loadOperacionesBySede(item.sede);
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.editingId.set(null);
    this.form.reset();
    this.proximoNumeroPlanta.set(null);
    this.proximoNumeroCliente.set(null);
    this.operaciones.set([]);
    this.newClienteRuc.set('');
    this.newClienteRazonSocial.set('');
    this.showNewClientePanel.set(false);
  }

  private loadOperacionesBySede(sede?: string): void {
    this.operacionService.getAll(sede).subscribe(res => {
      if (res.status) this.operaciones.set(res.items);
    });
  }

  onClienteChange(clienteId: string): void {
    if (!clienteId || this.editingId()) return;
    const operacionId = this.form.get('operacionId')?.value || undefined;
    this.cargarProximosNumeros(clienteId, operacionId);
  }

  onOperacionChange(operacionId: string): void {
    if (operacionId) {
      const op = this.operaciones().find(o => o.id === operacionId);
      if (op) this.form.patchValue({ sede: op.sede });
      if (!this.editingId()) {
        const clienteId = this.form.get('clienteId')?.value || undefined;
        this.cargarProximosNumeros(clienteId, operacionId);
      }
    } else {
      this.proximoNumeroPlanta.set(null);
      this.proximoNumeroCliente.set(null);
      this.form.patchValue({ numeroPlanta: null, numeroCliente: null });
    }
  }

  private cargarProximosNumeros(clienteId?: string, operacionId?: string): void {
    this.loadingNumero.set(true);
    this.despachoService.proximoNumero(operacionId).subscribe({
      next: res => {
        if (res.status && res.item) {
          const n = res.item.numeroPlanta;
          this.proximoNumeroPlanta.set(n);
          this.form.patchValue({ numeroPlanta: n });
        }
        this.loadingNumero.set(false);
      },
      error: () => this.loadingNumero.set(false)
    });

    if (clienteId) {
      this.loadingNumeroCliente.set(true);
      this.despachoService.proximoNumeroCliente(clienteId, operacionId).subscribe({
        next: res => {
          if (res.status && res.item) {
            const n = res.item.numeroCliente;
            this.proximoNumeroCliente.set(n);
            this.form.patchValue({ numeroCliente: n });
          }
          this.loadingNumeroCliente.set(false);
        },
        error: () => this.loadingNumeroCliente.set(false)
      });
    }
  }

  toggleNewClientePanel(): void {
    this.showNewClientePanel.update(v => !v);
    if (!this.showNewClientePanel()) {
      this.newClienteRuc.set('');
      this.newClienteRazonSocial.set('');
    }
  }

  buscarRucCliente(): void {
    const ruc = this.newClienteRuc().trim();
    if (ruc.length !== 11) { this.notification.error('Ingrese un RUC de 11 dígitos'); return; }
    this.rucLoadingCliente.set(true);
    this.clienteService.searchByRuc(ruc).subscribe({
      next: res => {
        if (res.status && res.item) {
          const d = res.item as any;
          this.newClienteRazonSocial.set(d.razonSocial ?? d.nombre ?? '');
          this.notification.success('Datos cargados desde SUNAT');
        } else {
          this.notification.error('RUC no encontrado');
        }
        this.rucLoadingCliente.set(false);
      },
      error: () => { this.notification.error('Error al consultar el RUC'); this.rucLoadingCliente.set(false); }
    });
  }

  saveNewCliente(): void {
    const ruc = this.newClienteRuc().trim();
    const razonSocial = this.newClienteRazonSocial().trim();
    if (!/^\d{11}$/.test(ruc)) { this.notification.error('El RUC debe tener 11 dígitos'); return; }
    if (!razonSocial) { this.notification.error('La razón social es obligatoria'); return; }
    this.savingCliente.set(true);
    const dto: ClienteCreateDto = { ruc, razonSocial };
    this.clienteService.create(dto).subscribe({
      next: res => {
        if (res.status && res.item) {
          this.notification.success('Cliente creado correctamente');
          this.loadClientes();
          this.form.patchValue({ clienteId: res.item.id });
          this.newClienteRuc.set('');
          this.newClienteRazonSocial.set('');
          this.showNewClientePanel.set(false);
        }
        this.savingCliente.set(false);
      },
      error: () => this.savingCliente.set(false)
    });
  }

  formatContenedor(event: Event): void {
    const input = event.target as HTMLInputElement;
    const raw = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    const letterPart = raw.substring(0, 4);
    const digitPart = raw.substring(4);
    const digits6 = digitPart.substring(0, 6);
    const digit1 = digitPart.substring(6, 7);
    let formatted = letterPart;
    if (digits6) formatted += ' ' + digits6;
    if (digit1) formatted += '-' + digit1;
    input.value = formatted;
    this.form.get('contenedor')?.setValue(formatted, { emitEvent: false });
  }

  saveDespacho(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;

    const dto: DespachoCreateDto = {
      clienteId: raw.clienteId!,
      frutaId: raw.frutaId!,
      sede: raw.sede!,
      operacionId: (raw as any).operacionId || undefined,
      contenedor: raw.contenedor || undefined,
      observaciones: raw.observaciones || undefined,
      numeroPlanta: raw.numeroPlanta ?? undefined,
      numeroCliente: (raw as any).numeroCliente ?? undefined,
    };

    const editId = this.editingId();
    const req = editId
      ? this.despachoService.update(editId, dto)
      : this.despachoService.create(dto);

    req.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(editId ? 'Despacho actualizado' : 'Despacho creado exitosamente');
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
