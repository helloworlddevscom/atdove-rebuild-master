@api
Feature: Org Admins can perform nescessary tasks.

  @javascript
  Scenario: Org admins can access group members user profiles.
    Given there is a group of type "organization" with the label "Behat-Gadmin-Access-Member-Profiles"
    And users:
      | name               | mail               | status | uid    | roles             |
      | gadmin@example.com | gadmin@example.com | 1      | 500004 | active_org_member |
      | normal@example.com | normal@example.com | 1      | 500005 | active_org_member |
    And user with name "normal@example.com" is a member of group with title "Behat-Gadmin-Access-Member-Profiles"
    And user with name "gadmin@example.com" is a member of group with title "Behat-Gadmin-Access-Member-Profiles" and group role "admin"
    When I am logged in as "gadmin@example.com"
    And I view the path "members" relative to the group with title "Behat-Gadmin-Access-Member-Profiles"
    Then I should see the text "Behat-Gadmin-Access-Member-Profiles members"
    And I click "normal@example.com"
    Then I should not see the text "Access denied"
