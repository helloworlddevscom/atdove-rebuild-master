@api
Feature: Check that migrated organizations are handled properly.

  Scenario: Organization groups that are expired without a stripe ID are moved to inactive on cron run.
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id   |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 4001 |
      | organization | Behat-A0E0S0 | inactive             | 2028-04-05T00:00:00           |                          | 4002 |
      | organization | Behat-A1E1S0 | active               | 2020-04-05T00:00:00           |                          | 4003 |
      | organization | Behat-A1E1S1 | active               | 2020-04-05T00:00:00           | BEHAT_PASS               | 4004 |
    And I set the state "AtDoveOrgLegacyExpirationLastRun" to 0
    When I am logged in as a user with the 'administrator' role
    And I visit "/admin/config/system/cron/jobs/core_event_dispatcher_cron/run"
    Then I should see the text "Cron job Default cron handler (Core Event Dispatcher) was successfully run."
    When I visit "/group/4001/edit"
    Then the "select[name='field_license_status'] option[selected='selected']" element should contain "Active"
    When I visit "/group/4002/edit"
    Then the "select[name='field_license_status'] option[selected='selected']" element should contain "Inactive"
    When I visit "/group/4003/edit"
    Then the "select[name='field_license_status'] option[selected='selected']" element should contain "Inactive"
    When I visit "/group/4004/edit"
    Then the "select[name='field_license_status'] option[selected='selected']" element should contain "Active"
