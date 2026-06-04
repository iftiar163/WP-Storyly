<?php
namespace WPStoryly\Admin;

defined( 'ABSPATH' ) || exit;

final class Settings {
    private const OPTION_GROUP = 'wp_storyly_settings';
    private const OPTION_NAME = 'wp_storyly_options';
    private const PAGE_SLUG = 'wp-storyly-settings';

    public function register() : void {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_menu_page() : void {
        add_menu_page(
            __('WP Storyly Settings', 'wp-storyly'),
            __('Storyly', 'wp-storyly'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function register_settings() : void {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default'           => $this->defaults(),
            ]
        );

        add_settings_section(
            'storyly_section_reading',
            __('Reading Experience', 'wp-storyly'),
            '__return_false',
            self::PAGE_SLUG,
        );

        add_settings_field(
            'stories_per_page',
            __('Stories Per Page', 'wp-storyly'),
            [$this, 'field_stories_per_page'],
            self::PAGE_SLUG,
            'storyly_section_reading'
        );

        add_settings_field(
            'show_reading_time',
            __('Show Reading Time', 'wp-storyly'),
            [$this, 'field_show_reading_time'],
            self::PAGE_SLUG,
            'storyly_section_reading'
        );

        add_settings_field(
            'show_progress_bar',
            __('Show reading progress bar', 'wp-storyly'),
            [$this, 'field_show_progress_bar'],
            self::PAGE_SLUG,
            'storyly_section_reading'
        );

        // Section: Archive
        add_settings_section(
            'storyly_section_archive',
            __( 'Archive & Feed', 'wp-storyly' ),
            '__return_false',
            self::PAGE_SLUG
        );

        add_settings_field(
            'archive_slug',
            __('Stories Archive Slug', 'wp-storyly'),
            [$this, 'field_archive_slug'],
            self::PAGE_SLUG,
            'storyly_section_archive'
        );

        add_settings_field(
            'show_author_bio',
            __('Show author bio on single story', 'wp-storyly'),
            [$this, 'field_show_author_bio'],
            self::PAGE_SLUG,
            'storyly_section_archive'
        );

        add_settings_field(
            'show_related',
            __('Show related stories', 'wp-storyly'),
            [$this, 'field_show_related'],
            self::PAGE_SLUG,
            'storyly_section_archive'
        );
    }

    public function field_stories_per_page() : void {
        $options = get_option();
        ?>
            <input
                type="number"
                name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stories_per_page]"
                value="<?php echo esc_attr( $options['stories_per_page'] ); ?>"
                min="1"
                max="50"
                class="small-text"
            />
            <p class="description">
                <?php esc_html_e( 'Number of stories shown on archive and topic pages.', 'wp-storyly' ); ?>
            </p>
        <?php
    }

    public function field_show_reading_time() : void{
        $options = get_option();
        ?>
            <label>
                <input 
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME) ?>['show_reading_time']"
                value="1"
                <?php checked(1, $options['show_reading_time']) ?>
                >
                <?php esc_html_e( 'Display estimated reading time on story cards and single pages.', 'wp-storyly' ); ?>
            </label>

        <?php
    }
}