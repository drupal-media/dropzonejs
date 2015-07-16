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

      var dropzoneInstance = new Dropzone("#" + selector.attr("id"), {
        // @todo we should get all this from somewhere.
        url: "/drupal-8-media/dropzonejs/upload",
        maxFilesize: 2,
      });

      dropzoneInstance.on("addedfile", function(file) {
        console.log(file);
      });
    }
  };


}(jQuery, Drupal, drupalSettings));
