<?php

namespace Storyly\Admin;

defined('ABSPATH') || exit;

final class Settings
{
    private const OPTION_GROUP = 'wp_storyly_settings';
    private const OPTION_NAME = 'wp_storyly_options';
    private const PAGE_SLUG = 'storyly-settings';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('plugin_action_links_' . STORYLY_BASENAME, [$this, 'add_settings_link']);
    }

    public function add_menu_page(): void
    {
        add_menu_page(
            __('Storyly Settings', 'storyly'),
            __('Storyly', 'storyly'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default'           => self::get_options(),
            ]
        );

        add_settings_section(
            'storyly_section_reading',
            __('Reading Experience', 'storyly'),
            '__return_false',
            self::PAGE_SLUG,
        );

        add_settings_field(
            'stories_per_page',
            __('Stories Per Page', 'storyly'),
            [$this, 'field_stories_per_page'],
            self::PAGE_SLUG,
            'storyly_section_reading'
        );

        add_settings_field(
            'show_reading_time',
            __('Show Reading Time', 'storyly'),
            [$this, 'field_show_reading_time'],
            self::PAGE_SLUG,
            'storyly_section_reading'
        );

        add_settings_field(
            'show_progress_bar',
            __('Show reading progress bar', 'storyly'),
            [$this, 'field_show_progress_bar'],
            self::PAGE_SLUG,
            'storyly_section_reading'
        );

        // Section: Archive
        add_settings_section(
            'storyly_section_archive',
            __('Archive & Feed', 'storyly'),
            '__return_false',
            self::PAGE_SLUG
        );

        add_settings_field(
            'archive_slug',
            __('Stories Archive Slug', 'storyly'),
            [$this, 'field_archive_slug'],
            self::PAGE_SLUG,
            'storyly_section_archive'
        );

        add_settings_field(
            'show_author_bio',
            __('Show author bio on single story', 'storyly'),
            [$this, 'field_show_author_bio'],
            self::PAGE_SLUG,
            'storyly_section_archive'
        );

        add_settings_field(
            'show_related',
            __('Show related stories', 'storyly'),
            [$this, 'field_show_related'],
            self::PAGE_SLUG,
            'storyly_section_archive'
        );
    }

    public function field_stories_per_page(): void
    {
        $options = $this->get_options();
?>
        <input
            type="number"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>[stories_per_page]"
            value="<?php echo esc_attr($options['stories_per_page']); ?>"
            min="1"
            max="50"
            class="small-text" />
        <p class="description">
            <?php esc_html_e('Number of stories shown on archive and topic pages.', 'storyly'); ?>
        </p>
    <?php
    }

    public function field_show_reading_time(): void
    {
        $options = $this->get_options();
    ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_reading_time]"
                value="1"
                <?php checked(1, $options['show_reading_time']) ?>>
            <?php esc_html_e('Display estimated reading time on story cards and single pages.', 'storyly'); ?>
        </label>

    <?php
    }

    public function field_show_progress_bar(): void
    {
        $options = $this->get_options();
    ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_progress_bar]"
                value="1"
                <?php checked(1, $options['show_progress_bar']); ?> />
            <?php esc_html_e('Show a reading progress bar at the top of single story pages.', 'storyly'); ?>
        </label>
    <?php
    }

    public function field_archive_slug(): void
    {
        $options = $this->get_options();
    ?>
        <input
            type="text"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>[archive_slug]"
            value="<?php echo esc_attr($options['archive_slug']); ?>"
            class="regular-text" />
        <p class="description">
            <?php esc_html_e('URL slug for the stories archive. Save changes then go to Settings → Permalinks and click Save to flush rewrite rules.', 'storyly'); ?>
        </p>
    <?php
    }

    public function field_show_author_bio(): void
    {
        $options = $this->get_options();
    ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_author_bio]"
                value="1"
                <?php checked(1, $options['show_author_bio']); ?> />
            <?php esc_html_e('Display the author biography box below each story.', 'storyly'); ?>
        </label>
    <?php
    }

    public function field_show_related(): void
    {
        $options = $this->get_options();
    ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_related]"
                value="1"
                <?php checked(1, $options['show_related']); ?> />
            <?php esc_html_e('Show related stories section at the bottom of each story.', 'storyly'); ?>
        </label>
    <?php
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

    ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('Storyly Settings', 'storyly'); ?>
            </h1>

            <?php settings_errors(self::OPTION_GROUP); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Save Settings', 'storyly'));
                ?>
            </form>
        </div>
<?php
    }

    public function sanitize_options(array $input): array
    {
        $clean = self::get_options();

        $clean['stories_per_page'] = isset($input['stories_per_page']) ? min(50, max(1, absint($input['stories_per_page']))) : 10;
        $clean['show_reading_time'] = !empty($input['show_reading_time']) ? 1 : 0;
        $clean['show_progress_bar'] = !empty($input['show_progress_bar']) ? 1 : 0;
        $clean['show_author_bio'] = !empty($input['show_author_bio']) ? 1 : 0;
        $clean['show_related'] = !empty($input['show_related']) ? 1 : 0;

        $clean['archive_slug'] = isset($input['archive_slug']) ? sanitize_title($input['archive_slug']) : 'stories';

        // Flush rewrite rules if archive slug changed.
        if ($clean['archive_slug'] !== $this->get_options()['archive_slug']) {
            flush_rewrite_rules();
        }

        return $clean;
    }

    public static function get_options(): array
    {
        return [
            'stories_per_page' => 10,
            'show_reading_time' => 1,
            'show_progress_bar' => 1,
            'show_author_bio'   => 1,
            'show_related'      => 1,
            'archive_slug'      => 'stories',
        ];
    }

    public function add_settings_link(array $links): array
    {
        $url = admin_url('options-general.php?page=' . self::PAGE_SLUG);
        $link = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Settings', 'storyly')
        );
        array_unshift($links, $link);
        return $links;
    }
}
