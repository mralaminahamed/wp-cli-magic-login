<?php

/**
 * Magic Login Handler (must-use plugin or drop-in)
 *
 * Hooks into `login_init` to intercept the ?action=magic_login request,
 * verify the one-time token, authenticate the user, and redirect.
 *
 * INSTALLATION:
 *   `wp magic-login` installs this automatically. To install manually:
 *   copy this file to wp-content/mu-plugins/wp-cli-magic-login-server.php
 *   (or run `wp magic-login install-server`).
 *
 * IMPORTANT:
 *   This handler is intended for local/staging environments. Do not deploy to
 *   production without additional security review.
 *
 * @package WP_CLI_Magic_Login
 *
 * Plugin Name: WP CLI Magic Login Command Server
 * Description: Companion plugin to the WP-CLI Magic Login Command
 * Author: Al Amin Ahamed
 * Author URI: https://alaminahamed.com
 * Plugin URI: https://github.com/mralaminahamed/wp-cli-magic-login
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Intercepts ?action=magic_login on wp-login.php and authenticates the user.
 *
 * The URL carries a random token; only its hash is stored in a per-user
 * transient. The transient is deleted on first access (single use) and the
 * token is compared with hash_equals() to avoid timing leaks.
 *
 * @return void
 */
function wp_cli_magic_login_handle_request(): void {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if (
        ! isset( $_GET['action'], $_GET['uid'], $_GET['token'] )
        || 'magic_login' !== $_GET['action']
    ) {
        return;
    }

    $uid         = absint( $_GET['uid'] );
    $token       = sanitize_text_field( wp_unslash( $_GET['token'] ) );
    $redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    if ( ! $uid || '' === $token ) {
        wp_die(
            esc_html__( 'Invalid magic login request.', 'magic-login' ),
            esc_html__( 'Magic Login Error', 'magic-login' ),
            array( 'response' => 400 )
        );
    }

    $transient_key = 'magic_login_' . $uid;
    $stored_hash   = get_transient( $transient_key );

    // Single-use: delete the transient immediately on first access.
    delete_transient( $transient_key );

    if ( ! is_string( $stored_hash ) || ! hash_equals( $stored_hash, wp_hash( $token ) ) ) {
        wp_die(
            esc_html__( 'This magic login link has expired or has already been used.', 'magic-login' ),
            esc_html__( 'Magic Login Error', 'magic-login' ),
            array( 'response' => 403 )
        );
    }

    $user = get_userdata( $uid );

    if ( ! $user ) {
        wp_die(
            esc_html__( 'User not found.', 'magic-login' ),
            esc_html__( 'Magic Login Error', 'magic-login' ),
            array( 'response' => 404 )
        );
    }

    // Log the user in and redirect.
    wp_set_auth_cookie( $user->ID, false );
    do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

    // wp_safe_redirect restricts to same-host targets, falling back to wp-admin.
    wp_safe_redirect( '' !== $redirect_to ? $redirect_to : admin_url() );
    exit;
}

add_action( 'login_init', 'wp_cli_magic_login_handle_request' );
