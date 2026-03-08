import { Controller } from "@hotwired/stimulus";

/**
 * PWA Install Controller — Le Rocher Académie
 *
 * Gère l'invite d'installation de la PWA selon la plateforme :
 * - Android/Chrome : intercept beforeinstallprompt → banner discret
 * - iOS/Safari     : détection + modal avec instructions visuelles
 *
 * Mémorisation des refus/installations via localStorage.
 */
export default class extends Controller {
    // Délai avant d'afficher la modal iOS (en ms) — ne pas être intrusif
    static DISMISS_KEY_ANDROID = "pwa-android-dismissed-at";
    static DISMISS_KEY_IOS = "pwa-ios-dismissed-at";
    static ANDROID_COOLDOWN_DAYS = 30;
    static IOS_COOLDOWN_DAYS = 7;
    static IOS_DELAY_MS = 30_000; // 30s

    deferredPrompt = null;
    iosTimer = null;

    connect() {
        // Ne rien faire si déjà en mode standalone (app déjà installée)
        if (this.isStandalone()) return;

        if (this.isAndroid()) {
            this.initAndroid();
        } else if (this.isIos()) {
            this.initIos();
        }

        // Écouter l'événement appinstalled
        window.addEventListener("appinstalled", this.onAppInstalled.bind(this));
    }

    disconnect() {
        if (this.iosTimer) clearTimeout(this.iosTimer);
        window.removeEventListener("appinstalled", this.onAppInstalled.bind(this));
    }

    // ────────────────────────────────────────────────────────────
    // ANDROID
    // ────────────────────────────────────────────────────────────

    initAndroid() {
        window.addEventListener("beforeinstallprompt", (e) => {
            e.preventDefault();
            this.deferredPrompt = e;

            if (this.wasDismissedRecently(this.constructor.DISMISS_KEY_ANDROID, this.constructor.ANDROID_COOLDOWN_DAYS)) {
                return;
            }

            this.showAndroidBanner();
        });
    }

    showAndroidBanner() {
        const banner = document.getElementById("pwa-android-banner");
        if (banner) banner.classList.remove("hidden");
    }

    async installAndroid() {
        if (!this.deferredPrompt) return;

        this.hideAndroidBanner();
        this.deferredPrompt.prompt();

        const { outcome } = await this.deferredPrompt.userChoice;
        if (outcome === "accepted") {
            this.deferredPrompt = null;
        } else {
            this.saveDismissal(this.constructor.DISMISS_KEY_ANDROID);
        }
    }

    dismissAndroid() {
        this.hideAndroidBanner();
        this.saveDismissal(this.constructor.DISMISS_KEY_ANDROID);
    }

    hideAndroidBanner() {
        const banner = document.getElementById("pwa-android-banner");
        if (banner) banner.classList.add("hidden");
    }

    // ────────────────────────────────────────────────────────────
    // iOS
    // ────────────────────────────────────────────────────────────

    initIos() {
        if (this.wasDismissedRecently(this.constructor.DISMISS_KEY_IOS, this.constructor.IOS_COOLDOWN_DAYS)) {
            return;
        }

        // Afficher après un délai pour ne pas interrompre l'utilisateur
        this.iosTimer = setTimeout(() => {
            this.showIosModal();
        }, this.constructor.IOS_DELAY_MS);
    }

    showIosModal() {
        const modal = document.getElementById("pwa-ios-modal");
        if (modal) modal.classList.remove("hidden");
    }

    dismissIos() {
        const modal = document.getElementById("pwa-ios-modal");
        if (modal) modal.classList.add("hidden");
        this.saveDismissal(this.constructor.DISMISS_KEY_IOS);
        if (this.iosTimer) clearTimeout(this.iosTimer);
    }

    // ────────────────────────────────────────────────────────────
    // Événement appinstalled
    // ────────────────────────────────────────────────────────────

    onAppInstalled() {
        this.hideAndroidBanner();
        const modal = document.getElementById("pwa-ios-modal");
        if (modal) modal.classList.add("hidden");
        // Marquer définitivement comme installé
        localStorage.setItem("pwa-installed", "true");
    }

    // ────────────────────────────────────────────────────────────
    // Détection de plateforme
    // ────────────────────────────────────────────────────────────

    isStandalone() {
        return (
            window.matchMedia("(display-mode: standalone)").matches ||
            window.navigator.standalone === true ||
            localStorage.getItem("pwa-installed") === "true"
        );
    }

    isAndroid() {
        return /Android/i.test(navigator.userAgent);
    }

    isIos() {
        // iPhone, iPad (iPadOS 13+ se détecte via maxTouchPoints)
        return (
            /iPhone|iPod/i.test(navigator.userAgent) ||
            (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1)
        );
    }

    isSafariBrowser() {
        return /Safari/i.test(navigator.userAgent) && !/Chrome|CriOS|FxiOS/i.test(navigator.userAgent);
    }

    // ────────────────────────────────────────────────────────────
    // LocalStorage helpers
    // ────────────────────────────────────────────────────────────

    saveDismissal(key) {
        localStorage.setItem(key, Date.now().toString());
    }

    wasDismissedRecently(key, cooldownDays) {
        const savedAt = parseInt(localStorage.getItem(key) || "0", 10);
        if (!savedAt) return false;
        const cooldownMs = cooldownDays * 24 * 60 * 60 * 1000;
        return Date.now() - savedAt < cooldownMs;
    }
}
