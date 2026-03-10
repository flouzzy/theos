import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        topic: String,
    }

    static targets = ['messages']

    connect() {
        const url = new URL('https://localhost:8096/.well-known/mercure');
        url.searchParams.append('topic', this.topicValue);

        this.eventSource = new EventSource(url);
        this.eventSource.onmessage = (event) => {
            // Trigger LiveComponent refresh
            // The LiveComponent automatically listens for real-time updates if we use #[LiveListener]
            // or we can manually trigger it.
            // For simplicity with Symfony UX, we can just use the built-in refreshing logic or a custom event.
            this.dispatch('message-received', { detail: JSON.parse(event.data) });

            // Or just call the component's render method via LiveComponent's internal API if available
            // But usually, we just let the LiveComponent handle it if configured.

            // Since we want to scroll to bottom after refresh:
            setTimeout(() => this.scrollToBottom(), 100);
        };

        this.scrollToBottom();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    scrollToBottom() {
        if (this.hasMessagesTarget) {
            this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
        }
    }
}
