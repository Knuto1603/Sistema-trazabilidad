import { Component, signal, inject, computed, HostListener, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router, NavigationStart, NavigationEnd, NavigationCancel, NavigationError } from '@angular/router';
import { Subscription } from 'rxjs';
import { AuthService } from '@core/services/auth.service';
import { CampaignService } from '@core/services/campaign.service';
import { SIDEBAR_MENU, MenuBlock } from '@core/constants/menu.config';
import { NotificationComponent } from '@shared/components/notification/notification.component';
import { IconComponent } from '@shared/components/icon/icon.component';
import { LoadingScreenComponent } from '@shared/loading-screen/loading-screen.component';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterModule, NotificationComponent, IconComponent, LoadingScreenComponent],
  templateUrl: './layout.component.html',
  styleUrl: './layout.component.css'
})
export class LayoutComponent implements OnInit, OnDestroy {
  private authService = inject(AuthService);
  private router = inject(Router);
  campaignService = inject(CampaignService);

  isCollapsed = signal(false);
  isMobileOpen = signal(false);
  isProfileOpen = signal(false);
  isCampaignOpen = signal(false);
  isNavigating = signal(false);

  private routerSub!: Subscription;

  user = computed(() => this.authService.currentUser());

  menuItems = computed(() => {
    return SIDEBAR_MENU.map(block => ({
      ...block,
      items: block.items.filter(item =>
        item.roles.some(role => this.authService.hasRole(role))
      )
    })).filter(block => block.items.length > 0);
  });

  ngOnInit() {
    this.routerSub = this.router.events.subscribe(event => {
      if (event instanceof NavigationStart) {
        this.isNavigating.set(true);
      } else if (event instanceof NavigationEnd || event instanceof NavigationCancel || event instanceof NavigationError) {
        this.isNavigating.set(false);
      }
    });
  }

  ngOnDestroy() {
    this.routerSub?.unsubscribe();
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('#profile-dropdown-wrapper')) {
      this.isProfileOpen.set(false);
    }
    if (!target.closest('#campaign-dropdown-wrapper')) {
      this.isCampaignOpen.set(false);
    }
  }

  toggleSidebar() { this.isCollapsed.update(v => !v); }
  toggleMobileMenu() { this.isMobileOpen.update(v => !v); }
  toggleProfileMenu(event: Event) { event.stopPropagation(); this.isProfileOpen.update(v => !v); }
  toggleCampaignMenu(event: Event) { event.stopPropagation(); this.isCampaignOpen.update(v => !v); }

  selectCampaign(campaign: any, event: Event) {
    event.stopPropagation();
    this.campaignService.setActiveCampaign(campaign);
    this.isCampaignOpen.set(false);
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/auth/login']);
  }
}
