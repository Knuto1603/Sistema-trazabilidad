export interface MenuItem {
  label: string;
  icon: string;
  path: string;
  moduleKey: string;
}

export interface MenuBlock {
  blockTitle: string;
  items: MenuItem[];
}

// Para agregar un nuevo módulo: 1) añadir la clave en modules.config.ts, 2) agregar el ítem aquí.
export const SIDEBAR_MENU: MenuBlock[] = [
  {
    blockTitle: 'General',
    items: [
      { label: 'Dashboard',         icon: 'dashboard',  path: '/app/dashboard',                moduleKey: 'dashboard' },
    ]
  },
  {
    blockTitle: 'Operaciones',
    items: [
      { label: 'Productores',       icon: 'producers',  path: '/app/productores',              moduleKey: 'productores' },
      { label: 'SENASA',            icon: 'senasa',     path: '/app/senasa',                   moduleKey: 'senasa' },
    ]
  },
  {
    blockTitle: 'Facturación',
    items: [
      { label: 'Despachos',         icon: 'despachos',  path: '/app/facturacion/despachos',    moduleKey: 'despachos' },
      { label: 'Clientes',          icon: 'clients',    path: '/app/facturacion/clientes',     moduleKey: 'clientes' },
      { label: 'Tipo de Cambio',    icon: 'currency',   path: '/app/facturacion/tipo-cambio',  moduleKey: 'tipo_cambio' },
      { label: 'Reporte',           icon: 'report',     path: '/app/facturacion/reporte',      moduleKey: 'reporte' },
    ]
  },
  {
    blockTitle: 'Cuentas por Cobrar',
    items: [
      { label: 'Cuentas por Cobrar', icon: 'accounts', path: '/app/cuentas-cobrar', moduleKey: 'cuentas_cobrar' },
      { label: 'Vouchers',           icon: 'currency',  path: '/app/vouchers',       moduleKey: 'cuentas_cobrar' },
    ]
  },
  {
    blockTitle: 'Administración',
    items: [
      { label: 'Usuarios',          icon: 'people',     path: '/app/users',                    moduleKey: 'usuarios' },
      { label: 'Configuración',     icon: 'settings',   path: '/app/settings',                 moduleKey: 'configuracion' },
    ]
  },
  {
    blockTitle: 'Developer',
    items: [
      { label: 'Panel Developer',   icon: 'dev',        path: '/app/dev',                      moduleKey: 'developer' },
    ]
  }
];
