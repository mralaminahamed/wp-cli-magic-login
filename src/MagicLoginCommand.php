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
 * @package AlAminAhamed\WpCli\MagicLogin
 */
class MagicLoginCommand extends WP_CLI_Command {

    /**
     * Generates a one-time magic login URL and optionally opens it in the browser.
     *
     * ## OPTIONS
     *
     * [--user=<user>]
     * : User login name or ID. Defaults to the first administrator account.
     *
     * [--expiry=<seconds>]
     * : Token expiry time in seconds. Default: 60.
     *
     * [--no-launch]
     * : Print the URL only; do not attempt to open the browser.
     *
     * [--porcelain]
     * : Output the raw URL only, suitable for scripting.
     *
     * ## EXAMPLES
     *
     *     $ wp magic-login
     *     $ wp magic-login --user=admin --expiry=300 --no-launch
     *
     * @when after_wp_load
     *
     * @param array<int, string>   $args       Positional arguments (unused).
     * @param array<string, mixed> $assoc_args Named arguments.
     *
     * @return void
     */
    public function __invoke( array $args, array $assoc_args ): void {
        $user   = $this->resolve_user( $assoc_args );
        $expiry = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'expiry', 60 );

        if ( $expiry < 1 ) {
            WP_CLI::error( 'Expiry must be a positive integer (seconds).' );
        }

        $url = $this->generate_login_url( $user, $expiry );

        $porcelain = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain', false );
        $no_launch = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'no-launch', false );

        if ( $porcelain ) {
            WP_CLI::line( $url );
            return;
        }

        WP_CLI::success(
            sprintf(
                'Magic login URL generated for user "%s" (expires in %ds):',
                $user->user_login,
                $expiry
            )
        );

        WP_CLI::line( $url );

        if ( ! $no_launch ) {
            $this->open_in_browser( $url );
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolves the target WordPress user from the provided flag or defaults.
     *
     * @param array<string, mixed> $assoc_args Named CLI arguments.
     *
     * @return \WP_User
     */
    private function resolve_user( array $assoc_args ): \WP_User {
        $user_flag = WP_CLI\Utils\get_flag_value( $assoc_args, 'user', null );

        if ( null !== $user_flag ) {
            // Support both numeric ID and login string.
            $field = is_numeric( $user_flag ) ? 'id' : 'login';
            $user  = get_user_by( $field, $user_flag );

            if ( ! $user ) {
                WP_CLI::error( sprintf( 'No user found for "%s".', $user_flag ) );
            }

            return $user;
        }

        // Default: first user with the administrator role.
        $admins = get_users(
            [
                'role'    => 'administrator',
                'number'  => 1,
                'orderby' => 'ID',
                'order'   => 'ASC',
                'fields'  => 'all',
            ]
        );

        if ( empty( $admins ) ) {
            WP_CLI::error( 'No administrator account found on this site.' );
        }

        return $admins[0];
    }

    /**
     * Generates a one-time login URL using a transient-backed token.
     *
     * The transient key encodes the user ID and a cryptographically random
     * token. On first use the token is deleted, making the URL single-use.
     *
     * @param \WP_User $user   The user to log in.
     * @param int      $expiry Token lifetime in seconds.
     *
     * @return string The fully-qualified magic login URL.
     */
    private function generate_login_url( \WP_User $user, int $expiry ): string {
        $token           = wp_generate_password( 32, false );
        $transient_key   = 'magic_login_' . $user->ID . '_' . $token;

        set_transient( $transient_key, $user->ID, $expiry );

        return add_query_arg(
            [
                'action' => 'magic_login',
                'uid'    => $user->ID,
                'token'  => $token,
            ],
            site_url( 'wp-login.php' )
        );
    }

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
    public function install( array $args, array $assoc_args ): void {
        $force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

        $muplugins_dir = defined( 'WPMU_PLUGIN_DIR' )
            ? WPMU_PLUGIN_DIR
            : WP_CONTENT_DIR . '/mu-plugins';

        if ( ! is_dir( $muplugins_dir ) ) {
            WP_CLI::error( sprintf( 'mu-plugins directory not found: %s', $muplugins_dir ) );
        }

        $source = dirname( __DIR__ ) . '/plugin/magic-login-handler.php';
        $target = $muplugins_dir . '/magic-login-handler.php';

        if ( file_exists( $target ) && ! $force ) {
            WP_CLI::error( 'magic-login handler is already installed. Use --force to overwrite.' );
        }

        if ( ! copy( $source, $target ) ) {
            WP_CLI::error( 'Failed to copy the handler file.' );
        }

        WP_CLI::success( sprintf( 'Installed magic-login handler to %s', $muplugins_dir ) );
    }

    /**
     * Opens a URL in the system's default browser.
     *
     * Supports macOS (`open`), Linux (`xdg-open`), and WSL.
     * Silently skips if no suitable command is detected.
     *
     * @param string $url The URL to open.
     *
     * @return void
     */
    private function open_in_browser( string $url ): void {
        $escaped = escapeshellarg( $url );

        if ( PHP_OS_FAMILY === 'Darwin' ) {
            // macOS — standard for Valet users.
            exec( 'open ' . $escaped . ' 2>/dev/null' );
            return;
        }

        if ( PHP_OS_FAMILY === 'Linux' ) {
            exec( 'xdg-open ' . $escaped . ' 2>/dev/null' );
            return;
        }

        // Windows / WSL fallback.
        if ( PHP_OS_FAMILY === 'Windows' ) {
            exec( 'start ' . $escaped );
        }
    }
}
