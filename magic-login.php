<?php

/**
 * WP-CLI Magic Login Package
 *
 * Registers command:
 *   - `wp magic-login` — generate magic login URL (with install subcommand)
 *
 * @package AlAminAhamed\WpCli\MagicLogin
 */

require_once __DIR__ . '/vendor/autoload.php';

WP_CLI::add_command( 'magic-login', AlAminAhamed\WpCli\MagicLogin\MagicLoginCommand::class );
