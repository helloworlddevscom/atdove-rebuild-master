/**
 * Contains global styling that is loaded on all pages.
 */

(function($, Drupal) {
  'use strict';

  var window_width = $(window).width();
  $(window).resize(function() {
    window_width = $(window).width();
  });

  //DETECT IE 11
  if (!!window.MSInputMethodContext && !!document.documentMode) {
    $('html').addClass('ie11');
  }


  /**
   * Example of a Drupal behavior.
   * Copy this as a start.
   */
  var sampleBehavior = false;
  Drupal.behaviors.sampleBehavior = {
    attach: function(context, settings) {
      // Attempt to resolve behavior being called multiple times per page load.
      if (context !== document) {
        return;
      }
      if (!sampleBehavior) {
        sampleBehavior = true;
      }
      else {
        return;
      }

      // Check if component exists.
      if ($('.example-component').length) {
        // Code here.
      }
    }
  };


})(jQuery, Drupal);
