@api
Feature: Check that users and their respective fields exist and are as expected.

  # @todo: populate all user fields.
  Scenario: All user fields exist in config.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/config/people/accounts/fields"
    # alpha order after here, example for jr dev.
    Then I should see "Address" in the "field_user_address" row
    Then I should see "Age Range" in the "field_user_age_range" row
    Then I should see "Bio" in the "field_user_bio" row
    Then I should see "Blog Description" in the "field_user_blog_description" row
    Then I should see "Blog Title" in the "field_user_blog_title" row
    Then I should see "Consultant" in the "field_user_consultant" row
    Then I should see "Consultant - Expert In" in the "field_user_consultant_expert_in" row
    Then I should see "Consultant - Extended Bio" in the "field_user_consultant_ext_bio" row

  Scenario: Field groups appear as expected.
    Given users:
      |name                    | mail                     | status| uid    | field_first_name | field_last_name |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | 999999 | Bob              | Dobalina        |
    When I am logged in as "bobadobalina@example.com"
    When I am at "/user/999999/edit"
    Then the "details[data-drupal-selector='edit-group-general']" element should contain "Account"
    Then the "details[data-drupal-selector='edit-group-personal']" element should contain "Personal"
    Then the "details[data-drupal-selector='edit-group-professional']" element should contain "Professional"
    Then the "details[data-drupal-selector='edit-group-race']" element should contain "RACE Cert"
