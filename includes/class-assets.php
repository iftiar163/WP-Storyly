<?php

namespace Narrato;

defined('ABSPATH') || exit;

final class Assets
{

    public function register(): void
    {
        add_action('wp_enqueue_scripts',    [$this, 'enqueue_frontend']);
        add_action('enqueue_block_assets',  [$this, 'enqueue_editor']);
        add_action('wp_footer', [$this, 'render_notification_bell']);
    }

    public function render_notification_bell(): void
    {
        if (! is_user_logged_in()) {
            return;
        }
?>
        <div class="narrato-notif-wrap">
            <button class="narrato-notif-bell" aria-label="<?php esc_attr_e('Notifications', 'narrato-for-writers'); ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                </svg>
                <span class="narrato-notif-badge" style="display:none;">0</span>
            </button>
            <div class="narrato-notif-dropdown"></div>
        </div>
<?php
    }

    public function enqueue_frontend(): void
    {
        if (! $this->is_narrato_page()) {
            return;
        }

        wp_enqueue_style(
            'narrato-frontend',
            NARRATO_URL . 'assets/css/frontend.css',
            [],
            NARRATO_VERSION
        );

        if (is_singular('narrato_story')) {
            wp_enqueue_script(
                'narrato-reading-progress',
                NARRATO_URL . 'assets/js/reading-progress.js',
                [],
                NARRATO_VERSION,
                true
            );
        }

        // Engagement features
        if (is_user_logged_in()) {
            wp_enqueue_style(
                'narrato-engagement',
                NARRATO_URL . 'assets/css/engagement.css',
                [],
                NARRATO_VERSION
            );

            wp_enqueue_script(
                'narrato-engagement',
                NARRATO_URL . 'assets/js/engagement.js',
                [],
                NARRATO_VERSION,
                true
            );

            wp_localize_script('narrato-engagement', 'narratoEngagement', [
                'restUrl'   => esc_url_raw(rest_url('narrato/v1')),
                'nonce'     => wp_create_nonce('wp_rest'),
                'postId'    => get_the_ID(),
                'isLoggedIn' => true,
                'maxClaps'  => 50,
                'i18n'      => [
                    'clap'           => __('Clap', 'narrato-for-writers'),
                    'clapped'        => __('Clapped!', 'narrato-for-writers'),
                    'bookmark'       => __('Save story', 'narrato-for-writers'),
                    'bookmarked'     => __('Saved!', 'narrato-for-writers'),
                    'loginRequired'  => __('Please log in to clap or bookmark stories.', 'narrato-for-writers'),
                    'maxReached'     => __('You\'ve used all 50 claps!', 'narrato-for-writers'),
                ],
            ]);
        }

        // Bookmark Page
        if (get_query_var('narrato_bookmarks')) {
            wp_enqueue_style(
                'narato-frontend',
                NARRATO_URL . 'assets/css/frontend.css',
                [],
                NARRATO_VERSION
            );
        }

        // Social — follow buttons + notification bell (all narrato pages, logged-in only)
        if (is_user_logged_in()) {
            wp_enqueue_style(
                'narrato-social',
                NARRATO_URL . 'assets/css/social.css',
                ['narrato-frontend'],
                NARRATO_VERSION
            );

            wp_enqueue_script(
                'narrato-social',
                NARRATO_URL . 'assets/js/social.js',
                [],
                NARRATO_VERSION,
                true
            );

            wp_localize_script('narrato-social', 'narratoSocial', [
                'restUrl' => esc_url_raw(rest_url('narrato/v1')),
                'nonce'   => wp_create_nonce('wp_rest'),
                'i18n'    => [
                    'follow'        => __('Follow', 'narrato-for-writers'),
                    'following'     => __('Following', 'narrato-for-writers'),
                    'notifications' => __('Notifications', 'narrato-for-writers'),
                    'noNotifs'      => __("You're all caught up!", 'narrato-for-writers'),
                ],
            ]);
        }
    }

    public function enqueue_editor(): void
    {
        if (! is_admin()) {
            return;
        }

        $screen = get_current_screen();
        if (! $screen || $screen->post_type !== 'narrato_story') {
            return;
        }

        wp_enqueue_style(
            'narrato-editor',
            NARRATO_URL . 'assets/css/editor.css',
            ['wp-edit-blocks'],
            NARRATO_VERSION
        );
    }

    private function is_narrato_page(): bool
    {
        return is_singular('narrato_story')
            || is_post_type_archive('narrato_story')
            || is_tax('narrato_topic')
            || (bool) get_query_var('narrato_bookmarks')
            || (bool) get_query_var('narrato_profile')
            || (bool) get_query_var('narrato_following');
    }
}
