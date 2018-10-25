@dgo
Feature: Selenium
  In order to prove tests are working properly
  As a developer
  I need to use the step definitions of this context

  @javascript
  Scenario: JS messages
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    When I press "Log in"
    Then I should see the error message containing "Unrecognized username or password. Forgot your password?"

  Scenario: Check a link should not exist in a region
    Given I am on the homepage
    Then I should not see the link "This link should never exist in a default Drupal install" in the "header"

  Scenario: Find an element in a region
    Given I am on the homepage
    Then I should see the "h1" element in the "content"

  Scenario: Find an element with an attribute in a region
    Given I am on the homepage
    Then I should see the "a" element with the "class" attribute set to "site-branding__logo" in the "header" region
