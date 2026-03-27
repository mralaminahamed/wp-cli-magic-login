<?php

/**
 * WP-CLI Magic Login Package
 *
 * Registers all commands provided by this package:
 *   - `wp magic-login`  — generate a one-time login URL for an admin account.
 *   - `wp mu-plugin`    — install, remove, and list must-use plugins on Valet sites.
 *
 * @package AlAminAhamed\WpCli\MagicLogin
 */

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

WP_CLI::add_command( 'magic-login', AlAminAhamed\WpCli\MagicLogin\MagicLoginCommand::class );
WP_CLI::add_command( 'mu-plugin',   AlAminAhamed\WpCli\MagicLogin\MuPluginCommand::class );
