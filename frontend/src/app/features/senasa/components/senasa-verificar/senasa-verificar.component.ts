import { Component, signal, inject } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { SenasaService, SenasaVerificarResponse } from '../../senasa.service';
import { NotificationService } from '@core/services/notification.service';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'app-senasa-verificar',
  standalone: true,
  imports: [ReactiveFormsModule, PageHeaderComponent],
  templateUrl: './senasa-verificar.component.html'
})
export class SenasaVerificarComponent {
  private senasaService = inject(SenasaService);
  private notification = inject(NotificationService);
  private fb = inject(FormBuilder);

  loading = signal(false);
  result = signal<SenasaVerificarResponse | null>(null);
  hasSearched = signal(false);

  form = this.fb.group({
    codigo: ['', [Validators.required, Validators.minLength(3)]],
    fecha: ['', [Validators.required]]
  });

  ngOnInit(): void {
    const today = new Date().toISOString().split('T')[0];
    this.form.patchValue({ fecha: today });
  }

  verificar(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.loading.set(true);
    this.result.set(null);
    this.hasSearched.set(false);

    const { codigo, fecha } = this.form.value as { codigo: string; fecha: string };

    this.senasaService.verificar(codigo, fecha).subscribe({
      next: res => {
        this.result.set(res);
        this.hasSearched.set(true);
        this.loading.set(false);
        if (!res.success) {
          this.notification.warning(res.error ?? 'No se encontraron resultados');
        }
      },
      error: (err) => {
        this.hasSearched.set(true);
        this.loading.set(false);
        const errMsg = err?.error?.error ?? 'Error al consultar SENASA';
        this.result.set({ success: false, error: errMsg });
      }
    });
  }

  reset(): void {
    this.result.set(null);
    this.hasSearched.set(false);
    this.form.reset();
    const today = new Date().toISOString().split('T')[0];
    this.form.patchValue({ fecha: today });
  }

  fieldInvalid(field: string): boolean {
    const c = this.form.get(field);
    return !!(c?.invalid && c?.touched);
  }

  getResultEntries(): { key: string; value: any }[] {
    const data = this.result()?.data;
    if (!data || typeof data !== 'object') return [];
    return Object.entries(data).map(([key, value]) => ({ key, value }));
  }
}
