@api
Feature: The groups admin view exists and works as expected.

  Scenario: Admin view of groups displays Stripe ID and allows filtering by stripe ID.
    Given group entities:
      | type                  | label              | field_stripe_customer_id |
      | organization          | Behat-STRIPE-ID    | BEHAT_PASS               |
      | organization          | Behat-NO-STRIPE    |                          |
      | organization          | Behat-OTHER-STRIPE | BEHAT_UNRELATED          |
    When I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/groups"
    # Confirm both orgs show, and that the stripe ID shows.
    Then I should see the text "BEHAT_PASS" in the "Behat-STRIPE-ID" row
    Then I should see the text "BEHAT_UNRELATED" in the "Behat-OTHER-STRIPE" row
    And I should see the text "Behat-NO-STRIPE" in the "Name" column
    # Test stripe ID filtering restricts to just the gripe with stripe ID.
    When I enter "BEHAT_PASS" for "Stripe Customer ID"
    And I press the "Apply" button
    Then I should see the text "BEHAT_PASS" in the "Behat-STRIPE-ID" row
    And I should not see the text "Behat-NO-STRIPE"
    And I should not see the text "Behat-OTHER-STRIPE"
