export interface ModuleDefinition {
  key: string;
  label: string;
  group: string;
}

export const SYSTEM_MODULES: ModuleDefinition[] = [
  { key: 'dashboard',      label: 'Dashboard',          group: 'General' },
  { key: 'productores',    label: 'Productores',         group: 'Operaciones' },
  { key: 'senasa',         label: 'SENASA',              group: 'Operaciones' },
  { key: 'despachos',      label: 'Despachos',           group: 'Facturación' },
  { key: 'clientes',       label: 'Clientes',            group: 'Facturación' },
  { key: 'tipo_cambio',    label: 'Tipo de Cambio',      group: 'Facturación' },
  { key: 'reporte',        label: 'Reporte',             group: 'Facturación' },
  { key: 'cuentas_cobrar', label: 'Cuentas por Cobrar',  group: 'Cobrar' },
  { key: 'usuarios',       label: 'Usuarios',            group: 'Administración' },
  { key: 'configuracion',  label: 'Configuración',       group: 'Administración' },
  { key: 'developer',      label: 'Panel Developer',     group: 'Developer' },
];

export const MODULE_GROUPS = [...new Set(SYSTEM_MODULES.map(m => m.group))];

export function getModulesByGroup(): { group: string; mods: ModuleDefinition[] }[] {
  return MODULE_GROUPS.map(group => ({
    group,
    mods: SYSTEM_MODULES.filter(m => m.group === group),
  }));
}
