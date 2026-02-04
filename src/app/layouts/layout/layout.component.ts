import { Component, signal, inject, computed, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { SIDEBAR_MENU, MenuBlock } from '@core/constants/menu.config';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './layout.component.html',
  styleUrl: './layout.component.css'
})
export class LayoutComponent {
  private authService = inject(AuthService);
  private router = inject(Router);

  // Estados del Layout
  isCollapsed = signal(false); // Sidebar en Desktop
  isMobileOpen = signal(false); // Sidebar en Móviles
  isProfileOpen = signal(false); // Desplegable de usuario

  // Datos reactivos del usuario
  user = computed(() => this.authService.currentUser());

  // Filtrado de menú por roles dinámicos
 menuItems = computed(() => {
    return SIDEBAR_MENU.map(block => ({
      ...block,
      items: block.items.filter(item => 
        item.roles.some(role => this.authService.hasRole(role)) // ← Agregar return implícito
      )
    })).filter(block => block.items.length > 0);
  });

  // Listener para cerrar el desplegable al hacer clic fuera
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('#profile-dropdown-wrapper')) {
      this.isProfileOpen.set(false);
    }
  }

  toggleSidebar() {
    this.isCollapsed.update(v => !v);
  }

  toggleMobileMenu() {
    this.isMobileOpen.update(v => !v);
  }

  toggleProfileMenu(event: Event) {
    event.stopPropagation();
    this.isProfileOpen.update(v => !v);
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/auth/login']);
  }
}