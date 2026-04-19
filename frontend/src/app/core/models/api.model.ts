export interface Pagination {
  page: number; 
  itemsPerPage: number; 
  count: number;
  totalItems: number; 
  startIndex: number; 
  endIndex: number;
}

export interface ApiResponse<T> { 
  status: boolean; 
  message?: string; 
  item?: T; 
}

export interface ApiListResponse<T> { 
  status: boolean; 
  items: T[]; 
  pagination: Pagination; 
}

export interface SharedItem { 
  id: string; 
  name: string; 
}

export interface FilterParams {
  page?: number; 
  itemsPerPage?: number; 
  search?: string; sort?: 
  string; direction?: string;
}
