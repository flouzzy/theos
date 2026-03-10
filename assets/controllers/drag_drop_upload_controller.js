import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["input", "preview", "container"];

    connect() {
        this.containerTarget.addEventListener('dragover', this.handleDragOver.bind(this));
        this.containerTarget.addEventListener('dragleave', this.handleDragLeave.bind(this));
        this.containerTarget.addEventListener('drop', this.handleDrop.bind(this));
    }

    handleDragOver(event) {
        event.preventDefault();
        this.containerTarget.classList.add('border-primary', 'bg-primary/5');
    }

    handleDragLeave() {
        this.containerTarget.classList.remove('border-primary', 'bg-primary/5');
    }

    handleDrop(event) {
        event.preventDefault();
        this.handleDragLeave();
        
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.inputTarget.files = files;
            this.updatePreview(files[0]);
        }
    }

    updatePreview(file) {
        if (this.hasPreviewTarget) {
            this.previewTarget.innerHTML = `
                <div class="flex items-center gap-2 text-primary font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-2.828-6.828l-6.414 6.586a6 6 0 008.486 8.486L20.5 13" />
                    </svg>
                    <span>${file.name}</span>
                    <span class="text-xs text-muted-foreground">(${(file.size / 1024).toFixed(1)} KB)</span>
                </div>
            `;
        }
    }

    selectFile() {
        this.inputTarget.click();
    }

    onInputChange() {
        if (this.inputTarget.files.length > 0) {
            this.updatePreview(this.inputTarget.files[0]);
        }
    }
}
