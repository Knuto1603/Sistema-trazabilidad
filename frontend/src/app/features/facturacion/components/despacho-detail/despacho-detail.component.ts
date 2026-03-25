import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { DespachoService } from '../../despacho.service';
import { FacturaService, FacturaCreateDto } from '../../factura.service';
import { ArchivoDespachoService } from '../../archivo-despacho.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Despacho, Factura, ArchivoDespacho } from '@core/models/core.model';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'app-despacho-detail',
  standalone: true,
  imports: [ReactiveFormsModule, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './despacho-detail.component.html'
})
export class DespachoDetailComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private despachoService = inject(DespachoService);
  private facturaService = inject(FacturaService);
  private archivoService = inject(ArchivoDespachoService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private fb = inject(FormBuilder);

  despachoId = signal<string>('');
  despacho = signal<Despacho | null>(null);
  facturas = signal<Factura[]>([]);
  archivos = signal<ArchivoDespacho[]>([]);
  loading = signal(false);
  savingFactura = signal(false);
  uploadingArchivo = signal(false);
  parsingXml = signal(false);

  showFacturaModal = signal(false);
  editingFacturaId = signal<string | null>(null);
  showDeleteFacturaConfirm = signal(false);
  deletingFacturaId = signal<string | null>(null);
  showAnularConfirm = signal(false);
  anulatingFacturaId = signal<string | null>(null);
  showDeleteArchivoConfirm = signal(false);
  deletingArchivoId = signal<string | null>(null);
  showUploadArchivoModal = signal(false);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  readonly TIPOS_DOCUMENTO = [
    { value: '01', label: 'Factura (01)' },
    { value: '09', label: 'Guía de Remisión (09)' },
    { value: '07', label: 'Nota de Crédito (07)' },
  ];

  readonly TIPOS_SERVICIO = ['MAQUILA', 'SOBRECOSTO', 'VENTA_CAJAS'];
  readonly TIPOS_OPERACION = ['MARITIMO', 'TERRESTRE'];
  readonly MONEDAS = ['USD', 'PEN'];
  readonly UNIDADES_MEDIDA = ['TNE', 'UND', 'KGM'];
  readonly TIPOS_ARCHIVO = ['FACTURA_XML', 'GUIA_XML', 'FACTURA_PDF', 'GUIA_PDF', 'PACKING_LIST', 'CDR', 'OTRO'];

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
  });

  archivoForm = this.fb.group({
    tipoArchivo: ['OTRO', Validators.required],
  });

  selectedArchivo: File | null = null;

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id') ?? '';
    this.despachoId.set(id);
    this.loadAll();
  }

  loadAll(): void {
    this.loading.set(true);
    const id = this.despachoId();

    this.despachoService.getById(id).subscribe(res => {
      if (res.status && res.item) this.despacho.set(res.item);
    });

    this.facturaService.getByDespacho(id).subscribe(res => {
      if (res.status) this.facturas.set(res.items);
      this.loading.set(false);
    });

    this.archivoService.getByDespacho(id).subscribe(res => {
      if (res.status) this.archivos.set(res.items);
    });
  }

  openNuevaFactura(): void {
    this.facturaForm.reset({ tipoDocumento: '01', moneda: 'USD', unidadMedida: 'TNE' });
    this.editingFacturaId.set(null);
    this.showFacturaModal.set(true);
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
      cantidad: factura.cantidad ?? null,
      valorUnitario: factura.valorUnitario ?? null,
      importe: factura.importe ?? null,
      igv: factura.igv ?? null,
      total: factura.total ?? null,
      tipoCambio: factura.tipoCambio ?? null,
      tipoServicio: factura.tipoServicio ?? '',
      tipoOperacion: factura.tipoOperacion ?? '',
      contenedor: factura.contenedor ?? '',
    });
    this.showFacturaModal.set(true);
  }

  closeFacturaModal(): void {
    this.showFacturaModal.set(false);
    this.facturaForm.reset({ tipoDocumento: '01', moneda: 'USD', unidadMedida: 'TNE' });
    this.editingFacturaId.set(null);
  }

  onXmlFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;
    const file = input.files[0];

    this.parsingXml.set(true);
    this.facturaService.parseXml(file).subscribe({
      next: res => {
        if (res.status && res.item) {
          const d = res.item as any;
          this.facturaForm.patchValue({
            tipoDocumento: d.tipoDocumento ?? '01',
            serie: d.serie ?? '',
            correlativo: d.correlativo ?? '',
            numeroGuia: d.numeroGuia ?? '',
            fechaEmision: d.fechaEmision ?? '',
            moneda: d.moneda ?? 'USD',
            detalle: d.detalle ?? '',
            kgCaja: d.kgCaja ?? null,
            unidadMedida: d.unidadMedida ?? 'TNE',
            cantidad: d.cantidad ?? null,
            valorUnitario: d.valorUnitario ?? null,
            importe: d.importe ?? null,
            igv: d.igv ?? null,
            total: d.total ?? null,
            tipoServicio: d.tipoServicio ?? '',
            tipoOperacion: d.tipoOperacion ?? '',
            contenedor: d.contenedor ?? '',
          });
          this.showFacturaModal.set(true);
          this.editingFacturaId.set(null);
          this.notification.success('XML procesado. Revise y confirme los datos.');
        } else {
          this.notification.error('No se pudo procesar el XML');
        }
        this.parsingXml.set(false);
        input.value = '';
      },
      error: () => { this.notification.error('Error al procesar el XML'); this.parsingXml.set(false); input.value = ''; }
    });
  }

  saveFactura(): void {
    if (this.facturaForm.invalid) { this.facturaForm.markAllAsTouched(); return; }
    this.savingFactura.set(true);
    const raw = this.facturaForm.value;

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
      despachoId: this.despachoId(),
    };

    const editId = this.editingFacturaId();
    const req = editId
      ? this.facturaService.update(editId, dto)
      : this.facturaService.create(dto);

    req.subscribe({
      next: res => {
        if (res.status) {
          this.notification.success(editId ? 'Factura actualizada' : 'Factura creada');
          this.closeFacturaModal();
          this.loadFacturas();
        }
        this.savingFactura.set(false);
      },
      error: () => this.savingFactura.set(false)
    });
  }

  loadFacturas(): void {
    this.facturaService.getByDespacho(this.despachoId()).subscribe(res => {
      if (res.status) this.facturas.set(res.items);
    });
  }

  confirmAnular(factura: Factura): void {
    this.anulatingFacturaId.set(factura.id);
    this.showAnularConfirm.set(true);
  }

  executeAnular(): void {
    const id = this.anulatingFacturaId();
    if (!id) return;
    this.facturaService.anular(id).subscribe(res => {
      if (res.status) { this.notification.success('Factura anulada'); this.loadFacturas(); }
      this.showAnularConfirm.set(false);
      this.anulatingFacturaId.set(null);
    });
  }

  confirmDeleteFactura(factura: Factura): void {
    this.deletingFacturaId.set(factura.id);
    this.showDeleteFacturaConfirm.set(true);
  }

  executeDeleteFactura(): void {
    const id = this.deletingFacturaId();
    if (!id) return;
    this.facturaService.delete(id).subscribe(res => {
      if (res.status) { this.notification.success('Factura eliminada'); this.loadFacturas(); }
      this.showDeleteFacturaConfirm.set(false);
      this.deletingFacturaId.set(null);
    });
  }

  openUploadArchivoModal(): void {
    this.archivoForm.reset({ tipoArchivo: 'OTRO' });
    this.selectedArchivo = null;
    this.showUploadArchivoModal.set(true);
  }

  closeUploadArchivoModal(): void {
    this.showUploadArchivoModal.set(false);
    this.selectedArchivo = null;
    this.archivoForm.reset({ tipoArchivo: 'OTRO' });
  }

  onArchivoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.selectedArchivo = input.files[0];
    }
  }

  uploadArchivo(): void {
    if (!this.selectedArchivo || this.archivoForm.invalid) return;
    this.uploadingArchivo.set(true);
    const tipo = this.archivoForm.get('tipoArchivo')?.value ?? 'OTRO';

    this.archivoService.upload(this.despachoId(), tipo, this.selectedArchivo).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Archivo subido exitosamente');
          this.closeUploadArchivoModal();
          this.archivoService.getByDespacho(this.despachoId()).subscribe(r => {
            if (r.status) this.archivos.set(r.items);
          });
        }
        this.uploadingArchivo.set(false);
      },
      error: () => { this.notification.error('Error al subir el archivo'); this.uploadingArchivo.set(false); }
    });
  }

  confirmDeleteArchivo(archivo: ArchivoDespacho): void {
    this.deletingArchivoId.set(archivo.id);
    this.showDeleteArchivoConfirm.set(true);
  }

  executeDeleteArchivo(): void {
    const id = this.deletingArchivoId();
    if (!id) return;
    this.archivoService.delete(id).subscribe(res => {
      if (res.status) {
        this.notification.success('Archivo eliminado');
        this.archivos.update(a => a.filter(x => x.id !== id));
      }
      this.showDeleteArchivoConfirm.set(false);
      this.deletingArchivoId.set(null);
    });
  }

  formatBytes(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  fieldFacturaInvalid(field: string): boolean {
    const c = this.facturaForm.get(field);
    return !!(c?.invalid && c?.touched);
  }
}
