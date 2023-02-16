@api
Feature:
  Check that Opigno AtDove Article activity type and the respective fields exist and are as expected.

  Scenario: AtDove Article activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/atdove_article/edit/fields"
    Then I should see "Accreditation Info" in the "field_accreditation_info" row
    Then I should see "Additional Content Categories" in the "field_additional_content_categor" row
    Then I should see "Article Body" in the "field_article_body" row
    Then I should see "Article Comments" in the "field_article_comments" row
    Then I should see "Article Image" in the "field_article_image" row
    Then I should see "Article Scorm" in the "field_article_scorm" row
    Then I should see "CE Matter Category" in the "field_ce_matter_category" row
    Then I should see "Content Category" in the "field_content_category" row
    Then I should see "Contributors" in the "field_contributors" row
    Then I should see "Credit Hours" in the "field_credit_hours" row
    Then I should see "Exclude From Search Results" in the "field_exclude_from_search_result" row
    Then I should see "Group Audience" in the "field_group_audience" row
    Then I should see "Job Category" in the "field_job_category" row
    Then I should see "Marketing Title" in the "field_marketing_title" row
    Then I should see "Media Image" in the "field_media_image" row
    Then I should see "Publish Date" in the "field_publish_date" row
    Then I should see "Related Ads" in the "field_related_ads" row
    Then I should see "Related Blog Entries" in the "field_related_blog" row
    Then I should see "Related Opigno Articles" in the "field_opigno_articles" row
    Then I should see "Related Opigno Quiz" in the "field_opigno_quiz" row
    Then I should see "Related Opigno Videos" in the "field_opigno_videos" row
    Then I should see "Related Surveys" in the "field_related_surveys" row
    Then I should see "Search Keywords" in the "field_search_keywords" row

  Scenario: An opigno activity type atdove article can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/atdove_article"
    And I fill in "Name" with "Test article name"
    Then I press the "Save" button
    Then I should see a created successfully message
