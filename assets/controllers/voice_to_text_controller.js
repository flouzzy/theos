import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["input"];

    connect() {
        this.isRecording = false;
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        if (SpeechRecognition) {
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = false;
            this.recognition.lang = 'fr-FR';

            this.recognition.onresult = (event) => {
                const transcript = event.results[event.results.length - 1][0].transcript;
                this.appendToInput(transcript);
            };

            this.recognition.onerror = (event) => {
                console.error('Speech recognition error', event.error);
                this.stop();
            };

            this.recognition.onend = () => {
                if (this.isRecording) {
                    this.recognition.start();
                }
            };
        } else {
            this.element.classList.add('hidden');
        }
    }

    toggle() {
        if (this.isRecording) {
            this.stop();
        } else {
            this.start();
        }
    }

    start() {
        this.isRecording = true;
        this.recognition.start();
        this.element.classList.add('animate-pulse', 'text-red-500');
    }

    stop() {
        this.isRecording = false;
        this.recognition.stop();
        this.element.classList.remove('animate-pulse', 'text-red-500');
    }

    appendToInput(text) {
        const input = document.querySelector('[data-model="newNoteContent"]');
        if (input) {
            const currentValue = input.value;
            input.value = currentValue ? currentValue + ' ' + text : text;
            // Trigger input event for LiveComponent/Alpine
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}
