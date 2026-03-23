import { Injectable, signal, inject, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment.development';
import { Campaign } from '../models/core.model';

@Injectable({ providedIn: 'root' })
export class CampaignService {
  private http = inject(HttpClient);
  private url = `${environment.coreUrl}/campahnas`;

  #campaigns = signal<Campaign[]>([]);
  #activeCampaign = signal<Campaign | null>(null);

  campaigns = this.#campaigns.asReadonly();
  activeCampaign = this.#activeCampaign.asReadonly();
  activeCampaignId = computed(() => this.#activeCampaign()?.id ?? null);

  constructor() {
    this.loadCampaigns();
  }

  loadCampaigns() {
    this.http.get<{ status: boolean; items: Campaign[] }>(`${this.url}/shared`)
      .subscribe(res => {
        if (res.status) {
          this.#campaigns.set(res.items);
          this.restoreOrSelectFirst(res.items);
        }
      });
  }

  setActiveCampaign(campaign: Campaign) {
    this.#activeCampaign.set(campaign);
    localStorage.setItem('activeCampaignId', campaign.id);
  }

  private restoreOrSelectFirst(campaigns: Campaign[]) {
    const savedId = localStorage.getItem('activeCampaignId');
    const found = savedId ? campaigns.find(c => c.id === savedId) : null;
    const selected = found ?? campaigns[0] ?? null;
    if (selected) {
      this.#activeCampaign.set(selected);
      localStorage.setItem('activeCampaignId', selected.id);
    }
  }
}
