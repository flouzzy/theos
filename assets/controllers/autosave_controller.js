import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { key: String };

    connect() {
        if (!this.hasKeyValue) return;
        const saved = localStorage.getItem(this.keyValue);
        if (saved) this.element.value = saved;
        
        this.element.addEventListener('input', () => {
            localStorage.setItem(this.keyValue, this.element.value);
        });
        
        this.element.closest('form').addEventListener('submit', () => {
            localStorage.removeItem(this.keyValue);
        });
    }
}
