<?php

namespace Narrato\Meta;

defined('ABSPATH') || exit;

final class StoryMetabox
{

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('save_post_narrato_story', [$this, 'save_metabox']);
    }

    public function add_metabox(): void
    {
        add_meta_box(
            'narrato_paywall_metabox',
            __('Narrato — Paywall', 'narrato-for-writers'),
            [$this, 'render_metabox'],
            'narrato_story',
            'side',
            'default'
        );
    }

    public function render_metabox(\WP_Post $post): void
    {
        wp_nonce_field('narrato_paywall_metabox', 'narrato_paywall_nonce');

        $current = get_post_meta($post->ID, '_narrato_paywall_type', true) ?: 'none';
?>
        <p>
            <label for="narrato_paywall_type">
                <strong><?php esc_html_e('Paywall Type', 'narrato-for-writers'); ?></strong>
            </label>
        </p>
        <select name="narrato_paywall_type" id="narrato_paywall_type" style="width:100%;">
            <option value="none" <?php selected($current, 'none'); ?>>
                <?php esc_html_e('None — free for everyone', 'narrato-for-writers'); ?>
            </option>
            <option value="metered" <?php selected($current, 'metered'); ?>>
                <?php esc_html_e('Metered — counts toward free monthly reads', 'narrato-for-writers'); ?>
            </option>
            <option value="hard" <?php selected($current, 'hard'); ?>>
                <?php esc_html_e('Hard — members only, no free reads', 'narrato-for-writers'); ?>
            </option>
        </select>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e('Controls whether non-members can read this story.', 'narrato-for-writers'); ?>
        </p>
<?php
    }

    public function save_metabox(int $post_id): void
    {
        if (
            ! isset($_POST['narrato_paywall_nonce']) ||
            ! wp_verify_nonce($_POST['narrato_paywall_nonce'], 'narrato_paywall_metabox')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $value = isset($_POST['narrato_paywall_type']) ? sanitize_text_field($_POST['narrato_paywall_type']) : 'none';
        if (! in_array($value, ['none', 'metered', 'hard'], true)) {
            $value = 'none';
        }

        update_post_meta($post_id, '_narrato_paywall_type', $value);
    }
}
