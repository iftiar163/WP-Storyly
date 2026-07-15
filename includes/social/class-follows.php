<?php

namespace Narrato\Social;

defined('ABSPATH') || exit;

final class Follows
{

    private const REST_NS = 'narrato/v1';
    private const TYPES = ['author', 'topic', 'story'];

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        // GET /narrato/v1/follows/status?type=author&object_id=5
        register_rest_route(self::REST_NS, '/follows/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_status'],
            'permission_callback' => '__return_true',
            'args'                => $this->route_args(),
        ]);

        // POST /narrato/v1/follows/toggle
        register_rest_route(self::REST_NS, '/follows/toggle', [
            'methods'             => 'POST',
            'callback'            => [$this, 'toggle'],
            'permission_callback' => fn() => is_user_logged_in(),
            'args'                => $this->route_args(),
        ]);

        // GET /narrato/v1/follows/counts?type=author&object_id=5
        register_rest_route(self::REST_NS, '/follows/counts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_counts'],
            'permission_callback' => '__return_true',
            'args'                => $this->route_args(),
        ]);
    }

    private function route_args(): array
    {
        return [
            'type' => [
                'required' => true,
                'validate_callback' => fn($v) => in_array($v, self::TYPES, true),
            ],
            'object_id' => [
                'required' => true,
                'validate_callback' => fn($v) => is_numeric($v) && $v > 0,
            ]
        ];
    }

    public function get_status(\WP_REST_Request $request): \WP_REST_Response
    {
        $type = $request->get_param('type');
        $object_id = (int) $request->get_param('object_id');

        if (! is_user_logged_in()) {
            return new \WP_REST_Response([
                'following' => false,
            ], 200);
        }

        $following = self::is_following(get_current_user_id(), $type, $object_id);

        return new \WP_REST_Response([
            'following' => $following,
        ], 200);
    }

    public function get_counts(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $type = $request->get_param('type');
        $object_id = (int) $request->get_param('object_id');
        $table = $wpdb->prefix . 'narrato_follows';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE follow_type = %s AND object_id = %d",
            $type,
            $object_id
        ));

        return new \WP_REST_Response([
            'count' => $count ?? 0,
        ], 200);
    }

    public function toggle(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $type = $request->get_param('type');
        $object_id = (int) $request->get_param('object_id');
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'narrato_follows';

        // Validate Target Exists
        if (! $this->target_exists($type, $object_id)) {
            return new \WP_REST_Response([
                'error' => __('Invalid target.', 'narrato-for-writers')
            ], 404);
        }

        // Prevent Self Follow for authors
        if ($type === 'author' && $object_id === $user_id) {
            return new \WP_REST_Response([
                'error' => __('You cannot follow yourself.', 'narrato-for-writers')
            ], 400);
        }

        $already_following = self::is_following($user_id, $type, $object_id);

        if ($already_following) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete(
                $table,
                [
                    'user_id' => $user_id,
                    'follow_type' => $type,
                    'object_id' => $object_id
                ],
                ['%d', '%s', '%d']
            );
            $action = 'unfollowed';
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                $table,
                [
                    'user_id'     => $user_id,
                    'follow_type' => $type,
                    'object_id'   => $object_id,
                ],
                ['%d', '%s', '%d']
            );
            $action = 'followed';

            // Trigger notification when following an author
            if ($type === 'author') {
                do_action('narrato_user_followed_author', $user_id, $object_id);
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE follow_type = %s AND object_id = %d",
            $type,
            $object_id
        ));

        return new \WP_REST_Response([
            'following' => ! $already_following,
            'action'    => $action,
            'count'     => $count,
        ], 200);
    }

    private function target_exists(string $type, int $object_id): bool
    {
        switch ($type) {
            case 'author':
                return (bool) get_userdata($object_id);
            case 'topic':
                return (bool) get_term($object_id, 'narrato_topic');
            case 'story':
                return get_post_type($object_id) === 'narrato_story';
            default:
                return false;
        }
    }

    // Static Helper 
    public static function is_following(int $user_id, string $type, int $object_id): bool
    {
        global $wpdb;
        if (! $user_id) return false;

        $table = $wpdb->prefix . 'narrato_follows';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND follow_type = %s AND object_id = %d",
            $user_id,
            $type,
            $object_id
        ));

        return (bool) $exists;
    }

    public static function get_count(string $type, int $object_id): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_follows';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE follow_type = %s AND object_id = %d",
            $type,
            $object_id
        ));
    }

    public static function get_followed_author_ids(int $user_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_follows';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT object_id FROM {$table} WHERE user_id = %d AND follow_type = 'author'",
            $user_id
        ));

        return array_map('intval', $ids);
    }

    public static function get_followed_topic_ids(int $user_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_follows';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT object_id FROM {$table} WHERE user_id = %d AND follow_type = 'topic'",
            $user_id
        ));

        return array_map('intval', $ids);
    }
}
