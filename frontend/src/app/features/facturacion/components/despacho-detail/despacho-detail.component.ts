import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { DecimalPipe, Location } from '@angular/common';
import { DespachoService } from '../../despacho.service';
import { FacturaService, FacturaCreateDto } from '../../factura.service';
import { ArchivoDespachoService } from '../../archivo-despacho.service';
import { TipoCambioService } from '../../tipo-cambio.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { Despacho, Factura, ArchivoDespacho } from '@core/models/core.model';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

interface PendingFile {
  file: File;
  tipoArchivo: string;
  uploading: boolean;
}

interface PendingFacturaItem {
  file: File;
  parsed: any;         // datos del ítem (cantidad, importe, detalle, etc.)
  header: any;         // datos del encabezado de la factura (serie, correlativo, fecha, etc.)
  destino: string;
  tipoServicio: string;
  linkedGuiaIdx: number | null;
  itemLabel: string | null;   // "Ítem 1 de 3", null si es factura de un solo ítem
  status: 'pending' | 'saving' | 'done' | 'error';
}

interface PendingGuiaItem {
  file: File;
  parsed: any; // incluye facturaReferencia
}

@Component({
  selector: 'app-despacho-detail',
  standalone: true,
  imports: [ReactiveFormsModule, DecimalPipe, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './despacho-detail.component.html'
})
export class DespachoDetailComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private location = inject(Location);
  private despachoService = inject(DespachoService);
  private facturaService = inject(FacturaService);
  private archivoService = inject(ArchivoDespachoService);
  private tipoCambioService = inject(TipoCambioService);
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
  fetchingTc = signal(false);
  aplicandoTc = signal(false);

  showFacturaModal = signal(false);
  editingFacturaId = signal<string | null>(null);
  showDeleteFacturaConfirm = signal(false);
  deletingFacturaId = signal<string | null>(null);
  showAnularConfirm = signal(false);
  anulatingFacturaId = signal<string | null>(null);
  showDeleteArchivoConfirm = signal(false);
  deletingArchivoId = signal<string | null>(null);
  showUploadArchivoModal = signal(false);

  // Subida múltiple + auto-procesado de XMLs
  pendingFiles = signal<PendingFile[]>([]);
  pendingFacturas = signal<PendingFacturaItem[]>([]);
  pendingGuias = signal<PendingGuiaItem[]>([]);
  parsingXmls = signal(false);
  processingAll = signal(false);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  readonly TIPOS_DOCUMENTO = [
    { value: '01', label: 'Factura (01)' },
    { value: '09', label: 'Guía de Remisión (09)' },
    { value: '07', label: 'Nota de Crédito (07)' },
    { value: '08', label: 'Nota de Débito (08)' },
  ];

  readonly TIPOS_SERVICIO = ['MAQUILA', 'SOBRECOSTO', 'VENTA_CAJAS'];
  readonly TIPOS_OPERACION = ['MARITIMO', 'TERRESTRE'];
  readonly MONEDAS = ['USD', 'PEN'];
  readonly UNIDADES_MEDIDA = ['TNE', 'KGM', 'KG', 'ZZ', 'UND', 'NIU'];
  readonly TIPOS_ARCHIVO = ['FACTURA_XML', 'GUIA_XML', 'NOTA_CREDITO_XML', 'NOTA_DEBITO_XML', 'FACTURA_PDF', 'GUIA_PDF', 'PACKING_LIST', 'CDR', 'OTRO'];

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

  archivoForm = this.fb.group({
    tipoArchivo: ['OTRO', Validators.required],
  });

  selectedArchivo: File | null = null;

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id') ?? '';
    this.despachoId.set(id);
    this.loadAll();

    // Auto-rellenar tipo de cambio (venta) cuando cambia la fecha de emisión
    this.facturaForm.get('fechaEmision')!.valueChanges.subscribe(fecha => {
      if (fecha && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        this.fetchTipoCambio(fecha, false);
      }
    });
  }

  goBack(): void {
    this.location.back();
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
      destino: factura.destino ?? '',
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

          if (d.ignorar) {
            this.notification.info('El archivo es un CDR (acuse de SUNAT). Solo puede adjuntarse como archivo.');
            this.parsingXml.set(false);
            input.value = '';
            return;
          }

          const isGuia = d.tipoDocumento === '09';

          if (isGuia) {
            const current = this.facturaForm.value;
            const patch: any = {};
            if (d.numeroDocumento) patch['numeroGuia'] = d.numeroDocumento;
            if (!current['cajas'] && d.cajas) patch['cajas'] = d.cajas;
            if (!current['kgCaja'] && d.kgCaja) patch['kgCaja'] = d.kgCaja;
            if (!current['cantidad'] && d.cantidad) patch['cantidad'] = d.cantidad;
            if (!current['unidadMedida'] && d.unidadMedida) patch['unidadMedida'] = d.unidadMedida;
            if (!current['tipoOperacion'] && d.tipoOperacion) patch['tipoOperacion'] = d.tipoOperacion;
            if (!current['contenedor'] && d.contenedor) patch['contenedor'] = d.contenedor;
            if (!current['detalle'] && d.detalle) patch['detalle'] = d.detalle;
            this.facturaForm.patchValue(patch);
            this.autocalcularCajas();
            this.notification.success('Guía XML procesada. Datos completados.');
          } else {
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
              cajas: d.cajas ?? null,
              cantidad: d.cantidad ?? null,
              valorUnitario: d.valorUnitario ?? null,
              importe: d.importe ?? null,
              igv: d.igv ?? null,
              total: d.total ?? null,
              tipoServicio: d.tipoServicio ?? '',
              tipoOperacion: d.tipoOperacion ?? '',
              contenedor: d.contenedor ?? '',
            });
            if (!d.cajas) this.autocalcularCajas();
            this.showFacturaModal.set(true);
            this.editingFacturaId.set(null);
            this.notification.success('Factura XML procesada. Revise y confirme.');
          }
        } else {
          this.notification.error('No se pudo procesar el XML');
        }
        this.parsingXml.set(false);
        input.value = '';
      },
      error: () => { this.notification.error('Error al procesar el XML'); this.parsingXml.set(false); input.value = ''; }
    });
  }

  private autocalcularCajas(): void {
    const v = this.facturaForm.value;
    const kgCaja = v['kgCaja'];
    const cantidad = v['cantidad'];
    const unidad = v['unidadMedida'];
    if (kgCaja && cantidad && !v['cajas']) {
      let cajas: number;
      if (unidad === 'TNE') {
        cajas = Math.round((cantidad * 1000) / kgCaja);
      } else if (unidad === 'KGM' || unidad === 'KG') {
        cajas = Math.round(cantidad / kgCaja);
      } else {
        return;
      }
      this.facturaForm.patchValue({ cajas });
    }
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
      destino: raw.destino || undefined,
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
      error: (err) => {
        const detail = err?.error?.detail ?? err?.error?.message ?? JSON.stringify(err?.error ?? err);
        this.notification.error('Error al guardar: ' + detail);
        console.error('422 body:', err?.error);
        this.savingFactura.set(false);
      }
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

  // --- Subida múltiple con auto-parseo de XMLs ---

  onMultipleFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const newFiles: PendingFile[] = Array.from(input.files).map(file => ({
      file,
      tipoArchivo: this.detectTipoArchivo(file.name),
      uploading: false,
    }));

    this.pendingFiles.update(existing => [...existing, ...newFiles]);
    this.showUploadArchivoModal.set(true);
    input.value = '';

    // Auto-parsear XMLs
    const xmlFacturas = newFiles.filter(f => ['FACTURA_XML', 'NOTA_CREDITO_XML', 'NOTA_DEBITO_XML'].includes(f.tipoArchivo));
    const xmlGuias = newFiles.filter(f => f.tipoArchivo === 'GUIA_XML');

    if (xmlFacturas.length > 0 || xmlGuias.length > 0) {
      this.parsingXmls.set(true);
      Promise.all([
        ...xmlFacturas.map(f => this.parseFileAsync(f.file)),
        ...xmlGuias.map(f => this.parseFileAsync(f.file)),
      ]).then(results => {
        const facturaResults = results.slice(0, xmlFacturas.length).filter(r => r !== null);
        const guiaResults = results.slice(xmlFacturas.length).filter(r => r !== null);

        const newGuias: PendingGuiaItem[] = guiaResults.map((parsed, i) => ({
          file: xmlGuias[i].file,
          parsed,
        }));

        // Índice de guías ya existentes + nuevas para el auto-link
        const allGuias = [...this.pendingGuias(), ...newGuias];

        const newFacturas: PendingFacturaItem[] = [];
        facturaResults.forEach((parsed, fileIdx) => {
          const items: any[] = parsed.items?.length > 0 ? parsed.items : [parsed];
          const totalItems = items.length;

          // Auto-link: solo buscar guía para este N° de documento
          const guiaIdx = allGuias.findIndex(g =>
            g.parsed?.facturaReferencia &&
            this.matchNumeroDocumento(g.parsed.facturaReferencia, parsed.numeroDocumento)
          );
          const resolvedGuiaIdx = guiaIdx >= 0 ? guiaIdx : null;

          items.forEach((item, itemIdx) => {
            newFacturas.push({
              file: xmlFacturas[fileIdx].file,
              parsed: item,
              header: parsed,
              destino: '',
              tipoServicio: item.tipoServicio ?? '',
              // solo el primer ítem se vincula a la guía
              linkedGuiaIdx: itemIdx === 0 ? resolvedGuiaIdx : null,
              itemLabel: totalItems > 1 ? `Ítem ${itemIdx + 1} de ${totalItems}` : null,
              status: 'pending' as const,
            });
          });
        });

        this.pendingGuias.update(existing => [...existing, ...newGuias]);
        this.pendingFacturas.update(existing => [...existing, ...newFacturas]);
        this.parsingXmls.set(false);
      }).catch(() => {
        this.parsingXmls.set(false);
        this.notification.error('Error al procesar algunos XMLs');
      });
    }
  }

  private parseFileAsync(file: File): Promise<any> {
    return new Promise(resolve => {
      this.facturaService.parseXml(file).subscribe({
        next: res => {
          const item = res.status ? (res.item ?? {}) : {};
          resolve((item as any).ignorar ? null : item);
        },
        error: () => resolve(null),
      });
    });
  }

  private matchNumeroDocumento(ref: string, num: string): boolean {
    if (!ref || !num) return false;
    const normalize = (s: string) => {
      const parts = s.split('-');
      if (parts.length >= 2) {
        return parts[0].toUpperCase() + '-' + parseInt(parts[parts.length - 1], 10);
      }
      return s.toUpperCase();
    };
    return normalize(ref) === normalize(num);
  }

  private detectTipoArchivo(filename: string): string {
    const name = filename.toUpperCase();
    if (/^R-\d{11}-/.test(filename)) return 'CDR';
    if (/^\d{11}-01-/.test(filename) && name.endsWith('.XML')) return 'FACTURA_XML';
    if (/^\d{11}-07-/.test(filename) && name.endsWith('.XML')) return 'NOTA_CREDITO_XML';
    if (/^\d{11}-08-/.test(filename) && name.endsWith('.XML')) return 'NOTA_DEBITO_XML';
    if (/^\d{11}-09-/.test(filename) && name.endsWith('.XML')) return 'GUIA_XML';
    if (name.includes('PACKING') && name.endsWith('.PDF')) return 'PACKING_LIST';
    if (name.includes('FACTURA') && name.endsWith('.PDF')) return 'FACTURA_PDF';
    if ((name.includes('GUIA') || name.includes('GUÍA')) && name.endsWith('.PDF')) return 'GUIA_PDF';
    return 'OTRO';
  }

  updateTipoArchivoPending(index: number, tipo: string): void {
    this.pendingFiles.update(files => {
      const copy = [...files];
      copy[index] = { ...copy[index], tipoArchivo: tipo };
      return copy;
    });
  }

  removePendingFile(index: number): void {
    this.pendingFiles.update(files => files.filter((_, i) => i !== index));
  }

  updatePendingFacturaTipoServicio(idx: number, value: string): void {
    this.pendingFacturas.update(list => {
      const copy = [...list];
      copy[idx] = { ...copy[idx], tipoServicio: value };
      return copy;
    });
  }

  updatePendingFacturaDestino(idx: number, value: string): void {
    this.pendingFacturas.update(list => {
      const copy = [...list];
      copy[idx] = { ...copy[idx], destino: value };
      return copy;
    });
  }

  updatePendingFacturaGuia(idx: number, value: string): void {
    this.pendingFacturas.update(list => {
      const copy = [...list];
      copy[idx] = { ...copy[idx], linkedGuiaIdx: value === '' ? null : parseInt(value, 10) };
      return copy;
    });
  }

  async processAll(): Promise<void> {
    if (this.processingAll()) return;
    this.processingAll.set(true);

    const despachoId = this.despachoId();
    const files = this.pendingFiles();
    const facturas = this.pendingFacturas();
    const guias = this.pendingGuias();

    // 1. Crear todas las facturas desde XMLs
    const createdFacturaIds: (string | null)[] = [];
    for (let i = 0; i < facturas.length; i++) {
      const f = facturas[i];
      this.pendingFacturas.update(list => {
        const copy = [...list];
        copy[i] = { ...copy[i], status: 'saving' };
        return copy;
      });

      const linkedGuia = f.linkedGuiaIdx !== null ? guias[f.linkedGuiaIdx] : null;
      const h = f.header ?? f.parsed; // encabezado (serie, correlativo, fecha, etc.)
      const it = f.parsed;            // datos del ítem (cantidad, importe, detalle, etc.)
      const dto: FacturaCreateDto = {
        tipoDocumento: h.tipoDocumento ?? '01',
        serie: h.serie ?? '',
        correlativo: h.correlativo ?? '',
        numeroGuia: linkedGuia?.parsed.numeroDocumento ?? h.numeroGuia ?? undefined,
        fechaEmision: h.fechaEmision ?? '',
        moneda: h.moneda ?? 'USD',
        detalle: it.detalle || undefined,
        kgCaja: it.kgCaja ?? undefined,
        unidadMedida: it.unidadMedida || undefined,
        cajas: linkedGuia?.parsed.cajas ?? it.cajas ?? undefined,
        cantidad: linkedGuia?.parsed.cantidad ?? it.cantidad ?? undefined,
        valorUnitario: it.valorUnitario ?? undefined,
        importe: it.importe ?? undefined,
        igv: it.igv ?? undefined,
        total: it.total ?? undefined,
        tipoServicio: f.tipoServicio || undefined,
        tipoOperacion: it.tipoOperacion || undefined,
        contenedor: it.contenedor || undefined,
        destino: f.destino || undefined,
        despachoId,
      };

      const facturaId = await new Promise<string | null>(resolve => {
        this.facturaService.create(dto).subscribe({
          next: res => resolve(res.status && res.item ? (res.item as any).id : null),
          error: () => resolve(null),
        });
      });

      createdFacturaIds.push(facturaId);
      this.pendingFacturas.update(list => {
        const copy = [...list];
        copy[i] = { ...copy[i], status: facturaId ? 'done' : 'error' };
        return copy;
      });
    }

    // 2. Subir todos los archivos, enlazando XMLs a su factura
    // Para facturas multi-ítem el mismo File puede aparecer en varios PendingFacturaItem:
    // solo subir el archivo XML una vez (vinculado al primer ítem creado de ese archivo).
    const uploadedFiles = new Set<File>();
    let successCount = 0;
    for (let i = 0; i < files.length; i++) {
      const entry = files[i];

      // Si ya se subió este archivo (mismo objeto File), saltar
      if (uploadedFiles.has(entry.file)) {
        this.pendingFiles.update(f => {
          const copy = [...f];
          copy[i] = { ...copy[i], uploading: false };
          return copy;
        });
        continue;
      }

      this.pendingFiles.update(f => {
        const copy = [...f];
        copy[i] = { ...copy[i], uploading: true };
        return copy;
      });

      let facturaId: string | undefined;
      if (entry.tipoArchivo === 'FACTURA_XML') {
        // Buscar el primer ítem creado de este archivo
        const facturaIdx = facturas.findIndex(f => f.file === entry.file);
        if (facturaIdx >= 0 && createdFacturaIds[facturaIdx]) {
          facturaId = createdFacturaIds[facturaIdx]!;
        }
      } else if (entry.tipoArchivo === 'GUIA_XML') {
        const guiaIdx = guias.findIndex(g => g.file === entry.file);
        if (guiaIdx >= 0) {
          const linkedFacturaIdx = facturas.findIndex(f => f.linkedGuiaIdx === guiaIdx);
          if (linkedFacturaIdx >= 0 && createdFacturaIds[linkedFacturaIdx]) {
            facturaId = createdFacturaIds[linkedFacturaIdx]!;
          }
        }
      }

      await new Promise<void>(resolve => {
        this.archivoService.upload(despachoId, entry.tipoArchivo, entry.file, facturaId).subscribe({
          next: res => { if (res.status) { successCount++; uploadedFiles.add(entry.file); } resolve(); },
          error: () => resolve(),
        });
      });

      this.pendingFiles.update(f => {
        const copy = [...f];
        copy[i] = { ...copy[i], uploading: false };
        return copy;
      });
    }

    // 3. Finalizar
    const createdCount = createdFacturaIds.filter(id => id !== null).length;
    this.pendingFiles.set([]);
    this.pendingFacturas.set([]);
    this.pendingGuias.set([]);
    this.processingAll.set(false);
    this.showUploadArchivoModal.set(false);

    if (createdCount > 0 || successCount > 0) {
      const msg = [
        createdCount > 0 ? `${createdCount} factura(s) registrada(s)` : '',
        successCount > 0 ? `${successCount} archivo(s) subido(s)` : '',
      ].filter(Boolean).join(' y ');
      this.notification.success(msg);
      this.loadAll();
    }
  }

  closeUploadMultipleModal(): void {
    if (!this.processingAll()) {
      this.pendingFiles.set([]);
      this.pendingFacturas.set([]);
      this.pendingGuias.set([]);
      this.showUploadArchivoModal.set(false);
    }
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

  async aplicarTcATodas(): Promise<void> {
    const lista = this.facturas().filter(f => f.fechaEmision && !f.isAnulada);
    if (!lista.length) return;

    this.aplicandoTc.set(true);
    let actualizadas = 0;
    let sinTc = 0;

    for (const factura of lista) {
      await new Promise<void>(resolve => {
        this.tipoCambioService.getByFecha(factura.fechaEmision).subscribe({
          next: res => {
            if (res.status && res.item) {
              const dto: FacturaCreateDto = {
                tipoDocumento: factura.tipoDocumento,
                serie: factura.serie,
                correlativo: factura.correlativo,
                numeroGuia: factura.numeroGuia,
                fechaEmision: factura.fechaEmision,
                moneda: factura.moneda,
                detalle: factura.detalle,
                kgCaja: factura.kgCaja,
                unidadMedida: factura.unidadMedida,
                cajas: factura.cajas,
                cantidad: factura.cantidad !== undefined ? Number(factura.cantidad) : undefined,
                valorUnitario: factura.valorUnitario !== undefined ? Number(factura.valorUnitario) : undefined,
                importe: factura.importe !== undefined ? Number(factura.importe) : undefined,
                igv: factura.igv !== undefined ? Number(factura.igv) : undefined,
                total: factura.total !== undefined ? Number(factura.total) : undefined,
                tipoCambio: res.item.venta,
                tipoServicio: factura.tipoServicio,
                tipoOperacion: factura.tipoOperacion,
                contenedor: factura.contenedor,
                destino: factura.destino,
                despachoId: factura.despachoId,
              };
              this.facturaService.update(factura.id, dto).subscribe({
                next: r => { if (r.status) actualizadas++; resolve(); },
                error: () => resolve(),
              });
            } else {
              sinTc++;
              resolve();
            }
          },
          error: () => { sinTc++; resolve(); },
        });
      });
    }

    this.aplicandoTc.set(false);
    this.loadFacturas();

    const msg = [`${actualizadas} factura(s) actualizadas con TC`];
    if (sinTc) msg.push(`${sinTc} sin TC registrado para su fecha`);
    this.notification.success(msg.join(' · '));
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
          this.notification.error(`No hay tipo de cambio registrado para ${f}`);
        }
        this.fetchingTc.set(false);
      },
      error: () => {
        if (mostrarError) this.notification.error('Error al obtener tipo de cambio');
        this.fetchingTc.set(false);
      }
    });
  }

  fieldFacturaInvalid(field: string): boolean {
    const c = this.facturaForm.get(field);
    return !!(c?.invalid && c?.touched);
  }
}
