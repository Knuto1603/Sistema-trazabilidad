import { Component, input, output, inject, signal, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CuentaCobrar } from '@core/models/core.model';
import { CuentasCobrarService } from '../../cuentas-cobrar.service';
import { NotificationService } from '@core/services/notification.service';

@Component({
  selector: 'app-factura-detail-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" (click)="onBackdropClick($event)">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" (click)="$event.stopPropagation()">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ cuenta().numeroDocumento }}</h2>
            <p class="text-sm text-gray-500">{{ cuenta().clienteRazonSocial }}</p>
          </div>
          <button (click)="closed.emit()" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Estado badge -->
        <div class="px-6 pt-4 flex items-center gap-3">
          <span [class]="estadoBadgeClass()">{{ cuenta().estado }}</span>
          @if (cuenta().tipoServicio) {
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
              {{ cuenta().tipoServicio }}
            </span>
          }
        </div>

        <!-- Datos del documento -->
        <div class="px-6 py-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Datos del documento</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-xs text-gray-400">Tipo documento</span>
              <p class="font-medium text-gray-800">{{ cuenta().tipoDocumento }}</p>
            </div>
            <div>
              <span class="text-xs text-gray-400">Fecha emisión</span>
              <p class="font-medium text-gray-800">{{ cuenta().fechaEmision }}</p>
            </div>
            <div>
              <span class="text-xs text-gray-400">Fecha vencimiento</span>
              <p class="font-medium" [class]="vencimientoClass()">{{ cuenta().fechaVencimiento ?? '—' }}</p>
            </div>
            @if (cuenta().contenedor) {
              <div>
                <span class="text-xs text-gray-400">Contenedor</span>
                <p class="font-medium text-gray-800">{{ cuenta().contenedor }}</p>
              </div>
            }
            @if (cuenta().numeroGuia) {
              <div>
                <span class="text-xs text-gray-400">N° Guía</span>
                <p class="font-medium text-gray-800">{{ cuenta().numeroGuia }}</p>
              </div>
            }
            @if (cuenta().destino) {
              <div>
                <span class="text-xs text-gray-400">Destino</span>
                <p class="font-medium text-gray-800">{{ cuenta().destino }}</p>
              </div>
            }
          </div>
        </div>

        <!-- Detalle de la línea -->
        @if (cuenta().detalle || cuenta().cajas || cuenta().importe) {
          <div class="px-6 pb-4 border-t border-gray-100 pt-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Detalle de la línea</h3>
            @if (cuenta().detalle) {
              <p class="text-sm text-gray-700 mb-3">{{ cuenta().detalle }}</p>
            }
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
              @if (cuenta().cajas) {
                <div>
                  <span class="text-xs text-gray-400">Cajas</span>
                  <p class="font-medium text-gray-800">{{ cuenta().cajas | number }}</p>
                </div>
              }
              @if (cuenta().kgCaja) {
                <div>
                  <span class="text-xs text-gray-400">Kg/Caja</span>
                  <p class="font-medium text-gray-800">{{ cuenta().kgCaja | number:'1.3-3' }}</p>
                </div>
              }
              @if (cuenta().importe) {
                <div>
                  <span class="text-xs text-gray-400">Importe</span>
                  <p class="font-medium text-gray-800">{{ cuenta().moneda }} {{ cuenta().importe | number:'1.2-2' }}</p>
                </div>
              }
              @if (cuenta().igv) {
                <div>
                  <span class="text-xs text-gray-400">IGV</span>
                  <p class="font-medium text-gray-800">{{ cuenta().moneda }} {{ cuenta().igv | number:'1.2-2' }}</p>
                </div>
              }
            </div>
          </div>
        }

        <!-- Totales y pagos -->
        <div class="px-6 pb-4 border-t border-gray-100 pt-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Resumen de cobro</h3>
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <span class="text-xs text-gray-400 block mb-1">Total factura</span>
              <p class="font-semibold text-gray-900">{{ cuenta().moneda }} {{ (cuenta().total ?? 0) | number:'1.2-2' }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
              <span class="text-xs text-gray-400 block mb-1">Pagado</span>
              <p class="font-semibold text-green-700">{{ cuenta().moneda }} {{ cuenta().montoPagado | number:'1.2-2' }}</p>
            </div>
            <div class="rounded-lg p-3 text-center" [class]="cuenta().montoPendiente > 0 ? 'bg-red-50' : 'bg-gray-50'">
              <span class="text-xs text-gray-400 block mb-1">Pendiente</span>
              <p class="font-semibold" [class]="cuenta().montoPendiente > 0 ? 'text-red-600' : 'text-gray-400'">
                {{ cuenta().moneda }} {{ cuenta().montoPendiente | number:'1.2-2' }}
              </p>
            </div>
          </div>
          @if (cuenta().tipoCambio) {
            <p class="text-xs text-gray-400 mt-2">T/C: {{ cuenta().tipoCambio | number:'1.3-3' }}</p>
          }
        </div>

        <!-- Historial de pagos -->
        @if (cuenta().pagos.length > 0) {
          <div class="px-6 pb-4 border-t border-gray-100 pt-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Historial de pagos</h3>
            <div class="space-y-2">
              @for (pago of cuenta().pagos; track pago.id) {
                <div class="rounded-lg text-xs"
                     [class]="pago.isActive ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200 opacity-60'">
                  <div class="flex items-start justify-between p-3">
                    <div class="flex-1">
                      <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-900">Voucher: {{ pago.voucherNumero }}</span>
                        @if (!pago.isActive) {
                          <span class="px-1.5 py-0.5 bg-red-100 text-red-600 rounded">ANULADO</span>
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
                        <button (click)="iniciarDesligar(pago.id)" title="Desligar voucher de esta factura"
                          class="text-red-400 hover:text-red-600 transition-colors shrink-0">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                          </svg>
                        </button>
                      }
                    </div>
                  </div>
                  @if (pagoDesligando() === pago.id) {
                    <div class="border-t border-green-200 px-3 pb-3 pt-2 flex flex-col sm:flex-row gap-2 items-start sm:items-center">
                      <input type="text" [(ngModel)]="justificanteDesligar"
                        placeholder="Motivo del desvinculado (requerido)"
                        class="flex-1 text-xs border border-red-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-red-400"/>
                      <div class="flex gap-2 shrink-0">
                        <button (click)="confirmarDesligar()"
                          [disabled]="!justificanteDesligar.trim() || guardandoDesligar()"
                          class="px-3 py-1.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs rounded-lg transition-colors">
                          @if (guardandoDesligar()) { Desligando... } @else { Confirmar }
                        </button>
                        <button (click)="cancelarDesligar()"
                          class="px-3 py-1.5 border border-gray-300 text-gray-600 text-xs rounded-lg hover:bg-gray-100 transition-colors">
                          Cancelar
                        </button>
                      </div>
                    </div>
                  }
                </div>
              }
            </div>
          </div>
        } @else {
          <div class="px-6 pb-4 border-t border-gray-100 pt-4 text-center text-gray-400 text-sm">
            Sin pagos registrados
          </div>
        }

        <!-- Despacho / Operación -->
        <div class="px-6 pb-6 border-t border-gray-100 pt-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Despacho</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-xs text-gray-400">Sede</span>
              <p class="font-medium text-gray-800">{{ cuenta().sede ?? '—' }}</p>
            </div>
            @if (cuenta().despachoNumero) {
              <div>
                <span class="text-xs text-gray-400">N° Despacho</span>
                <p class="font-medium text-gray-800">{{ cuenta().despachoNumero }}</p>
              </div>
            }
            @if (cuenta().operacionNombre) {
              <div>
                <span class="text-xs text-gray-400">Operación</span>
                <p class="font-medium text-gray-800">{{ cuenta().operacionNombre }}</p>
              </div>
            }
            <div>
              <span class="text-xs text-gray-400">Cliente</span>
              <p class="font-medium text-gray-800">{{ cuenta().clienteRuc }}</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  `
})
export class FacturaDetailModalComponent {
  cuenta = input.required<CuentaCobrar>();
  closed = output<void>();
  pagoAnulado = output<void>();

  private service = inject(CuentasCobrarService);
  private notif = inject(NotificationService);

  pagoDesligando = signal<string | null>(null);
  justificanteDesligar = '';
  guardandoDesligar = signal(false);

  iniciarDesligar(pagoId: string): void {
    this.pagoDesligando.set(pagoId);
    this.justificanteDesligar = '';
  }

  cancelarDesligar(): void {
    this.pagoDesligando.set(null);
    this.justificanteDesligar = '';
  }

  confirmarDesligar(): void {
    const pagoId = this.pagoDesligando();
    if (!pagoId || !this.justificanteDesligar.trim()) return;
    this.guardandoDesligar.set(true);
    this.service.deletePago(pagoId, { justificante: this.justificanteDesligar.trim() }).subscribe({
      next: () => {
        this.notif.success('Voucher desligado de la factura');
        this.pagoDesligando.set(null);
        this.justificanteDesligar = '';
        this.guardandoDesligar.set(false);
        this.pagoAnulado.emit();
      },
      error: (err) => {
        this.notif.error(err?.error?.message ?? 'Error al desligar el voucher');
        this.guardandoDesligar.set(false);
      }
    });
  }

  estadoBadgeClass(): string {
    const base = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ';
    switch (this.cuenta().estado) {
      case 'PAGADO':  return base + 'bg-green-100 text-green-700';
      case 'VENCIDA': return base + 'bg-red-100 text-red-700';
      default:        return base + 'bg-yellow-100 text-yellow-700';
    }
  }

  vencimientoClass(): string {
    const venc = this.cuenta().fechaVencimiento;
    if (!venc || this.cuenta().estado === 'PAGADO') return 'text-gray-800';
    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
    const d = new Date(venc + 'T00:00:00');
    if (d < hoy) return 'text-red-600 font-semibold';
    if ((d.getTime() - hoy.getTime()) / 86400000 <= 7) return 'text-orange-500 font-semibold';
    return 'text-gray-800';
  }

  @HostListener('document:keydown.escape')
  onEsc(): void { this.closed.emit(); }

  onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) this.closed.emit();
  }
}
