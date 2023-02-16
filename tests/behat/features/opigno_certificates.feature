@api
Feature: Certificates are configured properly.

  # @todo Create an opigno quiz non-programatically
  # @todo Relate a quiz to an opigno article non-programatically
  # @todo assign an article to a user non-programatically

  Scenario: A user can see the my assignments page.
    Given users:
      |name                    | mail                     | status| field_first_name | field_last_name | roles             | uid    | pass                     |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | Bob              | Dobalina        | active_org_member | 123456 | bobadobalina@example.com |
    Given there is an opigno quiz titled "behat mega test quiz 1"
    And there is an opigno article titled "Behat Test article 1" referencing quiz "behat mega test quiz 1"
    And the user "bobadobalina@example.com" is assigned the article with title "Behat Test article 1"
    When I am logged in as "bobadobalina@example.com"
    And I am at "/my-assignments"
    Then the response status code should be 200
    And I should see the text "My Assignments"
    And I should see "behat mega test quiz 1" in the "Behat Test article 1" row

  Scenario: A user can access the "My certificates" page view and it has the correct columns
    Given users:
      |name                    | mail                     | status| field_first_name | field_last_name | roles             | uid    | pass                     |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | Bob              | Dobalina        | active_org_member | 123456 | bobadobalina@example.com |
    When I am logged in as "bobadobalina@example.com"
    And I am at "/my-certificates"
    # @todo programatically create a certificate for a user and check it is there.

  @javascript
  Scenario: A user can be assigned a quiz, pass, and receive a certificate for passing.
    Given users:
      |name                    | mail                     | status| field_first_name | field_last_name | roles             | uid    | pass                     |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | Bob              | Dobalina        | active_org_member | 123456 | bobadobalina@example.com |
    Given there is an opigno quiz titled "behat mega test quiz 1"
    And there is an opigno article titled "Behat Test article 1" referencing quiz "behat mega test quiz 1"
    And the user "bobadobalina@example.com" is assigned the article with title "Behat Test article 1"
    When I am logged in as "bobadobalina@example.com"
    And I am at "/my-assignments"
    Then I should see the text "My Assignments"
    # Load the quiz, verify it loaded via heading.
    And I click "behat mega test quiz 1" on the row containing "Behat Test article 1"
    Then I should see the non case sensitive heading "behat mega test quiz 1"
    # Wait for quiz to load and then take the quiz
    And I wait for AJAX to finish
    And I click the h5p answer "B"
    And I wait for AJAX to finish
    And I click the ".h5p-question-finish" element
    And I wait for AJAX to finish
    Then I should have received a quiz score of 1 of 1
    Then I click the "a.atdove-submit-quiz-button" element
    And I wait for AJAX to finish
    And I wait 4 seconds
    Then I press the "Submit Quiz" button
    Then I wait for AJAX to finish
    And I should see the text "My Certificates"
    And I click "View Certificate" on the row containing "Behat Test article 1"
    And I should see the text "Certificate of Completion"

  @javascript
  Scenario: A user can pass a quiz from an accredited activity and see the accreditation values on the certificate.
    Given users:
      |name                    | mail                     | status| field_first_name | field_last_name | roles             | uid    | pass                     |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | Bob              | Dobalina        | active_org_member | 123456 | bobadobalina@example.com |
    Given there is an opigno quiz titled "accreditation quiz"
    #And there is an opigno article titled "accreditation article" referencing quiz "accreditation quiz"
    And there is an accredited opigno article titled "accreditation article" referencing quiz "accreditation quiz"
    And the user "bobadobalina@example.com" is assigned the article with title "accreditation article"
    When I am logged in as "bobadobalina@example.com"
    And I am at "/my-assignments"
    Then I should see the text "My Assignments"
    # Load the quiz, verify it loaded via heading.
    And I click "accreditation quiz" on the row containing "accreditation article"
    And I wait for AJAX to finish
    And I click the h5p answer "B"
    And I wait for AJAX to finish
    And I click the ".h5p-question-finish" element
    And I wait for AJAX to finish
    Then I should have received a quiz score of 1 of 1
    Then I click the "a.atdove-submit-quiz-button" element
    And I wait for AJAX to finish
    And I wait 4 seconds
    Then I press the "Submit Quiz" button
    Then I wait for AJAX to finish
    And I am at "/my-certificates"
    And I click "View Certificate" on the row containing "accreditation article"
    And I should see the text "Certificate of Completion"
    And I should see the text "RACE Program"
    And I should see the text "Credit Hours"
    And I should see the text "Delivery Method"
    And I should see the text "Subject Matter Category"
