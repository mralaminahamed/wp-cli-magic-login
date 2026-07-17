<?php

namespace WP_CLI_Magic_Login;

use WP_CLI;
use WP_CLI_Command;

/**
 * Generates a one-time magic login URL for a WordPress account.
 *
 * Designed for local development use with Laravel Valet and WP-CLI. The
 * generated URL logs the target user in without requiring a password, then
 * expires on first use. The companion handler mu-plugin is installed
 * automatically the first time you run the command.
 *
 * ## EXAMPLES
 *
 *     # Log in as the sole administrator (opens the browser automatically)
 *     $ wp magic-login
 *
 *     # Print the login URL only, do not open the browser
 *     $ wp magic-login --no-launch
 *
 *     # Target a specific user by login name, ID, or email
 *     $ wp magic-login --login=johndoe
 *     $ wp magic-login --login=3
 *     $ wp magic-login --login=jane@example.test
 *
 *     # Land on a specific admin screen after login
 *     $ wp magic-login --redirect=edit.php
 *
 * @package WP_CLI_Magic_Login
 */
class MagicLoginCommand extends WP_CLI_Command {

    /**
     * Generates a one-time magic login URL and optionally opens it in the browser.
     *
     * ## OPTIONS
     *
     * [--login=<user>]
     * : User to log in as by login name, ID, or email. Defaults to WP-CLI's global --user, else the sole administrator.
     *
     * [--expiry=<seconds>]
     * : Token lifetime in seconds. Default: 60.
     *
     * [--redirect=<path>]
     * : Same-host path or URL to land on after login. Default: the admin dashboard.
     *
     * [--launch]
     * : Open the URL in the browser (default on). Use --no-launch to only print it.
     *
     * [--porcelain]
     * : Output the raw URL only, for scripting.
     *
     * [--no-install-server]
     * : Skip the automatic handler install/refresh.
     *
     * [--force]
     * : Run on a production environment and force-refresh the handler.
     *
     * ## EXAMPLES
     *
     *     $ wp magic-login
     *     $ wp magic-login --login=admin --expiry=300 --no-launch
     *     # WP-CLI's global --user is also honoured via the current user:
     *     $ wp magic-login --user=admin
     *
     * @when after_wp_load
     *
     * @param array<int, string>   $args       Positional arguments (unused).
     * @param array<string, mixed> $assoc_args Named arguments.
     *
     * @return void
     */
    public function __invoke( array $args, array $assoc_args ): void {
        $force = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

        $this->guard_environment( $force );

        // The URL is useless without the handler; keep it installed and current.
        if ( ! (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'no-install-server', false ) ) {
            $this->ensure_handler( $force );
        }

        $user   = $this->resolve_user( $assoc_args );
        $expiry = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'expiry', 60 );

        if ( $expiry < 1 ) {
            WP_CLI::error( 'Expiry must be a positive integer (seconds).' );
        }

