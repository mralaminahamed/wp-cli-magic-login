<?php

namespace AlAminAhamed\WpCli\MagicLogin;

use WP_CLI;
use WP_CLI_Command;

/**
 * Manages must-use plugins for Valet-hosted WordPress sites.
 *
 * Installs, removes, and lists files in wp-content/mu-plugins without
 * requiring WordPress to be fully bootstrapped. Targets either the current
 * working directory or a specific Valet site by domain name.
 *
 * ## EXAMPLES
 *
 *     # Install the magic-login handler into the current site
 *     $ wp mu-plugin install magic-login-handler
 *
 *     # Install into a specific Valet site by domain
 *     $ wp mu-plugin install magic-login-handler --domain=mysite.test
 *
 *     # Install a custom PHP file from an absolute path
 *     $ wp mu-plugin install /path/to/my-plugin.php --domain=mysite.test
 *
 *     # Remove an mu-plugin by filename
 *     $ wp mu-plugin remove my-plugin.php
 *
 *     # List all mu-plugins in the current site
 *     $ wp mu-plugin list
 *
 *     # List mu-plugins for a specific domain
 *     $ wp mu-plugin list --domain=mysite.test
 *
 * @package AlAminAhamed\WpCli\MagicLogin
 */
class MuPluginCommand extends WP_CLI_Command {

    /**
     * Built-in plugin slugs that ship with this package.
     *
     * Maps a short slug → the PHP file bundled in the package root.
     *
     * @var array<string, string>
     */
    private const BUNDLED = [
        'magic-login-handler' => 'magic-login-handler.php',
    ];

    /**
     * Installs a must-use plugin into a site's wp-content/mu-plugins directory.
     *
     * <plugin>
     * : Either a bundled slug (e.g. "magic-login-handler") or an absolute
     *   path to a PHP file on disk.
     *
     * ## OPTIONS
     *
     * <plugin>
     * : Bundled slug or absolute path to a .php file.
     *
     * [--domain=<domain>]
     * : Valet site domain (e.g. mysite.test). Defaults to the current directory.
     *
     * [--force]
     * : Overwrite an existing file with the same name.
     *
     * ## EXAMPLES
     *
     *     $ wp mu-plugin install magic-login-handler
     *     $ wp mu-plugin install magic-login-handler --domain=mysite.test
     *     $ wp mu-plugin install /tmp/my-loader.php --domain=shop.test --force
     *
     * @subcommand install
     * @when       before_wp_load
     *
     * @param array<int, string>   $args
     * @param array<string, mixed> $assoc_args
     *
     * @return void
     */
    public function install( array $args, array $assoc_args ): void {
        if ( empty( $args[0] ) ) {
            WP_CLI::error( 'Please provide a plugin slug or file path. Run `wp mu-plugin install --help`.' );
        }

        $input  = $args[0];
        $domain = WP_CLI\Utils\get_flag_value( $assoc_args, 'domain', null );
        $force  = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

        [ $source_path, $filename ] = $this->resolve_source( $input );

        try {
            $muplugins_dir = ValetHelper::resolve_muplugins_path( $domain );
        } catch ( \RuntimeException $e ) {
            WP_CLI::error( $e->getMessage() );
        }

        $this->ensure_directory( $muplugins_dir );

        $destination = $muplugins_dir . DIRECTORY_SEPARATOR . $filename;

        if ( file_exists( $destination ) && ! $force ) {
            WP_CLI::error(
                sprintf(
                    '"%s" already exists in mu-plugins. Use --force to overwrite.',
                    $filename
                )
            );
        }

        if ( ! copy( $source_path, $destination ) ) {
            WP_CLI::error(
                sprintf( 'Failed to copy "%s" to "%s".', $source_path, $destination )
            );
        }

        WP_CLI::success(
            sprintf(
                'Installed "%s" → %s',
                $filename,
                $destination
            )
        );
    }

    /**
     * Removes a must-use plugin file from a site's mu-plugins directory.
     *
     * ## OPTIONS
     *
     * <file>
     * : Filename of the mu-plugin to remove (e.g. magic-login-handler.php).
     *   The .php extension may be omitted.
     *
     * [--domain=<domain>]
     * : Valet site domain. Defaults to the current directory.
     *
     * [--yes]
     * : Skip the confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     $ wp mu-plugin remove magic-login-handler.php
     *     $ wp mu-plugin remove magic-login-handler --domain=mysite.test --yes
     *
     * @subcommand remove
     * @when       before_wp_load
     *
     * @param array<int, string>   $args
     * @param array<string, mixed> $assoc_args
     *
     * @return void
     */
    public function remove( array $args, array $assoc_args ): void {
        if ( empty( $args[0] ) ) {
            WP_CLI::error( 'Please provide a filename. Run `wp mu-plugin remove --help`.' );
        }

        $filename = $this->normalise_filename( $args[0] );
        $domain   = WP_CLI\Utils\get_flag_value( $assoc_args, 'domain', null );

        try {
            $muplugins_dir = ValetHelper::resolve_muplugins_path( $domain );
        } catch ( \RuntimeException $e ) {
            WP_CLI::error( $e->getMessage() );
        }

        $target = $muplugins_dir . DIRECTORY_SEPARATOR . $filename;

        if ( ! file_exists( $target ) ) {
            WP_CLI::error(
                sprintf( '"%s" does not exist in mu-plugins (%s).', $filename, $muplugins_dir )
            );
        }

        WP_CLI::confirm(
            sprintf( 'Remove "%s" from mu-plugins?', $filename ),
            $assoc_args
        );

        if ( ! unlink( $target ) ) {
            WP_CLI::error( sprintf( 'Failed to remove "%s".', $target ) );
        }

        WP_CLI::success( sprintf( 'Removed "%s".', $filename ) );
    }

