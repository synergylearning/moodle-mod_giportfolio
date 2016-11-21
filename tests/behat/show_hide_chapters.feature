@mod @mod_giportfolio
Feature: Portfolio activity chapter visibility management
  In order to properly manage chapters in a giportfolio activity
  As a teacher
  I need to be able to show or hide chapters.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Portfolio" to section "1" and I fill the form with:
      | Name | Test giportfolio |
      | Summary | A giportfolio about dreams! |
    And I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    And I click on "Edit" "link" in the "1 Chapter1" "list_item"
    And I set the following fields to these values:
      | Chapter title | First chapter |
      | Content | First chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=1']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Second chapter |
      | Content | Second chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=2']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Sub chapter |
      | subchapter | 1 |
      | Content | Sub chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=3']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Third chapter |
      | subchapter | 0 |
      | Content | Third chapter |
    And I press "Save changes"
    And I click on "a[href*='pagenum=4']" "css_element"
    And I set the following fields to these values:
      | Chapter title | Fourth chapter |
      | Content | Fourth chapter |
    And I press "Save changes"

  @javascript
  Scenario: Show/hide chapters and subchapters
    When I click on "Hide" "link" in the "2 Second chapter" "list_item"
    And I click on "Hide" "link" in the "2 Third chapter" "list_item"
    And I navigate to "Turn editing off" node in "Portfolio administration"
    And I am on homepage
    And I follow "Course 1"
    And I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    Then I should not see "Second chapter" in the "Table of contents" "block"
    And I should not see "Third chapter" in the "Table of contents" "block"
    And I follow "Next"
    And I should see "Fourth chapter" in the ".giportfolio_content" "css_element"
    And I follow "Exit portfolio"
    And I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    And I should see "First chapter" in the ".giportfolio_content" "css_element"
    And I navigate to "Turn editing on" node in "Portfolio administration"
    And I follow "Next"
    And I should see "Second chapter" in the ".giportfolio_content" "css_element"
    And I should not see "Exit portfolio"
    And I follow "Next"
    And I should see "Sub chapter" in the ".giportfolio_content" "css_element"
    And I follow "Next"
    And I should see "Third chapter" in the ".giportfolio_content" "css_element"
    And I follow "Next"
    And I should see "Fourth chapter" in the ".giportfolio_content" "css_element"
    And I follow "Exit portfolio"

