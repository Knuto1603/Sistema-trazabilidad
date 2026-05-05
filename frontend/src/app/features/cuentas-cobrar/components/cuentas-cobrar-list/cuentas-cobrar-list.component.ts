import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';
import { CuentasCobrarService, CuentasCobrarParams } from '../../cuentas-cobrar.service';
import { CuentasCobrarExportService } from '../../cuentas-cobrar-export.service';
import { CuentaCobrar, EstadoCuenta, Operacion } from '@core/models/core.model';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { NotificationService } from '@core/services/notification.service';
import { PagoModalComponent } from '../pago-modal/pago-modal.component';
import { FacturaDetailModalComponent } from '../factura-detail-modal/factura-detail-modal.component';

const SEDES = ['SULLANA', 'TAMBOGRANDE', 'GENERAL'] as const;

@Component({
  selector: 'app-cuentas-cobrar-list',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeaderComponent, PaginationComponent, PagoModalComponent, FacturaDetailModalComponent],
  template: `
    <app-page-header title="Cuentas por Cobrar">
      <button
        (click)="exportarExcel()"
        [disabled]="items().length === 0 || exportando()"
        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        {{ exportando() ? 'Exportando...' : 'Exportar Excel' }}
      </button>
    </app-page-header>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-col sm:flex-row flex-wrap gap-3">
      <!-- Sede -->
      <select [(ngModel)]="filterSede" (ngModelChange)="onSedeChange()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Todas las sedes</option>
        @for (s of sedes; track s) {
          <option [value]="s">{{ s }}</option>
        }
      </select>

      <!-- Operación (dependiente de sede) -->
      <select [(ngModel)]="filterOperacionId" (ngModelChange)="loadData()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" [disabled]="!filterSede || operaciones().length === 0">
        <option value="">Todas las operaciones</option>
        @for (op of operaciones(); track op.id) {
          <option [value]="op.id">{{ op.nombre }}</option>
        }
      </select>

      <!-- Estado -->
      <select [(ngModel)]="filterEstado" (ngModelChange)="loadData()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Todos los estados</option>
        <option value="PENDIENTE">Pendiente</option>
        <option value="VENCIDA">Vencida</option>
        <option value="PAGADO">Pagado</option>
      </select>

      <!-- Búsqueda -->
      <input
        type="text"
        [(ngModel)]="filterSearch"
        (ngModelChange)="onSearchChange()"
        placeholder="Buscar factura, cliente, contenedor..."
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-72"
      />
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
      @if (loading()) {
        <div class="flex justify-center items-center py-16">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        </div>
      } @else if (items().length === 0) {
        <div class="text-center py-16 text-gray-400">
          <p class="text-lg font-medium">No hay facturas por cobrar</p>
          <p class="text-sm mt-1">Ajusta los filtros para ver resultados</p>
        </div>
      } @else {
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="w-8 px-2 py-3"></th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Cliente</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Factura</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Sede / Op.</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Emisión</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Vencimiento</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Total</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Pagado</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Pendiente</th>
              <th class="text-center px-4 py-3 font-medium text-gray-600">Estado</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            @for (item of items(); track item.id) {
              <!-- Fila principal -->
              <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100"
                  [class.border-b-0]="isExpanded(item.id)">
                <!-- Chevron expand -->
                <td class="w-8 px-2 py-3 text-center">
                  @if (activePagos(item).length > 0) {
                    <button (click)="toggleExpand(item.id)"
                      class="text-gray-400 hover:text-gray-600 transition-colors transition-transform"
                      [title]="isExpanded(item.id) ? 'Ocultar pagos' : 'Ver pagos'">
                      <svg class="w-4 h-4 transition-transform duration-200"
                           [class.rotate-180]="isExpanded(item.id)"
                           fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                      </svg>
                    </button>
                  }
                </td>
                <td class="px-4 py-3">
                  <div class="font-medium text-gray-900 text-xs">{{ item.clienteRazonSocial }}</div>
                  <div class="text-gray-400 text-xs">{{ item.clienteRuc }}</div>
                  @if (item.clienteFacturaRazonSocial) {
                    <div class="text-amber-600 text-xs font-medium mt-0.5 flex items-center gap-1"
                      [title]="'Facturado a: ' + item.clienteFacturaRazonSocial + ' · RUC ' + item.clienteFacturaRuc">
                      <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                      {{ item.clienteFacturaRazonSocial }}
                    </div>
                  }
                </td>
                <td class="px-4 py-3">
                  <div class="font-mono text-gray-900 text-xs">{{ item.numeroDocumento }}</div>
                  @if (item.contenedor) {
                    <div class="text-gray-400 text-xs">{{ item.contenedor }}</div>
                  }
                </td>
                <td class="px-4 py-3 text-xs text-gray-600">
                  <div>{{ item.sede }}</div>
                  @if (item.operacionNombre) {
                    <div class="text-gray-400">{{ item.operacionNombre }}</div>
                  }
                </td>
                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ item.fechaEmision }}</td>
                <td class="px-4 py-3 text-xs whitespace-nowrap" [class]="vencimientoClass(item)">
                  {{ item.fechaVencimiento ?? '—' }}
                </td>
                <td class="px-4 py-3 text-right text-xs font-mono text-gray-900 whitespace-nowrap">
                  {{ item.moneda }} {{ item.total | number:'1.2-2' }}
                </td>
                <td class="px-4 py-3 text-right text-xs font-mono text-green-600 whitespace-nowrap">
                  {{ item.moneda }} {{ item.montoPagado | number:'1.2-2' }}
                </td>
                <td class="px-4 py-3 text-right text-xs font-mono whitespace-nowrap"
                    [class]="item.montoPendiente > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'">
                  {{ item.moneda }} {{ item.montoPendiente | number:'1.2-2' }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span [class]="estadoBadgeClass(item.estado)">{{ item.estado }}</span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      (click)="abrirDetalle(item)"
                      class="text-xs px-3 py-1.5 border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg transition-colors whitespace-nowrap"
                    >
                      Ver
                    </button>
                    <button
                      (click)="abrirPagoModal(item)"
                      class="text-xs px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg transition-colors whitespace-nowrap"
                      [disabled]="item.estado === 'PAGADO'"
                    >
                      + Voucher
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Sub-fila de pagos -->
              @if (isExpanded(item.id)) {
                <tr class="border-b border-gray-100">
                  <td colspan="11" class="px-0 pb-0">
                    <div class="bg-blue-50 border-t border-blue-100 px-6 py-3">
                      <div class="text-xs font-semibold text-blue-700 mb-2 uppercase tracking-wider">Pagos registrados</div>
                      <div class="grid grid-cols-1 gap-2">
                        @for (pago of activePagos(item); track pago.id) {
                          <div class="flex flex-wrap items-center gap-x-6 gap-y-1 bg-white rounded-lg border border-blue-100 px-4 py-2.5 text-xs">
                            <!-- Voucher N° -->
                            <div class="flex items-center gap-1.5">
                              <span class="text-gray-400">Voucher</span>
                              <span class="font-semibold text-gray-900">{{ pago.voucherNumero }}</span>
                            </div>
                            <!-- N° Operación -->
                            @if (pago.voucherNumeroOperacion) {
                              <div class="flex items-center gap-1.5">
                                <span class="text-gray-400">Op.</span>
                                <span class="text-gray-700">{{ pago.voucherNumeroOperacion }}</span>
                              </div>
                            }
                            <!-- Fecha voucher -->
                            @if (pago.voucherFecha) {
                              <div class="flex items-center gap-1.5">
                                <span class="text-gray-400">Fecha dep.</span>
                                <span class="text-gray-700">{{ pago.voucherFecha }}</span>
                              </div>
                            }
                            <!-- Monto total del voucher -->
                            <div class="flex items-center gap-1.5">
                              <span class="text-gray-400">Total voucher</span>
                              <span class="text-gray-700">{{ item.moneda }} {{ pago.voucherMontoTotal | number:'1.2-2' }}</span>
                            </div>
                            <!-- Monto aplicado a esta factura -->
                            <div class="flex items-center gap-1.5">
                              <span class="text-gray-400">Aplicado aquí</span>
                              <span class="font-semibold text-green-700">{{ item.moneda }} {{ pago.montoAplicado | number:'1.2-2' }}</span>
                            </div>
                            <!-- Saldo restante del voucher -->
                            <div class="flex items-center gap-1.5">
                              <span class="text-gray-400">Saldo voucher</span>
                              <span [class]="(pago.voucherMontoRestante ?? 0) > 0 ? 'text-blue-600 font-medium' : 'text-gray-400'">
                                {{ item.moneda }} {{ pago.voucherMontoRestante | number:'1.2-2' }}
                              </span>
                            </div>
                            <!-- Fecha del pago -->
                            <div class="flex items-center gap-1.5 ml-auto">
                              <span class="text-gray-400">{{ pago.createdAt | date:'dd/MM/yyyy HH:mm' }}</span>
                            </div>
                            <!-- Botón desligar -->
                            <button (click)="iniciarDesligar(pago.id)" title="Desligar voucher de esta factura"
                              class="ml-2 text-red-400 hover:text-red-600 transition-colors shrink-0">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                              </svg>
                            </button>
                          </div>

                          <!-- Panel confirmación desligar -->
                          @if (pagoDesligando() === pago.id) {
                            <div class="mt-1 bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex flex-col sm:flex-row items-start sm:items-center gap-2">
                              <input type="text" [(ngModel)]="justificanteDesligar"
                                placeholder="Motivo (requerido)"
                                class="flex-1 text-xs border border-red-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-red-400"/>
                              <div class="flex gap-2 shrink-0">
                                <button (click)="confirmarDesligar()"
                                  [disabled]="!justificanteDesligar.trim() || guardandoDesligar()"
                                  class="px-3 py-1.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs rounded-lg transition-colors">
                                  @if (guardandoDesligar()) { Desligando... } @else { Confirmar }
                                </button>
                                <button (click)="cancelarDesligar()"
                                  class="px-3 py-1.5 border border-gray-300 text-gray-600 text-xs rounded-lg hover:bg-gray-100 transition-colors">
                                  Cancelar
                                </button>
                              </div>
                            </div>
                          }
                        }
                      </div>
                    </div>
                  </td>
                </tr>
              }
            }
          </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-200">
          <app-pagination
            [pagination]="pagination()"
            (pageChange)="onPageChange($event)"
          />
        </div>
      }
    </div>

    <!-- Modal detalle factura -->
    @if (detailItem()) {
      <app-factura-detail-modal
        [cuenta]="detailItem()!"
        (closed)="detailItem.set(null)"
        (pagoAnulado)="onPagoRegistrado()"
      />
    }

    <!-- Modal de pagos -->
    @if (selectedItem()) {
      <app-pago-modal
        [cuenta]="selectedItem()!"
        (closed)="cerrarPagoModal()"
        (pagoRegistrado)="onPagoRegistrado()"
      />
    }
  `
})
export class CuentasCobrarListComponent implements OnInit {
  private service = inject(CuentasCobrarService);
  private exportService = inject(CuentasCobrarExportService);
  private http = inject(HttpClient);
  private notif = inject(NotificationService);

