import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { forkJoin } from 'rxjs';
import { environment } from '@env/environment';
import { CuentasCobrarService, CuentasCobrarParams } from '../../cuentas-cobrar.service';
import { CuentaCobrar, EstadoCuenta, Operacion } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { NotificationService } from '@core/services/notification.service';
import { PagoModalComponent } from '../pago-modal/pago-modal.component';

const SEDES = ['SULLANA', 'TAMBOGRANDE', 'GENERAL'] as const;

@Component({
  selector: 'app-cuentas-cobrar-list',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeaderComponent, PaginationComponent, PagoModalComponent],
  template: `
    <app-page-header title="Cuentas por Cobrar" />

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
          <tbody class="divide-y divide-gray-100">
            @for (item of items(); track item.id) {
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-medium text-gray-900 text-xs">{{ item.clienteRazonSocial }}</div>
                  <div class="text-gray-400 text-xs">{{ item.clienteRuc }}</div>
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
                  <button
                    (click)="abrirPagoModal(item)"
                    class="text-xs px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors whitespace-nowrap"
                    [disabled]="item.estado === 'PAGADO'"
                    [class.opacity-40]="item.estado === 'PAGADO'"
                    [class.cursor-not-allowed]="item.estado === 'PAGADO'"
                  >
                    Ver / Pagar
                  </button>
                </td>
              </tr>
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
  items = signal<CuentaCobrar[]>([]);
  pagination = signal<any>({ page: 0, itemsPerPage: 20, count: 0, totalItems: 0 });
  operaciones = signal<Operacion[]>([]);
  selectedItem = signal<CuentaCobrar | null>(null);

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

  abrirPagoModal(item: CuentaCobrar): void {
    this.selectedItem.set(item);
  }

  cerrarPagoModal(): void {
    this.selectedItem.set(null);
  }

  onPagoRegistrado(): void {
    this.selectedItem.set(null);
    this.loadData();
    this.notif.success('Pago registrado correctamente');
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
