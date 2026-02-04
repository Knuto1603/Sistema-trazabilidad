import { Component, signal , OnInit} from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { LoadingScreenComponent } from "@shared/loading-screen/loading-screen.component";
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, LoadingScreenComponent, CommonModule],
  templateUrl: './app.component.html'
})
export class AppComponent implements OnInit {
  title = 'trazabilidad-frontend';
  isCheckingSession = signal(true);
  
  ngOnInit() {
    this.isCheckingSession.set(false);
  }
}