        $redirect = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'redirect', '' );
        $url      = $this->generate_login_url( $user, $expiry, $redirect );

        $porcelain = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain', false );
        $launch    = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'launch', true );

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

        if ( $launch && ! $this->open_in_browser( $url ) ) {
            WP_CLI::log( 'Could not open a browser automatically — open the URL above manually.' );
        }
    }

    // -------------------------------------------------------------------------
    // Guards & setup
    // -------------------------------------------------------------------------

    /**
     * Refuse to run on a production environment unless explicitly forced.
     *
     * Magic login is a passwordless backdoor; it must never fire on production
     * by accident.
     *
     * @param bool $force Whether --force was passed.
     *
     * @return void
     */
    private function guard_environment( bool $force ): void {
        $env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

        if ( 'production' === $env && ! $force ) {
            WP_CLI::error(
                'Refusing to run on a production environment. Magic login is a ' .
                'passwordless backdoor meant for local/staging use. Re-run with ' .
                '--force only if you are certain this is intended.'
            );
        }
    }

    /**
     * Install or refresh the handler mu-plugin, reporting only when it changes.
     *
     * @param bool $force Force a reinstall.
     *
     * @return void
     */
    private function ensure_handler( bool $force ): void {
        $result = MagicLoginServerInstallCommand::ensure_installed( $force );

        if ( 'installed' === $result['action'] ) {
            WP_CLI::log( sprintf( 'Installed the magic-login handler (v%s).', $result['version'] ) );
        } elseif ( 'updated' === $result['action'] ) {
            WP_CLI::log( sprintf( 'Updated the magic-login handler to v%s.', $result['version'] ) );
        }
    }

    // -------------------------------------------------------------------------
    // User resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve the target WordPress user from the flag, the global --user, or defaults.
     *
     * @param array<string, mixed> $assoc_args Named CLI arguments.
     *
     * @return \WP_User
     */
    private function resolve_user( array $assoc_args ): \WP_User {
        $flag = WP_CLI\Utils\get_flag_value( $assoc_args, 'login', null );

        if ( null !== $flag ) {
            $user = $this->find_user( (string) $flag );

            if ( ! $user instanceof \WP_User ) {
                WP_CLI::error( sprintf( 'No user found for "%s".', $flag ) );
            }

            return $user;
        }

        // WP-CLI consumes its global --user before the command runs, setting the
        // current user. Honour it so `wp magic-login --user=x` also works.
        $current = wp_get_current_user();
        if ( $current instanceof \WP_User && $current->ID > 0 ) {
            return $current;
        }

        return $this->default_admin();
    }

    /**
     * Resolve a user by numeric ID, email address, or login name.
     *
     * @param string $identifier The user identifier.
     *
     * @return \WP_User|false
     */
    private function find_user( string $identifier ) {
        if ( is_numeric( $identifier ) ) {
            return get_user_by( 'id', (int) $identifier );
        }

        if ( is_email( $identifier ) ) {
            return get_user_by( 'email', $identifier );
        }

        return get_user_by( 'login', $identifier );
    }

    /**
     * Pick the default administrator, or stop and list them when ambiguous.
     *
     * @return \WP_User
     */
    private function default_admin(): \WP_User {
        $admins = get_users(
            array(
                'role'    => 'administrator',
                'orderby' => 'ID',
                'order'   => 'ASC',
                'fields'  => array( 'ID', 'user_login' ),
            )
        );

        if ( empty( $admins ) ) {
            WP_CLI::error( 'No administrator account found on this site.' );
        }

        if ( count( $admins ) > 1 ) {
            $list = implode(
                ', ',
                array_map(
                    static function ( $admin ): string {
                        return sprintf( '%s (#%d)', $admin->user_login, (int) $admin->ID );
                    },
                    $admins
                )
            );

            WP_CLI::error(
                sprintf(
                    'Several administrators exist; pick one with --login=<user>. Administrators: %s',
                    $list
                )
            );
        }

        $user = get_userdata( (int) $admins[0]->ID );

        if ( ! $user instanceof \WP_User ) {
            WP_CLI::error( 'Failed to load the administrator account.' );
        }

        return $user;
    }

    // -------------------------------------------------------------------------
    // URL generation
    // -------------------------------------------------------------------------

    /**
     * Generate a one-time login URL backed by a hashed, single-use transient.
     *
     * A random token is handed out in the URL; only its hash is stored, keyed by
     * user ID (so a fresh URL invalidates any previous one). The handler deletes
     * the transient on first use and compares with hash_equals().
     *
     * @param \WP_User $user     The user to log in.
     * @param int      $expiry   Token lifetime in seconds.
     * @param string   $redirect Optional post-login path/URL.
     *
     * @return string The fully-qualified magic login URL.
     */
    private function generate_login_url( \WP_User $user, int $expiry, string $redirect = '' ): string {
        $token = wp_generate_password( 48, false );

        set_transient( 'magic_login_' . $user->ID, wp_hash( $token ), $expiry );

        $params = array(
            'action' => 'magic_login',
            'uid'    => $user->ID,
            'token'  => $token,
        );

        if ( '' !== $redirect ) {
            $params['redirect_to'] = $redirect;
        }

        return add_query_arg( $params, site_url( 'wp-login.php' ) );
    }

    // -------------------------------------------------------------------------
    // Browser launch
    // -------------------------------------------------------------------------

    /**
     * Open a URL in the system's default browser.
     *
     * Handles macOS (`open`), WSL (`wslview`/`cmd.exe`), Linux (`xdg-open`), and
     * Windows (`start`). Returns false when no suitable opener is available so
     * the caller can print a fallback.
     *
     * @param string $url The URL to open.
     *
     * @return bool True when an opener was dispatched.
     */
    private function open_in_browser( string $url ): bool {
        $escaped = escapeshellarg( $url );

        // WSL: use wslview when present, otherwise hand off to Windows.
        if ( false !== getenv( 'WSL_DISTRO_NAME' ) || false !== getenv( 'WSL_INTEROP' ) ) {
            if ( $this->command_exists( 'wslview' ) ) {
                exec( 'wslview ' . $escaped . ' >/dev/null 2>&1' );
                return true;
            }

            exec( 'cmd.exe /c start ' . $escaped . ' >/dev/null 2>&1' );
            return true;
        }

        switch ( PHP_OS_FAMILY ) {
            case 'Darwin':
                exec( 'open ' . $escaped . ' >/dev/null 2>&1' );
                return true;

            case 'Linux':
                if ( $this->command_exists( 'xdg-open' ) ) {
                    exec( 'xdg-open ' . $escaped . ' >/dev/null 2>&1' );
                    return true;
                }
                return false;

            case 'Windows':
                exec( 'start "" ' . $escaped );
                return true;
        }

        return false;
    }

    /**
     * Whether an executable is available on PATH.
     *
     * @param string $bin Executable name.
     *
     * @return bool
     */
    private function command_exists( string $bin ): bool {
        $lookup = ( 'Windows' === PHP_OS_FAMILY ) ? 'where' : 'command -v';

        $output = array();
        $code   = 1;
        exec( $lookup . ' ' . escapeshellarg( $bin ) . ' 2>/dev/null', $output, $code );

        return 0 === $code;
    }
}
