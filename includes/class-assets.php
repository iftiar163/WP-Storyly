<?php
namespace Storyly;

defined( 'ABSPATH' ) || exit;

final class Assets {

    public function register(): void {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_frontend' ] );
        add_action( 'enqueue_block_assets',  [ $this, 'enqueue_editor' ] );
    }

    public function enqueue_frontend(): void {
        if ( ! $this->is_storyly_page() ) {
            return;
        }

        wp_enqueue_style(
            'storyly-frontend',
            STORYLY_URL . 'assets/css/frontend.css',
            [],
            STORYLY_VERSION
        );
    }

    public function enqueue_editor(): void {
        if ( ! is_admin() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'story' ) {
            return;
        }

        wp_enqueue_style(
            'storyly-editor',
            STORYLY_URL . 'assets/css/editor.css',
            [ 'wp-edit-blocks' ],
            STORYLY_VERSION
        );
    }

    private function is_storyly_page(): bool {
        return is_singular( 'story' )
            || is_post_type_archive( 'story' )
            || is_tax( 'storyly_topic' );
    }
}