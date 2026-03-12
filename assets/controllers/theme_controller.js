import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["icon"];

    connect() {
        this.updateTheme();
    }

    toggle() {
        if (document.documentElement.classList.contains('dark')) {
            localStorage.theme = 'light';
        } else {
            localStorage.theme = 'dark';
        }
        this.updateTheme();
    }

    updateTheme() {
        document.documentElement.classList.remove('dark');
        this.setLightIcon();
    }

    setDarkIcon() {
        // We can toggle icons if we have targets
        this.element.querySelector('[data-theme-icon="sun"]').classList.remove('hidden');
        this.element.querySelector('[data-theme-icon="moon"]').classList.add('hidden');
    }

    setLightIcon() {
        this.element.querySelector('[data-theme-icon="sun"]').classList.add('hidden');
        this.element.querySelector('[data-theme-icon="moon"]').classList.remove('hidden');
    }
}
