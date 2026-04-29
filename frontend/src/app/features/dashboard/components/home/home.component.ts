import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { CampaignService } from '@core/services/campaign.service';
import { AuthService } from '@core/services/auth.service';
import { environment } from '@env/environment';
import { forkJoin, Observable, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';

interface StatDefinition {
  key: string;
  moduleKey: string | null; // null = siempre visible
  label: string;
  icon: string;
  color: string;
  bgColor: string;
  fetch$: () => Observable<number | string>;
}

interface DashboardStat {
  label: string;
  value: number | string;
  icon: string;
  color: string;
  bgColor: string;
}

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './home.component.html',
  styleUrl: './home.component.css'
})
export class HomeComponent implements OnInit {
  private http = inject(HttpClient);
  private campaignService = inject(CampaignService);
  private authService = inject(AuthService);

  loading = signal(true);
  stats = signal<DashboardStat[]>([]);

  activeCampaign = computed(() => this.campaignService.activeCampaign());
  userName = computed(() => this.authService.currentUser()?.fullname ?? this.authService.currentUser()?.username ?? 'Usuario');

  private core = environment.coreUrl;
  private security = environment.securityUrl;

  private totalItems = (url: string): Observable<number | string> =>
    this.http.get<any>(url).pipe(
      map(r => r?.pagination?.totalItems ?? '-'),
      catchError(() => of('-'))
    );

  private readonly STAT_CATALOG: StatDefinition[] = [
    {
      key: 'campahnas',
      moduleKey: null,
      label: 'Campañas',
      icon: 'calendar',
      color: 'text-blue-700',
      bgColor: 'bg-blue-100',
      fetch$: () => this.totalItems(`${this.core}/campahnas/?itemsPerPage=1`),
    },
    {
      key: 'productores',
      moduleKey: 'productores',
      label: 'Productores',
      icon: 'users',
      color: 'text-emerald-700',
      bgColor: 'bg-emerald-100',
      fetch$: () => this.totalItems(`${this.core}/productores/?itemsPerPage=1`),
    },
    {
      key: 'clientes',
      moduleKey: 'clientes',
      label: 'Clientes',
      icon: 'building',
      color: 'text-cyan-700',
      bgColor: 'bg-cyan-100',
      fetch$: () => this.totalItems(`${this.core}/clientes/?itemsPerPage=1`),
    },
    {
      key: 'despachos',
      moduleKey: 'despachos',
      label: 'Despachos',
      icon: 'truck',
      color: 'text-indigo-700',
      bgColor: 'bg-indigo-100',
      fetch$: () => this.totalItems(`${this.core}/despachos/?itemsPerPage=1`),
    },
    {
      key: 'cuentas_cobrar',
      moduleKey: 'cuentas_cobrar',
      label: 'Cuentas por Cobrar',
      icon: 'money',
      color: 'text-amber-700',
      bgColor: 'bg-amber-100',
      fetch$: () => this.totalItems(`${this.core}/facturas/?itemsPerPage=1&isAnulada=false`),
    },
    {
      key: 'frutas',
      moduleKey: 'configuracion',
      label: 'Frutas',
      icon: 'tag',
      color: 'text-orange-700',
      bgColor: 'bg-orange-100',
      fetch$: () => this.totalItems(`${this.core}/frutas/?itemsPerPage=1`),
    },
    {
      key: 'usuarios',
      moduleKey: 'usuarios',
      label: 'Usuarios',
      icon: 'person',
      color: 'text-violet-700',
      bgColor: 'bg-violet-100',
      fetch$: () => this.totalItems(`${this.security}/users/?itemsPerPage=1`),
    },
  ];

  ngOnInit(): void {
    this.loadStats();
  }

  private loadStats(): void {
    const visible = this.STAT_CATALOG.filter(s =>
      s.moduleKey === null || this.authService.hasModule(s.moduleKey)
    );

    if (visible.length === 0) {
      this.loading.set(false);
      return;
    }

    const requests: Record<string, Observable<number | string>> = {};
    visible.forEach(s => requests[s.key] = s.fetch$());

    forkJoin(requests).subscribe(results => {
      this.stats.set(
        visible.map(s => ({
          label: s.label,
          value: results[s.key],
          icon: s.icon,
          color: s.color,
          bgColor: s.bgColor,
        }))
      );
      this.loading.set(false);
    });
  }

  skeletonCount = computed(() => {
    const count = this.STAT_CATALOG.filter(s =>
      s.moduleKey === null || this.authService.hasModule(s.moduleKey)
    ).length;
    return Array.from({ length: count || 2 }, (_, i) => i);
  });
}
