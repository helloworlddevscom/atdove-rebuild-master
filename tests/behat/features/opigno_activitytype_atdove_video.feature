@api
Feature:
  As an admin, I want to be able to create an Opigno AtDove Video.

  Scenario: AtDove Video activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/atdove_video/edit/fields"
    Then I should see "Accreditation Info" in the "field_accreditation_info" row
    Then I should see "Accredited For" in the "field_accredited_for" row
    Then I should see "Additional Content Categories" in the "field_additional_content_categor" row
    Then I should see "Additional Downloads" in the "field_additional_downloads" row
    Then I should see "CE Matter Category" in the "field_ce_matter_category" row
    Then I should see "Content Category" in the "field_content_category" row
    Then I should see "Contributors" in the "field_contributors" row
    Then I should see "Credit Hours" in the "field_credit_hours" row
    Then I should see "Exclude From Search Results" in the "field_exclude_from_search_result" row
    Then I should see "External Contributors" in the "field_external_contributors" row
    Then I should see "Featured Image" in the "field_featured_image" row
    Then I should see "Group Audience" in the "field_group_audience" row
    Then I should see "Job Category" in the "field_job_category" row
    Then I should see "Marketing Title" in the "field_marketing_title" row
    Then I should see "Media Image" in the "field_media_image" row
    Then I should see "Premium" in the "field_premium" row
    Then I should see "Related Ads" in the "field_related_ads" row
    Then I should see "Related Blog Entries" in the "field_related_blog" row
    Then I should see "Related Opigno Articles" in the "field_opigno_articles" row
    Then I should see "Related Opigno Quiz" in the "field_opigno_quiz" row
    Then I should see "Related Opigno Videos" in the "field_opigno_videos" row
    Then I should see "Related Surveys" in the "field_related_surveys" row
    Then I should see "Search Keywords" in the "field_search_keywords" row
    Then I should see "Special Class" in the "field_special_class" row
    Then I should see "Thumbnail Image" in the "field_thumbnail_image" row
    Then I should see "Video Addon Button" in the "field_video_addon_button" row
    Then I should see "Video Body" in the "field_video_body" row
    Then I should see "Video Checklist" in the "field_video_checklist" row
    Then I should see "Video Comments" in the "field_video_comments" row
    Then I should see "Video Duration" in the "field_video_duration" row
    Then I should see "Wistia Video" in the "field_wistia_video" row

  Scenario: An Opigno AtDove Video can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/atdove_video"
    And I fill in "Name" with "Test Video Name"
    Then I press the "Save" button
    Then I should see a created successfully message
