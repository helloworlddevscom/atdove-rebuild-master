@api
Feature: Check that users can infact register with stripe.

  # @todo: Make sure we can't create a new user with the same email.

  @javascript
  Scenario: Anonymous user can signup an organization with a credit card.
    Given I am an anonymous user
    When I am at "/join"
    Then I should see the text "Pick Your Plan"
    # EQUIVELANT OF: And I click "start a free trial"
    And I click the "li.year a" element
    Then I should see the text "Create Account"
    And I fill in "First Name" with "Bob"
    And I fill in "Last Name" with "Dobbs"
    And I fill in "Email" with "bob@dobbs.org"
    And I fill in "Password" with "wh0C4r3s?"
    And I fill in "Confirm Password" with "wh0C4r3s?"
    And I press the "Next" button
    Then I should see the text "Organization Name"
    And I fill in "Organization Name" with "BobDobbsInc"
    And I press the "Next" button
    Then I should see the text "Your first 7 days are free! Your credit card will be charged when your free trial ends."
    And I wait 10 seconds
    Then I should see an "#card-element iframe" element
    Then I switch to iframe via css selector "#card-element iframe"
    And I fill in "cardnumber" with "4242 4242 4242 4242"
    # Give time for cardnumber to process.
    And I wait 5 seconds
    And I fill in "exp-date" with "12/32"
    And I fill in "cvc" with "123"
    # Give time for postal to appear.
    And I wait 5 seconds
    And I fill in "postal" with "12345"
    And I switch back to the main window.
    And I press the "registration-submit" button
    And I wait 10 seconds
    Then I should see the text "Welcome to AtDove!"
    # This checks for a link to an organization in the user dropdown menu.
    # This indicates the user was granted the organization-admin role within their organization.
    And I should see a ".user-menu-block .organization_0" element
    Then I am not logged in
    # Verify we get a group created, and we get stripe values.
    Given I am logged in as a user with the 'administrator' role
    When I edit the group with label "BobDobbsInc"
    Then the "Stripe Customer ID" field should not be empty
    And I clean up the user "bob@dobbs.org" and their groups

  @javascript
  Scenario: Anonymous user CANNOT signup an organization with invalid credit card information
    Given I am an anonymous user
    When I am at "/join"
    Then I should see the text "Pick Your Plan"
    # EQUIVELANT OF: And I click "start a free trial"
    And I click the "li.year a" element
    Then I should see the text "Create Account"
    And I fill in "First Name" with "BobFail"
    And I fill in "Last Name" with "DobbsFail"
    And I fill in "Email" with "bobfail@dobbsfail.org"
    And I fill in "Password" with "wh0C4r3s?"
    And I fill in "Confirm Password" with "wh0C4r3s?"
    And I press the "Next" button
    Then I should see the text "Organization Name"
    And I fill in "Organization Name" with "BobDobbsFailsInc"
    And I press the "Next" button
    Then I should see the text "Your first 7 days are free! Your credit card will be charged when your free trial ends."
    And I wait 10 seconds
    Then I should see an "#card-element iframe" element
    Then I switch to iframe via css selector "#card-element iframe"
    And I fill in "cardnumber" with "4111 4111 4111 4111"
    Then I should see an "div.CardBrandIcon-container[data-front-icon-name='error']" element
    And I fill in "exp-date" with "12/18"
    Then I should see an "span.CardField-expiry input.is-invalid" element
    And I fill in "exp-date" with "12/21"
    And I fill in "cvc" with "123"
    And I fill in "postal" with "12345"
    And I switch back to the main window.
    And I press the "registration-submit" button
    And I wait 10 seconds
    Then I should not see the text "Welcome to AtDove!"
    # @todo: come up with a superior way of checking failure.

  # @todo: add test for invalid email not accepted!
  # @todo: verify that user is given global active_org_member role.

  Scenario: Anonymous user CANNOT signup an organization using an existing email address
    Given users:
      |name                      | mail                      | status| field_first_name | field_last_name | roles             | uid     | pass                     |
      |bobadobalina2@example.com | bobadobalina2@example.com | 1     | Bob              | Dobalina2       | active_org_member | 7712346 | bobadobalina@example.com |
    When I am an anonymous user
    And I am at "/join"
    Then I should see the text "Pick Your Plan"
    # EQUIVELANT OF: And I click "start a free trial"
    And I click the "li.year a" element
    Then I should see the text "Create Account"
    And I fill in "First Name" with "BobFail"
    And I fill in "Last Name" with "DobbsFail"
    And I fill in "Email" with "bobadobalina2@example.com"
    And I fill in "Password" with "wh0C4r3s?"
    And I fill in "Confirm Password" with "wh0C4r3s?"
    And I press the "Next" button
    And I wait 3 seconds
    Then I should see the text "An account already exists with this email address"

  @javascript
  Scenario: Anonymous user cannot submit the registration form more than once
    Given I am an anonymous user
    When I am at "/join"
    Then I should see the text "Pick Your Plan"
    # EQUIVELANT OF: And I click "start a free trial"
    And I click the "li.year a" element
    Then I should see the text "Create Account"
    And I fill in "First Name" with "BobFail"
    And I fill in "Last Name" with "DobbsFail"
    And I fill in "Email" with "bobfail@dobbsfail.org"
    And I fill in "Password" with "wh0C4r3s?"
    And I fill in "Confirm Password" with "wh0C4r3s?"
    And I press the "Next" button
    Then I should see the text "Organization Name"
    And I fill in "Organization Name" with "BobDobbsFailsInc"
    And I press the "Next" button
    Then I should see the text "Your first 7 days are free! Your credit card will be charged when your free trial ends."
    And I wait 10 seconds
    Then I should see an "#card-element iframe" element
    Then I switch to iframe via css selector "#card-element iframe"
    And I fill in "cardnumber" with "4111 4111 4111 4111"
    Then I should see an "div.CardBrandIcon-container[data-front-icon-name='error']" element
    And I fill in "exp-date" with "12/18"
    Then I should see an "span.CardField-expiry input.is-invalid" element
    And I fill in "exp-date" with "12/21"
    And I fill in "cvc" with "123"
    And I fill in "postal" with "12345"
    And I switch back to the main window.
    And I press the "registration-submit" button
    And I wait 1 seconds
    Then the "registration-submit" button should be disabled
