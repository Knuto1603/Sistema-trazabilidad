import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CampaignService } from '@core/services/campaign.service';
import { AuthService } from '@core/services/auth.service';
import { environment } from '@env/environment';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';

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
  imports: [],
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

  ngOnInit(): void {
    this.loadStats();
  }

  private loadStats(): void {
    const core = environment.coreUrl;
    const security = environment.securityUrl;

    forkJoin({
      campaigns: this.http.get<any>(`${core}/campahnas/?itemsPerPage=1`).pipe(catchError(() => of(null))),
      productores: this.http.get<any>(`${core}/productores/?itemsPerPage=1`).pipe(catchError(() => of(null))),
      frutas: this.http.get<any>(`${core}/frutas/?itemsPerPage=1`).pipe(catchError(() => of(null))),
      users: this.http.get<any>(`${security}/users/?itemsPerPage=1`).pipe(catchError(() => of(null))),
    }).subscribe(results => {
      this.stats.set([
        {
          label: 'Campañas',
          value: results.campaigns?.pagination?.totalItems ?? '-',
          icon: 'calendar',
          color: 'text-blue-700',
          bgColor: 'bg-blue-100'
        },
        {
          label: 'Productores',
          value: results.productores?.pagination?.totalItems ?? '-',
          icon: 'users',
          color: 'text-emerald-700',
          bgColor: 'bg-emerald-100'
        },
        {
          label: 'Frutas',
          value: results.frutas?.pagination?.totalItems ?? '-',
          icon: 'tag',
          color: 'text-orange-700',
          bgColor: 'bg-orange-100'
        },
        {
          label: 'Usuarios',
          value: results.users?.pagination?.totalItems ?? '-',
          icon: 'person',
          color: 'text-violet-700',
          bgColor: 'bg-violet-100'
        },
      ]);
      this.loading.set(false);
    });
  }

  skeletonCards = [1, 2, 3, 4];
}
