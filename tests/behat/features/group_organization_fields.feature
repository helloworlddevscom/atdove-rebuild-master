@api
Feature: Check that groups and their respective fields exist and are as expected.

  Scenario: All organization group fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/group/types/manage/organization/fields"
    Then I should see "Allow EmployeeID" in the "field_allow_employeeid" row
    Then I should see "Allow Import Team Management" in the "field_allow_import_team_manageme" row
    Then I should see "Clinic Address" in the "field_clinic_address" row
    Then I should see "Clinic Logo" in the "field_clinic_logo" row
    Then I should see "Clinic URL" in the "field_clinic_url" row
    Then I should see "Current Expiration Date" in the "field_current_expiration_date" row
    Then I should see "Current_Product ID" in the "field_current_product_id" row
    Then I should see "Import Team File" in the "field_import_team_file" row
    Then I should see "License Status" in the "field_license_status" row
    Then I should see "Notes" in the "field_notes" row
    Then I should see "OpenID Client ID" in the "field_openid_client_id" row
    Then I should see "Phone" in the "field_phone" row
    Then I should see "Sponsor Name" in the "field_sponsor_name" row
    Then I should see "Stripe Customer ID" in the "stripe_customer_id" row
    Then I should see "Member Limit" in the "field_member_limit" row
    Then I should see "Description of Organization" in the "field_body" row

  Scenario: Stripe field is hidden for group admins editing their group.
    Given I am logged in as a user with the 'active_org_member' role
    And I am a "admin" in a "organization" group type
    When I view the path "edit" relative to my current group
    Then I should not see a "Stripe Customer ID" field

  Scenario: Stripe field is visible for global admins.
    Given group entities:
      | type | label | field_stripe_customer_id | id |
      | organization | Behat Test Org | BEHAT_PASS | 9999 |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/edit"
    Then I should see a "Stripe Customer ID" field

  @stripe-id
  Scenario: Organization stripe field displays error when edited.
    Given group entities:
      | type | label | field_stripe_customer_id | id |
      | organization | Behat Test Org | BEHAT_PASS | 9999 |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/edit"
    And I enter "BEHAT_TEST_FAIL" for "Stripe Customer ID"
    And I press the "Save" button
    Then I should see the text "The Stripe ID (BEHAT_TEST_FAIL) could not be validated by stripe. Please double check the ID and try again."
    And I enter "BEHAT_TEST_PASS" for "Stripe Customer ID"
    And I press the "Save" button
    Then I should see an updated successfully message

  Scenario: Member limit field is hidden for group admins editing their group.
    Given I am logged in as a user with the 'active_org_member' role
    And I am a "admin" in a "organization" group type
    When I view the path "edit" relative to my current group
    Then I should not see a "Member Limit" field

  Scenario: Stripe field is visible for global admins.
    Given group entities:
      | type | label | field_stripe_customer_id | id |
      | organization | Behat Test Org | BEHAT_PASS | 9999 |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/edit"
    Then I should see a "Member Limit" field
