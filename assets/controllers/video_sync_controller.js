import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["player"];

    seek(event) {
        event.preventDefault();
        const timestamp = event.currentTarget.dataset.timestamp;
        const video = document.querySelector('video');
        
        if (video && timestamp) {
            // Support both "MM:SS" and seconds formats
            let seconds = 0;
            if (timestamp.includes(':')) {
                const parts = timestamp.split(':');
                seconds = parseInt(parts[0]) * 60 + parseInt(parts[1]);
            } else {
                seconds = parseInt(timestamp);
            }
            
            video.currentTime = seconds;
            video.play();
            
            // Scroll video into view if needed
            video.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}
