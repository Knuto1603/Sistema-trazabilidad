import {
  Component, inject, signal, OnInit, HostListener
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { Cliente, Voucher, PagoEnVoucher } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { VoucherService, VoucherFormDto } from '../../voucher.service';
import { ClienteService } from '@features/facturacion/cliente.service';
import { NotificationService } from '@core/services/notification.service';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';

@Component({
  selector: 'app-vouchers',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeaderComponent, PaginationComponent],
  template: `
    <app-page-header title="Vouchers / Depósitos">
      @if (clienteSeleccionado()) {
        <button (click)="abrirNuevo()"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuevo Voucher
        </button>
      }
    </app-page-header>

    <div class="p-6 space-y-5">

      <!-- Selector de cliente -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
        <div class="relative max-w-md">
          <input
            type="text"
            [(ngModel)]="clienteBusqueda"
            (ngModelChange)="onClienteBusquedaChange($event)"
            (focus)="showClienteDropdown.set(true)"
            placeholder="Buscar por RUC o razón social..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          @if (showClienteDropdown() && clienteSugerencias().length > 0) {
            <div class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
              @for (c of clienteSugerencias(); track c.id) {
                <button type="button" (click)="seleccionarCliente(c)"
                  class="w-full text-left px-3 py-2.5 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-0">
                  <div class="font-medium text-gray-900">{{ c.razonSocial }}</div>
                  <div class="text-xs text-gray-400">{{ c.ruc }}</div>
                </button>
              }
            </div>
          }
        </div>
        @if (clienteSeleccionado()) {
          <div class="mt-2 flex items-center gap-3">
            <span class="text-sm text-green-700 font-medium">{{ clienteSeleccionado()!.razonSocial }}</span>
            <button type="button" (click)="limpiarCliente()" class="text-xs text-gray-400 hover:text-gray-600">
              Cambiar cliente
            </button>
          </div>
        }
      </div>

      <!-- Filtro de búsqueda -->
      @if (clienteSeleccionado()) {
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <input
            type="text"
            [(ngModel)]="qInput"
            (ngModelChange)="onQChange($event)"
            placeholder="Buscar por número o N° operación..."
            class="w-full sm:w-72 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <span class="text-sm text-gray-400">{{ pagination().totalItems }} voucher(s)</span>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          @if (cargando()) {
            <div class="py-12 text-center text-gray-400 text-sm">Cargando...</div>
          } @else if (items().length === 0) {
            <div class="py-12 text-center text-gray-400 text-sm">No hay vouchers registrados para este cliente.</div>
          } @else {
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">N° Voucher</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">N° Operación</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto Total</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Usado</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Disponible</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (v of items(); track v.id) {
                  <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ v.numero }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ v.numeroOperacion ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ v.fecha | date:'dd/MM/yyyy' }}</td>
                    <td class="px-4 py-3 text-right text-gray-900">{{ v.montoTotal | number:'1.2-2' }}</td>
                    <td class="px-4 py-3 text-right" [class]="v.montoUsado > 0 ? 'text-amber-600' : 'text-gray-400'">
                      {{ v.montoUsado | number:'1.2-2' }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium" [class]="v.montoRestante > 0.001 ? 'text-green-600' : 'text-gray-400'">
                      {{ v.montoRestante | number:'1.2-2' }}
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2 justify-end">
                        <button (click)="abrirEditar(v)" title="Editar"
                          class="text-blue-500 hover:text-blue-700 transition-colors">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                          </svg>
                        </button>
                        <button (click)="iniciarEliminar(v)" title="Eliminar"
                          class="text-red-400 hover:text-red-600 transition-colors">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                }
              </tbody>
            </table>
          }
        </div>

        <app-pagination
          [pagination]="pagination()"
          (pageChange)="onPageChange($event)"
        />
      }
    </div>

    <!-- Modal Crear / Editar -->
    @if (mostrarModal()) {
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" (click)="onBackdropClick($event)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" (click)="$event.stopPropagation()">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
              {{ editando() ? 'Editar Voucher' : 'Nuevo Voucher' }}
            </h2>
            <button (click)="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="px-6 py-5 space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">N° Voucher / Depósito *</label>
              <input type="text" [(ngModel)]="form.numero" placeholder="Ej: DEP-001234"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">N° Operación bancaria</label>
              <input type="text" [(ngModel)]="form.numeroOperacion" placeholder="Número de operación (opcional)"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monto Total *</label>
                <input type="number" [(ngModel)]="form.montoTotal" step="0.01" min="0.01" placeholder="0.00"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                <input type="date" [(ngModel)]="form.fecha"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
              </div>
            </div>
          </div>

          <div class="px-6 pb-5 flex gap-3">
            <button (click)="guardar()" [disabled]="!formValido() || guardando()"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
              @if (guardando()) { Guardando... } @else { {{ editando() ? 'Actualizar' : 'Crear voucher' }} }
            </button>
            <button (click)="cerrarModal()" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    }

    <!-- Modal Eliminar -->
    @if (voucherAEliminar()) {
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" (click)="cancelarEliminar()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" (click)="$event.stopPropagation()">
          <div class="px-6 py-5">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Eliminar Voucher</h3>
            <p class="text-sm text-gray-500 mb-4">
              <span class="font-medium">{{ voucherAEliminar()!.numero }}</span>
              — Monto total: {{ voucherAEliminar()!.montoTotal | number:'1.2-2' }}
            </p>

            @if (voucherAEliminar()!.montoUsado > 0) {
              <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-4 text-xs space-y-1">
                <p class="text-orange-700 font-semibold">Este voucher está vinculado a las siguientes facturas:</p>
                @if (cargandoPagos()) {
                  <p class="text-gray-400">Cargando...</p>
                } @else {
                  @for (pago of pagosAEliminar(); track pago.id) {
                    <div class="flex justify-between text-gray-700">
                      <span class="font-medium">{{ pago.facturaNumero ?? '—' }}</span>
                      <span class="text-gray-400 truncate max-w-[130px]" [title]="pago.facturaRazonSocial ?? ''">
                        {{ pago.facturaRazonSocial ?? '' }}
                      </span>
                      <span class="font-semibold text-orange-700">{{ pago.montoAplicado | number:'1.2-2' }}</span>
                    </div>
                  }
                }
                <p class="text-orange-600 mt-1">Los pagos vinculados serán anulados automáticamente.</p>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Motivo de eliminación *</label>
                <input type="text" [(ngModel)]="justificanteElim"
                  placeholder="Ej: Voucher registrado por error"
                  class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"/>
              </div>
            } @else {
              <p class="text-sm text-gray-600 mb-4">¿Confirmas la eliminación? Esta acción no se puede deshacer.</p>
            }

            <div class="flex gap-3">
              <button (click)="confirmarEliminar()"
                [disabled]="eliminando() || (voucherAEliminar()!.montoUsado > 0 && !justificanteElim.trim())"
                class="px-5 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors">
                @if (eliminando()) { Eliminando... }
                @else if (voucherAEliminar()!.montoUsado > 0) { Anular pagos y eliminar }
                @else { Eliminar }
              </button>
              <button (click)="cancelarEliminar()"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class VouchersComponent implements OnInit {
  private voucherService = inject(VoucherService);
  private clienteService = inject(ClienteService);
  private notif = inject(NotificationService);

  // Estado cliente
  clienteBusqueda = '';
  clienteSeleccionado = signal<Cliente | null>(null);
  clienteSugerencias = signal<Cliente[]>([]);
  showClienteDropdown = signal(false);
  private clienteSearch$ = new Subject<string>();

  // Lista
  items = signal<Voucher[]>([]);
  page = signal(0);
  pagination = signal<Pagination>({ totalItems: 0, page: 0, itemsPerPage: 20, count: 0, startIndex: 0, endIndex: 0 });
  cargando = signal(false);
  qInput = '';
  private qSearch$ = new Subject<string>();

  // Modal crear/editar
  mostrarModal = signal(false);
  editando = signal<Voucher | null>(null);
  guardando = signal(false);
  form: { numero: string; numeroOperacion: string; montoTotal: number | null; fecha: string } = {
    numero: '', numeroOperacion: '', montoTotal: null, fecha: new Date().toISOString().split('T')[0]
  };

  // Eliminar
  voucherAEliminar = signal<Voucher | null>(null);
  pagosAEliminar = signal<PagoEnVoucher[]>([]);
  cargandoPagos = signal(false);
  justificanteElim = '';
  eliminando = signal(false);

  formValido(): boolean {
    return !!this.form.numero.trim() && !!this.form.montoTotal && this.form.montoTotal > 0 && !!this.form.fecha;
  }

  ngOnInit(): void {
    this.clienteSearch$.pipe(debounceTime(300), distinctUntilChanged()).subscribe(q => {
      if (q.length >= 2) {
        this.clienteService.getAll({ search: q, itemsPerPage: 10 }).subscribe({
          next: res => {
            this.clienteSugerencias.set(res.items ?? []);
            this.showClienteDropdown.set(true);
          }
        });
      } else {
        this.clienteSugerencias.set([]);
        this.showClienteDropdown.set(false);
      }
    });

    this.qSearch$.pipe(debounceTime(350), distinctUntilChanged()).subscribe(q => {
      this.page.set(0);
      this.cargar();
    });
  }

  onClienteBusquedaChange(v: string): void {
    if (this.clienteSeleccionado()) this.clienteSeleccionado.set(null);
    this.clienteSearch$.next(v.trim());
  }

  seleccionarCliente(c: Cliente): void {
    this.clienteSeleccionado.set(c);
    this.clienteBusqueda = c.razonSocial;
    this.showClienteDropdown.set(false);
    this.clienteSugerencias.set([]);
    this.page.set(0);
    this.cargar();
  }

  limpiarCliente(): void {
    this.clienteSeleccionado.set(null);
    this.clienteBusqueda = '';
    this.items.set([]);
  }

  onQChange(v: string): void {
    this.qSearch$.next(v.trim());
  }

  onPageChange(p: number): void {
    this.page.set(p);
    this.cargar();
  }

  private cargar(): void {
    const cliente = this.clienteSeleccionado();
    if (!cliente) return;
    this.cargando.set(true);
    this.voucherService.list(cliente.id, this.qInput.trim(), this.page()).subscribe({
      next: res => {
        this.items.set(res.items ?? []);
        if (res.pagination) this.pagination.set(res.pagination);
        this.cargando.set(false);
      },
      error: () => this.cargando.set(false)
    });
  }

  abrirNuevo(): void {
    this.editando.set(null);
    this.form = { numero: '', numeroOperacion: '', montoTotal: null, fecha: new Date().toISOString().split('T')[0] };
    this.mostrarModal.set(true);
  }

  abrirEditar(v: Voucher): void {
    this.editando.set(v);
    this.form = { numero: v.numero, numeroOperacion: v.numeroOperacion ?? '', montoTotal: v.montoTotal, fecha: v.fecha };
    this.mostrarModal.set(true);
  }

  cerrarModal(): void {
    this.mostrarModal.set(false);
    this.editando.set(null);
  }

  guardar(): void {
    if (!this.formValido() || this.guardando()) return;
    const cliente = this.clienteSeleccionado();
    if (!cliente) return;
    this.guardando.set(true);

    const dto: VoucherFormDto = {
      clienteId: cliente.id,
      numero: this.form.numero.trim(),
      numeroOperacion: this.form.numeroOperacion.trim() || undefined,
      montoTotal: this.form.montoTotal!,
      fecha: this.form.fecha,
    };

    const edicion = this.editando();
    const req$ = edicion
      ? this.voucherService.update(edicion.id, dto)
      : this.voucherService.create(dto);

    req$.subscribe({
      next: () => {
        this.notif.success(edicion ? 'Voucher actualizado' : 'Voucher creado');
        this.cerrarModal();
        this.guardando.set(false);
        this.cargar();
      },
      error: (err) => {
        this.notif.error(err?.error?.message ?? 'Error al guardar');
        this.guardando.set(false);
      }
    });
  }

  iniciarEliminar(v: Voucher): void {
    this.voucherAEliminar.set(v);
    this.justificanteElim = '';
    this.pagosAEliminar.set([]);
    if (v.montoUsado > 0) {
      this.cargandoPagos.set(true);
      this.voucherService.getById(v.id).subscribe({
        next: res => {
          this.pagosAEliminar.set(res.item?.pagos ?? []);
          this.cargandoPagos.set(false);
        },
        error: () => this.cargandoPagos.set(false),
      });
    }
  }

  cancelarEliminar(): void {
    this.voucherAEliminar.set(null);
  }

  confirmarEliminar(): void {
    const v = this.voucherAEliminar();
    if (!v) return;
    this.eliminando.set(true);

    const req$ = v.montoUsado > 0
      ? this.voucherService.forceDelete(v.id, this.justificanteElim)
      : this.voucherService.delete(v.id);

    req$.subscribe({
      next: () => {
        this.notif.success(`Voucher ${v.numero} eliminado`);
        this.voucherAEliminar.set(null);
        this.eliminando.set(false);
        this.cargar();
      },
      error: (err) => {
        this.notif.error(err?.error?.message ?? 'No se pudo eliminar');
        this.eliminando.set(false);
      }
    });
  }

  onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) this.cerrarModal();
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (!target.closest('[data-cliente-search]')) {
      this.showClienteDropdown.set(false);
    }
  }
}
