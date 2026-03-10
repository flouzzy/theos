import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        key: String
    };

    connect() {
        if (!this.keyValue) {
            this.keyValue = window.location.pathname;
        }
        this.restore();
    }

    save() {
        const formData = {};
        const inputs = this.element.querySelectorAll('textarea, input:not([type="hidden"])');
        
        inputs.forEach(input => {
            if (input.name) {
                formData[input.name] = input.value;
            }
        });

        localStorage.setItem(`autosave_${this.keyValue}`, JSON.stringify(formData));
        
        // Show a small indicator if needed
        console.log('Draft autosaved');
    }

    restore() {
        const savedData = localStorage.getItem(`autosave_${this.keyValue}`);
        if (savedData) {
            const formData = JSON.parse(savedData);
            const inputs = this.element.querySelectorAll('textarea, input:not([type="hidden"])');
            
            inputs.forEach(input => {
                if (input.name && formData[input.name]) {
                    input.value = formData[input.name];
                    // Trigger input event for potential other listeners
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }
    }

    clear() {
        localStorage.removeItem(`autosave_${this.keyValue}`);
    }
}
