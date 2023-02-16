@api
Feature: User dashboard shows content as it should.

  Scenario: Users can see their assignments on their dashboard.
    Given users:
      |name                    | mail                     | status| field_first_name | field_last_name | roles             | uid   | pass                     |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | Bob              | Dobalina        | active_org_member | 12345 | bobadobalina@example.com |
    Given there is an opigno quiz titled "Behat mega test quiz 2"
    And there is an opigno article titled "Behat Test article 2" referencing quiz "Behat mega test quiz 2"
    And the user "bobadobalina@example.com" is assigned the article with title "Behat Test article 2"
    When I am logged in as "bobadobalina@example.com"
    And I am at "/user"
    Then I should see the text "My Assignments"
    Then I should see "Behat mega test quiz 2" in the "Behat Test article 2" row
