<?php

namespace Narrato\Engagement;

defined( 'ABSPATH' ) || exit;

final class Claps {
    
    private const MAX_CLAPS = 50;
    private const REST_NS = 'narrato/v1';

    public function register() : void {
        add_action( 'rest_api_init', [$this, 'register_routes'] );
    }

    public function register_routes() : void {
        // GET /narrto/v1/claps/{post_id} - get clap data for currnet user
        register_rest_route( self::REST_NS, '/claps/(?P<post_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_claps'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_id' => [
                    'validate_callback' => fn($v) => is_numeric($v) && $v > 0
                ]
            ]
        ] );

        // POST /narrato/v1/claps/{post_id}  — add claps
        register_rest_route( self::REST_NS, '/claps/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'add_claps'],
            'permission_callback' => [$this, 'is_logged_in'],
            'args' => [
                'post_id' => [
                    'validate_callback' => fn($v) => is_numeric($v) && $v > 0
                ],
                'count' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn( $v ) => $v >= 1 && $v <= self::MAX_CLAPS,
                ]
            ]
        ] );
    }

    public function is_logged_in() : bool {
        return is_user_logged_in();
    }

    public function get_claps( \WP_REST_Request $request ) : \WP_REST_Response {
        global $wpdb;

        $post_id = (int) $request->get_param('post_id');

        // Verify if the post exists and is a narrato_story
        if( get_post_type($post_id) !== 'narrato_story' ) {
            return new \WP_REST_Response(['error' => 'Invalid Story'], 404);
        }

        $table = $wpdb->prefix . 'narrato_claps';
        // Total claps for the post
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(clap_count) FROM {$table} WHERE post_id = %d",
            $post_id
        ) );

        $user_claps = 0;
        if( is_user_logged_in() ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $user_claps = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT clap_count FROM {$table} WHERE post_id = %d AND user_id = %d",
                $post_id,
                get_current_user_id()
            ) );
        }

        return new \WP_REST_Response([
            'post_id'    => $post_id,
            'total'      => $total,
            'user_claps' => $user_claps,
            'max_claps'  => self::MAX_CLAPS,
            'remaining'  => max( 0, self::MAX_CLAPS - $user_claps ),
        ], 200);
    }
}