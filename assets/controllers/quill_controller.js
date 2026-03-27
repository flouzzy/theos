import { Controller } from "@hotwired/stimulus";
import Quill from "quill";

/**
 * Quill 2.0 Editor Controller
 * Modern, open-source replacement for TinyMCE
 */
export default class extends Controller {
    static targets = ["input"];

    connect() {
        console.log("Quill controller connected");
        if (!this.hasInputTarget) {
            console.error("Quill: No input target found!");
            return;
        }
        
        // Create editor container
        this.container = document.createElement("div");
        this.container.classList.add("quill-editor-container");
        this.element.appendChild(this.container);

        // Initialize Quill
        this.quill = new Quill(this.container, {
            theme: "snow",
            modules: {
                toolbar: {
                    container: [
                        [{ header: [1, 2, 3, false] }],
                        ["bold", "italic", "underline", "strike"],
                        ["blockquote", "code-block"],
                        [{ list: "ordered" }, { list: "bullet" }],
                        [{ color: [] }, { background: [] }],
                        [{ align: [] }],
                        ["link", "image", "video"],
                        ["clean"],
                    ],
                    handlers: {
                        image: this.imageHandler.bind(this),
                    },
                },
            },
            placeholder: 'Commencez à écrire...',
        });

        // Set initial content from textarea
        if (this.hasInputTarget) {
            this.quill.root.innerHTML = this.inputTarget.value;
            this.inputTarget.style.display = "none";
        }

        // Sync Quill content to textarea on every change
        this.quill.on("text-change", () => {
            if (this.hasInputTarget) {
                this.inputTarget.value = this.quill.root.innerHTML;
            }
        });
    }

    /**
     * Custom Image Handler for Quill
     * Uploads image to server and inserts the resulting URL
     */
    imageHandler() {
        const input = document.createElement("input");
        input.setAttribute("type", "file");
        input.setAttribute("accept", "image/*");
        input.click();

        input.onchange = async () => {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("/admin/media/new", {
                    method: "POST",
                    body: formData,
                });

                if (response.ok) {
                    const result = await response.json();
                    const range = this.quill.getSelection(true);
                    this.quill.insertEmbed(range.index, "image", result.location);
                    this.quill.setSelection(range.index + 1);
                } else {
                    console.error("Image upload failed");
                    alert("Échec de l'envoi de l'image.");
                }
            } catch (error) {
                console.error("Error uploading image:", error);
                alert("Erreur lors de l'envoi de l'image.");
            }
        };
    }

    disconnect() {
        // Cleanup if needed
    }
}
