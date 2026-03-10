import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        enabled: Boolean
    };

    connect() {
        if (!this.enabledValue) return;
        
        this.video = this.element.querySelector('video');
        if (this.video) {
            this.video.onended = () => this.playNext();
        }
    }

    playNext() {
        const nextBtn = document.querySelector('[data-auto-play-target="nextButton"]');
        if (nextBtn) {
            // Give user a small delay/indicator? 
            // For now, direct redirect
            nextBtn.click();
        }
    }
}
