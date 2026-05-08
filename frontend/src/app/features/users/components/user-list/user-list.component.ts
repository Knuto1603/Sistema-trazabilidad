import {
  Component, inject, signal, computed, OnInit, OnDestroy, ChangeDetectionStrategy
} from '@angular/core';
import {
  ReactiveFormsModule, FormBuilder, FormGroup, Validators
} from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Subject, debounceTime, distinctUntilChanged, takeUntil, forkJoin } from 'rxjs';

import { UserService } from '../../user.service';
import { UserSmtpConfigService } from '../../user-smtp-config.service';
import { AuthService } from '@core/services/auth.service';
import { NotificationService } from '@core/services/notification.service';
import { Pagination } from '@core/models/api.model';
import { AppUser, UserSmtpConfig } from '@core/models/core.model';

import { PaginationComponent } from '@shared/components/pagination/pagination.component';
import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

interface AvailableRole { id: string; name: string; }

@Component({
  selector: 'app-user-list',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, PaginationComponent, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './user-list.component.html',
})
export class UserListComponent implements OnInit, OnDestroy {
  private userService        = inject(UserService);
  private smtpConfigService  = inject(UserSmtpConfigService);
  private authService        = inject(AuthService);
  private notificationService = inject(NotificationService);
  private fb                 = inject(FormBuilder);
  private destroy$           = new Subject<void>();
  private searchSubject      = new Subject<string>();

  users          = signal<AppUser[]>([]);
  pagination     = signal<Pagination>({ page: 0, itemsPerPage: 15, count: 0, totalItems: 0, startIndex: 0, endIndex: 0 });
  isLoading      = signal(false);
  searchTerm     = signal('');
  availableRoles = signal<AvailableRole[]>([]);

  // Modal usuario
  showModal    = signal(false);
  isEditing    = signal(false);
  editingUser  = signal<AppUser | null>(null);
  isSaving     = signal(false);
  userForm!: FormGroup;

  // Modal SMTP
  showSmtpModal     = signal(false);
  smtpUser          = signal<AppUser | null>(null);
  smtpCurrentConfig = signal<UserSmtpConfig | null>(null);
  isSavingSmtp      = signal(false);
  isLoadingSmtp     = signal(false);
  showSmtpPassword  = signal(false);
  smtpForm!: FormGroup;

  // Confirmaciones
  showDeleteConfirm    = signal(false);
  deletingUser         = signal<AppUser | null>(null);
  showClearSmtpConfirm = signal(false);

  togglingId = signal<string | null>(null);
  isAdmin    = computed(() => this.authService.hasRole('ROLE_ADMIN'));

  ngOnInit(): void {
    this.buildForms();
    this.loadRoles();
    this.loadUsers();
    this.searchSubject.pipe(debounceTime(400), distinctUntilChanged(), takeUntil(this.destroy$))
      .subscribe(term => { this.searchTerm.set(term); this.loadUsers(0); });
  }

  ngOnDestroy(): void { this.destroy$.next(); this.destroy$.complete(); }

  private buildForms(): void {
    this.userForm = this.fb.group({
      username: ['', [Validators.required, Validators.minLength(4), Validators.maxLength(30)]],
      fullname: ['', [Validators.required, Validators.minLength(4), Validators.maxLength(100)]],
      password: ['', [Validators.minLength(6)]],
      roles:    [[] as string[]],
    });

    this.smtpForm = this.fb.group({
      smtpEmail:    ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
      smtpPassword: ['', [Validators.required, Validators.minLength(4), Validators.maxLength(100)]],
    });
  }

  // ---- Usuarios ----

  loadRoles(): void {
    this.userService.getAvailableRoles().subscribe({
      next: res => { if (res.status) this.availableRoles.set(res.items); },
      error: () => this.notificationService.error('No se pudieron cargar los roles.'),
    });
  }

  loadUsers(page?: number): void {
    this.isLoading.set(true);
    const currentPage = page !== undefined ? page : this.pagination().page;
    forkJoin({
      users: this.userService.getAll({ page: currentPage, itemsPerPage: this.pagination().itemsPerPage, search: this.searchTerm() || undefined }),
      smtp:  this.smtpConfigService.getAll(),
    }).subscribe({
      next: ({ users, smtp }) => {
        const smtpMap = new Map((smtp.items ?? []).map(s => [s.userUuid, s.smtpEmail]));
        const enriched = users.items.map(u => ({
          ...u,
          hasSmtpConfig: smtpMap.has(u.id),
          smtpEmail: smtpMap.get(u.id) ?? null,
        }));
        this.users.set(enriched);
        this.pagination.set(users.pagination);
        this.isLoading.set(false);
      },
      error: () => { this.notificationService.error('Error al cargar los usuarios.'); this.isLoading.set(false); },
    });
  }

