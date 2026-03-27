<?php

/**
 * WP-CLI Magic Login Package
 *
 * Registers command:
 *   - `wp magic-login` — generate magic login URL (with install subcommand)
 *
 * @package WP_CLI_Magic_Login
 */

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

WP_CLI::add_command( 'magic-login', WP_CLI_Magic_Login\MagicLoginCommand::class );
WP_CLI::add_command( 'magic-login install-server', WP_CLI_Magic_Login\MagicLoginServerInstallCommand::class );
