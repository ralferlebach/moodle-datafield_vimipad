@mod @mod_data @datafield @datafield_vimipad
Feature: Browse database entries that contain a ViMi Pad field
  In order to read the maps my classmates submitted
  As a learner
  I need the database's list view and its search to work with a ViMi Pad field

  # This feature exists because of a real regression: data_field_base does not
  # define display_search_field(), but mod_data calls it on every field when it
  # builds the search form. A field type that omits it makes this very page die
  # with "Call to undefined method", which no earlier test noticed because they
  # all called the plugin directly instead of going through mod_data.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tay       | Teacher  | teacher1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name     | course | idnumber |
      | data     | Map bank | C1     | data1    |
    And the following "mod_data > fields" exist:
      | database | type    | name | description    |
      | data1    | vimipad | Map  | A ViMi Pad map |

  Scenario: The list view loads for a student when a ViMi Pad field is present
    When I am on the "Map bank" "data activity" page logged in as student1
    Then I should see "Map bank"
    And I should not see "Call to undefined method"
    And I should not see "Exception"

  Scenario: The list view loads for a teacher when a ViMi Pad field is present
    When I am on the "Map bank" "data activity" page logged in as teacher1
    Then I should see "Map bank"
    And I should not see "Call to undefined method"
    And I should not see "Exception"

  Scenario: The advanced search form builds with a ViMi Pad field present
    When I am on the "Map bank" "data activity" page logged in as teacher1
    And I set the field "Advanced search" to "1"
    Then I should not see "Call to undefined method"
    And I should not see "Exception"

  @javascript
  Scenario: A submitted map is listed in the browse view
    Given the following "mod_data > entries" exist:
      | database | user     | Map                                                                                   |
      | data1    | student1 | {"profile":"conceptmap","nodes":[{"stableid":"a","label":"Water"}],"relations":[]}     |
    When I am on the "Map bank" "data activity" page logged in as student1
    Then ".datafield_vimipad" "css_element" should exist
    And I should not see "Exception"
