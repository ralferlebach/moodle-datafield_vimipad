@datafield @datafield_vimipad
Feature: A teacher can add a ViMi Pad field to a database activity
  In order to collect visual knowledge maps as entries
  As a teacher
  I need to add and see a ViMi Pad field

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tay       | Teacher  | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | data     | Gallery | C1     | data1    |
    And the following "mod_data > fields" exist:
      | database | type    | name | description          | param1     |
      | data1    | vimipad | Map  | A visual concept map | conceptmap |

  @javascript
  Scenario: The ViMi Pad field is listed in the database field management
    When I am on the "Gallery" "data activity" page logged in as teacher1
    And I navigate to "Fields" in current page administration
    Then I should see "Map"
