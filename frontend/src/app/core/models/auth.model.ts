export interface User {
  id: string;
  username: string;
  fullname: string;
  roles: string[];
  avatar: string | null;
}

export interface LoginResponse {
  token: string;
  status: boolean;
  user: User;
}