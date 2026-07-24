<?php

namespace Narrato\Publications;

defined( 'ABSPATH' ) || exit;

final class Editors {
    
    public function register() : void {
        add_action( 'rest_api_init', [$this, 'register_routes'] );
        add_action( 'save_post_narrato_publication', [$this, 'ensure_creator_is_editor'], 10, 2 );
    }

    public function register_routes(): void {
        register_rest_route( 'narrato/v1', '/publications/(?P<id>\d+)/editors', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'add_editor' ],
            'permission_callback' => [ $this, 'can_manage' ],
            'args'                => [
                'user_id' => [
                    'required'          => true,
                    'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                ],
            ],
        ] );

        register_rest_route( 'narrato/v1', '/publications/(?P<id>\d+)/editors/(?P<user_id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'remove_editor' ],
            'permission_callback' => [ $this, 'can_manage' ],
        ] );
    }

    public function can_manage( \WP_REST_Request $request ) : bool {
        $pub_id = (int) $request->get_param('id');
        return is_user_logged_in() && self::is_editor( $pub_id, get_current_user_id() );
    }

    public function ensure_creator_is_editor( int $post_id, \WP_Post $post ): void {
        $editors = get_post_meta( $post_id, '_narrato_pub_editors', true );
        $editors = is_array($editors) ? $editors : [];

        if ( ! in_array( (int) $post->post_author, $editors, true ) ) {
            $editors[] = (int) $post->post_author;
            update_post_meta( $post_id, '_narrato_pub_editors', array_unique( $editors ) );
        }
    }

    public function add_editor( \WP_REST_Request $request ) : \WP_REST_Response {
        $pub_id = (int) $request->get_param('id');
        $user_id = (int) $request->get_param('user_id');

        if( ! get_userdata( $user_id ) ) {
            return new \WP_REST_Response( [ 'error' => __( 'User not found.', 'narrato-for-writers' ) ], 404 );
        }

        $editors = get_post_meta( $pub_id, '_narrato_pub_editors', true );
        $editors = is_array( $editors ) ? $editors : [];

        if( ! in_array( $user_id, $editors, true ) ) {
            $editors[] = $user_id;
            update_post_meta( $pub_id, '_narrato_pub_editors', $editors );
        }

        return new \WP_REST_Response([ 'editors' => $editors ], 200);
    }

    public function remove_editor( \WP_REST_Request $request ) : \WP_REST_Response {
        $pub_id     = (int) $request->get_param('id');
        $user_id    = (int) $request->get_param('user_id');

        $editors = get_post_meta( $pub_id, '_narrato_pub_editors', true );
        $editors = is_array( $editors ) ? $editors : [];

        // Prevent removing the last editor
        if( count( $editors ) <= 1 ) {
            return new \WP_REST_Response( [ 'error' => __( 'A publication must have at least one editor.', 'narrato-for-writers' ) ], 403 );
        }

        $editors = array_values( array_diff( $editors, [ $user_id ] ) );
        update_post_meta( $pub_id, '_narrato_pub_editors', $editors );

        return new \WP_REST_Response( [ 'editors' => $editors ], 200 );
    }

    // Static Helpers

    public static function is_editor( int $publication_id, int $user_id ) : bool {
        if( ! $user_id ) return false;
        $editors = get_post_meta( $publication_id, '_narrato_pub_editors', true );
        $editors = is_array( $editors ) ? array_map( 'intval', $editors ) : [];
        return in_array( $user_id, $editors, true );
    }

    public static function get_editor_ids( int $publication_id ) : array {
        $editors = get_post_meta( $publication_id, '_narrato_pub_editor', true );
        return is_array( $editors ) ? array_map( 'intval', $editors ) : [];
    }

    public static function get_editors_publications( int $user_id ): array {
        $query = new \WP_Query( [
            'post_type'      => 'narrato_publication',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [ [
                'key'     => '_narrato_pub_editors',
                'value'   => sprintf( ':%d;', $user_id ),
                'compare' => 'LIKE',
            ] ],
            'fields' => 'ids',
        ] );

        return $query->posts;
    }


}