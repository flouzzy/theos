import { Controller } from "@hotwired/stimulus";

/**
 * Editor JS using TinyMCE from CDN
 */
export default class extends Controller {
  connect() {
    this.loadTinyMCE();
  }

  loadTinyMCE() {
    if (typeof tinymce === 'undefined') {
        const script = document.createElement('script');
        // Utilisation de la version 6 stable via Cloud
        script.src = 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js';
        script.referrerPolicy = 'origin';
        script.onload = () => {
            this.initEditor();
        };
        document.head.appendChild(script);
    } else {
        this.initEditor();
    }
  }

  initEditor() {
    // On s'assure de supprimer toute instance existante avant d'initialiser
    tinymce.remove(".text-editor");

    tinymce.init({
      selector: ".text-editor",
      license_key: 'gpl', // Précise l'usage open-source
      promotion: false,
      branding: false,
      skin: "oxide",
      content_css: "default",
      base_url: 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6', // Force la base vers le CDN
      suffix: '.min',
      a11y_advanced_options: true,
      relative_urls: false,
      remove_script_host: false,
      convert_urls: true,
      document_base_url: window.location.origin,
      menubar: false,
      plugins: [
        "emoticons", "wordcount", "link", "image", "code", "table", 
        "autolink", "lists", "searchreplace", "fullscreen", "insertdatetime", "media"
      ],
      toolbar:
        "undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | table emoticons | code fullscreen",
      setup: function (editor) {
        editor.on("change", function () {
          tinymce.triggerSave();
        });
      },
      image_title: true,
      automatic_uploads: true,
      images_upload_url: "/admin/media/new",
      file_picker_types: "image",
      image_class_list: [
        { title: "Image responsive Simple", value: "img-responsive" },
        { title: "Image responsive Fullwidth", value: "img-responsive w-100" },
      ],
      file_picker_callback: (cb, value, meta) => {
        const input = document.createElement("input");
        input.setAttribute("type", "file");
        input.setAttribute("accept", "image/*");
        input.addEventListener("change", (e) => {
          const file = e.target.files[0];
          const reader = new FileReader();
          reader.addEventListener("load", () => {
            const id = "image-" + new Date().getTime();
            const blobCache = tinymce.activeEditor.editorUpload.blobCache;
            const base64 = reader.result.split(",")[1];
            const blobInfo = blobCache.create(id, file, base64);
            blobCache.add(blobInfo);
            cb(blobInfo.blobUri(), { title: file.name });
          });
          reader.readAsDataURL(file);
        });
        input.click();
      },
    });
  }

  disconnect() {
      if (typeof tinymce !== 'undefined') {
          tinymce.remove(".text-editor");
      }
  }
}
