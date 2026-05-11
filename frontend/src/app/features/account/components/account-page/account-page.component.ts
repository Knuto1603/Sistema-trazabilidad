import { Component, inject, signal, OnInit, ChangeDetectionStrategy } from '@angular/core';
import {
  ReactiveFormsModule, FormBuilder, FormGroup, Validators, AbstractControl, ValidationErrors
} from '@angular/forms';
import { forkJoin } from 'rxjs';

import { AccountService, UpdateMeDto, SaveSmtpDto } from '../../account.service';
import { NotificationService } from '@core/services/notification.service';
import { AppUser, UserSmtpConfig } from '@core/models/core.model';

import { ConfirmDialogComponent } from '@shared/components/confirm-dialog/confirm-dialog.component';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

function passwordMatchValidator(control: AbstractControl): ValidationErrors | null {
  const password = control.get('password');
  const confirm = control.get('passwordConfirm');
  if (!password?.value && !confirm?.value) return null;
  return password?.value === confirm?.value ? null : { passwordMismatch: true };
}

@Component({
  selector: 'app-account-page',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, ConfirmDialogComponent, PageHeaderComponent],
  templateUrl: './account-page.component.html',
})
export class AccountPageComponent implements OnInit {
  private accountService      = inject(AccountService);
  private notificationService = inject(NotificationService);
  private fb                  = inject(FormBuilder);

  activeTab      = signal<'perfil' | 'correo'>('perfil');
  isLoading      = signal(false);
  isSavingProfile = signal(false);
  isSavingSmtp   = signal(false);
  smtpConfig     = signal<UserSmtpConfig | null>(null);
  currentUser    = signal<AppUser | null>(null);
  showClearConfirm = signal(false);

  profileForm!: FormGroup;
  smtpForm!: FormGroup;

  ngOnInit(): void {
    this.initForms();
    this.loadData();
  }

  private initForms(): void {
    this.profileForm = this.fb.group({
      fullname: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
      password: ['', [Validators.minLength(6), Validators.maxLength(100)]],
      passwordConfirm: [''],
    }, { validators: passwordMatchValidator });

    this.smtpForm = this.fb.group({
      smtpEmail:    ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
      smtpPassword: [''],
      displayName:  ['', [Validators.maxLength(150)]],
      firmaNombre:  ['', [Validators.maxLength(150)]],
      firmaCargo:   ['', [Validators.maxLength(100)]],
      firmaEmpresa: ['', [Validators.maxLength(150)]],
      ccEmails:     [''],
    });
  }

  private loadData(): void {
    this.isLoading.set(true);
    forkJoin({
      me:   this.accountService.getMe(),
      smtp: this.accountService.getSmtpConfig(),
    }).subscribe({
      next: ({ me, smtp }) => {
        if (me.item) {
          this.currentUser.set(me.item);
          this.profileForm.patchValue({ fullname: me.item.fullname });
        }
        const config = smtp.item ?? null;
        this.smtpConfig.set(config);
        if (config) {
          this.smtpForm.patchValue({
            smtpEmail:    config.smtpEmail,
            displayName:  config.displayName ?? '',
            firmaNombre:  config.firmaNombre ?? '',
            firmaCargo:   config.firmaCargo ?? '',
            firmaEmpresa: config.firmaEmpresa ?? '',
            ccEmails:     config.ccEmails ?? '',
          });
        }
        this.updateSmtpPasswordValidator();
        this.isLoading.set(false);
      },
      error: () => {
        this.notificationService.error('Error al cargar datos de la cuenta.');
        this.isLoading.set(false);
      },
    });
  }

  private updateSmtpPasswordValidator(): void {
    const passCtrl = this.smtpForm.get('smtpPassword');
    if (!passCtrl) return;
    if (this.smtpConfig() === null) {
      passCtrl.setValidators([Validators.required, Validators.minLength(4), Validators.maxLength(100)]);
    } else {
      passCtrl.setValidators([Validators.minLength(4), Validators.maxLength(100)]);
    }
    passCtrl.updateValueAndValidity();
  }

  setTab(tab: 'perfil' | 'correo'): void {
    this.activeTab.set(tab);
  }

  get passwordValue(): string {
    return this.profileForm.get('password')?.value ?? '';
  }

  saveProfile(): void {
    if (this.profileForm.invalid) {
      this.profileForm.markAllAsTouched();
      return;
    }
    const { fullname, password } = this.profileForm.value;
    const payload: UpdateMeDto = { fullname };
    if (password) {
      payload.password = password;
      payload.passwordConfirm = this.profileForm.get('passwordConfirm')?.value;
    }
    this.isSavingProfile.set(true);
    this.accountService.updateMe(payload).subscribe({
      next: res => {
        if (res.item) this.currentUser.set(res.item);
        this.profileForm.patchValue({ password: '', passwordConfirm: '' });
        this.notificationService.success('Perfil actualizado correctamente.');
        this.isSavingProfile.set(false);
      },
      error: () => {
        this.notificationService.error('Error al actualizar el perfil.');
        this.isSavingProfile.set(false);
      },
    });
  }

  saveSmtp(): void {
    this.updateSmtpPasswordValidator();
    if (this.smtpForm.invalid) {
      this.smtpForm.markAllAsTouched();
      return;
    }
    const { smtpEmail, smtpPassword, displayName, firmaNombre, firmaCargo, firmaEmpresa, ccEmails } = this.smtpForm.value;
    const payload: SaveSmtpDto = {
      smtpEmail,
      displayName:  displayName || null,
      firmaNombre:  firmaNombre || null,
      firmaCargo:   firmaCargo || null,
      firmaEmpresa: firmaEmpresa || null,
      ccEmails:     ccEmails || null,
    };
    if (smtpPassword) payload.smtpPassword = smtpPassword;

    this.isSavingSmtp.set(true);
    this.accountService.saveSmtpConfig(payload).subscribe({
      next: res => {
        if (res.item) this.smtpConfig.set(res.item);
        this.smtpForm.patchValue({ smtpPassword: '' });
        this.updateSmtpPasswordValidator();
        this.notificationService.success('Configuración SMTP guardada.');
        this.isSavingSmtp.set(false);
      },
      error: () => {
        this.notificationService.error('Error al guardar la configuración SMTP.');
        this.isSavingSmtp.set(false);
      },
    });
  }

  confirmClear(): void {
    this.showClearConfirm.set(true);
  }

  cancelClear(): void {
    this.showClearConfirm.set(false);
  }

  clearSmtp(): void {
    this.showClearConfirm.set(false);
    this.accountService.clearSmtpConfig().subscribe({
      next: () => {
        this.smtpConfig.set(null);
        this.smtpForm.reset();
        this.updateSmtpPasswordValidator();
        this.notificationService.success('Configuración SMTP eliminada.');
      },
      error: () => {
        this.notificationService.error('Error al eliminar la configuración SMTP.');
      },
    });
  }

  fieldError(form: FormGroup, field: string, error: string): boolean {
    const ctrl = form.get(field);
    return !!(ctrl?.hasError(error) && ctrl?.touched);
  }
}
