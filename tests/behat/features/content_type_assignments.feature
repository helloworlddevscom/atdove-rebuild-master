@api @assignments
Feature:
  Check that Assignment content type and the respective fields exist and are as expected.

  Scenario: Assignment content type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types/manage/assignment/fields"
    Then I should see text matching "field_assigned_content"
    Then I should see text matching "field_assignee"
    Then I should see text matching "field_assignment_status"
    Then I should see text matching "body"
    Then I should see text matching "field_certificate"
    Then I should see text matching "field_completed"
    Then I should see text matching "field_completed_quizzes"
    Then I should see text matching "field_due_date"
    Then I should see text matching "field_organization"
    Then I should see text matching "field_related_quiz"
    Then I should see text matching "field_related_trainingplan"

  @javascript
  Scenario: An assignment can be created and viewed with training plans, quiz's and certificates.

    Given users:
      | name                        | mail                        | status | uid     | roles             |
      | orgadminofGroup@example.com | orgadminofGroup@example.com | 1      | 40011199 | active_org_member |
    And "opigno_activity" entities:
      | type           | id     | name                      |
      | atdove_article | 400001 | behat test atdove_article |
    Given I am logged in as a user with the "administrator" role
    When I am at "node/add/assignment"
    And I fill in "Title" with "Test Assignment Title"
    And I fill in "Assignee" with "orgadminofGroup@example.com"
    And I fill in "Assigned Content" with "behat test atdove_article"
    Then I press the "Save" button
    # @todo check the viewing portion of the scenerio
    Then I should see a node created successfully message

  @javascript
  Scenario: An article can be assigned to a user, and creates an assignment as it should.
    Given group entities:
      | type         | label            | field_license_status | id     | field_member_limit |
      | organization | Behat-Test-Main  | active               | 500001 | 30                 |
    And users:
      | name                    | mail                       | status | uid    | roles             |
      | gadmin@example.com      | gadmin@example.com         | 1      | 700004 | active_org_member |
      | othermember@example.com | subgroupmember@example.com | 1      | 700005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test-Main" and group role "admin"
    And user with name "othermember@example.com" is a member of group with title "Behat-Test-Main"
    And "opigno_activity" entities:
      | type           | id     | name                      |
      | atdove_article | 400001 | behat test atdove_article |
    When I am logged in as "gadmin@example.com"
    And I am at "/activity/400001"
    # Click to assign to person.
    And I click the "a.user-assign-to-person" element
    And I wait for AJAX to finish
    And I wait 4 seconds
    And I fill in "Assign to Users" with "othermember@example.com (700005)"
    And I fill in "Due Date" with "09/25/2025"
    And I press the "Assign Activity" button
    And I am not logged in
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/content"
    Then I should see the text "behat test atdove_article - uid-700005" in the "TITLE" column
    And I click "Edit" on the row containing "behat test atdove_article - uid-700005"
    Then the field "Assignee" should have a value of "othermember@example.com (700005)"
    Then the field "Assigned Content" should have a value of "behat test atdove_article (400001)"
    # Equivelant of: Then the field "Due On" should have a value of "09/25/2025"
    Then the field "field_due_date[0][value][date]" should have a value of "2025-09-25"

  @javascript
  Scenario: When a user gets assigned content, the notification that shows up redirects to the content itself, and not the assignment.

    Given users:
      | name                    | mail                    | status | uid      | roles              |
      | userofGroup@example.com | userofGroup@example.com | 1      | 40011199 | active_org_member  |
    And "opigno_activity" entities:
      | type           | id     | name                      |
      | atdove_article | 400001 | notification test atdove_article |
    Given I am logged in as a user with the "administrator" role
    When I am at "node/add/assignment"
    And I fill in "Title" with "Test Assignment Title"
    And I fill in "Assignee" with "userofGroup@example.com"
    And I fill in "Assigned Content" with "notification test atdove_article"
    Then I press the "Save" button
    Then I should see a node created successfully message
    When I am logged in as "userofGroup@example.com"
    And I click the "body > div.dialog-off-canvas-main-canvas > header > div > div > div.col-lg-5.col-xxl-5.col-right > div.block-notifications > div.block-notifications__item.block-notifications__item--notifications" element
    And I click "notification test"
    And I wait 5 seconds
    Then I should see the text "notification test"
    And I should see the text "comments"
    And I should see the text "quizzes"
