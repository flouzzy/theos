import { Controller } from "@hotwired/stimulus";
import tinymce from "tinymce";

/**
 * Editor JS
 */
export default class extends Controller {
  connect() {
    tinymce.init({
      selector: ".text-editor",
      promotion: false,
      branding: false,
      skin: "oxide",
      content_css: "default",
      a11y_advanced_options: true,
      relative_urls: false,
      remove_script_host: false,
      convert_urls: true,
      document_base_url: window.location.origin,
      menubar: false,
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
        "undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | table emoticons | code fullscreen",
      setup: function (editor) {
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
