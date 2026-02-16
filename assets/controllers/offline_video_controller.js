import { Controller } from "@hotwired/stimulus";
import OfflineVideoManager from "../lib/OfflineVideoManager.js";

export default class extends Controller {
  static values = {
    url: String,
    lessonId: String
  };

  static targets = ["downloadBtn", "playBtn", "deleteBtn", "playerContainer", "progressBar", "actions"];

  initialize() {
    this.manager = new OfflineVideoManager();
  }

  async connect() {
    if (!this.urlValue) {
      if (this.hasActionsTarget) {
          this.actionsTarget.style.display = 'none';
      }
      return;
    }
    await this.checkStatus();
  }

  async checkStatus() {
    try {
      const exists = await this.manager.hasVideo(this.lessonIdValue);
      if (exists) {
        this.showPlay();
      } else {
        this.showDownload();
      }
    } catch (e) {
      console.error("Error checking video status:", e);
      this.showDownload();
    }
  }

  showDownload() {
    this.downloadBtnTarget.classList.remove("hidden");
    this.playBtnTarget.classList.add("hidden");
    this.deleteBtnTarget.classList.add("hidden");
    this.progressBarTarget.classList.add("hidden");
  }

  showPlay() {
    this.downloadBtnTarget.classList.add("hidden");
    this.playBtnTarget.classList.remove("hidden");
    this.deleteBtnTarget.classList.remove("hidden");
    this.progressBarTarget.classList.add("hidden");
  }

  async download(event) {
    event.preventDefault();
    this.downloadBtnTarget.classList.add("hidden");
    this.progressBarTarget.classList.remove("hidden");

    try {
      const response = await fetch(this.urlValue);
      if (!response.ok) throw new Error("Network response was not ok");

      const blob = await response.blob();
      await this.manager.storeVideo(this.lessonIdValue, blob);
      this.showPlay();
    } catch (error) {
      console.error("Download failed:", error);
      alert("Download failed. Please try again.");
      this.showDownload();
    }
  }

  async play(event) {
    event.preventDefault();
    try {
        const blob = await this.manager.getVideo(this.lessonIdValue);
        const url = URL.createObjectURL(blob);

        // Replace content of playerContainer with a video tag
        this.playerContainerTarget.innerHTML = `
            <video controls autoplay class="w-full h-full rounded-2xl">
                <source src="${url}" type="${blob.type}">
                Your browser does not support the video tag.
            </video>
        `;
    } catch (error) {
        console.error("Playback failed:", error);
        alert("Could not play video.");
    }
  }

  async delete(event) {
    event.preventDefault();
    if (!confirm("Are you sure you want to delete this downloaded video?")) return;

    try {
        await this.manager.deleteVideo(this.lessonIdValue);
        window.location.reload();
    } catch (error) {
        console.error("Delete failed:", error);
    }
  }
}
