// Campaña
export interface Campaign {
  id: string; nombre: string; descripcion?: string;
  fechaInicio: string; fechaFin?: string;
  frutaId: string; frutaNombre: string; nombreCompleto: string; isActive: boolean;
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
