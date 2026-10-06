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

  # mod_data only offers advanced search once the database holds entries, and
  # the form is revealed by JavaScript, so both are needed to reach it - as in
  # core's own mod/data/tests/behat/advanced_search.feature.
  # @data_listview_js: on Moodle 5.3 every JavaScript page of mod_data's list
  # view times out in Behat with core/form-autocomplete, core/page_global and
  # core/utility pending - the modules 5.3 loads through its ESM bridge. A control
  # run with a database WITHOUT a ViMi Pad field failed identically, so the cause
  # lies in Moodle 5.3, not in this plugin. The CI excludes the tag on 5.3 only;
  # the server-side regression this feature guards (display_search_field) is still
  # covered there by the non-JavaScript scenarios above. Drop the exclusion in the
  # workflows once 5.3 serves the list view under Behat again.
  @javascript @data_listview_js
  Scenario: The advanced search form includes the ViMi Pad field
    Given the following "mod_data > entries" exist:
      | database | user     | Map                                                                               |
      | data1    | student1 | {"profile":"conceptmap","nodes":[{"stableid":"a","label":"Water"}],"relations":[]} |
    When I am on the "Map bank" "data activity" page logged in as teacher1
    And I click on "Advanced search" "checkbox"
    # The search input is rendered by display_search_field(), the very method
    # whose absence used to take this page down.
    Then I should see "Map" in the "data_adv_form" "region"
    And I should not see "Call to undefined method"

  @javascript @data_listview_js
  Scenario: A submitted map is listed in the browse view
    Given the following "mod_data > entries" exist:
      | database | user     | Map                                                                                   |
      | data1    | student1 | {"profile":"conceptmap","nodes":[{"stableid":"a","label":"Water"}],"relations":[]}     |
    When I am on the "Map bank" "data activity" page logged in as student1
    Then ".datafield_vimipad" "css_element" should exist
    And I should not see "Exception"
