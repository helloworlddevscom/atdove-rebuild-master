/**
 * JS for AtDove SSO Login form block.
 * Loaded by theme library atdove/sso-login.
 * See: block--atdove-sso-login.html.twig
 */

(function($, Drupal) {
  'use strict';

  /**
   * AtDove custom login form toggle functionality.
   */
  var loginToggle = false;
  Drupal.behaviors.loginToggle = {
    attach: function(context, settings) {
      // Attempt to resolve behavior being called multiple times per page load.
      if (context !== document) {
        return;
      }
      if (!loginToggle) {
        loginToggle = true;
      }
      else {
        return;
      }

      // Check if login form exists.
      if ($('.block-id--block-atdove-sso-login').length) {
        var $login_form = $('.block-id--block-atdove-sso-login').find('.default-login-form');
        $('.default-login-toggle').on('click tap touch', function(e) {
          e.preventDefault();
          e.stopPropagation();
          $login_form.toggleClass('active');
          if ($login_form.css('max-height') !== '0px') {
            $login_form.css('max-height', 0);
          } else {
            $login_form.css('max-height', $login_form[0].scrollHeight + "px");
            $login_form.css('overflow', 'hidden');
          }
        });

        // If Selenium, a Behat test must be running.
        // The Drupal Behat login feature fails if the form is hidden.
        if (navigator.webdriver) {
          $login_form.addClass('active');
          $login_form.css('max-height', 'none');
        }
      }
    }
  };


})(jQuery, Drupal);






