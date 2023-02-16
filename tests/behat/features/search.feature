@api @search_api
Feature: Search functionality is configured as expected and works.

  Scenario: Search is disabled
    Given I am logged in as a user with the 'administrator' role
    When I am at "admin/config/search/search-api"
    Then I should see the text "Disabled" in the "Sitewide Index" row
    And I should see the text "Disabled" in the "Database Server" row

# @todo Bring back if search is ever re-added to this project.
#  Scenario: Ensure that relevant search modules are enabled.
#    Given The module "search_api" should be enabled
#    And The module "search_api_db" should be enabled
#    And The module "search_api_exclude_entity" should be enabled
#
#  Scenario: Search configuration basics/essentials are in place.
#    Given I am logged in as a user with the 'administrator' role
#    When I am at "admin/config/search/search-api"
#    Then I should see the text "Enabled" in the "Sitewide Index" row
#    When I am at "admin/structure/views"
#    Then I should see the text "Page (/search)" in the "Site Search" row
#    When I am at "admin/structure/block/manage/exposedformsite_searchsearch_results_page"
#    Then I should see an "option[value='content'][selected]" element
#    # Check all search fields that are needed are enabled for fields.
#    When I am at "/admin/config/search/search-api/index/default_index/fields"
#    Then I should see an "input[name='fields[title][title]'][value='Title']" element
#    Then I should see an "input[name='fields[name][title]'][value='Name']" element
#    Then I should see an "input[name='fields[body][title]'][value='Body']" element
#    Then I should see an "input[name='fields[field_article_body][title]'][value='Article Body']" element
#    Then I should see an "input[name='fields[field_video_body][title]'][value='Video Body']" element
#    Then I should see an "input[name='fields[field_search_keywords_activity][title]'][value='Search Keywords']" element
#    Then I should see an "input[name='fields[field_search_keywords][title]'][value='Search Keywords']" element
#
#  Scenario: Content is indexeded and only shows in results only when relevant terms are searched.
#    Given the cache has been cleared
#    Given "blog" content:
#      | title           | status | body      |
#      | Search Result 1 | 1      | xylaphone |
#      | Search Result 2 | 1      | falafel   |
#    # Make sure we've got a fresh API index.
#    And I run drush "search-api:clear"
#    And I run drush "search-api:index"
#    When I am logged in as a user with the 'administrator' role
#    When I am at "/search"
#    And I fill in "search_api_fulltext" with "xylaphone"
#    And I press the "Search Site" button
#    Then I should see the text "Search Result 1"
#    And I should not see the text "Search Result 2"
#    When I fill in "search_api_fulltext" with "falafel"
#    And I press the "Search Site" button
#    Then I should not see the text "Search Result 1"
#    And I should see the text "Search Result 2"
#
#  Scenario: Content that is excluded is not indexeded and does not show in results
#    Given the cache has been cleared
#    Given "blog" content:
#      | title           | status | body        | field_exclude_from_search_result |
#      | Search Result 1 | 1      | exclude no  | 0                                |
#      | Search Result 2 | 1      | exclude yes | 1                                |
#    # Make sure we've got a fresh API index.
#    And I run drush "search-api:clear"
#    And I run drush "search-api:index"
#    When I am logged in as a user with the 'administrator' role
#    When I am at "/search"
#    And I fill in "search_api_fulltext" with "exclude"
#    And I press the "Search Site" button
#    Then I should see the text "Search Result 1"
#    And I should not see the text "Search Result 2"
#
#  Scenario: Content with keywords places higher than content without.
#    Given the cache has been cleared
#    Given "blog" content:
#      | title           | status | body           | field_search_keywords |
#      | Search Result 1 | 1      | important      | unrelated             |
#      | Search Result 2 | 1      | important      | important             |
#      | Search Result 3 | 1      | definitely not | important             |
#    # Make sure we've got a fresh API index.
#    And I run drush "search-api:clear"
#    And I run drush "search-api:index"
#    When I am logged in as a user with the 'administrator' role
#    When I am at "/search"
#    And I fill in "search_api_fulltext" with "important"
#    And I press the "Search Site" button
#    Then the search results order should be:
#      |Search Result 2 |
#      |Search Result 3 |
#      |Search Result 1 |
#
#  # @todo Create a test for at_dove_articles indexing, and exclusion
#  # @todo Create a test for at_dove_video indexing, and exclusion
