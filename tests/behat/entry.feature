@mod @mod_data @datafield @datafield_vimipad
Feature: Create and view database entries with a ViMi Pad field
  In order to collect visual maps in a database
  As a teacher and a learner
  I need the ViMi Pad editor in the entry form and a read-only map when browsing

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
      | activity | name       | course | idnumber |
      | data     | Map bank   | C1     | data1    |
    And the following "mod_data > fields" exist:
      | database | type    | name | description  |
      | data1    | vimipad | Map  | A ViMi Pad map |

  @javascript
  Scenario: The ViMi Pad editor is offered on the entry form
    When I am on the "Map bank" "data activity" page logged in as student1
    And I follow "Add entry"
    Then ".datafield_vimipad_editor" "css_element" should exist

  Scenario: The field is listed in the database field management
    When I am on the "Map bank" "mod_data > fields" page logged in as teacher1
    Then I should see "Map"