  onSearch(event: Event): void { this.searchSubject.next((event.target as HTMLInputElement).value); }
  onPageChange(page: number): void { this.loadUsers(page); }

  getRoleName(roleId: string): string { return this.availableRoles().find(r => r.id === roleId)?.name ?? roleId; }
  isRoleChecked(roleId: string): boolean { return (this.userForm.get('roles')?.value ?? []).includes(roleId); }

  onRoleToggle(roleId: string, checked: boolean): void {
    const current: string[] = this.userForm.get('roles')?.value ?? [];
    this.userForm.get('roles')?.setValue(checked ? [...current, roleId] : current.filter(id => id !== roleId));
  }

  openCreateModal(): void {
    this.isEditing.set(false);
    this.editingUser.set(null);
    this.userForm.reset({ username: '', fullname: '', password: '', roles: [] });
    this.userForm.get('password')?.setValidators([Validators.required, Validators.minLength(6)]);
    this.userForm.get('password')?.updateValueAndValidity();
    this.showModal.set(true);
  }

  openEditModal(user: AppUser): void {
    this.isEditing.set(true);
    this.editingUser.set(user);
    this.userForm.get('password')?.setValidators([Validators.minLength(6)]);
    this.userForm.get('password')?.updateValueAndValidity();
    this.userForm.patchValue({ username: user.username, fullname: user.fullname, password: '', roles: [...user.roles] });
    this.showModal.set(true);
  }

  closeModal(): void { this.showModal.set(false); this.isSaving.set(false); }

  saveUser(): void {
    if (this.userForm.invalid) { this.userForm.markAllAsTouched(); return; }
    this.isSaving.set(true);
    const { username, fullname, password, roles } = this.userForm.value;

    if (this.isEditing()) {
      const data: { username: string; fullname: string; password?: string; roles: string[] } = { username, fullname, roles: roles ?? [] };
      if (password?.trim()) data.password = password;

      this.userService.update(this.editingUser()!.id, data).subscribe({
        next: res => {
          if (res.status) { this.notificationService.success('Usuario actualizado.'); this.closeModal(); this.loadUsers(); }
          else { this.notificationService.error(res.message ?? 'Error al actualizar.'); this.isSaving.set(false); }
        },
        error: () => { this.notificationService.error('Error al actualizar el usuario.'); this.isSaving.set(false); },
      });
    } else {
      this.userService.create({ username, fullname, password, roles: roles ?? [] }).subscribe({
        next: res => {
          if (res.status) { this.notificationService.success('Usuario creado.'); this.closeModal(); this.loadUsers(0); }
          else { this.notificationService.error(res.message ?? 'Error al crear.'); this.isSaving.set(false); }
        },
        error: () => { this.notificationService.error('Error al crear el usuario.'); this.isSaving.set(false); },
      });
    }
  }

  toggleUser(user: AppUser): void {
    this.togglingId.set(user.id);
    (user.isActive ? this.userService.disable(user.id) : this.userService.enable(user.id)).subscribe({
      next: res => {
        if (res.status) { this.notificationService.success(`Usuario ${user.isActive ? 'desactivado' : 'activado'}.`); this.loadUsers(); }
        else this.notificationService.error(res.message ?? 'Error al cambiar estado.');
        this.togglingId.set(null);
      },
      error: () => { this.notificationService.error('Error al cambiar el estado.'); this.togglingId.set(null); },
    });
  }

  confirmDelete(user: AppUser): void { this.deletingUser.set(user); this.showDeleteConfirm.set(true); }
  cancelDelete(): void { this.showDeleteConfirm.set(false); this.deletingUser.set(null); }

  executeDelete(): void {
    const user = this.deletingUser();
    if (!user) return;
    this.userService.delete(user.id).subscribe({
      next: res => {
        if (res.status) { this.notificationService.success('Usuario eliminado.'); this.loadUsers(); }
        else this.notificationService.error(res.message ?? 'Error al eliminar.');
        this.cancelDelete();
      },
      error: () => { this.notificationService.error('Error al eliminar el usuario.'); this.cancelDelete(); },
    });
  }

