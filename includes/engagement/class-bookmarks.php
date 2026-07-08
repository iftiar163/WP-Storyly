<?php

namespace Narrato\Engagement;

defined( 'ABSPATH' ) || exit;

final class Bookmarks {

    private const REST_NS = 'narrato/v1';
    private const META_KEY = '_narrato_bookmarks';
    private const SLUG = 'my-bookmarks';

    public function register() : void {
        add_action( 'rest_api_init', [$this, 'register_routes'] );
        add_action( 'init', [$this, 'register_rewrite'] );
        add_filter( 'query_vars', [$this, 'add_query_var'] );
        add_filter( 'template_include', [$this, 'load_template'] );
    }

    public function register_routes() : void {
        // GET /narrato/v1/bookmarks - get bookmarks for current user
        register_rest_route( self::REST_NS, '/bookmarks', [
            'methods' => 'GET',
            'callback' => [$this, 'get_bookmarks'],
            'permission_callback' => [$this, 'is_logged_in'],
        ] );

        // POST /narrato/v1/bookmarks/{post_id}  — add bookmark
        register_rest_route( self::REST_NS, '/bookmarks/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'toggle_bookmark'],
            'permission_callback' => [$this, 'is_logged_in'],
            'args' => [
                'post_id' => [
                    'validate_callback' => fn($v) => is_numeric($v) && $v > 0
                ]
            ]
        ] );
    }

    public function is_logged_in() : bool {
        return is_user_logged_in();
    }

    public function get_bookmarks( \WP_REST_Request $request ) : \WP_REST_Response {
        $user_id = get_current_user_id();
        $bookmarks = self::get_user_bookmarks($user_id);

        return new \WP_REST_Response([
            'bookmarks' => $bookmarks,
            'count'     => count($bookmarks),
        ], 200);
    }

    public function toggle_bookmark( \WP_REST_Request $request ) : \WP_REST_Response {
        $post_id = (int) $request->get_param('post_id');
        $user_id = get_current_user_id();

        if( get_post_type($post_id) !== 'narrato_story' ) {
            return new \WP_REST_Response(['error' => 'Invalid Story'], 404);
        }

        $bookmarks = self::get_user_bookmarks($user_id);
        $bookmarked = in_array($post_id, $bookmarks, true);

        if( $bookmarked ) {
            $bookmarks = array_values( array_diff($bookmarks, [$post_id]) );
            $action = 'removed';
        } else {
            $bookmarks[] = $post_id;
            $action = 'added';
        }

        update_user_meta($user_id, self::META_KEY, $bookmarks);

        // Update bookmark count on post meta for display purposes
        $total_bookmarks = (int) get_post_meta( $post_id, '_narrato_bookmark_count', true );
        $total_bookmarks = $action === 'added' ? $total_bookmarks + 1 : max(0, $total_bookmarks - 1);
        update_post_meta( $post_id, '_narrato_bookmark_count', $total_bookmarks );

        return new \WP_REST_Response([
            'post_id'    => $post_id,
            'bookmarked' => ! $bookmarked,
            'action'     => $action,
            'count'      => count( $bookmarks ),
        ], 200);
    }

    // 'my-bookmarks' page rewrite for front-end display
    public function register_rewrite() : void {
        add_rewrite_rule(
            '^' . self::SLUG . '/?$',
            'index.php?narrato_bookmarks=1',
            'top'
        );
    }

    public function add_query_var( array $vars ) : array {
        $vars[] = 'narrato_bookmarks';
        return $vars;
    }

    public function load_template( string $template ) : string {
        if( get_query_var('narrato_bookmarks') ) {
            $custom = NARRATO_PATH . 'templates/my-bookmarks.php';
            if( file_exists($custom) ) {
                return $custom;
            }
        }

        return $template;
    }

    // Static Helpers
    public static function get_user_bookmarks( int $user_id ) : array {
        $bookmarks = get_user_meta($user_id, self::META_KEY, true);
        return is_array($bookmarks) ? array_map( 'intval', $bookmarks ) : [];
    }

    public static function is_bookmarked( int $post_id, int $user_id = 0  ) : bool {
        if( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if( ! $user_id ) {
            return false;
        }

        return in_array( $post_id, self::get_user_bookmarks($user_id), true );
    }


}