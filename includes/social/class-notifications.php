<?php

namespace Narrato\Social;

defined('ABSPATH') || exit;

final class Notifications
{

    private const REST_NS = 'narrato/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, register_routes]);
    }

    public function register_routes(): void
    {
        // GET /narrato/v1/notifications
        register_rest_route(self::REST_NS, '/notifications', [
            'methods'             => 'GET',
            'callback'            => [$this, get_notifications],
            'permission_callback' => fn() => is_user_logged_in(),
        ]);

        // POST /narrato/v1/notifications/read-all
        register_rest_route(self::REST_NS, '/notifications/read-all', [
            'methods'             => 'POST',
            'callback'            => [$this, mark_all_read],
            'permission_callback' => fn() => is_user_logged_in(),
        ]);
    }

    public function get_notifications(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $table   = $wpdb->prefix . 'narrato_notifications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
            $user_id
        ), ARRAY_A);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $unread = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));

        $formatted = array_map([$this, 'format_notification'], $rows ?: []);

        return new \WP_REST_Response([
            'notifications' => $formatted,
            'unread_count'  => $unread,
        ], 200);
    }

    private function format_notification(array $row): array
    {
        $message = '';
        $link    = '#';

        switch ($row['type']) {
            case 'new_follower':
                $actor   = get_userdata((int) $row['actor_id']);
                $name    = $actor ? $actor->display_name : __('Someone', 'narrato-for-writers');
                $message = sprintf(
                    /* translators: %s: follower display name */
                    __('%s started following you', 'narrato-for-writers'),
                    $name
                );
                $link = $actor ? home_url('/profile/' . $actor->user_nickname . '/') : '#';
                break;

            case 'new_story':
                $actor = get_userdata((int) $row['actor_id']);
                $post = get_post((int) $row['object_id']);
                $name = $actor ? $actor->display_name : __('A writer you follow', 'narrato-for-writers');
                $title = $post ? $post->post_title : '';
                $message = sprintf(
                    /* translators: 1: author name, 2: story title */
                    __('%1$s published "%2$s"', 'narrato-for-writers'),
                    $name,
                    $title
                );

                $link = $post ? get_permalink($post) : '#';
                break;

            default:
                $message = __('New notification', 'narrato-for-writers');
        }

        return [
            'id'         => (int) $row['id'],
            'type'       => $row['type'],
            'message'    => $message,
            'link'       => $link,
            'is_read'    => (bool) $row['is_read'],
            'created_at' => $row['created_at'],
            'time_ago'   => human_time_diff(strtotime($row['created_at']), current_time('timestamp')),
        ];
    }
}
