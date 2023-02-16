@api
Feature: Check that user messaging works

  Scenario: Users can view their own messages.
    Given users:
      |name                    | mail                     | status| uid    | field_first_name | field_last_name |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | 999999 | Bob              | Dobalina        |
    When I am logged in as "bobadobalina@example.com"
    And I am at "/user/"
    Then I should see the link "Go to my messages"
    And I click "Go to my messages"
    Then I should see the heading "Messages"

  Scenario: users can access sending another user a message.
    Given users:
      |name                       | mail                       | status| uid    | field_first_name | field_last_name |
      |user-sending@example.com   | user-sending@example.com   | 1     | 999998 | Bob              | Sending         |
      |user-receiving@example.com | user-receiving@example.com | 1     | 999999 | Bob              | Receiving       |
    # Check sending messages.
    When I am logged in as "user-sending@example.com"
    And I am at "/user/999999"
    Then I should see the link "Send user a message"
    And I click "Send user a message"
    Then I should see the heading "Messages"

  # @todo: This scenario doesn't work unfortunately, but has use for debugging or documentation
  @manual
  Scenario: Users can send other users messages.
    Given users:
      |name                       | mail                       | status| uid    | field_first_name | field_last_name |
      |user-sending@example.com   | user-sending@example.com   | 1     | 999998 | Bob              | Sending         |
      |user-receiving@example.com | user-receiving@example.com | 1     | 999999 | Bob              | Receiving       |
    # Check sending messages.
    When I am logged in as "user-sending@example.com"
    And I am at "/user/999999"
    Then I should see the link "Send user a message"
    And I click "Send user a message"
    Then I should see the heading "Messages"
    # @todo This step fails
    # And I fill in the wysiwyg on field ".form-item-message-0-value" with "TEST UNIQUE MESSAGE"
    And I press the "Send" button
    And I wait for AJAX to finish
    Then I should see the text "TEST UNIQUE MESSAGE"
    # Check receipt of messages.
    When I am logged in as "user-receiving@example.com"
    And I am at "/user/"
    Then I should see the link "Go to my messages"
    And I click "Go to my messages"
    Then I should see the heading "Messages"
    # @todo Cause the first step fails, this one then fails next.
    Then I should see the text "TEST UNIQUE MESSAGE"

