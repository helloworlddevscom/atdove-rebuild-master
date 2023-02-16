@api
Feature: Check that content type blog and the respective fields exist and are as expected.

  Scenario: All blog fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/types/manage/blog/fields"
    Then I should see "Additional Content Categories" in the "field_additional_content_categor" row
    Then I should see "Blog Categories" in the "field_blog_category" row
    Then I should see "Blog Comments" in the "field_blog_comments" row
    Then I should see "Blog Image" in the "field_blog_image" row
    Then I should see "Body" in the "body" row
    Then I should see "Content Category" in the "field_content_category" row
    Then I should see "Contributors" in the "field_contributors" row
    Then I should see "Exclude From Search Results" in the "field_exclude_from_search_result" row
    Then I should see "Job Category" in the "field_job_category" row
    Then I should see "Marketing Title" in the "field_marketing_title" row
    Then I should see "Media Image" in the "field_media_image" row
    Then I should see "Related Ads" in the "field_related_ads" row
    Then I should see "Related Blog Entries" in the "field_related_blog" row
    Then I should see "Related Opigno Articles" in the "field_opigno_articles" row
    Then I should see "Related Opigno Videos" in the "field_opigno_videos" row
    Then I should see "Related Surveys" in the "field_related_surveys" row
    Then I should see "Search Keywords" in the "field_search_keywords" row

  Scenario: A blog is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/blog"
    And I enter "Blog Test" for "Title"
    And I press the "Save" button
    Then I should see a node created successfully message

