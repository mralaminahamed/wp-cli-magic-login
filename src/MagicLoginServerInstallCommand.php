<?php

namespace WP_CLI_Magic_Login;

use WP_CLI;
use WP_CLI_Command;

/**
 * Generates a one-time magic login URL for a WordPress admin account.
 *
 * Designed for local development use with Laravel Valet and WP-CLI.
 * The generated URL logs the target user in without requiring a password.
 *
 * ## EXAMPLES
 *
 *     # Log in as the first administrator (opens browser automatically)
 *     $ wp magic-login
 *
 *     # Print the login URL only, do not open the browser
 *     $ wp magic-login --no-launch
 *
 *     # Log in as a specific user by login name
 *     $ wp magic-login --user=johndoe
 *
 *     # Log in as a specific user by ID
 *     $ wp magic-login --user=3
 *
 *     # Set a custom expiry (in seconds, default 60)
 *     $ wp magic-login --expiry=300
 *
 * @package WP_CLI_Magic_Login
 */
class MagicLoginServerInstallCommand extends WP_CLI_Command {

    /**
     * Installs the magic-login handler mu-plugin.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Overwrite existing installation.
     *
     * ## EXAMPLES
     *
     *     $ wp magic-login install
     *     $ wp magic-login install --force
     *
     * @param array<int, string>   $args
     * @param array<string, mixed> $assoc_args
     *
     * @return void
     */
    public function __invoke( array $args, array $assoc_args ): void {
        $force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

        $muplugins_dir = defined( 'WPMU_PLUGIN_DIR' )
            ? WPMU_PLUGIN_DIR
            : WP_CONTENT_DIR . '/mu-plugins';

        if ( ! is_dir( $muplugins_dir ) ) {
            WP_CLI::error( sprintf( 'mu-plugins directory not found: %s', $muplugins_dir ) );
        }

        $source = dirname( __DIR__ ) . '/plugin/wp-cli-magic-login-server.php';
        $target = $muplugins_dir . '/wp-cli-magic-login-server.php';

        if ( file_exists( $target ) && ! $force ) {
            WP_CLI::error( 'magic-login handler is already installed. Use --force to overwrite.' );
        }

        if ( ! copy( $source, $target ) ) {
            WP_CLI::error( 'Failed to copy the handler file.' );
        }

        WP_CLI::success( sprintf( 'Installed magic-login handler to %s', $muplugins_dir ) );
    }
}
