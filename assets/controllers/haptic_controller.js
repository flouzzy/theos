import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    vibrate() {
        if ("vibrate" in navigator) {
            navigator.vibrate(100);
        }
    }
}
