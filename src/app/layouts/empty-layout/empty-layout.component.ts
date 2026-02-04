import { Component } from '@angular/core';
import { RouterModule } from "@angular/router";
import { LoadingScreenComponent } from "@shared/loading-screen/loading-screen.component";

@Component({
  selector: 'app-empty-layout',
  imports: [RouterModule, LoadingScreenComponent],
  templateUrl: './empty-layout.component.html',
  styleUrl: './empty-layout.component.css'
})
export class EmptyLayoutComponent {

}
