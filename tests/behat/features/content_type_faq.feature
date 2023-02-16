@api
Feature: Check that content type faq and the respective fields exist and are as expected.

  Scenario: All FAQ fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/types/manage/faq/fields"
    Then I should see "Body" in the "body" row
    Then I should see "Help Category" in the "field_help_category" row
    Then I should see "Insert Image" in the "field_insert_image" row
    Then I should see "Job Category" in the "field_job_category" row
    Then I should see "Related FAQs" in the "field_related_faqs" row

  Scenario: A FAQ is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/faq"
    And I enter "FAQ Test" for "Title"
    And I press the "Save" button
    Then I should see a node created successfully message
