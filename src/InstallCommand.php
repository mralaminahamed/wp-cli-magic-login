<?php

namespace AlAminAhamed\WpCli\MagicLogin;

use WP_CLI;
use WP_CLI_Command;

/**
 * Installs the magic-login handler mu-plugin to WordPress site.
 *
 * ## EXAMPLES
 *
 *     $ wp magic-login install
 *     $ wp magic-login install --force
 *
 * @when after_wp_load
 */
class InstallCommand extends WP_CLI_Command {

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

        $source = dirname( __DIR__ ) . '/magic-login-handler.php';
        $target = $muplugins_dir . '/magic-login-handler.php';

        if ( file_exists( $target ) && ! $force ) {
            WP_CLI::error( 'magic-login handler is already installed. Use --force to overwrite.' );
        }

        if ( ! copy( $source, $target ) ) {
            WP_CLI::error( 'Failed to copy the handler file.' );
        }

        WP_CLI::success( sprintf( 'Installed magic-login handler to %s', $muplugins_dir ) );
    }
}
