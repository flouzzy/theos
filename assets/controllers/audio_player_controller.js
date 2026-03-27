import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { src: String };

    play() {
        const audio = new Audio(this.srcValue);
        audio.play().catch(e => console.error("Audio playback failed:", e));
    }
}