  // ---- SMTP Config ----

  openSmtpModal(user: AppUser): void {
    this.smtpUser.set(user);
    this.smtpCurrentConfig.set(null);
    this.showSmtpPassword.set(false);
    this.smtpForm.reset({ smtpEmail: '', smtpPassword: '' });
    this.showSmtpModal.set(true);
    this.isLoadingSmtp.set(true);

    this.smtpConfigService.get(user.id).subscribe({
      next: res => {
        if (res.status && res.item) {
          this.smtpCurrentConfig.set(res.item);
          this.smtpForm.patchValue({ smtpEmail: res.item.smtpEmail, smtpPassword: '' });
          this.smtpForm.get('smtpPassword')?.setValidators([Validators.minLength(4), Validators.maxLength(100)]);
        } else {
          this.smtpForm.get('smtpPassword')?.setValidators([Validators.required, Validators.minLength(4), Validators.maxLength(100)]);
        }
        this.smtpForm.get('smtpPassword')?.updateValueAndValidity();
        this.isLoadingSmtp.set(false);
      },
      error: () => {
        this.smtpForm.get('smtpPassword')?.setValidators([Validators.required, Validators.minLength(4), Validators.maxLength(100)]);
        this.smtpForm.get('smtpPassword')?.updateValueAndValidity();
        this.isLoadingSmtp.set(false);
      },
    });
  }

  closeSmtpModal(): void { this.showSmtpModal.set(false); this.isSavingSmtp.set(false); }

  toggleShowSmtpPassword(): void { this.showSmtpPassword.update(v => !v); }

  saveSmtpConfig(): void {
    if (this.smtpForm.invalid) { this.smtpForm.markAllAsTouched(); return; }
    const { smtpEmail, smtpPassword } = this.smtpForm.value;

    // Nueva config: contraseña requerida
    if (!this.smtpCurrentConfig() && !smtpPassword?.trim()) {
      this.smtpForm.get('smtpPassword')?.setErrors({ required: true });
      this.smtpForm.markAllAsTouched();
      return;
    }

    this.isSavingSmtp.set(true);
    const payload: { smtpEmail: string; smtpPassword?: string } = { smtpEmail };
    if (smtpPassword?.trim()) payload.smtpPassword = smtpPassword;

    this.smtpConfigService.save(this.smtpUser()!.id, payload as { smtpEmail: string; smtpPassword: string }).subscribe({
      next: res => {
        if (res.status) {
          this.notificationService.success(`Correo SMTP configurado para ${this.smtpUser()?.fullname}.`);
          this.updateUserSmtpStatus(this.smtpUser()!.id, smtpEmail, true);
          this.closeSmtpModal();
        } else {
          this.notificationService.error(res.message ?? 'Error al guardar la configuración SMTP.');
          this.isSavingSmtp.set(false);
        }
      },
      error: () => { this.notificationService.error('Error al guardar la configuración SMTP.'); this.isSavingSmtp.set(false); },
    });
  }

  confirmClearSmtp(): void { this.showClearSmtpConfirm.set(true); }
  cancelClearSmtp(): void { this.showClearSmtpConfirm.set(false); }

  executeClearSmtp(): void {
    const user = this.smtpUser();
    if (!user) return;
    this.smtpConfigService.clear(user.id).subscribe({
      next: res => {
        if (res.status) {
          this.notificationService.success('Configuración SMTP eliminada.');
          this.updateUserSmtpStatus(user.id, null, false);
          this.closeSmtpModal();
        } else {
          this.notificationService.error(res.message ?? 'Error al eliminar la configuración.');
        }
        this.showClearSmtpConfirm.set(false);
      },
      error: () => { this.notificationService.error('Error al eliminar la configuración SMTP.'); this.showClearSmtpConfirm.set(false); },
    });
  }

  private updateUserSmtpStatus(userId: string, smtpEmail: string | null, hasSmtpConfig: boolean): void {
    this.users.update(list => list.map(u => u.id === userId ? { ...u, smtpEmail, hasSmtpConfig } : u));
  }

  // ---- Form helpers ----

  fieldInvalid(form: FormGroup, name: string): boolean {
    const ctrl = form.get(name);
    return !!(ctrl?.invalid && ctrl.touched);
  }

  fieldError(form: FormGroup, name: string, error: string): boolean {
    const ctrl = form.get(name);
    return !!(ctrl?.hasError(error) && ctrl.touched);
  }
}
