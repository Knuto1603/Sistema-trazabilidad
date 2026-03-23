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
      { label: 'Dashboard', icon: 'dashboard', path: '/app/dashboard', roles: ['ROLE_USER', 'ROLE_ADMIN', 'KNUTO_ROLE'] },
    ]
  },
  {
    blockTitle: 'Operaciones',
    items: [
      { label: 'Productores', icon: 'producers', path: '/app/productores', roles: ['KNUTO_ROLE', 'ROLE_ADMIN'] },
      { label: 'SENASA', icon: 'senasa', path: '/app/senasa', roles: ['KNUTO_ROLE', 'ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Administración',
    items: [
      { label: 'Usuarios', icon: 'people', path: '/app/users', roles: ['ROLE_ADMIN'] },
      { label: 'Configuración', icon: 'settings', path: '/app/settings', roles: ['ROLE_ADMIN'] },
    ]
  }
];
