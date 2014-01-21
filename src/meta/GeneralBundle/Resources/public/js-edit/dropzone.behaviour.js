Dropzone.autoDiscover = false;

$(document).ready(function(){

  dp = new Dropzone("#dropzone-trigger", {
    paramName: $("#dropzone-trigger").attr('rel'),
    params: {"resource[_token]": $("#dropzone-trigger").attr('token') },
    url: $("#dropzone-trigger").parents('form').attr('action'),
    autoProcessQueue: false,
    addRemoveLinks: true,
    parallelUploads: 10,
    init: function() {
      var submitButton = $("#upload")
          myDropzone = this; // closure

      submitButton.on("click", function() {
        myDropzone.processQueue(); // Tell Dropzone to process all queued files.
      });

      // You might want to show the submit button only when 
      // files are dropped here:
      this.on("addedfile", function() {
        // Show submit button here and/or inform user to click it.
        $("#upload").show();
      });

      myDropzone.on("sending", function(file, xhr, formData) {
        formData.append("filesize", file.size); // Will send the filesize along with the file as POST data.
      });

      this.on("success", function(file, responseText) {
        // Handle the responseText here.
        if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
          // File finished uploading, and there aren't any left in the queue.
          if (responseText.redirect) {
            window.location.replace(responseText.redirect);
          }
        }
      });

      this.on("error", function(file, responseText) {
        // Handle the responseText here.
        alertify.error(responseText.error);
      });

    },
    
    dictDefaultMessage: "<i class='fa fa-cloud-upload'></i> " + Translator.trans("dropzone.dictDefaultMessage"),//  The message that gets displayed before any files are dropped. This is normally replaced by an image but defaults to "Drop files here to upload"
    dictFallbackMessage: Translator.trans("dropzone.dictFallbackMessage"),//   If the browser is not supported, the default message will be replaced with this text. Defaults to "Your browser does not support drag'n'drop file uploads."
    dictFallbackText: Translator.trans("dropzone.dictFallbackText"),//  This will be added before the file input files. If you provide a fallback element yourself, or if this option is null this will be ignored. Defaults to "Please use the fallback form below to upload your files like in the olden days."
    dictInvalidFileType: Translator.trans("dropzone.dictInvalidFileType"),//   Shown as error message if the file doesn't match the file type.
    dictFileTooBig: Translator.trans("dropzone.dictFileTooBig"),//  Shown when the file is too big. and will be replaced.
    dictResponseError: Translator.trans("dropzone.dictResponseError"),//   Shown as error message if the server response was invalid. `` will be replaced with the servers status code.
    dictCancelUpload: Translator.trans("dropzone.dictCancelUpload"),//  If addRemoveLinks is true, the text to be used for the cancel upload link.
    dictCancelUploadConfirmation: Translator.trans("dropzone.dictCancelUploadConfirmation"),//  If addRemoveLinks is true, the text to be used for confirmation when cancelling upload.
    dictRemoveFile: Translator.trans("dropzone.dictRemoveFile"),//  If addRemoveLinks is true, the text to be used to remove a file.
    dictMaxFilesExceeded: Translator.trans("dropzone.dictMaxFilesExceeded")

  });

});
