@api
Feature: Check that organization group permissions are maintained as necessary for invite functionality.

  Scenario: Org admin role has "Administer group members" permission.
    # If this fails it means that an org admin will not see the invite members buttons
    # at /group/GROUP_ID/members
    Given I am logged in as a user with the 'administrator' role
    And I am at "/admin/group/types/manage/organization/permissions"
    Then the "edit-organization-admin-view-group-membership-content" checkbox should be checked

  Scenario: Org admin role has permissions to allow creating/editing/deleting subgroups.
    Given I am logged in as a user with the 'administrator' role
    And I am at "/admin/group/types/manage/organization/permissions"
    Then the "edit-organization-admin-create-subgrouporganizational-groups-content" checkbox should be checked
    And the "edit-organization-admin-delete-any-subgrouporganizational-groups-content" checkbox should be checked
    And the "edit-organization-admin-delete-own-subgrouporganizational-groups-content" checkbox should be checked
    And the "edit-organization-admin-update-any-subgrouporganizational-groups-content" checkbox should be checked
    And the "edit-organization-admin-update-own-subgrouporganizational-groups-content" checkbox should be checked
    And the "edit-organization-admin-view-subgrouporganizational-groups-content" checkbox should be checked
