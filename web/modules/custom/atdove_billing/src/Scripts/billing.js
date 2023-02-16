(function ($, Drupal, drupalSettings, Stripe) {

  // Custom styling can be passed to options when creating an Element.
  var style = {
      base: {
        iconColor: '#2F3759',
        color: '#333',
        fontWeight: '500',
        fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif',
        fontSize: '14px',
        fontSmoothing: 'antialiased',
        ':-webkit-autofill': {
          color: '#999',
        },
        '::placeholder': {
          color: '#999',
        },
      }
  };

  const stripe = Stripe(drupalSettings.billing.stripe.pubkey);
  var elements = stripe.elements();
  var card = elements.create('card', {style: style});

  // Add an instance of the card Element into the `card-element` <div>.
  card.mount('#card-element');

  $('#edit-submit').hide();

  if($('#atdove-organizations-create-acct-org-form-three').length > 0) {
    var payment_form = $('#atdove-organizations-create-acct-org-form-three');
    var _form = $('#atdove-organizations-create-acct-org-form-three');
  } else {
    var payment_form = $('.payment-ui-card');
    var _form = $('#atdove-billing-existing-org-subscribe-form');
  }

  $('<button id="registration-submit">Submit</button>').insertAfter(payment_form);

  // The .one() method prevents more than one handler execution
  $('#registration-submit').one('click', function() {
    stripe.createToken(card).then(function(result) {
      if (result.error) {
        // Inform the customer that there was an error.
        var errorElement = document.getElementById('card-errors');
        errorElement.textContent = result.error.message;
      } else {
        // Send the token to your server.
        stripeTokenHandler(result.token, _form);
      }
    });

    // Disabling submit button upon click to further prevent multiple form submissions
    $(this).prop('disabled', true);
  });

  function stripeTokenHandler(token, form) {
    var hiddenInput = document.createElement('input');
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', 'token');
    hiddenInput.setAttribute('value', token.id);
    form[0].appendChild(hiddenInput);
    form[0].submit();
  }

})(jQuery, Drupal, drupalSettings, Stripe);
