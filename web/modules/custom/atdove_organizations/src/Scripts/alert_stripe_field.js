/**
 * @file
 *
 * Throws an alert on the stripe field if clicked.
 */
(function ($, Drupal)
{
  Drupal.behaviors.alert_stripe_field = {
    attach: function (context, settings)
    {
      var selector = "input[name='field_stripe_customer_id[0][value]'], input[name='stripe_customer_id[0][value]']";
      var form_selectors = 'form#group-organization-edit-form, form#user-form';

      // Warn users who click the stripe customer ID field.
      $(selector).one('click', function(){
        alert('BEWARE! Altering the Stripe Customer ID to another users ID or incorrect ID can cause issues. Invalid IDs are caught, but entering a valid ID from another user can cause issues.');
      });

      // Add form submit confirmation.
      $(selector).one('change', function(){
        $(form_selectors).submit(function() {
          var c = confirm('You have altered the Stripe Customer. Please verify one last time the value entered is correct.');
          return c; //you can just return c because it will be true or false
        });
      });
    }
  };
}(jQuery, Drupal));
