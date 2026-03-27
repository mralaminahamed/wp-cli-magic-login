<?php

namespace WP_CLI_Magic_Login\Tests;

use WP_CLI_Magic_Login\MagicLoginCommand;

/**
 * Test the magic login command.
 */
class MagicLoginCommandTest extends \WP_CLI\Tests\TestCase {

    public function test_it_has_install_subcommand() {
        $command = new MagicLoginCommand();
        $this->assertTrue( method_exists( $command, 'install' ) );
    }

    public function test_it_has_invoke_method() {
        $command = new MagicLoginCommand();
        $this->assertTrue( method_exists( $command, '__invoke' ) );
    }
}
