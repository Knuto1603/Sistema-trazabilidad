import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { ProductorService, ProductorCreateDto } from '../../productor.service';
import { NotificationService } from '@core/services/notification.service';
import { AuthService } from '@core/services/auth.service';
import { CampaignService } from '@core/services/campaign.service';
import { Producer } from '@core/models/core.model';
import { Pagination } from '@core/models/api.model';
import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'app-productores-list',
  standalone: true,
  imports: [ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './productores-list.component.html'
})
export class ProductoresListComponent implements OnInit {
  private productorService = inject(ProductorService);
  private notification = inject(NotificationService);
  private authService = inject(AuthService);
  private campaignService = inject(CampaignService);
  private fb = inject(FormBuilder);

  items = signal<Producer[]>([]);
  pagination = signal<Pagination>({ page: 0, itemsPerPage: 10, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  loading = signal(false);
  saving = signal(false);
  search = signal('');
  currentPage = signal(0);

  showModal = signal(false);
  showDeleteConfirm = signal(false);
  deletingId = signal<string | null>(null);
  deletingNombre = signal<string | null>(null);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));
  activeCampaign = computed(() => this.campaignService.activeCampaign());
  viewMode = signal<'all' | 'campaign'>('campaign');

  form = this.fb.group({
    codigo: ['', [Validators.required, Validators.minLength(4), Validators.maxLength(4)]],
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    clp: ['', [Validators.pattern(/^\d{3}-\d{5}-\d{2}$/)]],
    mtdCeratitis: ['', [Validators.pattern(/^\d\.\d{4}$/)]],
    mtdAnastrepha: ['', [Validators.pattern(/^\d\.\d{4}$/)]],
    campahnaId: ['']
  });

  private searchTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const campahna = this.activeCampaign();

    if (this.viewMode() === 'campaign' && campahna) {
      this.productorService.getByCampaign(campahna.id, {
        page: this.currentPage(), itemsPerPage: 10, search: this.search()
      }).subscribe({
        next: res => {
          if (res.status) { this.items.set(res.items); this.pagination.set(res.pagination); }
          this.loading.set(false);
        },
        error: () => this.loading.set(false)
      });
    } else {
      this.productorService.getAll({ page: this.currentPage(), itemsPerPage: 10, search: this.search() }).subscribe({
        next: res => {
          if (res.status) { this.items.set(res.items); this.pagination.set(res.pagination); }
          this.loading.set(false);
        },
        error: () => this.loading.set(false)
      });
    }
  }

  onSearch(event: Event): void {
    this.search.set((event.target as HTMLInputElement).value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => { this.currentPage.set(0); this.load(); }, 400);
  }

  onPageChange(page: number): void { this.currentPage.set(page); this.load(); }

  toggleViewMode(): void {
    this.viewMode.set(this.viewMode() === 'campaign' ? 'all' : 'campaign');
    this.currentPage.set(0);
    this.load();
  }

  openCreateModal(): void {
    this.form.reset();
    const campahna = this.activeCampaign();
    if (campahna) {
      this.form.patchValue({ campahnaId: campahna.id });
    }
    this.suggestNextCode();
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.form.reset();
  }

  suggestNextCode(): void {
    this.productorService.getLastCode().subscribe(res => {
      const last = (res as any).lastCode as string | null;
      if (last && /^\d{4}$/.test(last)) {
        const next = String(parseInt(last, 10) + 1).padStart(4, '0');
        this.form.patchValue({ codigo: next });
      } else {
        this.form.patchValue({ codigo: '0001' });
      }
    });
  }

  saveProductor(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const raw = this.form.value;
    const campahna = this.activeCampaign();

    const dto: ProductorCreateDto = {
      codigo: raw.codigo!,
      nombre: raw.nombre!,
      clp: raw.clp || undefined,
      mtdCeratitis: raw.mtdCeratitis || undefined,
      mtdAnastrepha: raw.mtdAnastrepha || undefined,
      campahnaId: raw.campahnaId || campahna?.id || ''
    };

    this.productorService.create(dto).subscribe({
      next: res => {
        if (res.status) {
          this.notification.success('Productor creado');
          this.closeModal();
          this.load();
        }
        this.saving.set(false);
      },
      error: () => this.saving.set(false)
    });
  }

  confirmDelete(item: Producer): void {
    this.deletingId.set(item.id);
    this.deletingNombre.set(item.nombre);
    this.showDeleteConfirm.set(true);
  }

  cancelDelete(): void {
    this.showDeleteConfirm.set(false);
    this.deletingId.set(null);
    this.deletingNombre.set(null);
  }

  executeDelete(): void {
    const id = this.deletingId();
    if (!id) return;
    this.productorService.delete(id).subscribe(res => {
      if (res.status) { this.notification.success('Productor eliminado'); this.load(); }
      this.cancelDelete();
    });
  }

  fieldInvalid(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  skeletonRows = [1, 2, 3, 4, 5];
}
