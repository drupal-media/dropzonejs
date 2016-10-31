/**
 * @file dropzonejs_eb_widget.common.js
 *
 * Bundles various dropzone eb widget behaviours.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsPostIntegrationEbWidgetCommon = {
    attach: function(context) {
      if (typeof drupalSettings.dropzonejs.instances !== "undefined") {
        _.each(drupalSettings.dropzonejs.instances, function (item) {
          var $form = $(item.instance.element).parents('form');

          if ($form.hasClass("dropzonejs-disable-submit")) {
            var $submit = $form.find('.is-entity-browser-submit');
            $submit.prop("disabled", true);

            item.instance.on("queuecomplete", function () {
              if (item.instance.getRejectedFiles().length == 0) {
                $submit.prop("disabled", false);
              }
              else {
                $submit.prop("disabled", true);
              }
            });

            item.instance.on("removedfile", function (file) {
              if (item.instance.getRejectedFiles().length == 0) {
                $submit.removeAttr("disabled");
              }

              // If there are no files in DropZone -> disable Button.
              if (item.instance.getAcceptedFiles().length === 0) {
                $submit.prop("disabled", true);
              }
            });

            if (drupalSettings.dropzonejs.auto_select) {
              item.instance.on("queuecomplete", function () {
                var dzInstance = item.instance;
                var filesInQueue = dzInstance.getQueuedFiles();
                var i, rejectedFiles;

                if (filesInQueue.length === 0) {
                  // Remove filed files.
                  rejectedFiles = dzInstance.getRejectedFiles();
                  for (i = 0; i < rejectedFiles.length; i++) {
                    dzInstance.removeFile(rejectedFiles[i]);
                  }

                  // Ensure that there are some files that should be submitted.
                  if (dzInstance.getAcceptedFiles().length > 0 && dzInstance.getUploadingFiles().length === 0) {
                    jQuery(dzInstance.element)
                      .parent()
                      .siblings('[name="auto_select_handler"]')
                      .trigger('auto_select_enity_browser_widget');
                  }
                }
              });
            }

          }
        });
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
