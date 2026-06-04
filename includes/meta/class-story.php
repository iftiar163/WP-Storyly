<?php
namespace WPStoryly\Meta;

defined( 'ABSPATH' ) || exit;

final class Story {
    public function register() : void {
        add_action('init', [$this, 'register_meta_fields']);
        add_action('save_post_story', [$this, 'save_reading_time'], 10, 2);
        add_action('add_meta_boxes_story', [$this, 'register_meta_boxes']);
        add_action('save_post_story', [$this, 'save_subtitle_meta'], 10, 2);
    }

    public function register_meta_fields(): void {
        // Subtitle
        register_post_meta( 'story', '_storyly_subtitle', [
            'type'              => 'string',
            'description'       => __( 'Story subtitle', 'wp-storyly' ),
            'single'            => true,
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
            'show_in_rest'      => true,
        ] );

        // Reading time (minutes) — auto-calculated
        register_post_meta( 'story', '_storyly_reading_time', [
            'type'              => 'integer',
            'description'       => __( 'Estimated reading time in minutes', 'wp-storyly' ),
            'single'            => true,
            'default'           => 1,
            'sanitize_callback' => 'absint',
            'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
            'show_in_rest'      => true,
        ] );
    }

    public function register_meta_boxes(): void {
        add_meta_box(
            'storyly_subtitle',
            __('Story Subtitle', 'wp-storyly'),
            [$this, 'render_subtitle_field'],
            'story',
            'normal',
            'high'
        );
    }

    public function render_subtitle_field( \WP_Post $post ): void {
        $subtitle = get_post_meta( $post->ID, '_storyly_subtitle', true );
        wp_nonce_field( 'storyly_subtitle_nonce', 'storyly_subtitle_nonce' );
        ?>
        <input 
            type="text" 
            id="storyly_subtitle" 
            name="storyly_subtitle" 
            value="<?php echo esc_attr( $subtitle ); ?>" 
            placeholder="<?php esc_attr_e( 'Enter story subtitle', 'wp-storyly' ); ?>"
            style="width: 100%; padding: 8px; font-size: 14px;"
        />
        <?php
    }

    public function save_subtitle_meta( int $post_id, \WP_Post $post ): void {
        // Skip autosaves and revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['storyly_subtitle_nonce'] ) || ! wp_verify_nonce( $_POST['storyly_subtitle_nonce'], 'storyly_subtitle_nonce' ) ) {
            return;
        }

        // Check user capability
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save subtitle
        if ( isset( $_POST['storyly_subtitle'] ) ) {
            $subtitle = sanitize_text_field( $_POST['storyly_subtitle'] );
            update_post_meta( $post_id, '_storyly_subtitle', $subtitle );
        } else {
            delete_post_meta( $post_id, '_storyly_subtitle' );
        }
    }

    public function save_reading_time( int $post_id, \WP_Post $post ): void {
        // Skip autosaves and revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $content      = wp_strip_all_tags( $post->post_content );
        $word_count   = str_word_count( $content );
        $reading_time = (int) ceil( $word_count / 200 ); // 200 wpm average
        $reading_time = max( 1, $reading_time );          // minimum 1 min

        update_post_meta( $post_id, '_storyly_reading_time', $reading_time );
    }
}