import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { FacturaService } from '../../factura.service';
import { NotificationService } from '@core/services/notification.service';
import { Factura } from '@core/models/core.model';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

type SortField = 'numeroDocumento' | 'fechaEmision' | 'clienteRazonSocial' | 'importe' | 'total';

@Component({
  selector: 'app-reporte-facturacion',
  standalone: true,
  imports: [FormsModule, DecimalPipe, PageHeaderComponent],
  templateUrl: './reporte-facturacion.component.html',
})
export class ReporteFacturacionComponent implements OnInit {
  private facturaService = inject(FacturaService);
  private notification = inject(NotificationService);

  facturas = signal<Factura[]>([]);
  loading = signal(false);
  exporting = signal(false);

  searchText = signal('');
  filterServicio = signal('');
  filterAnuladas = signal<'todas' | 'activas' | 'anuladas'>('activas');

  sortField = signal<SortField>('numeroDocumento');
  sortDir = signal<'asc' | 'desc'>('asc');

  readonly SERVICIOS = ['MAQUILA', 'SOBRECOSTO', 'VENTA_CAJAS'];

  ngOnInit(): void {
    this.loadAll();
  }

  loadAll(): void {
    this.loading.set(true);
    this.facturaService.getAll({ page: 0, itemsPerPage: 1000 }).subscribe({
      next: res => {
        if (res.status) this.facturas.set(res.items ?? []);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  facturasFiltradas = computed(() => {
    let list = [...this.facturas()];
    const search = this.searchText().toLowerCase().trim();
    const servicio = this.filterServicio();
    const anuladas = this.filterAnuladas();

    if (search) {
      list = list.filter(f =>
        f.numeroDocumento?.toLowerCase().includes(search) ||
        f.clienteRazonSocial?.toLowerCase().includes(search) ||
        f.numeroGuia?.toLowerCase().includes(search) ||
        f.contenedor?.toLowerCase().includes(search) ||
        f.destino?.toLowerCase().includes(search)
      );
    }

    if (servicio) {
      list = list.filter(f => f.tipoServicio === servicio);
    }

    if (anuladas === 'activas') {
      list = list.filter(f => !f.isAnulada);
    } else if (anuladas === 'anuladas') {
      list = list.filter(f => f.isAnulada);
    }

    // Ordenamiento
    const field = this.sortField();
    const dir = this.sortDir() === 'asc' ? 1 : -1;

    list.sort((a, b) => {
      const va = (a as any)[field] ?? '';
      const vb = (b as any)[field] ?? '';
      if (va < vb) return -dir;
      if (va > vb) return dir;
      return 0;
    });

    return list;
  });

  totalImporte = computed(() =>
    this.facturasFiltradas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.importe ?? 0), 0)
  );

  totalIgv = computed(() =>
    this.facturasFiltradas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.igv ?? 0), 0)
  );

  totalGeneral = computed(() =>
    this.facturasFiltradas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.total ?? 0), 0)
  );

  sortBy(field: SortField): void {
    if (this.sortField() === field) {
      this.sortDir.update(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      this.sortField.set(field);
      this.sortDir.set('asc');
    }
  }

  getSortIcon(field: SortField): string {
    if (this.sortField() !== field) return '↕';
    return this.sortDir() === 'asc' ? '↑' : '↓';
  }

  exportarExcel(): void {
    const search = this.searchText() || undefined;
    this.facturaService.exportReporte(search);
  }
}
