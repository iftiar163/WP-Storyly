<?php

namespace Narrato\Publications;

defined('ABSPATH') || exit;

final class Submissoins
{
    private const REST_NS = 'narrato/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        // POST /narrato/v1/publications/{pub_id}/submit
        register_rest_route(self::REST_NS, '/publications/(?P<pub_id>\d+)/submit', [
            'methods'               => 'POST',
            'callback'              => [$this, 'submit_story'],
            'permission_callback'   => fn() => is_user_logged_in(),
            'args'                  => [
                'pub_id' => [
                    'validate_callback' => fn($v) => is_numeric($v) && $v > 0,
                ],

                'story_id' => [
                    'required'          => true,
                    'validate_callback' => fn($v) => is_numeric($v) && $v > 0,
                ]
            ],
        ]);

        // GET /narrato/v1/publications/{pub_id}/submissions?status=pending
        register_rest_route(self::REST_NS, '/publications/(?P<pub_id>\d+)/submissions', [
            'methods'               => 'GET',
            'callback'              => [$this, 'get_submissions'],
            'permission_callback'   => [$this, 'is_editor_of_publication'],
        ]);

        // POST /narrato/v1/submissions/{id}/review
        register_rest_route(self::REST_NS, '/submissions/(?P<id>\d+)/review', [
            'methods'             => 'POST',
            'callback'            => [$this, 'review_submission'],
            'permission_callback' => [$this, 'can_review'],
            'args'                => [
                'action' => [
                    'required'          => true,
                    'validate_callback' => fn($v) => in_array($v, ['approve', 'reject', 'request_changes'], true),
                ],
                'note' => [
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // GET /narrato/v1/my-submissions — writer's own submission statuses
        register_rest_route(self::REST_NS, '/my-submissions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_my_submissions'],
            'permission_callback' => fn() => is_user_logged_in(),
        ]);
    }

    // Permission Checks
    public function is_editor_of(\WP_REST_Request $request): bool
    {
        $pub_id = $request->get_param('pub_id');
        return is_user_logged_in() && Editors::is_editor($pub_id, get_current_user_id());
    }

    public function can_review(\WP_REST_Request $request): bool
    {
        if (! is_user_logged_in()) return false;

        $submission = $this->get_submission_row((int) $request->get_param('id'));
        if (! $submission) return false;

        return Editors::is_editor($submission['publication_id'], get_current_user_id());
    }

    // Submit a Story
    public function submit_story(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $pub_id = (int) $request->get_param('pub_id');
        $story_id = (int) $request->get_param('story_id');
        $user_id  = get_current_user_id();

        // Validate publication exists and accepts open submissions
        $publication = get_post($pub_id);

        if (! $publication || $publication->post_type !== 'narrato_publication') {
            return new \WP_REST_Response(['error' => __('Publication not found.', 'narrato-for-writers')], 404);
        }

        $open = (bool) get_post_meta($pub_id, '_narrato_pub_open_submissions', true);
        if (! $open) {
            return new \WP_REST_Response(['error' => __('This publication is not accepting submissions right now.', 'narrato-for-writers')], 403);
        }

        // Validate story exists and belongs to the submitting user
        $story = get_post($story_id);
        if (! $story || $story->post_type !== 'narrato_story') {
            return new \WP_REST_Response(['error' => __('Story not found.', 'narrato-for-writers')], 404);
        }

        if ((int) $story->post_author !== $user_id) {
            return new \WP_REST_Response(['error' => __('You can only submit your own stories.', 'narrato-for-writers')], 403);
        }

        $table = $wpdb->prefix . 'narrato_submissions';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE story_id = %d AND publication_id = %d",
            $story_id,
            $pub_id
        ), ARRAY_A);

        if ($existing && $existing['status'] === 'pending') {
            return new \WP_REST_Response(['error' => __('This story is already pending review for this publication.', 'narrato-for-writers')], 409);
        }

        if ($existing && $existing['status'] === 'approved') {
            return new \WP_REST_Response(['error' => __('This story is already published in this publication.', 'narrato-for-writers')], 409);
        }

        if ($existing) {
            // Re-submitting after rejection or changes-requested — reset to pending
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update(
                $table,
                ['status' => 'pending', 'editor_note' => null, 'reviewed_by' => null],
                ['id' => $existing['id']],
                ['%s', '%s', '%d'],
                ['%d']
            );
            $submission_id = (int) $existing['id'];
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                $table,
                [
                    'story_id'       => $story_id,
                    'publication_id' => $pub_id,
                    'submitted_by'   => $user_id,
                    'status'         => 'pending',
                ],
                ['%d', '%d', '%d', '%s']
            );
            $submission_id = (int) $wpdb->insert_id;
        }

        $this->notify_editors($pub_id, $story, $publication);

        return new \WP_REST_Response([
            'submission_id' => $submission_id,
            'status'        => 'pending',
        ], 200);
    }

    // Editor Review Queue

    public function get_submissions(\WP_REST_Request $request): \WP_REST_Response
    {

        global $wpdb;

        $pub_id = (int) $request->get_param('pub_id');
        $status = $request->get_param('status') ?: 'pending';
        $table = $wpdb->prefix . 'narrato_submissions';

        $allowed_statuses = ['pending', 'approved', 'rejected', 'requested_changes'];
        if (! in_array($status, $allowed_statuses,  true)) {
            $status = 'pending';
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE publication_id = %d AND status = %s ORDER BY created_at DESC",
            $pub_id,
            $status
        ), ARRAY_A);

        $formatted = array_map([$this, 'format_submission'], $rows ?: []);

        return new \WP_REST_Response(['submissions' => $formatted], 200);
    }

    public function get_my_submission(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'narrato_submissions';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE submitted_by = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        $formatted = array_map([$this, 'format_submission'], $rows ?: []);

        return new \WP_REST_Response(['submissions' => $formatted], 200);
    }

    private function format_submission(array $row): array
    {
        $story = get_post((int) $row['story_id']);
        $publication = get_post((int) $row['publication_id']);
        $submitter = get_userdata((int) $row['submitted_by']);

        return [
            'id'               => (int) $row['id'],
            'story_id'         => (int) $row['story_id'],
            'story_title'      => $story ? $story->post_title : '',
            'story_link'       => $story ? get_permalink($story) : '',
            'publication_id'   => (int) $row['publication_id'],
            'publication_name' => $publication ? $publication->post_title : '',
            'submitted_by'     => $submitter ? $submitter->display_name : '',
            'status'           => $row['status'],
            'editor_note'      => $row['editor_note'],
            'created_at'       => $row['created_at'],
            'time_ago'         => human_time_diff(strtotime($row['created_at']), current_time('timestamp', true)),
        ];
    }

    // Review action — approve / reject / request changes
}
