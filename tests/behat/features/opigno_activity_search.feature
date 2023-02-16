@api
Feature: Privileged users can access and use the opigno_activity_search view.

  Scenario: Users with active_org_member global role can access opigno_activity_search view.
    Given I am logged in as a user with the "active_org_member" role
    When I am at "/opigno-activity-search/veterinarian"
    Then the response status code should be 200
    And I should see the heading "veterinarian"

  Scenario: A user can search for activities and filter based on job category.
    Given I am logged in as a user with the 'administrator' role
    Given "job_categories" terms:
      | name            | tid  |
      | behat_testing_1 | 1234 |
      | behat_testing_2 | 1235 |
    # Create a video.
    When I am at "admin/structure/opigno_activity/add/atdove_video"
    And I fill in "Name" with "Hulk"
    And I fill in "Job Category" with "behat_testing_1 (1234)"
    And I press the "Save" button
    Then I should see the text "Created the Hulk Activity" in the "messages" region
    # Create an article.
    When I am at "/admin/structure/opigno_activity/add/atdove_article"
    And I fill in "Name" with "Hogan"
    And I fill in "Job Category" with "behat_testing_2 (1235)"
    And I press the "Save" button
    Then I should see the text "Created the Hogan Activity" in the "messages" region
    # Go and actually search.
    When I am at '/opigno-activity-search'
    Then I should see the text "Hulk"
    And I should see the text "Hogan"
    When I select "behat_testing_1" from "Pick Your Track"
    And I press the "Apply" button
    Then I should see the text "Hulk"
    And I should not see the text "Hogan"
    When I select "behat_testing_2" from "Pick Your Track"
    And I press the "Apply" button
    Then I should see the text "Hogan"
    And I should not see the text "Hulk"
    # Cleanup all opigno content created as part of the test.
    Then I am at "/admin/structure/opigno-activities"
    And I click "Delete" on the row containing "Hulk"
    And I press the "Delete" button
    Then I am at "/admin/structure/opigno-activities"
    Then I click "Delete" on the row containing "Hogan"
    And I press the "Delete" button
    Then I am at "/admin/structure/opigno-activities"
    Then I should not see the text "Hulk"
    And I should not see the text "Hogan"


