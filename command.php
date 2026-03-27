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

require_once __DIR__ . '/vendor/autoload.php';

WP_CLI::add_command( 'magic-login', WP_CLI_Magic_Login\MagicLoginCommand::class );
