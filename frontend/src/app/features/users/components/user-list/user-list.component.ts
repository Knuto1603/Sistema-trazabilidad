import {
  Component, inject, signal, computed, OnInit, OnDestroy
} from '@angular/core';
import {
  ReactiveFormsModule, FormBuilder, FormGroup, Validators, AbstractControl
} from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Subject, debounceTime, distinctUntilChanged, takeUntil } from 'rxjs';

import { UserService } from '../../user.service';
import { AuthService } from '@core/services/auth.service';
import { NotificationService } from '@core/services/notification.service';
import { Pagination } from '@core/models/api.model';
import { AppUser } from '@core/models/core.model';

import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

interface AvailableRole {
  id: string;
  name: string;
}

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    PaginationComponent,
    ConfirmDialogComponent,
    PageHeaderComponent,
  ],
  templateUrl: './user-list.component.html',
})
export class UserListComponent implements OnInit, OnDestroy {
  private userService = inject(UserService);
  private authService = inject(AuthService);
  private notificationService = inject(NotificationService);
  private fb = inject(FormBuilder);
  private destroy$ = new Subject<void>();

  // State
  users = signal<AppUser[]>([]);
  pagination = signal<Pagination>({
    page: 0, itemsPerPage: 15, count: 0,
    totalItems: 0, startIndex: 0, endIndex: 0
  });
  isLoading = signal(false);
  searchTerm = signal('');
  availableRoles = signal<AvailableRole[]>([]);

  // Modal state
  showModal = signal(false);
  isEditing = signal(false);
  editingUser = signal<AppUser | null>(null);
  isSaving = signal(false);

  // Delete confirm
  showDeleteConfirm = signal(false);
  deletingUser = signal<AppUser | null>(null);

  // Toggle loading per user id
  togglingId = signal<string | null>(null);

  isAdmin = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  // Search subject for debounce
  private searchSubject = new Subject<string>();

  // Form
  userForm!: FormGroup;

