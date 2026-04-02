// Campaña
export interface Campaign {
  id: string; nombre: string; descripcion?: string;
  fechaInicio: string; fechaFin?: string;
  frutaId: string; frutaNombre: string; nombreCompleto: string;
  sede?: 'SULLANA' | 'TAMBOGRANDE' | 'GENERAL';
  isActive: boolean;
}

// Operación (por sede)
export interface Operacion {
  id: string; nombre: string; sede: 'SULLANA' | 'TAMBOGRANDE' | 'GENERAL'; isActive: boolean;
}

// Fruta (NO tiene update/delete en backend, solo create + enable/disable)
export interface Fruit {
  id: string; codigo: string; nombre: string; isActive: boolean;
}

// Parámetro (árbol: parentId puede ser null)
export interface Parameter {
  id: string; name: string; alias: string; value: number | string | null;
  parentId?: string; parentName?: string; isActive: boolean;
}

// Productor (campahnaId es requerido para crear, tiene campos de contexto de campaña)
export interface Producer {
  id: string; codigo: string; nombre: string;
  clp?: string; mtdCeratitis?: string; mtdAnastrepha?: string;
  nombreProductor?: string;
  direccion?: string; departamento?: string; provincia?: string;
  distrito?: string; zona?: string; sector?: string; subsector?: string;
  campahnaId?: string; campahnaName?: string;
  frutaName?: string; periodoName?: string; isActive: boolean;
}

// Usuario (roles son UUIDs de UserRole)
export interface AppUser {
  id: string; username: string; fullname: string;
  roles: string[]; photo?: string; photoUrl?: string; isActive: boolean;
}

// Rol de usuario
export interface UserRole {
  id: string; name: string; alias: string;
  userIds?: string[]; userCount?: number; isActive: boolean;
}

export interface Cliente {
  id: string; ruc: string; razonSocial: string; nombreComercial?: string;
  direccion?: string; departamento?: string; provincia?: string; distrito?: string;
  estado?: string; condicion?: string; tipoContribuyente?: string;
  telefono?: string; email?: string; emailDestinatarios?: string; isActive: boolean;
}

export interface TipoCambio {
  id: string; fecha: string; compra: number; venta: number; isActive: boolean;
}

export interface Despacho {
  id: string; numeroCliente: number; numeroPlanta?: number; sede: 'SULLANA' | 'TAMBOGRANDE' | 'GENERAL';
  contenedor?: string; observaciones?: string;
  clienteId: string; clienteRuc?: string; clienteRazonSocial?: string;
  frutaId: string; frutaNombre?: string;
  operacionId?: string; operacionNombre?: string;
  isActive: boolean;
}

export interface Factura {
  id: string; tipoDocumento: string; serie: string; correlativo: string;
  numeroDocumento: string; numeroGuia?: string; fechaEmision: string;
  moneda: string; detalle?: string; kgCaja?: number; unidadMedida?: string;
  cajas?: number; cantidad?: number; valorUnitario?: number;
  importe?: number; igv?: number; total?: number; tipoCambio?: number;
  tipoServicio?: string; tipoOperacion?: string; isAnulada: boolean;
  contenedor?: string; destino?: string;
  despachoId: string; despachoNumero?: number; clienteRazonSocial?: string; isActive: boolean;
}

export interface ArchivoDespacho {
  id: string; nombre: string;
  tipoArchivo: 'FACTURA_XML' | 'GUIA_XML' | 'FACTURA_PDF' | 'GUIA_PDF' | 'PACKING_LIST' | 'CDR' | 'OTRO';
  ruta: string; tamanho: number; despachoId: string; facturaId?: string; isActive: boolean;
}
