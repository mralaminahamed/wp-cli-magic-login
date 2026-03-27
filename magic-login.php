<?php

/**
 * WP-CLI Magic Login Package
 *
 * Registers commands:
 *   - `wp magic-login`    — generate magic login URL (with install subcommand)
 *   - `wp mu-plugin`     — manage must-use plugins on Valet sites
 *
 * @package AlAminAhamed\WpCli\MagicLogin
 */

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

WP_CLI::add_command( 'magic-login', AlAminAhamed\WpCli\MagicLogin\MagicLoginCommand::class );
WP_CLI::add_command( 'mu-plugin',   AlAminAhamed\WpCli\MagicLogin\MuPluginCommand::class );
