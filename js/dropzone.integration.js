/**
 * @file dropzone.integration.js
 *
 * Defines the behaviors needed for dropzonejs integration.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsIntegraion = {
    attach: function(context) {
      Dropzone.autoDiscover = false;
      var selector = $(".dropzone-enable");
      selector.addClass("dropzone");

      // Initiate dropzonejs.
      var dropzoneInstance = new Dropzone("#" + selector.attr("id"), {
        // @todo we should get all this from somewhere.
        url: "/drupal-8-media/dropzonejs/upload",
        maxFilesize: 2,
        addRemoveLinks: true,
      });

      // React on add file. Add only accepted files.
      dropzoneInstance.on("addedfile", function(file) {
        var uploadedFilesElement = selector.siblings(':hidden');
        var currentValue = uploadedFilesElement.attr('value');

        // @todo handle files with validaiton errors.
        uploadedFilesElement.attr('value', currentValue + file.name + ';');
      });

      // React on file removing.
      dropzoneInstance.on("removedfile", function(file) {
        var uploadedFilesElement = selector.siblings(':hidden');
        var currentValue = uploadedFilesElement.attr('value');

        // Remove the file from the element.
        if (currentValue.length) {
          var fileNames = currentValue.split(";");
          for (var i in fileNames) {
            if (fileNames[i] == file.name) {
              fileNames.splice(i,1);
              break;
            }
          }

          var newValue = fileNames.join(';');
          uploadedFilesElement.attr('value', newValue);
        }
      });
    }
  };


}(jQuery, Drupal, drupalSettings));
