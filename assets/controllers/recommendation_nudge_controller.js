import { Controller } from "@hotwired/stimulus";
import DOMPurify from 'dompurify';

export default class extends Controller {
    static values = {
        url: String,
        delay: { type: Number, default: 30000 } // 30 seconds default
    };

    connect() {
        this.timeout = setTimeout(() => {
            this.showNudge();
        }, this.delayValue);
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }

    async showNudge() {
        try {
            const response = await fetch(this.urlValue);
            if (!response.ok) return;
            
            const html = await response.text();
            if (html.trim() === '') return;

            const container = document.createElement('div');
            container.innerHTML = DOMPurify.sanitize(html);
            document.body.appendChild(container.firstElementChild);
        } catch (error) {
            console.error('Failed to load recommendation nudge:', error);
        }
    }
}
