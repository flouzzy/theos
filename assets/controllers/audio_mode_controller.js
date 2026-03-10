import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["videoContainer", "audioPlayer"];

    connect() {
        this.isAudioMode = false;
    }

    toggle() {
        this.isAudioMode = !this.isAudioMode;
        
        if (this.isAudioMode) {
            this.enableAudioMode();
        } else {
            this.disableAudioMode();
        }
    }

    enableAudioMode() {
        const video = this.videoContainerTarget.querySelector('video');
        if (video) {
            const currentTime = video.currentTime;
            video.pause();
            
            if (this.hasAudioPlayerTarget) {
                this.audioPlayerTarget.currentTime = currentTime;
                this.audioPlayerTarget.play();
                
                // Show a placeholder or just hide video
                this.videoContainerTarget.classList.add('hidden');
                
                // Scroll to audio player or highlight it
                this.audioPlayerTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    disableAudioMode() {
        if (this.hasAudioPlayerTarget) {
            const currentTime = this.audioPlayerTarget.currentTime;
            this.audioPlayerTarget.pause();
            
            this.videoContainerTarget.classList.remove('hidden');
            const video = this.videoContainerTarget.querySelector('video');
            if (video) {
                video.currentTime = currentTime;
                video.play();
            }
        }
    }
}
