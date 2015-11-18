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

  Drupal.AjaxCommands.prototype.update_dropzone = function (ajax, response, status) {
    $(response.selector).val(function (value) {
      return value + response.files.join(';') + ';';
    });
  };

  Drupal.dropzonejs = {
    responseHandlers: {
      /**
       * Handles JSON-RPC response from DropzoneJS' UploadController.
       */
      jsonRPC: {
        canHandle: function (response) {
          return response.hasOwnProperty('jsonrpc');
        },
        handle: function (response) {
          var uploadedFilesElement = this.element.siblings(':hidden');
          var currentValue = uploadedFilesElement.attr('value');

          // The file is transliterated on upload. The element has to reflect
          // the real filename.
          file.processedName = response.result;

          uploadedFilesElement.attr('value', currentValue + response.result + ';');
        }
      },

      /**
       * Handles response from Drupal's AJAX framework (an array of commands).
       */
      drupalAjax: {
        canHandle: function (response) {
          return response instanceof Array;
        },
        handle: function (response) {
          if (typeof this.drupalAjax === 'undefined') {
            var settings = {
              element: $(this.element)
            };
            settings.url = settings.element.siblings('input[data-upload-path]').attr('data-upload-path');
            this.drupalAjax = Drupal.ajax(settings);
          }
          this.drupalAjax.success(response);
        }
      }
    }
  };

  Drupal.dropzonejsInstances = [];

  Drupal.behaviors.dropzonejsIntegraion = {
    attach: function(context) {
      Dropzone.autoDiscover = false;
      var selector = $(".dropzone-enable");
      selector.addClass("dropzone");
      var input = selector.siblings('input');

      // Initiate dropzonejs.
      var config = {
        url: input.attr('data-upload-path'),
        addRemoveLinks: true
      };
      var instanceConfig = drupalSettings.dropzonejs.instances[selector.attr('id')];
      var dropzoneInstance = new Dropzone("#" + selector.attr("id"), $.extend({}, instanceConfig, config));

      // Other modules might need instances.
      drupalSettings["dropzonejs"]["instances"][selector.attr("id")]["instance"] = dropzoneInstance;

      // React on add file. Add only accepted files.
      dropzoneInstance.on("success", function(file, response) {
        // Find the appropriate response handler.
        for (var type in Drupal.dropzonejs.responseHandlers) {
          var handler = Drupal.dropzonejs.responseHandlers[type];
          if (handler.canHandle.call(this, response)) {
            handler.handle.call(this, response);
            break;
          }
        }
      });

      // React on file removing.
      dropzoneInstance.on("removedfile", function(file) {
        console.log(this);
        var uploadedFilesElement = selector.siblings(':hidden');
        var currentValue = uploadedFilesElement.attr('value');

        // Remove the file from the element.
        if (currentValue.length) {
          var fileNames = currentValue.split(";");
          for (var i in fileNames) {
            if (fileNames[i] == file.processedName) {
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
