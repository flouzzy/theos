import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["fill"];
    static values = { percentage: Number };

    connect() {
        const p = this.percentageValue;
        // Effet d'accélération exponentielle au-dessus de 80%
        // Si p=90, on affiche 80 + 10*1.5 = 95%
        const displayWidth = p > 80 ? Math.min(100, 80 + (p - 80) * 1.5) : p;
        
        this.fillTarget.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
        this.fillTarget.style.width = `${displayWidth}%`;
    }
}
