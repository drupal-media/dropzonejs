/**
 * @file dropzone.integration.js
 *
 * Defines the behaviors needed for dropzonejs integration.
 *
 * @todo Implement maxfilesexceeded.
 *
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsIntegraion = {
    attach: function(context) {
      Dropzone.autoDiscover = false;
      var selector = $(".dropzone-enable");
      selector.addClass("dropzone");

      // Initiate dropzonejs.
      var config = {
        url: drupalSettings.dropzonejs.upload_path,
        addRemoveLinks: true,
      };
      var instanceConfig = drupalSettings.dropzonejs.instances[selector.attr('id')];
      var dropzoneInstance = new Dropzone("#" + selector.attr("id"), $.extend({}, instanceConfig, config));

      // React on add file. Add only accepted files.
      dropzoneInstance.on("addedfile", function(file) {
        var uploadedFilesElement = selector.siblings(':hidden');
        var currentValue = uploadedFilesElement.attr('value');

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
