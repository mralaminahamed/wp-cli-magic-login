<?php

namespace WP_CLI_Magic_Login;

use WP_CLI;

/**
 * Installs and keeps current the magic-login handler mu-plugin.
 *
 * Internal helper used by {@see MagicLoginCommand}: `wp magic-login` installs and
 * refreshes the handler automatically, so there is no separate install command
 * (WP-CLI does not allow the leaf `magic-login` command to also own an
 * `install-server` subcommand). Run `wp magic-login --force` to force a reinstall.
 *
 * @package WP_CLI_Magic_Login
 */
class MagicLoginServerInstallCommand {

    /**
     * The handler filename, shared between source and target.
     */
    const HANDLER_FILE = 'wp-cli-magic-login-server.php';

    /**
     * Resolve the site's mu-plugins directory.
     *
     * @return string
     */
    public static function muplugins_dir(): string {
        return defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
    }

    /**
     * Path to the bundled handler shipped with this package.
     *
     * @return string
     */
    public static function source_path(): string {
        return dirname( __DIR__ ) . '/plugin/' . self::HANDLER_FILE;
    }

    /**
     * Path the handler is installed to inside the site.
     *
     * @return string
     */
    public static function target_path(): string {
        return self::muplugins_dir() . '/' . self::HANDLER_FILE;
    }

    /**
     * Read the `Version:` header from a handler file.
     *
     * @param string $file Absolute path.
     *
     * @return string|null The version, or null when the file is unreadable/unversioned.
     */
    public static function file_version( string $file ): ?string {
        if ( ! is_readable( $file ) ) {
            return null;
        }

        $data = get_file_data( $file, array( 'Version' => 'Version' ) );

        return '' !== $data['Version'] ? $data['Version'] : null;
    }

    /**
     * Version bundled with this package.
     *
     * @return string|null
     */
    public static function bundled_version(): ?string {
        return self::file_version( self::source_path() );
    }

    /**
     * Version currently installed in the site, if any.
     *
     * @return string|null
     */
    public static function installed_version(): ?string {
        return self::file_version( self::target_path() );
    }

    /**
     * Ensure the handler is installed and current, installing/updating as needed.
     *
     * @param bool $force Reinstall even when the installed handler is current.
     *
     * @return array{action:string,version:?string,dir:string} action is one of
     *               installed|updated|current.
     */
    public static function ensure_installed( bool $force = false ): array {
        $dir = self::muplugins_dir();

        if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
            WP_CLI::error( sprintf( 'mu-plugins directory does not exist and could not be created: %s', $dir ) );
        }

        $bundled   = self::bundled_version();
        $installed = self::installed_version();

        $needs_write = $force
            || null === $installed
            || null === $bundled
            || version_compare( (string) $installed, (string) $bundled, '<' );

        if ( ! $needs_write ) {
            return array(
                'action'  => 'current',
                'version' => $installed,
                'dir'     => $dir,
            );
        }

        if ( ! copy( self::source_path(), self::target_path() ) ) {
            WP_CLI::error( 'Failed to copy the handler file into mu-plugins.' );
        }

        return array(
            'action'  => null === $installed ? 'installed' : 'updated',
            'version' => $bundled,
            'dir'     => $dir,
        );
    }
}
