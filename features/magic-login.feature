Feature: Magic Login Command

  Scenario: Install the magic login handler
    Given a WP install
    When I run `wp magic-login install-server`
    Then STDOUT should contain:
      """
      Installed magic-login handler
      """

  Scenario: Generate a magic login URL
    Given a WP install
    And I run `wp magic-login install-server`
    When I run `wp magic-login --porcelain`
    Then STDOUT should contain:
      """
      http://
      """
