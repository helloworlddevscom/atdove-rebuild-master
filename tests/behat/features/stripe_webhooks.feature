@api
Feature: Check that all stripe webhooks function as expected.

  Scenario Outline:
    Given users:
      | name                     | mail                     | status| uid  | field_first_name | field_last_name |
      | bobadobalina@example.com | bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        |
    And group:
      | label          | type         | field_license_status | field_stripe_customer_id |
      | behat test org | organization | <initial_status>     | behattest1234            |
    And user with name "bobadobalina@example.com" is a member of group with title "behat test org"
    Given I am logged in as a user with the 'administrator' role
    When I trigger the webhook for a "<event>" event with stripe customerid of "behattest1234"
    And I edit the group with label "behat test org"
    Then I should see an "option[value='<final_status>'][selected='selected']" element

    Examples:
      | event                 | initial_status | final_status |
      | subscription created  | inactive       | active       |
      | subscription renewed  | inactive       | active       |
      | subscription trialing | inactive       | active       |
      | subscription expired  | active         | inactive     |
      | subscription deleted  | active         | inactive     |
      | subscription unpaid   | active         | inactive     |

  Scenario: New subscriptions set the group member limit as expected (Default data = 75)
    Given users:
      | name                     | mail                     | status| uid  | field_first_name | field_last_name |
      | bobadobalina@example.com | bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        |
    And group:
      | label          | type         | field_license_status | field_stripe_customer_id |
      | behat test org | organization | <initial_status>     | behattest1234            |
    And user with name "bobadobalina@example.com" is a member of group with title "behat test org"
    Given I am logged in as a user with the 'administrator' role
    When I trigger the webhook for a "subscription created" event with stripe customerid of "behattest1234"
    And I edit the group with label "behat test org"
    Then the field "Member Limit" should have a value of "75"

  Scenario: New subscriptions that do not have a number in the description set the group member limit as expected (Default data = 75)
    Given users:
      | name                     | mail                     | status| uid  | field_first_name | field_last_name |
      | bobadobalina@example.com | bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        |
    And group:
      | label          | type         | field_license_status | field_stripe_customer_id |
      | behat test org | organization | <initial_status>     | behattest1234            |
    And user with name "bobadobalina@example.com" is a member of group with title "behat test org"
    Given I am logged in as a user with the 'administrator' role
    When I trigger the webhook for a "subscription created no description" event with stripe customerid of "behattest1234"
    And I edit the group with label "behat test org"
    Then the field "Member Limit" should have a value of "50"

  Scenario: New subscriptions that do not have any discernable limit set the group member limit as expected (20)
    Given users:
      | name                     | mail                     | status| uid  | field_first_name | field_last_name |
      | bobadobalina@example.com | bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        |
    And group:
      | label          | type         | field_license_status | field_stripe_customer_id |
      | behat test org | organization | <initial_status>     | behattest1234            |
    And user with name "bobadobalina@example.com" is a member of group with title "behat test org"
    Given I am logged in as a user with the 'administrator' role
    When I trigger the webhook for a "subscription created no anything" event with stripe customerid of "behattest1234"
    And I edit the group with label "behat test org"
    Then the field "Member Limit" should have a value of "20"
