import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { FacturaService, FacturaCreateDto } from '../../factura.service';
import { ParametroService } from '@features/settings/services/parametro.service';
import { ArchivoDespachoService } from '../../archivo-despacho.service';
import { TipoCambioService } from '../../tipo-cambio.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Factura, ArchivoDespacho } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { PdfViewerComponent } from '@shared/components/pdf-viewer/pdf-viewer.component';

type SortField = 'numeroDocumento' | 'fechaEmision' | 'clienteRazonSocial' | 'importe' | 'total';
type EstadoFilter = 'todas' | 'activas' | 'anuladas';

@Component({
  selector: 'app-reporte-facturacion',
  standalone: true,
  imports: [FormsModule, ReactiveFormsModule, DecimalPipe, PageHeaderComponent, PaginationComponent, PdfViewerComponent],
  templateUrl: './reporte-facturacion.component.html',
})
export class ReporteFacturacionComponent implements OnInit {
  private facturaService = inject(FacturaService);
  private parametroService = inject(ParametroService);
  private archivoService = inject(ArchivoDespachoService);
  private tipoCambioService = inject(TipoCambioService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private fb = inject(FormBuilder);

  facturas = signal<Factura[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 25, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  totales = signal<{
    totalImporteUsd: number; totalIgvUsd: number; totalGeneralUsd: number;
    totalImportePen: number; totalIgvPen: number; totalGeneralPen: number;
    countActivas: number; countAnuladas: number;
    countActivasUsd: number; countActivasPen: number;
  } | null>(null);
  loading = signal(false);
  loadingTotales = signal(false);
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

  // Totales globales por moneda (API) — usados en las tarjetas de métricas
  totalImporteUsd  = computed(() => this.totales()?.totalImporteUsd ?? 0);
  totalIgvUsd      = computed(() => this.totales()?.totalIgvUsd ?? 0);
  totalGeneralUsd  = computed(() => this.totales()?.totalGeneralUsd ?? 0);
  totalImportePen  = computed(() => this.totales()?.totalImportePen ?? 0);
  totalIgvPen      = computed(() => this.totales()?.totalIgvPen ?? 0);
  totalGeneralPen  = computed(() => this.totales()?.totalGeneralPen ?? 0);
  countActivas     = computed(() => this.totales()?.countActivas ?? 0);
  countActivasUsd  = computed(() => this.totales()?.countActivasUsd ?? 0);
  countActivasPen  = computed(() => this.totales()?.countActivasPen ?? 0);

  // Totales de página — usados en el tfoot (solo filas visibles activas)
  pageImporte  = computed(() => this.facturas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.importe ?? 0), 0));
  pageIgv      = computed(() => this.facturas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.igv ?? 0), 0));
  pageTotal    = computed(() => this.facturas().filter(f => !f.isAnulada).reduce((s, f) => s + (f.total ?? 0), 0));
  pageCount    = computed(() => this.facturas().filter(f => !f.isAnulada).length);

  servicios = signal<string[]>([]);

  readonly TIPOS_DOCUMENTO = [
    { value: '01', label: 'Factura (01)' },
    { value: '09', label: 'Guía de Remisión (09)' },
    { value: '07', label: 'Nota de Crédito (07)' },
    { value: '08', label: 'Nota de Débito (08)' },
  ];
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

  private syncQueryParams(): void {
    const params: Record<string, string> = {};
    if (this.searchText()) params['q'] = this.searchText();
    if (this.filterServicio()) params['servicio'] = this.filterServicio();
    if (this.filterAnuladas() !== 'activas') params['estado'] = this.filterAnuladas();
    if (this.filterFechaDesde()) params['desde'] = this.filterFechaDesde();
    if (this.filterFechaHasta()) params['hasta'] = this.filterFechaHasta();
    if (this.currentPage()) params['page'] = String(this.currentPage());
    if (this.itemsPerPage() !== 25) params['size'] = String(this.itemsPerPage());
    if (this.sortField() !== 'fechaEmision') params['sort'] = this.sortField();
    if (this.sortDir() !== 'desc') params['dir'] = this.sortDir();
    this.router.navigate([], { relativeTo: this.route, queryParams: params, replaceUrl: true });
  }

  ngOnInit(): void {
    const qp = this.route.snapshot.queryParamMap;
    this.searchText.set(qp.get('q') ?? '');
    this.filterServicio.set(qp.get('servicio') ?? '');
    this.filterAnuladas.set((qp.get('estado') as EstadoFilter) ?? 'activas');
    this.filterFechaDesde.set(qp.get('desde') ?? '');
    this.filterFechaHasta.set(qp.get('hasta') ?? '');
    this.currentPage.set(Number(qp.get('page') ?? 0));
    const size = Number(qp.get('size') ?? 25);
    this.itemsPerPage.set(this.PAGE_SIZES.includes(size) ? size : 25);
    this.sortField.set((qp.get('sort') as SortField) ?? 'fechaEmision');
    this.sortDir.set((qp.get('dir') as 'asc' | 'desc') ?? 'desc');
    this.load();
    this.loadTotales();
    this.parametroService.getByParentAlias('TIPOSERVICIO').subscribe({
      next: res => this.servicios.set(res.items.map(p => p.name)),
    });
    this.facturaForm.get('fechaEmision')!.valueChanges.subscribe(fecha => {
      if (fecha && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        this.fetchTipoCambio(fecha, false);
      }
    });
  }

  private buildFilterParams(): any {
    const params: any = {};
    if (this.searchText()) params['search'] = this.searchText();
    const estado = this.filterAnuladas();
    if (estado === 'activas') params['isAnulada'] = false;
    else if (estado === 'anuladas') params['isAnulada'] = true;
    if (this.filterServicio()) params['tipoServicio'] = this.filterServicio();
    if (this.filterFechaDesde()) params['fechaDesde'] = this.filterFechaDesde();
    if (this.filterFechaHasta()) params['fechaHasta'] = this.filterFechaHasta();
    return params;
  }

  load(): void {
    this.loading.set(true);
    const params: any = {
      ...this.buildFilterParams(),
      page: this.currentPage(),
      itemsPerPage: this.itemsPerPage(),
      sort: this.sortField(),
      direction: this.sortDir(),
    };

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

  loadTotales(): void {
    this.loadingTotales.set(true);
    this.facturaService.getTotales(this.buildFilterParams()).subscribe({
      next: res => {
        if (res.status) this.totales.set(res.item ?? null);
        else this.notification.error('Error al calcular totales');
        this.loadingTotales.set(false);
      },
      error: () => {
        this.notification.error('Error al obtener totales globales');
        this.loadingTotales.set(false);
      },
    });
  }

  onSearch(event: Event): void {
    this.searchText.set((event.target as HTMLInputElement).value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => { this.currentPage.set(0); this.syncQueryParams(); this.load(); this.loadTotales(); }, 400);
  }

  onFilterChange(): void {
    this.currentPage.set(0);
    this.syncQueryParams();
    this.load();
    this.loadTotales();
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.syncQueryParams(); this.load(); }

  onPageSizeChange(size: number): void { this.itemsPerPage.set(size); this.currentPage.set(0); this.syncQueryParams(); this.load(); }

  sortBy(field: SortField): void {
    if (this.sortField() === field) {
      this.sortDir.update(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      this.sortField.set(field);
      this.sortDir.set('asc');
    }
    this.currentPage.set(0);
    this.syncQueryParams();
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

  viewingArchivo = signal<ArchivoDespacho | null>(null);
  loadingPdf = signal<string | null>(null); // facturaId cargando

  verPdfFactura(factura: Factura): void {
    this.loadingPdf.set(factura.id);
    this.archivoService.getByFactura(factura.id).subscribe({
      next: res => {
        this.loadingPdf.set(null);
        const items = res.items ?? [];
        const pdf = items.find(a => a.tipoArchivo === 'FACTURA_PDF' || a.tipoArchivo === 'GUIA_PDF');
        if (pdf) {
          this.viewingArchivo.set(pdf);
          return;
        }
        const xml = items.find(a => a.tipoArchivo === 'FACTURA_XML' || a.tipoArchivo === 'GUIA_XML');
        if (xml) {
          this.loadingPdf.set(xml.id);
          this.archivoService.download(xml.id).subscribe({
            next: blob => {
              this.loadingPdf.set(null);
              const url = URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = xml.nombre;
              a.click();
              URL.revokeObjectURL(url);
            },
            error: () => {
              this.loadingPdf.set(null);
              this.notification.error('Error al descargar el archivo');
            },
          });
          return;
        }
        this.notification.error('No hay archivos vinculados a esta factura');
      },
      error: () => {
        this.loadingPdf.set(null);
        this.notification.error('Error al buscar el archivo');
      },
    });
  }

  skeletonRows = [1, 2, 3, 4, 5, 6, 7, 8];
}
