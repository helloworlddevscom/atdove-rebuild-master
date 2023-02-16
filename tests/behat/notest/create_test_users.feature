@api @notest
Feature: Create test users for manual functional QA and/or development purposes

  # This mostly copies tests/behat/features/user_registration.feature
  # If it is failing, look there for the latest expected registration process.
  @javascript
  Scenario: Create an org admin user.
    Given I am an anonymous user
    When I am at "/join"
    Then I should see the text "Pick Your Plan"
    And I should see the text "Start a Free Trial"
    # EQUIVELANT OF: And I click "start a free trial"
    And I click the "li.year a" element
    Then I should see the text "Create Account"
    And I fill in "First Name" with "Bob"
    And I fill in "Last Name" with "Dobbs"
    And I fill in "Email" with "test-org-admin@helloworlddevs.com"
    And I fill in "Password" with "wh0C4r3s?"
    And I fill in "Confirm Password" with "wh0C4r3s?"
    And I press the "Next" button
    Then I should see the text "Organization Name"
    And I fill in "Organization Name" with "Test Organization"
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
