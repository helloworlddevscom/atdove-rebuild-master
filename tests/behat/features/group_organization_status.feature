@api
Feature: Members of an organization can utilize the site as necessary based on their organization's status.

  Scenario: Members of a group change with it's active status, and remain if they have a secondary active group.
    Given group entities:
      | type         | label                 | field_license_status | id   |
      | organization | Behat ACTIVE Test Org | active               | 4001 |
      | organization | Behat STATUS Test Org | inactive             | 4002 |
    And users:
      | name                                         | mail                          | status | uid  | roles             |
      | memberofExpiredGroup@example.com             | singleGroupUser1@example.com  | 1      | 5001 |                   |
      | MemberOfActiveGroup2@example.com             | singleGroupUser2@example.com  | 1      | 5002 | active_org_member |
      | MemberOfActiveAndInactiveGroup2@example.com  | singleGroupUser3@example.com  | 1      | 5003 | active_org_member |
      | OrgAdminOfOrganization@example.com           | singleGroupUser4@example.com  | 1      | 5004 |                   |
    And Group memberships:
      | uid  | gid  | roles              |
      | 5001 | 4002 |                    |
      | 5002 | 4001 |                    |
      | 5003 | 4001 |                    |
      | 5003 | 4002 |                    |
    And user with name "OrgAdminOfOrganization@example.com" is a member of group with title "Behat STATUS Test Org" and group role "admin"
    When I am logged in as "memberofExpiredGroup@example.com"
    Then I should see the text "Your Organization Behat STATUS Test Org has had their subscription expire. Please reach out to your organizations billing admins to have them renew" in the "messages" region
    # @todo: check user CANNOT still access the site.
    When I am logged in as "MemberOfActiveGroup2@example.com"
    Then I should not see the text "Your Organization Behat ACTIVE Test Org has had their subscription expire. Please reach out to your organizations billing admins to have them renew"
    # @todo: check user CAN still access the site.
    When I am logged in as "MemberOfActiveAndInactiveGroup2@example.com"
    Then I should see the text "The license for your organization Behat STATUS Test Org has expired. Please contact your Organization Admin." in the "messages" region
    # @todo: check user CAN still access the site.
    When I am logged in as "OrgAdminOfOrganization@example.com"
    Then I should see the text "The subscription for your organization Behat STATUS Test Org has expired. Please renew your subscription here" in the "messages" region
    # @todo: check user CANNOT still access the site.
