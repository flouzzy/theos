import { Controller } from "@hotwired/stimulus";
import { visit } from "@hotwired/turbo";

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
  connect() {
    // Init A2HS
    this.initA2HSEvent();

    // Init SW
    this.initSW();

    if (!this.isMobile()) {
      // Force le style IOS pour la version web
      window.Ionic = {
        config: {
          // rippleEffect: false,
          mode: "ios",
          hardwareBackButton: true,
          experimentalCloseWatcher: true,
        },
      };
    }

    this.initRefresherEvent();

    // Responsive tables
    this.responsiveTable();

    // Turbo transitions

    document.addEventListener("turbo:before-frame-render", (event) => {
      // fade out the old body
      console.log("turbo:before-frame-render", event);
    });

    document.addEventListener("turbo:frame-render", (event) => {
      // fade out the old body
      // console.log("turbo:frame-render", event);
      this.initRefresherEvent();
      document.body.classList.remove("turbo-loading");
    });
  }

  initRefresherEvent() {
    // Ionic refresher
    const refresher = document.getElementById("refresher");

    refresher.addEventListener("ionRefresh", () => {
      // Reload current page
      visit(window.location.href);
      refresher.complete();
    });
  }

  initSW() {
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker
        .register("/sw.js")
        .then((serviceWorker) => {
          // console.log("Service Worker registered: ", serviceWorker);
        })
        .catch((error) => {
          // console.error("Error registering the Service Worker: ", error);
        });
    }
  }

  initA2HSEvent() {
    const installBtn = document.querySelector("#installApp");
    let deferredPrompt;

    window.addEventListener("beforeinstallprompt", (e) => {
      // Prevent Chrome 67 and earlier from automatically showing the prompt
      e.preventDefault();
      // Stash the event so it can be triggered later.
      deferredPrompt = e;

      // Show Modal install app
      if (this.isMobile()) {
        // true for mobile device
        this.toggleA2HSModal();
      } else {
        // false for not mobile device
        console.info("not mobile device");
      }

      installBtn.addEventListener("click", (e) => {
        // hide our user interface that shows our A2HS button
        this.toggleA2HSModal();

        // Show the prompt
        deferredPrompt.prompt();

        // Wait for the user to respond to the prompt
        deferredPrompt.userChoice.then((choiceResult) => {
          if (choiceResult.outcome === "accepted") {
            console.log("User accepted the A2HS prompt");
          } else {
            console.log("User dismissed the A2HS prompt");
          }
          deferredPrompt = null;
        });
      });
    });
  }
  toggleA2HSModal() {
    const modalAppInstall = document.querySelector("ion-modal#modalAppInstall");
    modalAppInstall.isOpen = modalAppInstall.isOpen == true ? false : true;
  }

  isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );
  }

  /**
   * Rendre les tables responsive
   */
  responsiveTable() {
    // Étape 1 : Sélection de tous les éléments <table>
    document.querySelectorAll("table").forEach((tableElement) => {
      // Étape 2 : Création d'un nouvel élément <div> avec une classe responsive
      var divWrapper = document.createElement("div");
      divWrapper.className = "table-responsive"; // Ajouter la classe désirée

      // Étape 3 : Insérer l'élément <table> à l'intérieur du nouvel élément <div>
      divWrapper.appendChild(tableElement.cloneNode(true)); // cloneNode(true) pour copier l'élément et ses enfants

      // Étape 4 : Remplacer l'élément <table> original par le nouvel élément <div> dans le DOM
      tableElement.parentNode.replaceChild(divWrapper, tableElement);
    });
  }
}
