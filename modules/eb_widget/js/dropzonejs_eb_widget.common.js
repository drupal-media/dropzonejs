/**
 * @file dropzonejs_eb_widget.common.js
 *
 * Bundles various dropzone eb widget behaviours.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsEbWidgetCommon = {
    attach: function(context) {
      if (Drupal.dropzonejsInstances.length) {
        for (var i = 0; i < Drupal.dropzonejsInstances.length; i++) {
          var instance = Drupal.dropzonejsInstances[i];
          var $form = $(Drupal.dropzonejsInstances[i].element).parents('form');

          if (form.hasClass('dropzonejs-disable-submit')) {
            var $submit = form.find('input.form-submit');
            submit.attr("disabled", "true");

            instance.on("queuecomplete", function () {
              if (instance.getRejectedFiles().length == 0) {
                submit.removeAttr("disabled");
              }
              else {
                submit.attr("disabled", "true");
              }
            });

            instance.on("removedfile", function (file) {
              if (instance.getRejectedFiles().length == 0) {
                submit.removeAttr("disabled");
              }
            });
          }
        }
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
