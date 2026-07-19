<?php

namespace Narrato\Social;

defined('ABSPATH') || exit;

final class Notifications
{

    private const REST_NS = 'narrato/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('narrato_user_followed_author', [$this, 'on_new_follower'], 10, 2);
        add_action('transition_post_status', [$this, 'on_story_published'], 10, 3);
    }

    public function register_routes(): void
    {
        // GET /narrato/v1/notifications
        register_rest_route(self::REST_NS, '/notifications', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_notifications'],
            'permission_callback' => fn() => is_user_logged_in(),
        ]);

        // POST /narrato/v1/notifications/read-all
        register_rest_route(self::REST_NS, '/notifications/read-all', [
            'methods'             => 'POST',
            'callback'            => [$this, 'mark_all_read'],
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
            'time_ago' => human_time_diff(strtotime($row['created_at']), current_time('timestamp', true)),
        ];
    }

    public function mark_all_read(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'narrato_notifications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update(
            $table,
            ['is_read' => 1],
            ['user_id' => $user_id, 'is_read' => 0],
            ['%d'],
            ['%d', '%d']
        );

        return new \WP_REST_Response([
            'success' => true,
        ], 200);
    }

    // Trigger
    public function on_new_follower(int $follower_id, int $author_id): void
    {
        if ($this->recent_duplicate_exists($author_id, 'new_follower', $follower_id)) {
            return;
        }
        $this->insert_notification($author_id, 'new_follower', $follower_id, 0);
    }

    private function recent_duplicate_exists(int $user_id, string $type, int $actor_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_notifications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table}
         WHERE user_id = %d AND type = %s AND actor_id = %d
         AND created_at > DATE_SUB(%s, INTERVAL 24 HOUR)
         LIMIT 1",
            $user_id,
            $type,
            $actor_id,
            current_time('mysql', true)
        ));

        return (bool) $exists;
    }

    public function on_story_published(string $new_status, string $old_status, \WP_Post $post): void
    {
        if ($post->post_type !== 'narrato_story') return;
        if ($new_status !== 'publish' || $old_status === 'publish') return;

        $author_id = (int) $post->post_author;
        $follower_ids = $this->get_author_followers($author_id);

        foreach ($follower_ids as $follower_id) {
            $this->insert_notification($follower_id, 'new_story', $author_id, $post->ID);
        }
    }

    private function get_author_followers(int $author_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_follows';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE follow_type = 'author' AND object_id = %d",
            $author_id
        ));

        return array_map('intval', $ids);
    }

    private function insert_notification(int $user_id, string $type, int $actor_id, int $object_id): void
    {
        global $wpdb;
        if (! $user_id) return;

        $table = $wpdb->prefix . 'narrato_notifications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'type'       => $type,
                'actor_id'   => $actor_id,
                'object_id'  => $object_id,
                'created_at' => current_time('mysql', true), // ← GMT, explicit
            ],
            ['%d', '%s', '%d', '%d', '%s']
        );
    }

    public static function get_unread_count(int $user_id): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_notifications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }
}
