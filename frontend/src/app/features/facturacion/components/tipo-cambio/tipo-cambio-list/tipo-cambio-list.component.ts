import { Component, OnInit, signal, inject } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { TipoCambioService, TipoCambioDto } from '../../../tipo-cambio.service';
import { NotificationService } from '@core/services/notification.service';
import { TipoCambio } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'app-tipo-cambio-list',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent, PageHeaderComponent],
  templateUrl: './tipo-cambio-list.component.html'
})
export class TipoCambioListComponent implements OnInit {
  private tipoCambioService = inject(TipoCambioService);
  private notification = inject(NotificationService);
  private fb = inject(FormBuilder);

  items = signal<TipoCambio[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  saving = signal(false);
  scraping = signal(false);
  importing = signal(false);
  currentPage = signal(0);

  showModal = signal(false);

  form = this.fb.group({
    fecha: [new Date().toISOString().split('T')[0], Validators.required],
    compra: [null as number | null, [Validators.required, Validators.min(0.001)]],
    venta: [null as number | null, [Validators.required, Validators.min(0.001)]],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.tipoCambioService.getAll({ page: this.currentPage(), itemsPerPage: 15 }).subscribe({
      next: res => {
        if (res.status) { this.items.set(res.items); this.pagination.set(res.pagination); }
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.load(); }

  openModal(): void {
    this.form.patchValue({ fecha: new Date().toISOString().split('T')[0], compra: null, venta: null });
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.form.reset({ fecha: new Date().toISOString().split('T')[0] });
  }

  saveTipoCambio(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;

    const dto: TipoCambioDto = {
      fecha: raw.fecha!,
      compra: raw.compra!,
      venta: raw.venta!,
    };

    this.tipoCambioService.createOrUpdate(dto).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Tipo de cambio guardado');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => this.saving.set(false)
    });
  }

  scrapeFromSunat(): void {
    this.scraping.set(true);
    this.tipoCambioService.scrapeFromSunat().subscribe({
      next: res => {
        if (res.status && res.item) {
          const d = res.item as any;
          this.form.patchValue({
            fecha: d.fecha ?? new Date().toISOString().split('T')[0],
            compra: d.compra,
            venta: d.venta,
          });
          this.showModal.set(true);
          this.notification.info('Datos obtenidos de SUNAT. Confirme para guardar.');
        } else {
          this.notification.error('No se pudo obtener el tipo de cambio de SUNAT');
        }
        this.scraping.set(false);
      },
      error: () => { this.notification.error('Error al consultar SUNAT'); this.scraping.set(false); }
    });
  }

  importarAnio(): void {
    this.importing.set(true);
    this.tipoCambioService.importarAnio().subscribe({
      next: res => {
        if (res.status) {
          const d = res.item as any;
          this.notification.success(`Importación completada: ${d?.importados ?? 0} registros guardados`);
          this.load();
        } else {
          this.notification.error('No se pudo importar los tipos de cambio');
        }
        this.importing.set(false);
      },
      error: () => { this.notification.error('Error al importar desde SUNAT'); this.importing.set(false); }
    });
  }

  fieldInvalid(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
  currentYear = new Date().getFullYear();
}
