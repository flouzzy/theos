import { Controller } from "@hotwired/stimulus";
// import tinymce from "https://cdn.jsdelivr.net/npm/tinymce@6.8.2/+esm";

/**
 * Editor JS
 */
export default class extends Controller {
  connect() {
    console.log("tinymce", tinymce);
    tinymce.init({
      selector: "textarea[name='page[content]']",
      a11y_advanced_options: true,
      relative_urls: false,
      remove_script_host: false,
      convert_urls: true,
      document_base_url: window.location.origin,
      menubar: "edit view",
      plugins: [
        "emoticons",
        "wordcount",
        "link",
        "image",
        "code",
        "table",
        "autolink",
        "lists",
        "searchreplace",
        "fullscreen",
        "insertdatetime",
        "media",
      ],
      toolbar:
        "blocks |bold italic link |image emoticons| blockquote| styleselect | alignleft aligncenter alignright alignjustify | table bullist numlist outdent indent",
      setup: function (editor) {
        editor.on("init", function (e) {
          // Retrait du copyright par défaut \o/
          document.querySelector(".tox-statusbar [href*='tiny']").remove();
        });

        editor.on("change", function () {
          tinymce.triggerSave();
        });
      },

      // Image Uploader
      image_title: true,
      /* enable automatic uploads of images represented by blob or data URIs*/
      automatic_uploads: true,
      images_upload_url: "/admin/media/new",
      /*
      URL of our upload handler (for more details check: https://www.tiny.cloud/docs/configure/file-image-upload/#images_upload_url)
      images_upload_url: 'postAcceptor.php',
      here we add custom filepicker only to Image dialog
    */
      file_picker_types: "image",
      image_class_list: [
        { title: "Image responsive Simple", value: "img-responsive" },
        { title: "Image responsive Fullwidth", value: "img-responsive w-100" },
      ],
      /* and here's our custom image picker*/
      file_picker_callback: (cb, value, meta) => {
        const input = document.createElement("input");
        input.setAttribute("type", "file");
        input.setAttribute("accept", "image/*");

        input.addEventListener("change", (e) => {
          const file = e.target.files[0];

          const reader = new FileReader();
          reader.addEventListener("load", () => {
            /*
                  Note: Now we need to register the blob in TinyMCEs image blob
                  registry. In the next release this part hopefully won't be
                  necessary, as we are looking to handle it internally.
                */
            const id = "image-" + new Date().getTime();
            const blobCache = tinymce.activeEditor.editorUpload.blobCache;
            const base64 = reader.result.split(",")[1];
            const blobInfo = blobCache.create(id, file, base64);
            blobCache.add(blobInfo);

            /* call the callback and populate the Title field with the file name */
            cb(blobInfo.blobUri(), { title: file.name });
          });
          reader.readAsDataURL(file);
        });

        input.click();
      },
    });
  }
}
