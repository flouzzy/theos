import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.handler = this.handleKeydown.bind(this);
        window.addEventListener('keydown', this.handler);
    }

    disconnect() {
        window.removeEventListener('keydown', this.handler);
    }

    handleKeydown(event) {
        // Don't trigger if user is typing in an input or textarea
        const activeElement = document.activeElement;
        const isInput = activeElement.tagName === 'INPUT' || 
                        activeElement.tagName === 'TEXTAREA' || 
                        activeElement.isContentEditable;
        
        if (isInput) return;

        const video = this.element.querySelector('video');
        if (!video) return;

        switch (event.code) {
            case 'Space':
                event.preventDefault();
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
                break;
            case 'ArrowRight':
                video.currentTime += 5;
                break;
            case 'ArrowLeft':
                video.currentTime -= 5;
                break;
            case 'ArrowUp':
                event.preventDefault();
                video.volume = Math.min(1, video.volume + 0.1);
                break;
            case 'ArrowDown':
                event.preventDefault();
                video.volume = Math.max(0, video.volume - 0.1);
                break;
            case 'KeyF':
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    this.element.requestFullscreen();
                }
                break;
            case 'KeyP':
                this.togglePiP();
                break;
            case 'Equal': // '+' key
            case 'NumpadAdd':
                video.playbackRate = Math.min(2, video.playbackRate + 0.25);
                break;
            case 'Minus':
            case 'NumpadSubtract':
                video.playbackRate = Math.max(0.5, video.playbackRate - 0.25);
                break;
        }
    async togglePiP() {
        const video = this.element.querySelector('video');
        if (!video) return;

        try {
            if (video !== document.pictureInPictureElement) {
                await video.requestPictureInPicture();
            } else {
                await document.exitPictureInPicture();
            }
        } catch (error) {
            console.error('PiP failed', error);
        }
    }
}