  ngOnInit(): void {
    this.buildForm();
    this.loadRoles();
    this.loadUsers();

    this.searchSubject.pipe(
      debounceTime(400),
      distinctUntilChanged(),
      takeUntil(this.destroy$)
    ).subscribe(term => {
      this.searchTerm.set(term);
      this.loadUsers(0);
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private buildForm(): void {
    this.userForm = this.fb.group({
      username: ['', [Validators.required, Validators.minLength(4), Validators.maxLength(30)]],
      fullname: ['', [Validators.required, Validators.minLength(4), Validators.maxLength(100)]],
      password: ['', [Validators.minLength(6)]],
      roles: [[] as string[]],
    });
  }

  loadRoles(): void {
    this.userService.getAvailableRoles().subscribe({
      next: res => {
        if (res.status) {
          this.availableRoles.set(res.items);
        }
      },
      error: () => {
        this.notificationService.error('No se pudieron cargar los roles disponibles.');
      }
    });
  }

  loadUsers(page?: number): void {
    this.isLoading.set(true);
    const currentPage = page !== undefined ? page : this.pagination().page;

    this.userService.getAll({
      page: currentPage,
      itemsPerPage: this.pagination().itemsPerPage,
      search: this.searchTerm() || undefined,
    }).subscribe({
      next: res => {
        this.users.set(res.items);
        this.pagination.set(res.pagination);
        this.isLoading.set(false);
      },
      error: () => {
        this.notificationService.error('Error al cargar los usuarios.');
        this.isLoading.set(false);
      }
    });
  }

  onSearch(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.searchSubject.next(value);
  }

  onPageChange(page: number): void {
    this.loadUsers(page);
  }

  // ---- Role helpers ----

  getRoleName(roleId: string): string {
    const role = this.availableRoles().find(r => r.id === roleId);
    return role ? role.name : roleId;
  }

  isRoleChecked(roleId: string): boolean {
    const selectedRoles: string[] = this.userForm.get('roles')?.value ?? [];
    return selectedRoles.includes(roleId);
  }

  onRoleToggle(roleId: string, checked: boolean): void {
    const currentRoles: string[] = this.userForm.get('roles')?.value ?? [];
    let newRoles: string[];
    if (checked) {
      newRoles = [...currentRoles, roleId];
    } else {
      newRoles = currentRoles.filter((id: string) => id !== roleId);
    }
    this.userForm.get('roles')?.setValue(newRoles);
  }

  // ---- Modal ----

  openCreateModal(): void {
    this.isEditing.set(false);
    this.editingUser.set(null);
    this.userForm.reset({ username: '', fullname: '', password: '', roles: [] });

    // Password required for create
    this.userForm.get('password')?.setValidators([Validators.required, Validators.minLength(6)]);
    this.userForm.get('password')?.updateValueAndValidity();

    this.showModal.set(true);
  }

  openEditModal(user: AppUser): void {
    this.isEditing.set(true);
    this.editingUser.set(user);

    // Password optional for edit
    this.userForm.get('password')?.setValidators([Validators.minLength(6)]);
    this.userForm.get('password')?.updateValueAndValidity();

    this.userForm.patchValue({
      username: user.username,
      fullname: user.fullname,
      password: '',
      roles: [...user.roles],
    });

    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
    this.isSaving.set(false);
  }

  saveUser(): void {
    if (this.userForm.invalid) {
      this.userForm.markAllAsTouched();
      return;
    }

    this.isSaving.set(true);
    const { username, fullname, password, roles } = this.userForm.value;

    if (this.isEditing()) {
      const id = this.editingUser()!.id;
      const data: { username: string; fullname: string; password?: string; roles: string[] } = {
        username, fullname, roles: roles ?? []
      };
      if (password && password.trim().length > 0) {
        data.password = password;
      }

      this.userService.update(id, data).subscribe({
        next: res => {
          if (res.status) {
            this.notificationService.success('Usuario actualizado correctamente.');
            this.closeModal();
            this.loadUsers();
          } else {
            this.notificationService.error(res.message ?? 'Error al actualizar el usuario.');
            this.isSaving.set(false);
          }
        },
        error: () => {
          this.notificationService.error('Error al actualizar el usuario.');
          this.isSaving.set(false);
        }
      });
    } else {
      this.userService.create({ username, fullname, password, roles: roles ?? [] }).subscribe({
        next: res => {
          if (res.status) {
            this.notificationService.success('Usuario creado correctamente.');
            this.closeModal();
            this.loadUsers(0);
          } else {
            this.notificationService.error(res.message ?? 'Error al crear el usuario.');
            this.isSaving.set(false);
          }
        },
        error: () => {
          this.notificationService.error('Error al crear el usuario.');
          this.isSaving.set(false);
        }
      });
    }
  }

  // ---- Toggle active ----

  toggleUser(user: AppUser): void {
    this.togglingId.set(user.id);
    const action$ = user.isActive
      ? this.userService.disable(user.id)
      : this.userService.enable(user.id);

    action$.subscribe({
      next: res => {
        if (res.status) {
          const label = user.isActive ? 'desactivado' : 'activado';
          this.notificationService.success(`Usuario ${label} correctamente.`);
          this.loadUsers();
        } else {
          this.notificationService.error(res.message ?? 'Error al cambiar el estado.');
        }
        this.togglingId.set(null);
      },
      error: () => {
        this.notificationService.error('Error al cambiar el estado del usuario.');
        this.togglingId.set(null);
      }
    });
  }

  // ---- Delete ----

  confirmDelete(user: AppUser): void {
    this.deletingUser.set(user);
    this.showDeleteConfirm.set(true);
  }

  cancelDelete(): void {
    this.showDeleteConfirm.set(false);
    this.deletingUser.set(null);
  }

  executeDelete(): void {
    const user = this.deletingUser();
    if (!user) return;

    this.userService.delete(user.id).subscribe({
      next: res => {
        if (res.status) {
          this.notificationService.success('Usuario eliminado correctamente.');
          this.loadUsers();
        } else {
          this.notificationService.error(res.message ?? 'Error al eliminar el usuario.');
        }
        this.cancelDelete();
      },
      error: () => {
        this.notificationService.error('Error al eliminar el usuario.');
        this.cancelDelete();
      }
    });
  }

  // ---- Form field helpers ----

  fieldInvalid(name: string): boolean {
    const ctrl = this.userForm.get(name);
    return !!(ctrl && ctrl.invalid && ctrl.touched);
  }

  fieldError(name: string, error: string): boolean {
    const ctrl = this.userForm.get(name);
    return !!(ctrl && ctrl.hasError(error) && ctrl.touched);
  }
}
