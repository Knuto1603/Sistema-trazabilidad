export interface MenuItem {
  label: string;
  icon: string;
  path: string;
  roles: string[];
}

export interface MenuBlock {
  blockTitle: string;
  items: MenuItem[];
}

export const SIDEBAR_MENU: MenuBlock[] = [
  {
    blockTitle: 'General',
    items: [
      { label: 'Dashboard', icon: 'dashboard', path: '/app/dashboard', roles: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_KNUTO'] },
    ]
  },
  {
    blockTitle: 'Operaciones',
    items: [
      { label: 'Productores', icon: 'producers', path: '/app/productores', roles: ['ROLE_KNUTO', 'ROLE_ADMIN'] },
      { label: 'SENASA', icon: 'senasa', path: '/app/senasa', roles: ['ROLE_KNUTO', 'ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Facturación',
    items: [
      { label: 'Despachos', icon: 'despachos', path: '/app/facturacion/despachos', roles: ['ROLE_KNUTO', 'ROLE_ADMIN'] },
      { label: 'Clientes', icon: 'clients', path: '/app/facturacion/clientes', roles: ['ROLE_KNUTO', 'ROLE_ADMIN'] },
      { label: 'Tipo de Cambio', icon: 'currency', path: '/app/facturacion/tipo-cambio', roles: ['ROLE_ADMIN'] },
      { label: 'Reporte', icon: 'report', path: '/app/facturacion/reporte', roles: ['ROLE_KNUTO', 'ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Cuentas por Cobrar',
    items: [
      { label: 'Cuentas por Cobrar', icon: 'accounts', path: '/app/cuentas-cobrar', roles: ['ROLE_KNUTO', 'ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Administración',
    items: [
      { label: 'Usuarios', icon: 'people', path: '/app/users', roles: ['ROLE_ADMIN'] },
      { label: 'Configuración', icon: 'settings', path: '/app/settings', roles: ['ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Developer',
    items: [
      { label: 'Panel Developer', icon: 'dev', path: '/app/dev', roles: ['ROLE_KNUTO'] },
    ]
  }
];
