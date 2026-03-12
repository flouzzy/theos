import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
        token: String
    };

    claim(event) {
        event.preventDefault();
        const element = event.currentTarget;
        
        if (element.dataset.claimed) return;

        fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                _token: this.tokenValue
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.dataset.claimed = "true";
                element.classList.add('animate-ping', 'text-yellow-500');
                this.showFlash(data.message);
                
                // Optional: confetti
                if (window.confetti) {
                    window.confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }
            }
        })
        .catch(error => console.error('Easter egg error:', error));
    }

    showFlash(message) {
        const flash = document.createElement('div');
        flash.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 bg-yellow-500 text-white px-6 py-3 rounded-full shadow-2xl z-50 animate-bounce font-bold';
        flash.innerText = "✨ " + message;
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 4000);
    }
}
