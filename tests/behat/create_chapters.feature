@mod @mod_giportfolio
Feature: In a giportfolio, create chapters and sub chapters
  In order to create chapters and subchapters
  As a teacher
  I need to add chapters and subchapters to a giportfolio.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Portfolio" to section "1" and I fill the form with:
      | Name | Test giportfolio |
      | Summary | A giportfolio about dreams! |

  Scenario: Create chapters and sub chapters and navigate between them
    Given I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    And I click on "Edit" "link" in the "1 Chapter1" "list_item"
    And I set the following fields to these values:
      | Chapter title | Dummy first chapter |
      | Content | Dream is the start of a journey |
    And I press "Save changes"
    And I should see "1 Dummy first chapter" in the "Table of contents" "block"
    And I click on "Add new chapter" "link" in the "Table of contents" "block"
    And I set the following fields to these values:
      | Chapter title | Dummy second chapter |
      | Content | The path is the second part |
    And I press "Save changes"
    And I should see "2 Dummy second chapter" in the "Table of contents" "block"
    And I click on "Add new chapter" "link" in the "Table of contents" "block"
    And I set the following fields to these values:
      | Chapter title | Dummy first subchapter |
      | Content | The path is the second part |
      | Subchapter | true |
    And I press "Save changes"
    And I should see "1.1 Dummy first subchapter" in the "Table of contents" "block"
    And I should see "1 Dummy first chapter" in the ".giportfolio_content" "css_element"
    And I should see "1.1 Dummy first subchapter" in the ".giportfolio_content" "css_element"
    And I click on "Next" "link"
    And I should see "2 Dummy second chapter" in the ".giportfolio_content" "css_element"
    And I should see "2 Dummy second chapter" in the "strong" "css_element"
    And I should not see "Next" in the ".giportfolio_content" "css_element"
    And I click on "Exit portfolio" "link"
    And I should see "Test giportfolio" in the "Topic 1" "section"
    And I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    And I should not see "Previous" in the ".giportfolio_content" "css_element"
    And I should see "1 Dummy first chapter" in the "strong" "css_element"
    When I click on "Next" "link"
    Then I should see "1.1 Dummy first subchapter" in the ".giportfolio_content" "css_element"
    And I should see "1.1 Dummy first subchapter" in the "strong" "css_element"
    And I click on "Previous" "link"
    And I should see "1 Dummy first chapter" in the ".giportfolio_content" "css_element"
    And I should see "1 Dummy first chapter" in the "strong" "css_element"

  Scenario: Change editing mode for an individual chapter
    Given I follow "Test giportfolio"
    And I press "View/Edit portfolio template"
    And I click on "Edit" "link" in the "1 Chapter1" "list_item"
    And I set the following fields to these values:
      | Chapter title | Dummy first chapter |
      | Content | Dream is the start of a journey |
    And I press "Save changes"
    And I should see "1 Dummy first chapter" in the "Table of contents" "block"
    And "Edit" "link" should exist in the "1 Dummy first chapter" "list_item"
    And "Delete" "link" should exist in the "1 Dummy first chapter" "list_item"
    And "Hide" "link" should exist in the "1 Dummy first chapter" "list_item"
    And "Add new chapter" "link" should exist in the "1 Dummy first chapter" "list_item"
    When I click on "Turn editing off" "link" in the "Administration" "block"
    Then "Edit" "link" should not exist in the "1 Dummy first chapter" "list_item"
    And "Delete" "link" should not exist in the "1 Dummy first chapter" "list_item"
    And "Hide" "link" should not exist in the "1 Dummy first chapter" "list_item"
    And "Add new chapter" "link" should not exist in the "1 Dummy first chapter" "list_item"
