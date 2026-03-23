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
      { label: 'Dashboard', icon: 'dashboard', path: 'dashboard', roles: ['ROLE_USER', 'ROLE_ADMIN', 'KNUTO_ROLE'] },
    ]
  },
  {
    blockTitle: 'Operaciones',
    items: [
      { label: 'Productores', icon: 'producers', path: 'productores', roles: ['KNUTO_ROLE', 'ROLE_ADMIN'] },
      { label: 'SENASA', icon: 'senasa', path: 'senasa', roles: ['KNUTO_ROLE', 'ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Administración',
    items: [
      { label: 'Usuarios', icon: 'people', path: 'users', roles: ['ROLE_ADMIN'] },
      { label: 'Configuración', icon: 'settings', path: 'settings', roles: ['ROLE_ADMIN'] },
    ]
  }
];
