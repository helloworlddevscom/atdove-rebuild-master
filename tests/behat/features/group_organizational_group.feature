@api
Feature: Check that organizational groups are configured as desired.

  Scenario: Organizational group permissions are set.
    Given I am logged in as a user with the "administrator" role
    When I am at "/admin/group/types/manage/organizational_groups/permissions"
    Then the "organizational_groups-member[view group]" checkbox should not be checked

  Scenario: Global admin role has "Administer group members" permission.
    Given I am logged in as a user with the 'administrator' role
    And I am at "/admin/group/types/manage/organizational_groups/permissions/outsider"
    Then the "edit-organizational-groups-a416e6833-administer-members" checkbox should be checked

  Scenario: A group admin can navigate to a listing of organizational groups via org admin top menu + drop down
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | gadmin@example.com    | gadmin@example.com    | 1      | 500004 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    When I am logged in as "gadmin@example.com"
    And I am on "/group/40001"
    Then I should see the link "Sub-Groups" in the "org admin menu" region
    And I should see the link "Sub-Groups" in the "group admin drop down menu" region
    And I click "Sub-Groups" in the "org admin menu" region
    Then I should see the text "TEST Subgroups"
    And I click "Sub-Groups" in the "group admin drop down menu" region
    Then I should see the text "TEST Subgroups"

  Scenario: A group admin can navigate to an organizational group via the Sub Groups view.
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | gadmin@example.com    | gadmin@example.com    | 1      | 500004 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    When I am logged in as "gadmin@example.com"
    And I am on "/group/40001/subgroups"
    Then the ".view-subgroups" element should contain "Test Sub Group"
    And I click "Test Sub Group"
    Then I should see the text "TEST SUB GROUP"

  Scenario: A group admin can ACCESS an organizational group, even if not member of sub group
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 500005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    When I am logged in as "gadmin@example.com"
    And I load the group with title "Test Sub Group"
    Then I should see the text "TEST SUB GROUP"

  Scenario: A group admin can create a sub group
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | gadmin@example.com    | gadmin@example.com    | 1      | 500004 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    When I am logged in as "gadmin@example.com"
    And I load the group with title "Behat-Test"
    And I click "Create Organizational Group" in the "group operations" region
    Then I should see the heading "Add Organization: Subgroup (Organizational Groups)"
    And I fill in "Title" with "DELETE- Behat subgroup"
    And I press the "Save" button
    Then I should see a created successfully message

  Scenario: A group admin can edit and delete an organizational group
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 500005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    And I am logged in as "gadmin@example.com"
    And I am at "/group/40001/subgroups"
    # Check editing.
    When I click "Edit Subgroup" on the row containing "Test Sub Group"
    And I fill in "Title" with "Test EDIT Sub Group"
    And I press the "Save" button
    Then I should see an updated successfully message
    # Check Deleting
    When I click "Delete Subgroup" on the row containing "Test EDIT Sub Group"
    And I press the "Delete" button
    Then I should see the text "has been deleted" in the "messages" region

  Scenario: A normal user CANNOT access an organizational group they are a member of.
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 500005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    And user with name "subgroupmember@example.com" is a member of group with title "Behat-Test"
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    And user with name "subgroupmember@example.com" is a member of group with title "Test Sub Group"
    When I am logged in as "subgroupmember@example.com"
    And I load the group with title "Test Sub Group"
    Then the response status code should be 403

  Scenario: A user can NOT access an organizational group they are NOT a member of.
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 500005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    And user with name "subgroupmember@example.com" is a member of group with title "Behat-Test"
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    When I am logged in as "subgroupmember@example.com"
    And I load the group with title "Test Sub Group"
    Then the response status code should be 403

  Scenario: A group admin can access a list of group members in an organizational group.
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 500005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    And user with name "subgroupmember@example.com" is a member of group with title "Test Sub Group"
    When I am logged in as "gadmin@example.com"
    And I am at "/group/40001/subgroups"
    And I click "Manage Members" on the row containing "Test Sub Group"
    Then the response status code should be 200
    And I should see the text "Test Sub Group members"
    # View for some reason shortens user member name.
    And I should see the text "subgroupmember"

  @todo
  Scenario: A group admin can add & remove members to an organizational group.
    Given group entities:
      | type         | label      | field_license_status | id    | field_member_limit |
      | organization | Behat-Test | active               | 40001 | 30                 |
    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 500005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
    When I am logged in as "gadmin@example.com"
    And I am at "/group/40001/subgroups"
    And I click "Manage Members" on the row containing "Test Sub Group"
    And I click "Add member"
    Then I should see the heading "Add Organizational Groups: Group membership"
    And I fill in "User" with "subgroupmember@example.com (500005)"
    And I press the "Save" button
    Then I should see the text "Test Sub Group members"
    And I should see the text "subgroupmember"
    # Test removing a member from a group
    And I click "Remove Member" on the row containing "subgroupmember"
    Then I should see the text "Are you sure you want to delete"
    And I press the "Delete" button
    Then I should see the text "Test Sub Group members"
    And I should not see the text "subgroupmember"

  @javascript @assignments
  Scenario: When assigning an article to a group, relevant subgroups should be in the options presented.
    Given group entities:
      | type         | label            | field_license_status | id     | field_member_limit |
      | organization | Behat-Test-Main  | active               | 500001 | 30                 |
      | organization | Behat-Test-Extra | active               | 500002 | 30                 |
    # @todo create a training plan we want to make sure does not render as an option.
    And the group "Behat-Test-Main" has a subgroup of type "organizational_groups" titled "Test Sub Group INCLUDED 1"
    And the group "Behat-Test-Main" has a subgroup of type "organizational_groups" titled "Test Sub Group INCLUDED 2"
    And the group "Behat-Test-Extra" has a subgroup of type "organizational_groups" titled "Test Sub Group EXCLUDED"
    And users:
      | name                       | mail                       | status | uid    | roles             |
      | gadmin@example.com         | gadmin@example.com         | 1      | 700004 | active_org_member |
      | subgroupmember@example.com | subgroupmember@example.com | 1      | 700005 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test-Main" and group role "admin"
    And "opigno_activity" entities:
      | type           | id     | name                      |
      | atdove_article | 400001 | behat test atdove_article |
    When I am logged in as "gadmin@example.com"
    And I am at "/activity/400001"
    # Click to assign to person.
    And I click the "a.user-assign-to-person" element
    And I wait for AJAX to finish
    And I wait 4 seconds
    Then the "Assign to a Group" field should have options:
    """
    Behat-Test-Main
    Test Sub Group INCLUDED 1
    Test Sub Group INCLUDED 2
    """
    And the "Assign to a Group" field should not have options:
    """
    Test Sub Group EXCLUDED
    Behat-Test-Extra
    """

  @javascript @assignments
  Scenario: You can assign an article to an organizational group and all members get the assignment
    Given group entities:
      | type         | label            | field_license_status | id     | field_member_limit |
      | organization | Behat-Test-Main  | active               | 500001 | 30                 |
    # @todo create a training plan we want to make sure does not render as an option.
    And the group "Behat-Test-Main" has a subgroup of type "organizational_groups" titled "Test Organizational Group"
    And users:
      | name                        | mail                       | status | uid    | roles             |
      | gadmin@example.com          | gadmin@example.com         | 1      | 700004 | active_org_member |
      | subgroupmember1@example.com | subgroupmember@example.com | 1      | 700005 | active_org_member |
      | subgroupmember2@example.com | subgroupmember@example.com | 1      | 700006 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Test-Main" and group role "admin"
    And user with name "subgroupmember1@example.com" is a member of group with title "Behat-Test-Main"
    And user with name "subgroupmember1@example.com" is a member of group with title "Test Organizational Group"
    And user with name "subgroupmember2@example.com" is a member of group with title "Behat-Test-Main"
    And user with name "subgroupmember2@example.com" is a member of group with title "Test Organizational Group"
    And "opigno_activity" entities:
      | type           | id     | name                      |
      | atdove_article | 400001 | behat test atdove_article |
    # ASSIGN AN ARTICLE TO SUB GROUP
    When I am logged in as "gadmin@example.com"
    And I am at "/activity/400001"
    # Click to assign to person.
    And I click the "a.user-assign-to-person" element
    And I wait for AJAX to finish
    And I wait 4 seconds
    And I select "Test Organizational Group" from "Assign to a Group"
    And I press the "Assign Activity" button
    And I am not logged in
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/content"
    Then I should see the text "behat test atdove_article - uid-700005" in the "TITLE" column
    Then I should see the text "behat test atdove_article - uid-700006" in the "TITLE" column

#  @wip @todo
#  Scenario: A group admin cannot add a user to a subgroup that is not in the primary group.
#    Given group entities:
#      | type         | label      | field_license_status | id    | field_member_limit |
#      | organization | Behat-Test | active               | 40001 | 30                 |
#    And users:
#      | name                       | mail                       | status | uid    | roles             |
#      | gadmin@example.com         | gadmin@example.com         | 1      | 500004 | active_org_member |
#      | externalmember@example.com | externalmember@example.com | 1      | 500005 | active_org_member |
#    And user with name "gadmin@example.com" is a member of group with title "Behat-Test" and group role "admin"
#    And the group "Behat-Test" has a subgroup of type "organizational_groups" titled "Test Sub Group"
#    When I am logged in as "subgroupmember@example.com"
#    And I load the group with title "Test Sub Group"
#    # @todo: Attempt to add external member
