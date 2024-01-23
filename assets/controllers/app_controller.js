import { Controller } from "@hotwired/stimulus";
import Swiper from "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.mjs";

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
      this.toggleA2HSModal();

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
}
