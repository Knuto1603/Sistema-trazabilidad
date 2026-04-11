import {
  Component, input, output, inject, signal, computed, OnInit, HostListener
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { CuentaCobrar, PagoFactura, Voucher } from '@core/models/core.model';
import { CuentasCobrarService } from '../../cuentas-cobrar.service';
import { VoucherService } from '../../voucher.service';
import { NotificationService } from '@core/services/notification.service';

interface PagoForm {
  voucherNumero: string;
  voucherNumeroOperacion: string;
  voucherMontoTotal: number | null;
  voucherFecha: string;
  montoAplicado: number | null;
}

@Component({
  selector: 'app-pago-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <!-- Backdrop -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" (click)="onBackdropClick($event)">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" (click)="$event.stopPropagation()">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Pagos - {{ cuenta().numeroDocumento }}</h2>
            <p class="text-sm text-gray-500">{{ cuenta().clienteRazonSocial }}</p>
          </div>
          <button (click)="closed.emit()" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Resumen de la factura -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-gray-500">Total factura</span>
              <p class="font-semibold text-gray-900">{{ cuenta().moneda }} {{ (cuenta().total ?? 0) | number:'1.2-2' }}</p>
            </div>
            <div>
              <span class="text-gray-500">Pagado</span>
              <p class="font-semibold text-green-600">{{ cuenta().moneda }} {{ montoPagadoActual() | number:'1.2-2' }}</p>
            </div>
            <div>
              <span class="text-gray-500">Pendiente</span>
              <p class="font-semibold" [class]="montoPendienteActual() > 0 ? 'text-red-600' : 'text-gray-400'">
                {{ cuenta().moneda }} {{ montoPendienteActual() | number:'1.2-2' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Historial de pagos -->
        @if (pagos().length > 0) {
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Historial de pagos</h3>
            <div class="space-y-2">
              @for (pago of pagos(); track pago.id) {
                <div class="flex items-start justify-between rounded-lg p-3 text-xs"
                     [class]="pago.isActive ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200 opacity-60'">
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-gray-900">Voucher: {{ pago.voucherNumero }}</span>
                      @if (!pago.isActive) {
                        <span class="px-1.5 py-0.5 bg-red-100 text-red-600 rounded text-xs">ANULADO</span>
                      }
                    </div>
                    @if (pago.voucherNumeroOperacion) {
                      <div class="text-gray-500">Op: {{ pago.voucherNumeroOperacion }}</div>
                    }
                    <div class="text-gray-400 mt-0.5">{{ pago.createdAt | date:'dd/MM/yyyy HH:mm' }}</div>
                    @if (!pago.isActive && pago.justificanteEliminacion) {
                      <div class="text-red-500 mt-1">Motivo: {{ pago.justificanteEliminacion }}</div>
                    }
                  </div>
                  <div class="flex items-center gap-3 ml-4">
                    <span class="font-semibold" [class]="pago.isActive ? 'text-green-700' : 'text-gray-400'">
                      {{ cuenta().moneda }} {{ pago.montoAplicado | number:'1.2-2' }}
                    </span>
                    @if (pago.isActive) {
                      <!-- Editar -->
                      <button (click)="iniciarEdicion(pago)" class="text-blue-500 hover:text-blue-700 transition-colors" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <!-- Eliminar -->
                      <button (click)="iniciarEliminacion(pago)" class="text-red-400 hover:text-red-600 transition-colors" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    }
                  </div>
                </div>
              }
            </div>
          </div>
        }

        <!-- Panel de eliminación -->
        @if (pagoAEliminar()) {
          <div class="px-6 py-4 bg-red-50 border-b border-red-200">
            <h3 class="text-sm font-semibold text-red-700 mb-2">Anular pago de {{ cuenta().moneda }} {{ pagoAEliminar()!.montoAplicado | number:'1.2-2' }}</h3>
            <input
              type="text"
              [(ngModel)]="justificanteElim"
              placeholder="Ingresa el motivo de la anulación (requerido)"
              class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-3"
            />
            <div class="flex gap-2">
              <button (click)="confirmarEliminacion()" [disabled]="!justificanteElim.trim() || guardando()"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm rounded-lg transition-colors">
                Confirmar anulación
              </button>
              <button (click)="cancelarEliminacion()" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        }

        <!-- Formulario nuevo pago / edición -->
        @if (cuenta().estado !== 'PAGADO' || modoEdicion()) {
          <div class="px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">
              {{ modoEdicion() ? 'Editar pago' : 'Registrar nuevo pago' }}
            </h3>

            <!-- Autocomplete de voucher -->
            <div class="mb-4 relative">
              <label class="block text-xs font-medium text-gray-600 mb-1">N° Voucher / Depósito *</label>
              <input
                type="text"
                [(ngModel)]="form.voucherNumero"
                (ngModelChange)="onVoucherNumeroChange($event)"
                placeholder="Escribe el número de voucher..."
                [disabled]="!!modoEdicion()"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50"
              />
              <!-- Dropdown autocomplete -->
              @if (showAutocomplete() && voucherSugerencias().length > 0) {
                <div class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                  @for (v of voucherSugerencias(); track v.id) {
                    <button type="button" (click)="seleccionarVoucher(v)"
                      class="w-full text-left px-3 py-2.5 hover:bg-blue-50 text-xs border-b border-gray-100 last:border-0">
                      <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-900">{{ v.numero }}</span>
                        <span class="text-green-600 font-semibold">Disponible: {{ v.montoRestante | number:'1.2-2' }}</span>
                      </div>
                      @if (v.numeroOperacion) {
                        <div class="text-gray-400">Op: {{ v.numeroOperacion }}</div>
                      }
                      <div class="text-gray-400">Total: {{ v.montoTotal | number:'1.2-2' }} | Usado: {{ v.montoUsado | number:'1.2-2' }}</div>
                    </button>
                  }
                </div>
              }
            </div>

            <!-- Info del voucher existente -->
            @if (voucherSeleccionado()) {
              <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs">
                <div class="font-semibold text-blue-800 mb-1">Voucher {{ voucherSeleccionado()!.numero }}</div>
                <div class="grid grid-cols-3 gap-2 text-blue-700">
                  <div><span class="text-blue-500">Total:</span> {{ voucherSeleccionado()!.montoTotal | number:'1.2-2' }}</div>
                  <div><span class="text-blue-500">Usado:</span> {{ voucherSeleccionado()!.montoUsado | number:'1.2-2' }}</div>
                  <div class="font-semibold"><span class="text-blue-500">Disponible:</span> {{ voucherSeleccionado()!.montoRestante | number:'1.2-2' }}</div>
                </div>
              </div>
            }

            <!-- Monto total del voucher (solo si es nuevo) -->
            @if (!voucherSeleccionado() && !modoEdicion() && form.voucherNumero.trim()) {
              <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Monto Total del Voucher *</label>
                <input
                  type="number"
                  [(ngModel)]="form.voucherMontoTotal"
                  step="0.01" min="0.01"
                  placeholder="Total del voucher bancario"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p class="text-xs text-gray-400 mt-1">Ingresa el monto total del depósito/transferencia bancaria</p>
              </div>

              <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha del Voucher</label>
                <input type="date" [(ngModel)]="form.voucherFecha"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            }

            <!-- N° Operación -->
            <div class="mb-4">
              <label class="block text-xs font-medium text-gray-600 mb-1">N° Operación bancaria</label>
              <input
                type="text"
                [(ngModel)]="form.voucherNumeroOperacion"
                placeholder="Número de operación (opcional)"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Monto a aplicar -->
            <div class="mb-6">
              <label class="block text-xs font-medium text-gray-600 mb-1">Monto a aplicar a esta factura *</label>
              <input
                type="number"
                [(ngModel)]="form.montoAplicado"
                step="0.01" min="0.01"
                [max]="maxMontoAplicable()"
                placeholder="0.00"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-400 mt-1">
                Máximo aplicable: {{ cuenta().moneda }} {{ maxMontoAplicable() | number:'1.2-2' }}
                @if (voucherSeleccionado()) {
                  (pendiente factura: {{ montoPendienteActual() | number:'1.2-2' }} /
                  disponible voucher: {{ voucherSeleccionado()!.montoRestante | number:'1.2-2' }})
                }
              </p>
            </div>

            <!-- Botones -->
            <div class="flex gap-3">
              <button
                (click)="guardar()"
                [disabled]="!formValido() || guardando()"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
              >
                @if (guardando()) { Guardando... } @else { {{ modoEdicion() ? 'Actualizar' : 'Registrar pago' }} }
              </button>
              @if (modoEdicion()) {
                <button (click)="cancelarEdicion()" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                  Cancelar
                </button>
              }
            </div>
          </div>
        } @else {
          <div class="px-6 py-8 text-center text-green-600">
            <svg class="w-12 h-12 mx-auto mb-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-semibold">Factura completamente pagada</p>
          </div>
        }

      </div>
    </div>
  `
})
export class PagoModalComponent implements OnInit {
  cuenta = input.required<CuentaCobrar>();
  closed = output<void>();
  pagoRegistrado = output<void>();

  private service = inject(CuentasCobrarService);
  private voucherService = inject(VoucherService);
  private notif = inject(NotificationService);

  pagos = signal<PagoFactura[]>([]);
  guardando = signal(false);
  voucherSugerencias = signal<Voucher[]>([]);
  voucherSeleccionado = signal<Voucher | null>(null);
  showAutocomplete = signal(false);
  modoEdicion = signal<PagoFactura | null>(null);
  pagoAEliminar = signal<PagoFactura | null>(null);
  justificanteElim = '';

  private voucherSearch$ = new Subject<string>();

  form: PagoForm = {
    voucherNumero: '',
    voucherNumeroOperacion: '',
    voucherMontoTotal: null,
    voucherFecha: new Date().toISOString().split('T')[0],
    montoAplicado: null,
  };

  montoPagadoActual = computed(() =>
    this.pagos().filter(p => p.isActive).reduce((acc, p) => acc + p.montoAplicado, 0)
  );

  montoPendienteActual = computed(() => {
    const total = this.cuenta().total ?? 0;
    return Math.max(0, total - this.montoPagadoActual());
  });

  maxMontoAplicable = computed(() => {
    const pendiente = this.montoPendienteActual();
    // Si editando, sumar lo que tenía el pago
    const extraEdicion = this.modoEdicion() ? (this.modoEdicion()!.montoAplicado ?? 0) : 0;
    const pendienteReal = pendiente + extraEdicion;
    const disponibleVoucher = this.voucherSeleccionado()?.montoRestante;
    if (disponibleVoucher !== undefined) {
      // Si editando, el disponible es disponibleVoucher + lo que ya tenía este pago
      const disponibleReal = disponibleVoucher + extraEdicion;
      return Math.min(pendienteReal, disponibleReal);
    }
    return pendienteReal;
  });

  formValido = computed(() => {
    if (!this.form.voucherNumero.trim()) return false;
    if (!this.form.montoAplicado || this.form.montoAplicado <= 0) return false;
    if (!this.modoEdicion() && !this.voucherSeleccionado() && !this.form.voucherMontoTotal) return false;
    return true;
  });

  ngOnInit(): void {
    this.cargarPagos();
    this.voucherSearch$.pipe(debounceTime(300), distinctUntilChanged()).subscribe(q => {
      if (q.length >= 1 && this.cuenta().clienteId) {
        this.voucherService.search(this.cuenta().clienteId!, q).subscribe({
          next: res => {
            this.voucherSugerencias.set(res.items ?? []);
            this.showAutocomplete.set(true);
          }
        });
      } else {
        this.voucherSugerencias.set([]);
        this.showAutocomplete.set(false);
      }
    });
  }

  private cargarPagos(): void {
    this.service.getPagosByFactura(this.cuenta().id).subscribe({
      next: res => this.pagos.set(res.items ?? [])
    });
  }

  onVoucherNumeroChange(value: string): void {
    this.voucherSeleccionado.set(null);
    this.voucherSearch$.next(value.trim());
  }

  seleccionarVoucher(v: Voucher): void {
    this.voucherSeleccionado.set(v);
    this.form.voucherNumero = v.numero;
    this.form.voucherNumeroOperacion = v.numeroOperacion ?? '';
    this.showAutocomplete.set(false);
    this.voucherSugerencias.set([]);
    // Pre-rellenar monto: mínimo entre disponible del voucher y pendiente de factura
    this.form.montoAplicado = Math.min(v.montoRestante, this.montoPendienteActual());
  }

  iniciarEdicion(pago: PagoFactura): void {
    this.pagoAEliminar.set(null);
    this.modoEdicion.set(pago);
    this.form.voucherNumero = pago.voucherNumero ?? '';
    this.form.voucherNumeroOperacion = pago.voucherNumeroOperacion ?? '';
    this.form.montoAplicado = pago.montoAplicado;
    // Simular el voucher seleccionado para mostrar info
    if (pago.voucherId) {
      this.voucherSeleccionado.set({
        id: pago.voucherId,
        numero: pago.voucherNumero ?? '',
        numeroOperacion: pago.voucherNumeroOperacion,
        montoTotal: pago.voucherMontoTotal ?? 0,
        montoRestante: (pago.voucherMontoRestante ?? 0) + pago.montoAplicado,
        montoUsado: (pago.voucherMontoUsado ?? 0) - pago.montoAplicado,
        fecha: pago.voucherFecha ?? '',
        isActive: true,
      });
    }
  }

  cancelarEdicion(): void {
    this.modoEdicion.set(null);
    this.resetForm();
  }

  iniciarEliminacion(pago: PagoFactura): void {
    this.modoEdicion.set(null);
    this.pagoAEliminar.set(pago);
    this.justificanteElim = '';
  }

  cancelarEliminacion(): void {
    this.pagoAEliminar.set(null);
    this.justificanteElim = '';
  }

  confirmarEliminacion(): void {
    const pago = this.pagoAEliminar();
    if (!pago || !this.justificanteElim.trim()) return;
    this.guardando.set(true);
    this.service.deletePago(pago.id, { justificante: this.justificanteElim.trim() }).subscribe({
      next: () => {
        this.notif.success('Pago anulado');
        this.pagoAEliminar.set(null);
        this.justificanteElim = '';
        this.guardando.set(false);
        this.cargarPagos();
        this.pagoRegistrado.emit(); // refrescar lista principal
      },
      error: () => {
        this.notif.error('Error al anular el pago');
        this.guardando.set(false);
      }
    });
  }

  guardar(): void {
    if (!this.formValido() || this.guardando()) return;
    this.guardando.set(true);

    const edicion = this.modoEdicion();

    if (edicion) {
      this.service.updatePago(edicion.id, {
        montoAplicado: this.form.montoAplicado!,
        voucherNumeroOperacion: this.form.voucherNumeroOperacion || undefined,
      }).subscribe({
        next: () => {
          this.notif.success('Pago actualizado');
          this.cancelarEdicion();
          this.guardando.set(false);
          this.cargarPagos();
          this.pagoRegistrado.emit();
        },
        error: (err) => {
          this.notif.error(err?.error?.message ?? 'Error al actualizar');
          this.guardando.set(false);
        }
      });
    } else {
      this.service.createPago({
        facturaId: this.cuenta().id,
        montoAplicado: this.form.montoAplicado!,
        voucherNumero: this.form.voucherNumero.trim(),
        voucherNumeroOperacion: this.form.voucherNumeroOperacion || undefined,
        voucherMontoTotal: this.voucherSeleccionado() ? undefined : (this.form.voucherMontoTotal ?? undefined),
        voucherFecha: this.voucherSeleccionado() ? undefined : this.form.voucherFecha,
      }).subscribe({
        next: () => {
          this.notif.success('Pago registrado');
          this.resetForm();
          this.guardando.set(false);
          this.cargarPagos();
          this.pagoRegistrado.emit();
        },
        error: (err) => {
          this.notif.error(err?.error?.message ?? 'Error al registrar el pago');
          this.guardando.set(false);
        }
      });
    }
  }

  private resetForm(): void {
    this.form = {
      voucherNumero: '',
      voucherNumeroOperacion: '',
      voucherMontoTotal: null,
      voucherFecha: new Date().toISOString().split('T')[0],
      montoAplicado: null,
    };
    this.voucherSeleccionado.set(null);
    this.voucherSugerencias.set([]);
    this.showAutocomplete.set(false);
    this.modoEdicion.set(null);
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    this.showAutocomplete.set(false);
  }

  onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      this.closed.emit();
    }
  }
}