  readonly sedes = SEDES;

  // Filtros
  filterSede = '';
  filterOperacionId = '';
  filterEstado = '';
  filterSearch = '';
  currentPage = 0;
  private searchTimeout: ReturnType<typeof setTimeout> | null = null;

  // Estado
  loading = signal(false);
  exportando = signal(false);
  items = signal<CuentaCobrar[]>([]);
  pagination = signal<any>({ page: 0, itemsPerPage: 20, count: 0, totalItems: 0 });
  operaciones = signal<Operacion[]>([]);
  selectedItem = signal<CuentaCobrar | null>(null);
  detailItem = signal<CuentaCobrar | null>(null);
  expandedIds = signal<Set<string>>(new Set());

  // Desligar voucher de factura (anular pago)
  pagoDesligando = signal<string | null>(null);
  justificanteDesligar = '';
  guardandoDesligar = signal(false);

  ngOnInit(): void {
    this.loadData();
  }

  onSedeChange(): void {
    this.filterOperacionId = '';
    this.operaciones.set([]);
    if (this.filterSede) {
      this.loadOperaciones();
    }
    this.loadData();
  }

  private loadOperaciones(): void {
    this.http.get<{ status: boolean; items: Operacion[] }>(
      `${environment.coreUrl}/operaciones`,
      { params: { sede: this.filterSede } }
    ).subscribe({ next: res => this.operaciones.set(res.items ?? []) });
  }

