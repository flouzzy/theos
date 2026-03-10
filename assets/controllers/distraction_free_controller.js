import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["sidebar", "header", "content"];

    connect() {
        this.isEnabled = false;
    }

    toggle() {
        this.isEnabled = !this.isEnabled;
        
        // Find syllabus sidebar and header (global)
        const sidebar = document.querySelector('aside.lg\\:block');
        const header = document.querySelector('header.sticky');
        const main = document.querySelector('main.flex-1');

        if (this.isEnabled) {
            if (sidebar) sidebar.classList.add('lg:hidden');
            if (header) header.classList.add('hidden');
            if (main) main.classList.add('!pl-0');
            this.element.classList.add('bg-primary', 'text-white');
        } else {
            if (sidebar) sidebar.classList.remove('lg:hidden');
            if (header) header.classList.remove('hidden');
            if (main) main.classList.remove('!pl-0');
            this.element.classList.remove('bg-primary', 'text-white');
        }
    }
}
