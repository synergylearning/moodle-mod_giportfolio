@mod @mod_giportfolio
Feature: In a giportfolio, add a contribution to a chapter

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Portfolio" to section "1" and I fill the form with:
      | Name                           | Test giportfolio            |
      | Summary                        | A giportfolio about dreams! |
      | Notify teachers of new entries | Yes                         |
      | Initial number of Chapters:    | 2                           |
    And I log out

  Scenario: Student can contribute to a chapter
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test giportfolio"
    And I press "Start Contributing"
    When I press "Add Contribution"
    And I set the following fields to these values:
      | Title   | My contribution      |
      | Content | Here is some content |
    And I press "Update Contribution"
    Then I should see "My contribution"
    And I should see "Here is some content"

    When I follow "Chapter2"
    Then I should not see "My contribution"

    When I follow "Chapter1"
    And I click on "Edit" "link" in the ".giportfolio-contribution" "css_element"
    And the following fields match these values:
      | Title   | My contribution      |
      | Content | Here is some content |
    And I set the following fields to these values:
      | Title   | My edited contribution |
      | Content | Updated content        |
    And I press "Update Contribution"
    Then I should see "My edited contribution"
    And I should see "Updated content"
    And I should not see "My contribution"
    And I should not see "Here is some content"
