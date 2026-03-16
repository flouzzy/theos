import { Controller } from '@hotwired/stimulus';
import confetti from 'canvas-confetti';

export default class extends Controller {
    connect() {
        this.fireHandler = this.fire.bind(this);
        this.element.addEventListener('confetti:fire', this.fireHandler);
        window.addEventListener('confetti:fire', this.fireHandler);
    }

    disconnect() {
        this.element.removeEventListener('confetti:fire', this.fireHandler);
        window.removeEventListener('confetti:fire', this.fireHandler);
    }

    fire(event) {
        // Fire confetti!
        var duration = 3 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            var particleCount = 50 * (timeLeft / duration);
            
            // since particles fall down, start a bit higher than random
            confetti({
                ...defaults, particleCount,
                origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
            });
            confetti({
                ...defaults, particleCount,
                origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
            });
        }, 250);

        document.body.classList.add('animate-shake');
        setTimeout(() => document.body.classList.remove('animate-shake'), 500);
    }
}
