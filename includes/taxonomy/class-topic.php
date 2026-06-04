<?php
namespace WPStoryly\Taxonomy;

defined( 'ABSPATH' ) || exit;

final class Topic {
    public function register() : void {
        add_action('init', [$this, 'register_taxonomy']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_taxonomy(): void {
        $labels = [
            'name'                       => __( 'Topics',                           'wp-storyly' ),
            'singular_name'              => __( 'Topic',                            'wp-storyly' ),
            'search_items'               => __( 'Search Topics',                    'wp-storyly' ),
            'popular_items'              => __( 'Popular Topics',                   'wp-storyly' ),
            'all_items'                  => __( 'All Topics',                       'wp-storyly' ),
            'edit_item'                  => __( 'Edit Topic',                       'wp-storyly' ),
            'update_item'                => __( 'Update Topic',                     'wp-storyly' ),
            'add_new_item'               => __( 'Add New Topic',                    'wp-storyly' ),
            'new_item_name'              => __( 'New Topic Name',                   'wp-storyly' ),
            'separate_items_with_commas' => __( 'Separate topics with commas',      'wp-storyly' ),
            'add_or_remove_items'        => __( 'Add or remove topics',             'wp-storyly' ),
            'choose_from_most_used'      => __( 'Choose from the most used topics', 'wp-storyly' ),
            'not_found'                  => __( 'No topics found.',                 'wp-storyly' ),
            'menu_name'                  => __( 'Topics',                           'wp-storyly' ),
        ];

        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,  // Required for Gutenberg block editor
            'show_tagcloud'     => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'topic', 'with_front' => false ],
        ];

        register_taxonomy( 'storyly_topic', [ 'story' ], $args );
    }

    public function load_template( string $template ) : string {
        if ( is_tax( 'storyly_topic' ) ) {
            $custom = WP_STORYLY_PATH . 'templates/taxonomy-topic.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }
}