  loadData(): void {
    this.loading.set(true);
    const params: CuentasCobrarParams = {
      page: this.currentPage,
      itemsPerPage: 20,
    };
    if (this.filterSede) params['sede'] = this.filterSede;
    if (this.filterOperacionId) params['operacionId'] = this.filterOperacionId;
    if (this.filterEstado) params['estado'] = this.filterEstado;
    if (this.filterSearch) params['search'] = this.filterSearch;

    this.service.getAll(params).subscribe({
      next: res => {
        this.items.set(res.items ?? []);
        this.pagination.set(res.pagination ?? {});
        this.expandedIds.set(new Set());
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSearchChange(): void {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
      this.currentPage = 0;
      this.loadData();
    }, 400);
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadData();
  }

  toggleExpand(id: string): void {
    const next = new Set(this.expandedIds());
    next.has(id) ? next.delete(id) : next.add(id);
    this.expandedIds.set(next);
  }

  isExpanded(id: string): boolean {
    return this.expandedIds().has(id);
  }

  activePagos(item: CuentaCobrar) {
    return item.pagos?.filter(p => p.isActive) ?? [];
  }

  abrirDetalle(item: CuentaCobrar): void {
    this.detailItem.set(item);
  }

  abrirPagoModal(item: CuentaCobrar): void {
    this.selectedItem.set(item);
  }

  cerrarPagoModal(): void {
    this.selectedItem.set(null);
  }

  onPagoRegistrado(): void {
    const currentId = this.selectedItem()?.id;
    const params: CuentasCobrarParams = { page: this.currentPage, itemsPerPage: 20 };
    if (this.filterSede) params['sede'] = this.filterSede;
    if (this.filterOperacionId) params['operacionId'] = this.filterOperacionId;
    if (this.filterEstado) params['estado'] = this.filterEstado;
    if (this.filterSearch) params['search'] = this.filterSearch;

    this.service.getAll(params).subscribe({
      next: res => {
        this.items.set(res.items ?? []);
        this.pagination.set(res.pagination ?? {});
        if (currentId) {
          const updated = (res.items ?? []).find(i => i.id === currentId);
          if (updated) this.selectedItem.set(updated);
        }
      }
    });
  }

  exportarExcel(): void {
    if (this.exportando()) return;
    this.exportando.set(true);

    const params: CuentasCobrarParams = { page: 0, itemsPerPage: 9999 };
    if (this.filterSede) params['sede'] = this.filterSede;
    if (this.filterOperacionId) params['operacionId'] = this.filterOperacionId;
    if (this.filterEstado) params['estado'] = this.filterEstado;
    if (this.filterSearch) params['search'] = this.filterSearch;

    this.service.getAll(params).subscribe({
      next: res => {
        this.exportService.exportToExcel(res.items ?? []);
        this.exportando.set(false);
      },
      error: () => {
        this.notif.error('Error al exportar');
        this.exportando.set(false);
      },
    });
  }

  iniciarDesligar(pagoId: string): void {
    this.pagoDesligando.set(pagoId);
    this.justificanteDesligar = '';
  }

  cancelarDesligar(): void {
    this.pagoDesligando.set(null);
    this.justificanteDesligar = '';
  }

  confirmarDesligar(): void {
    const pagoId = this.pagoDesligando();
    if (!pagoId || !this.justificanteDesligar.trim()) return;
    this.guardandoDesligar.set(true);
    this.service.deletePago(pagoId, { justificante: this.justificanteDesligar.trim() }).subscribe({
      next: () => {
        this.notif.success('Voucher desligado de la factura');
        this.pagoDesligando.set(null);
        this.justificanteDesligar = '';
        this.guardandoDesligar.set(false);
        this.onPagoRegistrado();
      },
      error: (err) => {
        this.notif.error(err?.error?.message ?? 'Error al desligar el voucher');
        this.guardandoDesligar.set(false);
      }
    });
  }

  estadoBadgeClass(estado: EstadoCuenta): string {
    const base = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ';
    switch (estado) {
      case 'PAGADO':   return base + 'bg-green-100 text-green-700';
      case 'VENCIDA':  return base + 'bg-red-100 text-red-700';
      default:         return base + 'bg-yellow-100 text-yellow-700';
    }
  }

  vencimientoClass(item: CuentaCobrar): string {
    if (!item.fechaVencimiento || item.estado === 'PAGADO') return 'text-gray-600';
    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
    const venc = new Date(item.fechaVencimiento + 'T00:00:00');
    if (venc < hoy) return 'text-red-600 font-semibold';
    const diff = (venc.getTime() - hoy.getTime()) / 86400000;
    if (diff <= 7) return 'text-orange-500 font-semibold';
    return 'text-gray-600';
  }
}
