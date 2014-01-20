$(document).ready(function(){

  Dropzone.options.file  = {
    autoProcessQueue: false,
    addRemoveLinks: true,
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

    },
    dictDefaultMessage: Translator.trans(""),//  The message that gets displayed before any files are dropped. This is normally replaced by an image but defaults to "Drop files here to upload"
    dictFallbackMessage: Translator.trans(""),//   If the browser is not supported, the default message will be replaced with this text. Defaults to "Your browser does not support drag'n'drop file uploads."
    dictFallbackText: Translator.trans(""),//  This will be added before the file input files. If you provide a fallback element yourself, or if this option is null this will be ignored. Defaults to "Please use the fallback form below to upload your files like in the olden days."
    dictInvalidFileType: Translator.trans(""),//   Shown as error message if the file doesn't match the file type.
    dictFileTooBig: Translator.trans(""),//  Shown when the file is too big. and will be replaced.
    dictResponseError: Translator.trans(""),//   Shown as error message if the server response was invalid. `` will be replaced with the servers status code.
    dictCancelUpload: Translator.trans(""),//  If addRemoveLinks is true, the text to be used for the cancel upload link.
    dictCancelUploadConfirmation: Translator.trans(""),//  If addRemoveLinks is true, the text to be used for confirmation when cancelling upload.
    dictRemoveFile: Translator.trans(""),//  If addRemoveLinks is true, the text to be used to remove a file.
    dictMaxFilesExceeded: Translator.trans("")
  }

});
