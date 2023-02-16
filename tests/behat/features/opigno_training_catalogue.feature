@api
Feature: Privileged users can access the opigno_training_catalogue view.

  Scenario: Users with active_org_member global role can access opigno_training_catalogue view.
    Given I am logged in as a user with the "active_org_member" role
    When I am at "/catalogue"
    Then the response status code should be 200
    And I should see the heading "Training catalogue"
