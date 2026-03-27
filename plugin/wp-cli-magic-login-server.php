<?php

/**
 * Magic Login Handler (must-use plugin or drop-in)
 *
 * Hooks into `login_init` to intercept the ?action=magic_login request,
 * verify the one-time token, authenticate the user, and redirect.
 *
 * INSTALLATION:
 *   Copy this file to wp-content/mu-plugins/wp-cli-magic-login-server.php
 *   OR include it from your theme's functions.php (development only).
 *
 * IMPORTANT:
 *   This handler is intentionally designed for local/staging environments.
 *   Do not deploy to production without additional security review.
 *
 * @package WP_CLI_Magic_Login
 *
 * Plugin Name: WP CLI Magic Login Command Server
 * Description: Companion plugin to the WP-CLI Magic Login Command
 * Author: Al Amin Ahamed
 * Author URI: https://alaminahamed.com
 * Plugin URI: https://github.com/mralaminahamed/wp-cli-magic-login
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Intercepts ?action=magic_login on wp-login.php and authenticates the user.
 *
 * @return void
 */
function magic_login_handle_request(): void {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if (
        ! isset( $_GET['action'], $_GET['uid'], $_GET['token'] )
        || 'magic_login' !== $_GET['action']
    ) {
        return;
    }

    $uid   = absint( $_GET['uid'] );
    $token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    if ( ! $uid || ! $token ) {
        wp_die(
            esc_html__( 'Invalid magic login request.', 'magic-login' ),
            esc_html__( 'Magic Login Error', 'magic-login' ),
            [ 'response' => 400 ]
        );
    }

    $transient_key   = 'magic_login_' . $uid . '_' . $token;
    $stored_uid      = get_transient( $transient_key );

    // Single-use: delete the transient immediately on first access.
    delete_transient( $transient_key );

    if ( false === $stored_uid || (int) $stored_uid !== $uid ) {
        wp_die(
            esc_html__( 'This magic login link has expired or has already been used.', 'magic-login' ),
            esc_html__( 'Magic Login Error', 'magic-login' ),
            [ 'response' => 403 ]
        );
    }

    $user = get_userdata( $uid );

    if ( ! $user ) {
        wp_die(
            esc_html__( 'User not found.', 'magic-login' ),
            esc_html__( 'Magic Login Error', 'magic-login' ),
            [ 'response' => 404 ]
        );
    }

    // Log the user in and redirect to the admin dashboard.
    wp_set_auth_cookie( $user->ID, false );
    do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

    wp_safe_redirect( admin_url() );
    exit;
}

add_action( 'login_init', 'magic_login_handle_request' );
