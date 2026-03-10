import { Controller } from '@hotwired/stimulus';
import { marked } from 'marked';

export default class extends Controller {
    static targets = ["input", "preview", "editorContainer", "previewContainer"];

    connect() {
        this.isAperçu = false;
    }

    togglePreview() {
        this.isAperçu = !this.isAperçu;
        
        if (this.isAperçu) {
            this.showPreview();
        } else {
            this.showEditor();
        }
    }

    showPreview() {
        const content = this.inputTarget.value;
        this.previewTarget.innerHTML = marked.parse(content || '*Rien à afficher*');
        
        this.editorContainerTarget.classList.add('hidden');
        this.previewContainerTarget.classList.remove('hidden');
        this.element.querySelector('[data-mode="edit"]').classList.remove('text-primary', 'border-primary');
        this.element.querySelector('[data-mode="preview"]').classList.add('text-primary', 'border-primary');
    }

    showEditor() {
        this.editorContainerTarget.classList.remove('hidden');
        this.previewContainerTarget.classList.add('hidden');
        this.element.querySelector('[data-mode="preview"]').classList.remove('text-primary', 'border-primary');
        this.element.querySelector('[data-mode="edit"]').classList.add('text-primary', 'border-primary');
    }
}
