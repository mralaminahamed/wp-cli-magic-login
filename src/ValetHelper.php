<?php

namespace AlAminAhamed\WpCli\MagicLogin;

/**
 * Resolves WordPress installation paths for Laravel Valet sites.
 *
 * Valet parks sites under one or more "parked" directories (e.g. ~/Sites).
 * This helper locates the correct site root either from the current working
 * directory or from a domain name supplied on the CLI.
 *
 * @package AlAminAhamed\WpCli\MagicLogin
 */
class ValetHelper {

    /**
     * Attempts to resolve the absolute path to a site's wp-content/mu-plugins
     * directory from a domain name.
     *
     * Resolution order:
     *   1. If `--domain` is omitted, use the current working directory.
     *   2. Strip a trailing `.test` (or any TLD) to get the folder name.
     *   3. Walk every Valet parked/linked path looking for a match.
     *   4. Fall back to ~/Sites/<folder> when Valet config is unavailable.
     *
     * @param string|null $domain  e.g. "mysite.test" or null for cwd.
     *
     * @return string Absolute path to the mu-plugins directory.
     *
     * @throws \RuntimeException When the site root cannot be determined.
     */
    public static function resolve_muplugins_path( ?string $domain ): string {
        $site_root = null === $domain
            ? self::site_root_from_cwd()
            : self::site_root_from_domain( $domain );

        return rtrim( $site_root, DIRECTORY_SEPARATOR )
            . DIRECTORY_SEPARATOR . 'wp-content'
            . DIRECTORY_SEPARATOR . 'mu-plugins';
    }

    /**
     * Returns the site root derived from the current working directory.
     *
     * WP-CLI is typically invoked from within the WordPress root, so this
     * is the simplest and most reliable resolution strategy.
     *
     * @return string Absolute path to the WordPress root.
     *
     * @throws \RuntimeException When wp-config.php is not found in cwd.
     */
    public static function site_root_from_cwd(): string {
        $cwd = getcwd();

        if ( false === $cwd ) {
            throw new \RuntimeException( 'Unable to determine the current working directory.' );
        }

        // Walk upward up to 3 levels to find wp-config.php (handles running
        // from a sub-directory such as wp-content or public/).
        $dir = $cwd;
        for ( $i = 0; $i < 3; $i++ ) {
            if ( file_exists( $dir . DIRECTORY_SEPARATOR . 'wp-config.php' ) ) {
                return $dir;
            }
            $parent = dirname( $dir );
            if ( $parent === $dir ) {
                break;
            }
            $dir = $parent;
        }

        // If WP-CLI bootstrapped WordPress, ABSPATH is defined.
        if ( defined( 'ABSPATH' ) ) {
            return rtrim( ABSPATH, DIRECTORY_SEPARATOR );
        }

        throw new \RuntimeException(
            sprintf(
                'Could not locate wp-config.php from the current directory (%s). ' .
                'Run the command from within a WordPress installation root.',
                $cwd
            )
        );
    }

    /**
     * Resolves the WordPress root for a Valet site by its domain name.
     *
     * Reads Valet's config.json to find all parked and linked paths, then
     * matches the folder name derived from the domain.
     *
     * @param string $domain e.g. "mysite.test".
     *
     * @return string Absolute path to the WordPress root.
     *
     * @throws \RuntimeException When the site directory cannot be found.
     */
    public static function site_root_from_domain( string $domain ): string {
        // Strip TLD (e.g. ".test", ".local") to get the folder name.
        $folder = self::domain_to_folder( $domain );

        // Collect candidate base directories from Valet config.
        $base_dirs = self::valet_base_directories();

        foreach ( $base_dirs as $base ) {
            $candidate = rtrim( $base, DIRECTORY_SEPARATOR )
                . DIRECTORY_SEPARATOR . $folder;

            if ( is_dir( $candidate ) ) {
                return $candidate;
            }

            // Some setups nest WordPress inside a public/ sub-directory.
            $public = $candidate . DIRECTORY_SEPARATOR . 'public';
            if ( is_dir( $public ) && file_exists( $public . DIRECTORY_SEPARATOR . 'wp-config.php' ) ) {
                return $public;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Could not locate a site directory for domain "%s" (resolved folder: "%s"). ' .
                'Searched: %s',
                $domain,
                $folder,
                implode( ', ', $base_dirs )
            )
        );
    }

    /**
     * Strips the TLD from a domain to return the directory folder name.
     *
     * Examples:
     *   "mysite.test"  → "mysite"
     *   "shop.local"   → "shop"
     *   "blog"         → "blog"   (no TLD, used as-is)
     *
     * @param string $domain Full domain string.
     *
     * @return string Folder name.
     */
    public static function domain_to_folder( string $domain ): string {
        // Remove http(s):// if accidentally included.
        $domain = preg_replace( '#^https?://#', '', $domain );

        $parts = explode( '.', $domain );

        // If only one part (no dot), treat as folder directly.
        if ( count( $parts ) <= 1 ) {
            return $domain;
        }

        // Strip the last segment (TLD).
        array_pop( $parts );

        return implode( '.', $parts );
    }

    /**
     * Reads Valet's configuration to return all base site directories.
     *
     * Falls back to ~/Sites when Valet config is absent or unreadable.
     *
     * @return array<int, string> List of absolute directory paths.
     */
    private static function valet_base_directories(): array {
        $config_path = self::valet_config_path();
        $dirs        = [];

        if ( $config_path && file_exists( $config_path ) ) {
            $raw    = file_get_contents( $config_path );
            $config = $raw ? json_decode( $raw, true ) : null;

            if ( is_array( $config ) ) {
                // Parked directories.
                if ( ! empty( $config['paths'] ) && is_array( $config['paths'] ) ) {
                    foreach ( $config['paths'] as $path ) {
                        $dirs[] = (string) $path;
                    }
                }

                // Linked sites — each value is the absolute path to the site root itself.
                if ( ! empty( $config['links'] ) && is_array( $config['links'] ) ) {
                    foreach ( $config['links'] as $path ) {
                        $dirs[] = dirname( (string) $path );
                    }
                }
            }
        }

        // Always include ~/Sites as a safe fallback.
        $home   = self::home_directory();
        $dirs[] = $home . DIRECTORY_SEPARATOR . 'Sites';

        return array_unique( array_filter( $dirs ) );
    }

    /**
     * Returns the absolute path to Valet's config.json.
     *
     * Valet stores its configuration in ~/.config/valet/config.json.
     *
     * @return string|null Path to config.json, or null if undetectable.
     */
    private static function valet_config_path(): ?string {
        $home = self::home_directory();
        if ( ! $home ) {
            return null;
        }

        return $home
            . DIRECTORY_SEPARATOR . '.config'
            . DIRECTORY_SEPARATOR . 'valet'
            . DIRECTORY_SEPARATOR . 'config.json';
    }

    /**
     * Returns the current user's home directory.
     *
     * @return string Home directory path, or empty string on failure.
     */
    private static function home_directory(): string {
        // POSIX.
        if ( ! empty( $_SERVER['HOME'] ) ) {
            return (string) $_SERVER['HOME'];
        }

        // macOS / Linux fallback.
        $home = getenv( 'HOME' );
        if ( $home ) {
            return $home;
        }

        // Windows.
        if ( ! empty( $_SERVER['USERPROFILE'] ) ) {
            return (string) $_SERVER['USERPROFILE'];
        }

        return '';
    }
}
