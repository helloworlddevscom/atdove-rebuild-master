@api
Feature: Organization membership invites work as expected.

  Scenario: A logged in user should see an invite link only if they have an invite.
    Given group entities:
      | type         | label         | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    | field_member_limit |
      | organization | Behat1-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 | 3                  |
      | organization | Behat2-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40002 | 3                  |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | testing1@example.com  | testing1@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing1@example.com" is a member of group with title "Behat1-A1E0S1"
    When I am logged in as "testing1@example.com"
    Then I should not see the link "Invitation" in the "userDropDownMenu" region
    # Reload the page after invite the user to a group
    When the user "testing1@example.com" has an invite to group with name "Behat2-A1E0S1"
    And I am at "/user"
    Then I should see the link "Invitation" in the "userDropDownMenu" region

  # @todo: Add test coverage that an org admin can create a group invitation
  # @todo: Add test coverage that an accepted invitation gets a user into a group

  Scenario: Global admins can still add members to an organization with members totaling equal or over limit.
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    | field_member_limit |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 | 3                  |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | testing1@example.com  | testing1@example.com  | 1      | 500001 | active_org_member |
      | testing2@example.com  | testing2@example.com  | 1      | 500002 | active_org_member |
      | testing3@example.com  | testing3@example.com  | 1      | 500003 | active_org_member |
    And user with name "testing1@example.com" is a member of group with title "Behat-A1E0S1"
    And user with name "testing2@example.com" is a member of group with title "Behat-A1E0S1"
    And user with name "testing3@example.com" is a member of group with title "Behat-A1E0S1"
    When I am logged in as a user with the 'administrator' role
    And I am on "/group/40001/content/add/group_invitation"
    Then I should not see the text "You are currently over the limit of users for this group and will need a larger plan."
    And the response status code should be 200

  Scenario: An organization with members totaling equal or over limit cannot invite additional members.
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    | field_member_limit |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 | 3                  |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | testing1@example.com  | testing1@example.com  | 1      | 500001 | active_org_member |
      | testing2@example.com  | testing2@example.com  | 1      | 500002 | active_org_member |
      | testing3@example.com  | testing3@example.com  | 1      | 500003 | active_org_member |
      | gadmin@example.com    | gadmin@example.com   | 1      | 500004 | active_org_member |
    And user with name "testing1@example.com" is a member of group with title "Behat-A1E0S1"
    And user with name "testing2@example.com" is a member of group with title "Behat-A1E0S1"
    And user with name "testing3@example.com" is a member of group with title "Behat-A1E0S1"
    And user with name "gadmin@example.com" is a member of group with title "Behat-A1E0S1" and group role "admin"
    When I am logged in as "gadmin@example.com"
    And I am on "/group/40001/content/add/group_invitation"
    Then I should see the text "You are currently over the limit of users for this group and will need a larger plan."
    And the response status code should be 403
    When I am on "/group/40001/invite-members"
    Then the response status code should be 403

  # @todo: Org admins cannot see invites anyways but can access invite forms?
#  Scenario: Invite members buttons do not render for org admins in a group over it's membership limit.
#    Given group entities:
#      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    | field_member_limit |
#      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 | 3                  |
#    And users:
#      | name                  | mail                  | status | uid    | roles             |
#      | testing1@example.com  | testing1@example.com  | 1      | 500001 | active_org_member |
#      | testing2@example.com  | testing2@example.com  | 1      | 500002 | active_org_member |
#      | testing3@example.com  | testing3@example.com  | 1      | 500003 | active_org_member |
#      | gadmin@example.com    | gadmin@example.com   | 1      | 500004 | active_org_member |
#    And user with name "testing1@example.com" is a member of group with title "Behat-A1E0S1"
#    And user with name "testing2@example.com" is a member of group with title "Behat-A1E0S1"
#    And user with name "testing3@example.com" is a member of group with title "Behat-A1E0S1"
#    And user with name "gadmin@example.com" is a member of group with title "Behat-A1E0S1" and group role "admin"
#    When I am logged in as "gadmin@example.com"
#    And I am at "/group/40001/invitations"
#    Then I should not see the text "Invite member"
#    And I should not see the text "Invite members"
