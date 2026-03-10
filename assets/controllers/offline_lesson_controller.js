import { Controller } from "@hotwired/stimulus";
import OfflineLessonManager from "../lib/OfflineLessonManager.js";

export default class extends Controller {
    static values = {
        lessonId: String,
        title: String,
        content: String,
    };

    static targets = ["offlineBtn", "offlineStatus"];

    initialize() {
        this.manager = new OfflineLessonManager();
    }

    async connect() {
        await this.checkStatus();

        // Check if we are offline
        if (!navigator.onLine) {
            this.handleOffline();
        }

        window.addEventListener('offline', () => this.handleOffline());
        window.addEventListener('online', () => this.handleOnline());
    }

    async checkStatus() {
        const isSaved = await this.manager.hasLesson(this.lessonIdValue);
        if (isSaved) {
            this.offlineBtnTarget.innerHTML = `
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" stroke="none"><path d="M5 13l4 4L19 7" /></svg>
        ${window.translations?.saved_offline || 'Sauvegardé'}
      `;
            this.offlineBtnTarget.classList.add('bg-green-100', 'text-green-700', 'border-green-200');
        }
    }

    async toggleOffline(event) {
        event.preventDefault();
        const isSaved = await this.manager.hasLesson(this.lessonIdValue);

        if (isSaved) {
            if (confirm(window.translations?.remove_offline_confirm || 'Supprimer de la consultation hors-ligne ?')) {
                await this.manager.deleteLesson(this.lessonIdValue);
                window.location.reload();
            }
        } else {
            const lessonData = {
                title: this.titleValue,
                content: this.contentValue,
                courseTitle: document.querySelector('h3.font-serif')?.innerText || '',
            };
            await this.manager.storeLesson(this.lessonIdValue, lessonData);
            await this.checkStatus();
            this.showFlash(window.translations?.lesson_saved_success || 'Leçon enregistrée pour consultation hors-ligne.');
        }
    }

    handleOffline() {
        this.offlineStatusTarget?.classList.remove('hidden');
        document.body.classList.add('is-offline');
    }

    handleOnline() {
        this.offlineStatusTarget?.classList.add('hidden');
        document.body.classList.remove('is-offline');
    }

    showFlash(message) {
        // Basic flash implementation if notice component is not available
        const flash = document.createElement('div');
        flash.className = 'fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-full shadow-2xl z-50 animate-bounce';
        flash.innerText = message;
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 3000);
    }
}
