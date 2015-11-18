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
    $(response.selector).val(function (i, value) {
      value = value.split(';');
      value.push(response.file);
      return value.join(';');
    });
  };

  Drupal.dropzonejs = {
    responseHandlers: {
      /**
       * Handles JSON-RPC response from DropzoneJS' UploadController.
       */
      jsonRPC: {
        canHandle: function (file, response) {
          return response.hasOwnProperty('jsonrpc');
        },
        handle: function (file, response) {
          // The file is transliterated on upload. The element has to reflect
          // the real filename.
          file.processedName = response.result;

          this.element.siblings(':hidden').val(function (i, value) {
            value = value.split(';');
            value.push(response.result);
            return value.join(';');
          });
        }
      },

      /**
       * Handles response from Drupal's AJAX framework (an array of commands).
       */
      drupalAjax: {
        canHandle: function (file, response) {
          return response instanceof Array;
        },
        handle: function (file, response) {
          // Create a Drupal.Ajax object so that we can call its success() method to
          // run the commands in the response.
          if (typeof this.drupalAjax === 'undefined') {
            var settings = {
              element: $(this.element)
            };
            settings.url = settings.element.siblings('input[data-upload-path]').attr('data-upload-path');
            this.drupalAjax = Drupal.ajax(settings);
          }
          response.forEach(function (command) {
            if (command.command === 'update_dropzone') {
              file.processedName = command.file;
            }
          })
          this.drupalAjax.success(response);
        }
      }
    }
  };

  Drupal.dropzonejsInstances = [];

  Drupal.behaviors.dropzonejsIntegration = {
    attach: function () {
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
          if (handler.canHandle.call(this, file, response)) {
            handler.handle.call(this, file, response);
            break;
          }
        }
      });

      // React on file removing.
      dropzoneInstance.on("removedfile", function(file) {
        selector.siblings(':hidden').val(function (i, value) {
          return value.split(';').filter(function (f) { return f !== file.processedName; }).join(';');
        });
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
