@api
Feature:Group Organization Assignments related functionality works as expected.

  Scenario: Group users can either access or not access the group assignments view based on group role.
    Given there is a group of type "organization" with the label "Behat Testing Assignments View"
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | testingRegular@example.com | testingRegular@example.com | 1      | 500001 | active_org_member |
      | testingAdmin@example.com   | testingAdmin@example.com   | 1      | 500002 | active_org_member |
    And user with name "testingRegular@example.com" is a member of group with title "Behat Testing Assignments View"
    And user with name "testingAdmin@example.com" is a member of group with title "Behat Testing Assignments View" and group role "admin"
    When I am logged in as "testingRegular@example.com"
    And I view the path "assignments" relative to the group with title "Behat-Testing-Assignments-View"
    Then the response status code should be 403
    When I am logged in as "testingAdmin@example.com"
    And I view the path "assignments" relative to the group with title "Behat-Testing-Assignments-View"
    Then the response status code should be 200
