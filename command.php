<?php

/**
 * WP-CLI Magic Login Package
 *
 * Registers commands:
 *   - `wp magic-login`                — generate a one-time magic login URL
 *   - `wp magic-login install-server` — install the handler mu-plugin
 *
 * @package WP_CLI_Magic_Login
 */

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

WP_CLI::add_command( 'magic-login', WP_CLI_Magic_Login\MagicLoginCommand::class );
WP_CLI::add_command( 'magic-login install-server', WP_CLI_Magic_Login\MagicLoginServerInstallCommand::class );
