import { Injectable } from '@angular/core';
import * as XLSX from 'xlsx';
import { CuentaCobrar } from '@core/models/core.model';

@Injectable({ providedIn: 'root' })
export class CuentasCobrarExportService {

  exportToExcel(items: CuentaCobrar[], filename = 'cuentas-cobrar'): void {
    const rows = this.buildRows(items);
    const ws = XLSX.utils.json_to_sheet(rows);
    this.applyColumnWidths(ws);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Cuentas por Cobrar');
    XLSX.writeFile(wb, `${filename}_${this.dateStamp()}.xlsx`);
  }

  private buildRows(items: CuentaCobrar[]): object[] {
    const rows: object[] = [];

    for (const item of items) {
      const base = {
        'RUC Cliente':        item.clienteRuc ?? '',
        'Razón Social':       item.clienteRazonSocial ?? '',
        'Tipo Documento':     item.tipoDocumento,
        'N° Factura':         item.numeroDocumento,
        'N° Guía':            item.numeroGuia ?? '',
        'Sede':               item.sede ?? '',
        'Operación':          item.operacionNombre ?? '',
        'Contenedor':         item.contenedor ?? '',
        'Fecha Emisión':      item.fechaEmision,
        'Fecha Vencimiento':  item.fechaVencimiento ?? '',
        'Moneda':             item.moneda,
        'Total Factura':      item.total ?? 0,
        'Monto Pagado':       item.montoPagado,
        'Monto Pendiente':    item.montoPendiente,
        'Estado':             item.estado,
      };

      const pagosActivos = item.pagos.filter(p => p.isActive);

      if (pagosActivos.length === 0) {
        rows.push({
          ...base,
          'N° Voucher':             '',
          'N° Operación Voucher':   '',
          'Fecha Voucher':          '',
          'Monto Aplicado':         '',
          'Monto Total Voucher':    '',
        });
      } else {
        for (const pago of pagosActivos) {
          rows.push({
            ...base,
            'N° Voucher':             pago.voucherNumero ?? '',
            'N° Operación Voucher':   pago.voucherNumeroOperacion ?? '',
            'Fecha Voucher':          pago.voucherFecha ?? '',
            'Monto Aplicado':         pago.montoAplicado,
            'Monto Total Voucher':    pago.voucherMontoTotal ?? '',
          });
        }
      }
    }

    return rows;
  }

  private applyColumnWidths(ws: XLSX.WorkSheet): void {
    ws['!cols'] = [
      { wch: 13 }, // RUC
      { wch: 36 }, // Razón Social
      { wch: 14 }, // Tipo Documento
      { wch: 18 }, // N° Factura
      { wch: 18 }, // N° Guía
      { wch: 12 }, // Sede
      { wch: 22 }, // Operación
      { wch: 16 }, // Contenedor
      { wch: 13 }, // Fecha Emisión
      { wch: 16 }, // Fecha Vencimiento
      { wch: 8  }, // Moneda
      { wch: 13 }, // Total
      { wch: 13 }, // Pagado
      { wch: 14 }, // Pendiente
      { wch: 10 }, // Estado
      { wch: 16 }, // N° Voucher
      { wch: 20 }, // N° Op. Voucher
      { wch: 13 }, // Fecha Voucher
      { wch: 14 }, // Monto Aplicado
      { wch: 16 }, // Monto Total Voucher
    ];
  }

  private dateStamp(): string {
    return new Date().toISOString().slice(0, 10);
  }
}