    /**
     * Lists all PHP files present in a site's mu-plugins directory.
     *
     * ## OPTIONS
     *
     * [--domain=<domain>]
     * : Valet site domain. Defaults to the current directory.
     *
     * [--format=<format>]
     * : Output format. Accepts: table, csv, json, yaml, ids, count.
     * : Default: table.
     *
     * ## EXAMPLES
     *
     *     $ wp mu-plugin list
     *     $ wp mu-plugin list --domain=mysite.test
     *     $ wp mu-plugin list --domain=mysite.test --format=json
     *
     * @subcommand list
     * @when       before_wp_load
     *
     * @param array<int, string>   $args
     * @param array<string, mixed> $assoc_args
     *
     * @return void
     */
    public function list( array $args, array $assoc_args ): void {
        $domain = WP_CLI\Utils\get_flag_value( $assoc_args, 'domain', null );
        $format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

        try {
            $muplugins_dir = ValetHelper::resolve_muplugins_path( $domain );
        } catch ( \RuntimeException $e ) {
            WP_CLI::error( $e->getMessage() );
        }

        if ( ! is_dir( $muplugins_dir ) ) {
            WP_CLI::warning(
                sprintf( 'mu-plugins directory does not exist: %s', $muplugins_dir )
            );
            return;
        }

        $files = glob( $muplugins_dir . DIRECTORY_SEPARATOR . '*.php' );

        if ( empty( $files ) ) {
            WP_CLI::line( 'No must-use plugins found.' );
            return;
        }

        $rows = [];
        foreach ( $files as $file ) {
            $rows[] = [
                'file'     => basename( $file ),
                'size'     => $this->human_filesize( filesize( $file ) ),
                'modified' => date( 'Y-m-d H:i:s', filemtime( $file ) ),
                'path'     => $file,
            ];
        }

        WP_CLI\Utils\format_items(
            $format,
            $rows,
            [ 'file', 'size', 'modified', 'path' ]
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolves a user-supplied input to a [ source_path, filename ] tuple.
     *
     * Accepts:
     *  - A bundled slug key (e.g. "magic-login-handler")
     *  - An absolute or relative path to an existing .php file
     *
     * @param string $input Raw CLI argument.
     *
     * @return array{0: string, 1: string} [ absolute source path, filename ].
     */
    private function resolve_source( string $input ): array {
        // 1. Bundled slug.
        if ( isset( self::BUNDLED[ $input ] ) ) {
            $filename    = self::BUNDLED[ $input ];
            $source_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . $filename;

            if ( ! file_exists( $source_path ) ) {
                WP_CLI::error(
                    sprintf(
                        'Bundled file "%s" not found at expected path: %s',
                        $filename,
                        $source_path
                    )
                );
            }

            return [ $source_path, $filename ];
        }

        // 2. File path supplied directly.
        $real = realpath( $input );

        if ( false === $real || ! file_exists( $real ) ) {
            WP_CLI::error(
                sprintf(
                    '"%s" is not a recognised slug and was not found as a file path. ' .
                    'Available slugs: %s',
                    $input,
                    implode( ', ', array_keys( self::BUNDLED ) )
                )
            );
        }

        if ( pathinfo( $real, PATHINFO_EXTENSION ) !== 'php' ) {
            WP_CLI::error( 'Only .php files may be installed as must-use plugins.' );
        }

        return [ $real, basename( $real ) ];
    }

    /**
     * Ensures the mu-plugins directory exists, creating it if necessary.
     *
     * @param string $dir Absolute path.
     *
     * @return void
     */
    private function ensure_directory( string $dir ): void {
        if ( is_dir( $dir ) ) {
            return;
        }

        if ( ! mkdir( $dir, 0755, true ) ) {
            WP_CLI::error( sprintf( 'Could not create directory: %s', $dir ) );
        }

        WP_CLI::debug( sprintf( 'Created mu-plugins directory: %s', $dir ), 'mu-plugin' );
    }

    /**
     * Normalises a filename by ensuring it has a .php extension.
     *
     * @param string $name User-supplied filename or slug.
     *
     * @return string Filename with .php extension.
     */
    private function normalise_filename( string $name ): string {
        if ( pathinfo( $name, PATHINFO_EXTENSION ) !== 'php' ) {
            return $name . '.php';
        }

        return $name;
    }

    /**
     * Converts a byte count to a human-readable string.
     *
     * @param int|false $bytes Raw byte count from filesize().
     *
     * @return string Formatted size string (e.g. "4.2 KB").
     */
    private function human_filesize( $bytes ): string {
        if ( false === $bytes || $bytes < 0 ) {
            return '—';
        }

        $units = [ 'B', 'KB', 'MB', 'GB' ];
        $i     = 0;

        while ( $bytes >= 1024 && $i < count( $units ) - 1 ) {
            $bytes /= 1024;
            $i++;
        }

        return round( $bytes, 1 ) . ' ' . $units[ $i ];
    }
}
