<?php
namespace Narrato;

defined( 'ABSPATH' ) || exit;

final class Assets {

    public function register(): void {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_frontend' ] );
        add_action( 'enqueue_block_assets',  [ $this, 'enqueue_editor' ] );
    }

    public function enqueue_frontend(): void {
        if ( ! $this->is_narrato_page() ) {
            return;
        }

        wp_enqueue_style(
            'narrato-frontend',
            NARRATO_URL . 'assets/css/frontend.css',
            [],
            NARRATO_VERSION
        );

        if ( is_singular( 'narrato_story' ) ) {
            wp_enqueue_script(
                'narrato-reading-progress',
                NARRATO_URL . 'assets/js/reading-progress.js',
                [],
                NARRATO_VERSION,
                true
            );
        }
    }

    public function enqueue_editor(): void {
        if ( ! is_admin() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'narrato_story' ) {
            return;
        }

        wp_enqueue_style(
            'narrato-editor',
            NARRATO_URL . 'assets/css/editor.css',
            [ 'wp-edit-blocks' ],
            NARRATO_VERSION
        );
    }

    private function is_narrato_page(): bool {
        return is_singular( 'narrato_story' )
            || is_post_type_archive( 'narrato_story' )
            || is_tax( 'narrato_topic' );
    }
}