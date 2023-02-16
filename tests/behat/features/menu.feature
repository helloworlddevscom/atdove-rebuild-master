@api @search_api
Feature: Menus are configured as expected and work
  # This tests whether atdove_default_content_update_9001() ran as expected.

  Scenario: main menu links imported successfully
    When I am logged in as a user with the 'administrator' role
    When I am at "admin/structure/menu/manage/main"
    Then I should see the heading 'Edit menu Main navigation'
    And I should not see "Training Catalogue" in the "#menu-overview" element
    And I should see "My Assignments" in the "#menu-overview" element
    And I should see "My Bookmarks" in the "#menu-overview" element

  Scenario: Administrator can see main menu links
    When I am logged in as a user with the 'administrator' role
    When I am at "/user"
    # Should see.
    And I should see "My Assignments" in the ".main-menu" element
    And I should see "My Bookmarks" in the ".main-menu" element
    And I should see "Catalogue" in the ".main-menu" element
    # Should not see.
    And I should not see "Training Catalogue" in the ".main-menu" element
    And I should not see "Home" in the ".main-menu" element
    And I should not see "Calendar" in the ".main-menu" element
    And I should not see "Statistics" in the ".main-menu" element
    And I should not see "My Flagging Collections" in the ".main-menu" element

  Scenario: main-menu menu links imported successfully
    When I am logged in as a user with the 'administrator' role
    When I am at "admin/structure/menu/manage/main-menu"
    Then I should see "Veterinarian" in the "#menu-overview" element
    And I should see "Technician" in the "#menu-overview" element
    And I should see "Assistant" in the "#menu-overview" element
    And I should see "Receptionist" in the "#menu-overview" element
    And I should see "Manager" in the "#menu-overview" element

  Scenario: Administrator can see opigno-admin menu via toolbar_menu
    When I am logged in as a user with the 'administrator' role
    When I am at "/user"
    Then I should see an ".toolbar-icon-toolbar-menu-opigno_manager" element
    And the ".main-menu" element should not contain "<span>Management</span>"

  Scenario: The "members" link for a group renders and takes users to the correct view.
    Given group entities:
      | type         | label                   | field_license_status | id    |
      | organization | Behat-Members-Link-Test | active               | 40001 |
    And users:
      | name                  | mail                  | status | uid    | roles             |
      | gadmin@example.com    | gadmin@example.com    | 1      | 500004 | active_org_member |
    And user with name "gadmin@example.com" is a member of group with title "Behat-Members-Link-Test" and group role "admin"
    When I am logged in as "gadmin@example.com"
    When I load the group with title "Behat-Members-Link-Test"
    Then I should see the link "Members" in the "org admin menu" region
    And I click "Members" in the "org admin menu" region
    Then I should see the heading "Behat-Members-Link-Test members"
    Then the url should match "/group/40001/members"

  @wip
  Scenario: Org admin can see organization link in user dropdown menu
    # When I am logged in as an org admin.
    # When I am at "/"
    # Then I should see ".organization_0" in ".user-menu-block"

  @wip
  Scenario: Org admin can see org_admin menu
  # When I am logged in as an org admin.
  # When I am at "/group/GROUP_ID"
  # Then I should see the org-admin menu.
