export interface MenuItem {
  label: string;
  icon: string; // Usaremos SVGs inline para evitar dependencias pesadas
  path: string;
  roles: string[]; // Nombres lógicos como 'ROLE_ADMIN', 'KNUTO_ROLE', 'ROLE_USER'
}

export interface MenuBlock {
  blockTitle: string;
  items: MenuItem[];
}

/**
 * Definición centralizada del menú de navegación.
 * Se filtra dinámicamente en el componente según los roles del usuario.
 */
export const SIDEBAR_MENU: MenuBlock[] = [
  {
    blockTitle: 'General',
    items: [
      { label: 'Dashboard', icon: 'dashboard', path: 'dashboard', roles: ['ROLE_USER', 'ROLE_ADMIN', 'Uzt4j6AdNhNdzjBdP2THQK'] },
    ]
  },
  {
    blockTitle: 'Operaciones',
    items: [
      { label: 'Trazabilidad', icon: 'route', path: 'trazabilidad', roles: ['KNUTO_ROLE', 'ROLE_ADMIN'] },
      { label: 'Lotes de Fruta', icon: 'inventory', path: 'lotes', roles: ['KNUTO_ROLE', 'ROLE_ADMIN'] },
    ]
  },
  {
    blockTitle: 'Seguridad',
    items: [
      { label: 'Usuarios', icon: 'people', path: 'users', roles: ['ROLE_ADMIN'] },
      { label: 'Configuración', icon: 'settings', path: 'settings', roles: ['ROLE_ADMIN'] },
    ]
  }
];