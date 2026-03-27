import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        nextUrl: String,
        prevUrl: String
    };

    connect() {
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        
        // Augmentation de la distance minimale pour plus de tolérance (100px au lieu de 70px)
        this.minSwipeDistance = 100;
        // Ratio minimum entre l'horizontal et le vertical pour confirmer l'intention de swipe
        this.swipeRatio = 2.0; 

        this.startHandler = this.touchStart.bind(this);
        this.endHandler = this.touchEnd.bind(this);

        this.element.addEventListener('touchstart', this.startHandler, { passive: true });
        this.element.addEventListener('touchend', this.endHandler, { passive: true });
    }

    disconnect() {
        this.element.removeEventListener('touchstart', this.startHandler);
        this.element.removeEventListener('touchend', this.endHandler);
    }

    touchStart(event) {
        this.touchStartX = event.changedTouches[0].screenX;
        this.touchStartY = event.changedTouches[0].screenY;
    }

    touchEnd(event) {
        this.touchEndX = event.changedTouches[0].screenX;
        this.touchEndY = event.changedTouches[0].screenY;
        this.handleSwipe();
    }

    handleSwipe() {
        const diffX = this.touchEndX - this.touchStartX;
        const diffY = this.touchEndY - this.touchStartY;
        
        const absX = Math.abs(diffX);
        const absY = Math.abs(diffY);

        // 1. Vérifier si la distance est suffisante
        if (absX < this.minSwipeDistance) return;

        // 2. Vérifier si c'est bien un mouvement horizontal (plus horizontal que vertical)
        // On exige que le mouvement X soit au moins 2 fois plus grand que le mouvement Y
        if (absX < absY * this.swipeRatio) {
            // C'est probablement un scroll vertical, on ignore
            return;
        }

        if (diffX < 0 && this.nextUrlValue) {
            // Swipe Left -> Next
            this.navigate(this.nextUrlValue);
        } else if (diffX > 0 && this.prevUrlValue) {
            // Swipe Right -> Prev
            this.navigate(this.prevUrlValue);
        }
    }

    navigate(url) {
        // Optionnel : ajouter un petit feedback visuel ou loader avant de partir
        window.location.href = url;
    }
}
