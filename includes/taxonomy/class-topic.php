<?php
namespace Storyly\Taxonomy;

defined( 'ABSPATH' ) || exit;

final class Topic {
    public function register() : void {
        add_action('init', [$this, 'register_taxonomy']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_taxonomy(): void {
        $labels = [
            'name'                       => __( 'Topics',                           'storyly' ),
            'singular_name'              => __( 'Topic',                            'storyly' ),
            'search_items'               => __( 'Search Topics',                    'storyly' ),
            'popular_items'              => __( 'Popular Topics',                   'storyly' ),
            'all_items'                  => __( 'All Topics',                       'storyly' ),
            'edit_item'                  => __( 'Edit Topic',                       'storyly' ),
            'update_item'                => __( 'Update Topic',                     'storyly' ),
            'add_new_item'               => __( 'Add New Topic',                    'storyly' ),
            'new_item_name'              => __( 'New Topic Name',                   'storyly' ),
            'separate_items_with_commas' => __( 'Separate topics with commas',      'storyly' ),
            'add_or_remove_items'        => __( 'Add or remove topics',             'storyly' ),
            'choose_from_most_used'      => __( 'Choose from the most used topics', 'storyly' ),
            'not_found'                  => __( 'No topics found.',                 'storyly' ),
            'menu_name'                  => __( 'Topics',                           'storyly' ),
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
            $custom = STORYLY_PATH . 'templates/taxonomy-topic.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }
}