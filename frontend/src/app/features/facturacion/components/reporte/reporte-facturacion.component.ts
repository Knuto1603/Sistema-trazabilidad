import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { FacturaService, FacturaCreateDto } from '../../factura.service';
import { TipoCambioService } from '../../tipo-cambio.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Factura } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';

type SortField = 'numeroDocumento' | 'fechaEmision' | 'clienteRazonSocial' | 'importe' | 'total';
type EstadoFilter = 'todas' | 'activas' | 'anuladas';

@Component({
  selector: 'app-reporte-facturacion',
  standalone: true,
  imports: [FormsModule, ReactiveFormsModule, DecimalPipe, PageHeaderComponent, PaginationComponent],
  templateUrl: './reporte-facturacion.component.html',
})
export class ReporteFacturacionComponent implements OnInit {
  private facturaService = inject(FacturaService);
  private tipoCambioService = inject(TipoCambioService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  facturas = signal<Factura[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 25, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  exporting = signal(false);
  savingFactura = signal(false);
  fetchingTc = signal(false);

  showEditModal = signal(false);
  editingFacturaId = signal<string | null>(null);

  searchText = signal('');
  filterServicio = signal('');
  filterAnuladas = signal<EstadoFilter>('activas');
  filterFechaDesde = signal('');
  filterFechaHasta = signal('');

  currentPage = signal(0);
  itemsPerPage = signal(25);
  readonly PAGE_SIZES = [10, 25, 50, 100];

  sortField = signal<SortField>('fechaEmision');
  sortDir = signal<'asc' | 'desc'>('desc');

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  totalImporte = computed(() =>
    this.facturas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.importe ?? 0), 0)
  );
  totalIgv = computed(() =>
    this.facturas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.igv ?? 0), 0)
  );
  totalGeneral = computed(() =>
    this.facturas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.total ?? 0), 0)
  );
  countActivas = computed(() => this.facturas().filter(f => !f.isAnulada).length);

  readonly TIPOS_DOCUMENTO = [
    { value: '01', label: 'Factura (01)' },
    { value: '09', label: 'Guía de Remisión (09)' },
    { value: '07', label: 'Nota de Crédito (07)' },
    { value: '08', label: 'Nota de Débito (08)' },
  ];
  readonly SERVICIOS = ['MAQUILA', 'SOBRECOSTO', 'VENTA_CAJAS'];
  readonly TIPOS_OPERACION = ['MARITIMO', 'TERRESTRE'];
  readonly MONEDAS = ['USD', 'PEN'];
  readonly UNIDADES_MEDIDA = ['TNE', 'KGM', 'KG', 'ZZ', 'UND', 'NIU'];

  facturaForm = this.fb.group({
    tipoDocumento: ['01', Validators.required],
    serie: ['', Validators.required],
    correlativo: ['', Validators.required],
    numeroGuia: [''],
    fechaEmision: ['', Validators.required],
    moneda: ['USD', Validators.required],
    detalle: [''],
    kgCaja: [null as number | null],
    unidadMedida: ['TNE'],
    cajas: [null as number | null],
    cantidad: [null as number | null],
    valorUnitario: [null as number | null],
    importe: [null as number | null],
    igv: [null as number | null],
    total: [null as number | null],
    tipoCambio: [null as number | null],
    tipoServicio: [''],
    tipoOperacion: [''],
    contenedor: [''],
    destino: [''],
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.load();
    this.facturaForm.get('fechaEmision')!.valueChanges.subscribe(fecha => {
      if (fecha && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        this.fetchTipoCambio(fecha, false);
      }
    });
  }

  load(): void {
    this.loading.set(true);
    const params: any = {
      page: this.currentPage(),
      itemsPerPage: this.itemsPerPage(),
      sort: this.sortField(),
      direction: this.sortDir(),
    };
    if (this.searchText()) params['search'] = this.searchText();
    const estado = this.filterAnuladas();
    if (estado === 'activas') params['isAnulada'] = false;
    else if (estado === 'anuladas') params['isAnulada'] = true;
    if (this.filterServicio()) params['tipoServicio'] = this.filterServicio();
    if (this.filterFechaDesde()) params['fechaDesde'] = this.filterFechaDesde();
    if (this.filterFechaHasta()) params['fechaHasta'] = this.filterFechaHasta();

    this.facturaService.getAll(params).subscribe({
      next: res => {
        if (res.status) {
          this.facturas.set(res.items ?? []);
          this.pagination.set(res.pagination);
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSearch(event: Event): void {
    this.searchText.set((event.target as HTMLInputElement).value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => { this.currentPage.set(0); this.load(); }, 400);
  }

  onFilterChange(): void {
    this.currentPage.set(0);
    this.load();
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.load(); }

  onPageSizeChange(size: number): void { this.itemsPerPage.set(size); this.currentPage.set(0); this.load(); }

  sortBy(field: SortField): void {
    if (this.sortField() === field) {
      this.sortDir.update(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      this.sortField.set(field);
      this.sortDir.set('asc');
    }
    this.currentPage.set(0);
    this.load();
  }

  getSortIcon(field: SortField): string {
    if (this.sortField() !== field) return '↕';
    return this.sortDir() === 'asc' ? '↑' : '↓';
  }

  openEditFactura(factura: Factura): void {
    this.editingFacturaId.set(factura.id);
    this.facturaForm.patchValue({
      tipoDocumento: factura.tipoDocumento,
      serie: factura.serie,
      correlativo: factura.correlativo,
      numeroGuia: factura.numeroGuia ?? '',
      fechaEmision: factura.fechaEmision,
      moneda: factura.moneda,
      detalle: factura.detalle ?? '',
      kgCaja: factura.kgCaja ?? null,
      unidadMedida: factura.unidadMedida ?? 'TNE',
      cajas: factura.cajas ?? null,
      cantidad: factura.cantidad !== undefined ? Number(factura.cantidad) : null,
      valorUnitario: factura.valorUnitario !== undefined ? Number(factura.valorUnitario) : null,
      importe: factura.importe !== undefined ? Number(factura.importe) : null,
      igv: factura.igv !== undefined ? Number(factura.igv) : null,
      total: factura.total !== undefined ? Number(factura.total) : null,
      tipoCambio: factura.tipoCambio !== undefined ? Number(factura.tipoCambio) : null,
      tipoServicio: factura.tipoServicio ?? '',
      tipoOperacion: factura.tipoOperacion ?? '',
      contenedor: factura.contenedor ?? '',
      destino: factura.destino ?? '',
    });
    this.showEditModal.set(true);
  }

  closeEditModal(): void {
    this.showEditModal.set(false);
    this.editingFacturaId.set(null);
    this.facturaForm.reset({ tipoDocumento: '01', moneda: 'USD', unidadMedida: 'TNE' });
  }

  saveFactura(): void {
    if (this.facturaForm.invalid) { this.facturaForm.markAllAsTouched(); return; }
    const id = this.editingFacturaId();
    if (!id) return;

    this.savingFactura.set(true);
    const raw = this.facturaForm.value;
    const facturaOriginal = this.facturas().find(f => f.id === id)!;

    const dto: FacturaCreateDto = {
      tipoDocumento: raw.tipoDocumento!,
      serie: raw.serie!,
      correlativo: raw.correlativo!,
      numeroGuia: raw.numeroGuia || undefined,
      fechaEmision: raw.fechaEmision!,
      moneda: raw.moneda ?? 'USD',
      detalle: raw.detalle || undefined,
      kgCaja: raw.kgCaja ?? undefined,
      unidadMedida: raw.unidadMedida || undefined,
      cajas: raw.cajas ?? undefined,
      cantidad: raw.cantidad ?? undefined,
      valorUnitario: raw.valorUnitario ?? undefined,
      importe: raw.importe ?? undefined,
      igv: raw.igv ?? undefined,
      total: raw.total ?? undefined,
      tipoCambio: raw.tipoCambio ?? undefined,
      tipoServicio: raw.tipoServicio || undefined,
      tipoOperacion: raw.tipoOperacion || undefined,
      contenedor: raw.contenedor || undefined,
      destino: raw.destino || undefined,
      despachoId: facturaOriginal.despachoId,
    };

    this.facturaService.update(id, dto).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Factura actualizada');
          this.closeEditModal();
          this.load();
        }
        this.savingFactura.set(false);
      },
      error: err => {
        const detail = err?.error?.detail ?? err?.error?.message ?? JSON.stringify(err?.error ?? err);
        this.notification.error('Error al guardar: ' + detail);
        this.savingFactura.set(false);
      },
    });
  }

  fetchTipoCambio(fecha?: string, mostrarError = true): void {
    const f = fecha ?? this.facturaForm.get('fechaEmision')?.value;
    if (!f) return;
    this.fetchingTc.set(true);
    this.tipoCambioService.getByFecha(f).subscribe({
      next: res => {
        if (res.status && res.item) {
          this.facturaForm.patchValue({ tipoCambio: res.item.venta });
        } else if (mostrarError) {
          this.notification.error(`No hay tipo de cambio para ${f}`);
        }
        this.fetchingTc.set(false);
      },
      error: () => {
        if (mostrarError) this.notification.error('Error al obtener tipo de cambio');
        this.fetchingTc.set(false);
      },
    });
  }

  fieldInvalid(field: string): boolean {
    const c = this.facturaForm.get(field);
    return !!(c?.invalid && c?.touched);
  }

  exportarExcel(): void {
    this.exporting.set(true);
    const search = this.searchText() || undefined;
    const fechaDesde = this.filterFechaDesde() || undefined;
    const fechaHasta = this.filterFechaHasta() || undefined;
    this.facturaService.exportReporte(search, fechaDesde, fechaHasta).subscribe({
      next: blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'reporte-facturacion.xlsx';
        a.click();
        URL.revokeObjectURL(url);
        this.exporting.set(false);
      },
      error: () => {
        this.notification.error('Error al exportar el reporte');
        this.exporting.set(false);
      },
    });
  }

  skeletonRows = [1, 2, 3, 4, 5, 6, 7, 8];
}
