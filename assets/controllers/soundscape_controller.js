import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { progress: Number };

    connect() {
        this.audio = new Audio('/sounds/ambient.mp3');
        this.audio.loop = true;
        // Ajuster la vitesse selon le progrès
        this.audio.playbackRate = 0.8 + (this.progressValue / 200);
        this.audio.play();
    }
}
