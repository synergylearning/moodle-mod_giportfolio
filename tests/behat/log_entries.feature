@mod @mod_giportfolio
Feature: In a giportfolio, verify log entries
  In order to create log entries
  As an admin
  I need to perform various actions in a giportfolio.

  @javascript
  Scenario: perform various giportfolio actions and verify log entries.
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    When I add a "Portfolio" to section "1" and I fill the form with:
      | Name | Test giportfolio |
      | Summary | A giportfolio about dreams! |
    And I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    And I click on "Edit" "link" in the "1 Chapter1" "list_item"
    And I set the following fields to these values:
      | Chapter title | First chapter |
      | Content | First chapter |
    And I press "Save changes"
    And I click on "Add new chapter" "link" in the "Table of contents" "block"
    And I set the following fields to these values:
      | Chapter title | Second chapter |
      | Content | Second chapter |
    And I press "Save changes"
    And I click on "Edit" "link" in the "Table of contents" "block"
    And I set the following fields to these values:
      | Chapter title | First chapter edited |
      | Content | First chapter edited |
    And I press "Save changes"
    And I click on "Next" "link"
    And I click on "Previous" "link"
    And I click on "Print portfolio" "link" in the "Administration" "block"
    And I click on "Logs" "link" in the "Administration" "block"
    Then I should see "Portfolio printed"
    And I should see "Chapter updated" in the "#report_log_r1_c5" "css_element"
    And I should see "Chapter created" in the "#report_log_r2_c5" "css_element"
    And I should see "Chapter updated" in the "#report_log_r3_c5" "css_element"
