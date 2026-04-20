<?php

namespace WP_CLI_Magic_Login\Tests\Unit;

use WP_CLI_Magic_Login\MagicLoginCommand;
use WP_CLI_Magic_Login\MagicLoginServerInstallCommand;

/**
 * Unit tests for the magic login command classes.
 */
class MagicLoginCommandTest extends \WP_CLI\Tests\TestCase {

    public function test_magic_login_command_has_invoke_method(): void {
        $command = new MagicLoginCommand();
        $this->assertTrue( method_exists( $command, '__invoke' ) );
    }

    public function test_server_install_command_has_invoke_method(): void {
        $command = new MagicLoginServerInstallCommand();
        $this->assertTrue( method_exists( $command, '__invoke' ) );
    }
}
