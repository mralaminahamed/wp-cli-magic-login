<?php

/**
 * WP-CLI Magic Login Package
 *
 * Registers the command:
 *   - `wp magic-login` — generate a one-time magic login URL. The handler
 *     mu-plugin is installed/refreshed automatically; pass --force to reinstall.
 *
 * `MagicLoginServerInstallCommand` is loaded as an internal helper (autoloaded
 * via composer's `files`), not as a nested subcommand: WP-CLI does not allow a
 * leaf command (`magic-login`) to also own subcommands.
 *
 * @package WP_CLI_Magic_Login
 */

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

WP_CLI::add_command( 'magic-login', WP_CLI_Magic_Login\MagicLoginCommand::class );
