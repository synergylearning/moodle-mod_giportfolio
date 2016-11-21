@mod @mod_giportfolio
Feature: SYNERGY LEARNING the 'collapse subchapter' setting starts the chapter tree collapsed

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | course | user     | role           |
      | C1     | teacher1 | editingteacher |
      | C1     | student1 | student        |
    And the following "activities" exist:
      | activity | name   | idnumber | course |
      | giportfolio     | Portfolio 1 | giportfolio1    | C1     |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Portfolio 1"
    And I press "View/Edit portfolio template"
    And I navigate to "Turn editing on" node in "Portfolio administration"
    And I click on "Edit" "link" in the "1 Chapter1" "list_item"
    And I set the following fields to these values:
      | Chapter title | Chapter 1   |
      | Content       | Ch1 content |
      | Subchapter    | 0           |
    And I press "Save changes"
    And I click on "Add new chapter" "link" in the "Chapter 1" "list_item"
    And I set the following fields to these values:
      | Chapter title | Chapter 1a   |
      | Content       | Ch1a content |
      | Subchapter    | 1            |
    And I press "Save changes"
    And I click on "Add new chapter" "link" in the "Chapter 1a" "list_item"
    And I set the following fields to these values:
      | Chapter title | Chapter 1b   |
      | Content       | Ch1b content |
      | Subchapter    | 1            |
    And I press "Save changes"
    And I click on "Add new chapter" "link" in the "Chapter 1b" "list_item"
    And I set the following fields to these values:
      | Chapter title | Chapter 2   |
      | Content       | Ch2 content |
      | Subchapter    | 0           |
    And I press "Save changes"
    And I click on "Add new chapter" "link" in the "Chapter 2" "list_item"
    And I set the following fields to these values:
      | Chapter title | Chapter 2a   |
      | Content       | Ch2a content |
      | Subchapter    | 1            |
    And I press "Save changes"
    And I click on "Add new chapter" "link" in the "Chapter 2a" "list_item"
    And I set the following fields to these values:
      | Chapter title | Chapter 2b   |
      | Content       | Ch2b content |
      | Subchapter    | 1            |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Without 'collapse subchapter' all the chapters are shown
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Portfolio 1"
    And I press "Start Contributing"
    Then "Chapter 1" "text" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible

    When I click on "a.ygtvspacer" "css_element" in the "Chapter 1" "table_row"
    Then "Chapter 1" "text" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1a" "link" in the ".giportfolio_toc_numbered" "css_element" should not be visible
    And "Chapter 1b" "link" in the ".giportfolio_toc_numbered" "css_element" should not be visible
    And "Chapter 2" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible

    When I click on "a.ygtvspacer" "css_element" in the "Chapter 1" "table_row"
    Then "Chapter 1" "text" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible

  @javascript
  Scenario: With the 'collapse subchapter', only the current chapter is shown, by default
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Portfolio 1"
    And I navigate to "Edit settings" node in "Portfolio administration"
    And I set the following fields to these values:
      | Collapse subchapters | Yes |
    And I press "Save and return to course"
    And I log out

    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Portfolio 1"
    And I press "Start Contributing"
    Then "Chapter 1" "text" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 1b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And I should not see "Chapter 2a"
    And I should not see "Chapter 2b"

    When I follow "Chapter 2"
    Then "Chapter 1" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And I should not see "Chapter 1a"
    And I should not see "Chapter 1b"
    And "Chapter 2" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2a" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
    And "Chapter 2b" "link" in the ".giportfolio_toc_numbered" "css_element" should be visible
