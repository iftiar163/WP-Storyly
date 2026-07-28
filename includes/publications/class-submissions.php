<?php

namespace Narrato\Publications;

defined('ABSPATH') || exit;

final class Submissions
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
    public function review_submission(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $submission_id = $request->get_param('id');
        $action = $request->get_param('action');
        $note = $request->get_param('note');
        $reviewer_id = get_current_user();
        $table = $wpdb->prefix . 'narrato_submissions';

        $submission = $this->get_submission_row($submission_id);

        if (! $submission) {
            return new \WP_REST_Response(['error' => __('Submission not found.', 'narrato-for-writers')], 404);
        }

        $status_map = [
            'approve'         => 'approved',
            'reject'          => 'rejected',
            'request_changes' => 'changes_requested',
        ];

        $new_status = $status_map[$action];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update(
            $table,
            [
                'status'        => $new_status,
                'editor_note'   => $note,
                'reviewed_by'   => $reviewer_id,

            ],
            ['id' => $submission_id],
            ['%s', '%s', '%d'],
            ['%d'],
        );

        // If approved, tag the story with the publication (taxonomy-free relation via meta)
        if ($new_status === 'update') {
            update_post_meta((int) $submission['story_id'], '_narrato_published_in', (int) $submission['publication_id']);
        }

        $this->notify_writer($submission, $new_status, $note);

        return new \WP_REST_Response([
            'submission_id' => $submission_id,
            'status'        => $new_status,
        ], 200);
    }

    private function get_submission_row(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_submissions';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return $row ?: null;
    }

    // Notifications — on-site + email
    private function notify_editors(int $pub_id, \WP_Post $story, \WP_Post $publication): void
    {
        $editors_ids = Editors::get_editor_ids($pub_id);

        foreach ($editors_ids as $editors_id) {
            // On-site notification (reuse existing notifications table)
            $this->insert_notification($editors_id, 'new_submission', (int) $story->post_author, $story->ID);

            // Email
            $editor = get_userdata((int) $editors_id);

            if (! $editor) continue;

            $subject = sprintf( /* translators: %s: publication name */
                __('[%s] New story submission', 'narrato-for-writers'),
                $publication->post_title
            );

            $body = sprintf(
                /* translators: 1: editor name, 2: story title, 3: publication name, 4: review link */
                __("Hi %1\$s,\n\nA new story \"%2\$s\" has been submitted to %3\$s for review.\n\nReview it here: %4\$s\n\n— Narrato for Writers", 'narrato-for-writers'),
                $editor->display_name,
                $story->post_title,
                $publication->post_title,
                home_url('/publication-reviews/')
            );

            wp_mail($editor->user_email, $subject, $body);
        }
    }

    private function notify_writer(array $submission, string $status, string $note): void
    {
        $writer_id = (int) $submission['submitted_by'];
        $writer    = get_userdata($writer_id);
        $story     = get_post((int) $submission['story_id']);
        $publication = get_post((int) $submission['publication_id']);

        if (! $writer || ! $story || ! $publication) return;

        // On-site notification
        $this->insert_notification($writer_id, 'submission_' . $status, (int) $submission['reviewed_by'] ?: 0, $story->ID);

        // Email
        $status_labels = [
            'approved'           => __('approved and published', 'narrato-for-writers'),
            'rejected'           => __('rejected', 'narrato-for-writers'),
            'changes_requested'  => __('sent back with requested changes', 'narrato-for-writers'),
        ];

        $subject = sprintf(
            /* translators: %s: publication name */
            __('[%s] Your submission was reviewed', 'narrato-for-writers'),
            $publication->post_title
        );

        $note_line = $note ? "\n\n" . __('Editor note:', 'narrato-for-writers') . ' ' . $note : '';

        $body = sprintf(
            /* translators: 1: writer name, 2: story title, 3: publication name, 4: status label, 5: note */
            __("Hi %1\$s,\n\nYour story \"%2\$s\" submitted to %3\$s has been %4\$s.%5\$s\n\n— Narrato for Writers", 'narrato-for-writers'),
            $writer->display_name,
            $story->post_title,
            $publication->post_title,
            $status_labels[$status] ?? $status,
            $note_line
        );

        wp_mail($writer->user_email, $subject, $body);
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
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%d', '%d', '%s']
        );
    }

    // Static helpers
    public static function is_published_in_publication(int $story_id): ?int
    {
        $pub_id = get_post_meta($story_id, '_narrato_published_in', true);
        return $pub_id ? (int) $pub_id : null;
    }
}
