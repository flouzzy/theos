import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        nextUrl: String,
        prevUrl: String
    };

    connect() {
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.minSwipeDistance = 70;

        this.startHandler = this.touchStart.bind(this);
        this.endHandler = this.touchEnd.bind(this);

        this.element.addEventListener('touchstart', this.startHandler, false);
        this.element.addEventListener('touchend', this.endHandler, false);
    }

    disconnect() {
        this.element.removeEventListener('touchstart', this.startHandler);
        this.element.removeEventListener('touchend', this.endHandler);
    }

    touchStart(event) {
        this.touchStartX = event.changedTouches[0].screenX;
    }

    touchEnd(event) {
        this.touchEndX = event.changedTouches[0].screenX;
        this.handleSwipe();
    }

    handleSwipe() {
        const distance = this.touchEndX - this.touchStartX;
        
        if (Math.abs(distance) < this.minSwipeDistance) return;

        if (distance < 0 && this.nextUrlValue) {
            // Swipe Left -> Next
            window.location.href = this.nextUrlValue;
        } else if (distance > 0 && this.prevUrlValue) {
            // Swipe Right -> Prev
            window.location.href = this.prevUrlValue;
        }
    }
}
