<?php
namespace WPStoryly;

defined( 'ABSPATH' ) || exit;

final class Plugin {
    public function init() : void {
        // Initialize plugin components here
        $this->load_textdomain();
        $this->register_services();
    }

    private function load_textdomain() : void {
        load_plugin_textdomain(
            'wp-storyly',
            false,
            dirname( WP_STORYLY_BASENAME ) . '/languages/'
        );
    }

    private function register_services() : void {
        (new CPT\Story())->register();
        (new Taxonomy\Topic())->register();
        (new Meta\Story())->register();
        (new Assets())->register();
        (new Admin\Settings())->register();
    }
}