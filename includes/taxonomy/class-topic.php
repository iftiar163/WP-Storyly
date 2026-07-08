<?php
namespace Narrato\Taxonomy;

defined( 'ABSPATH' ) || exit;

final class Topic {
    public function register() : void {
        add_action('init', [$this, 'register_taxonomy']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_taxonomy(): void {
        $labels = [
            'name'                       => __( 'Topics',                           'narrato-for-writers' ),
            'singular_name'              => __( 'Topic',                            'narrato-for-writers' ),
            'search_items'               => __( 'Search Topics',                    'narrato-for-writers' ),
            'popular_items'              => __( 'Popular Topics',                   'narrato-for-writers' ),
            'all_items'                  => __( 'All Topics',                       'narrato-for-writers' ),
            'edit_item'                  => __( 'Edit Topic',                       'narrato-for-writers' ),
            'update_item'                => __( 'Update Topic',                     'narrato-for-writers' ),
            'add_new_item'               => __( 'Add New Topic',                    'narrato-for-writers' ),
            'new_item_name'              => __( 'New Topic Name',                   'narrato-for-writers' ),
            'separate_items_with_commas' => __( 'Separate topics with commas',      'narrato-for-writers' ),
            'add_or_remove_items'        => __( 'Add or remove topics',             'narrato-for-writers' ),
            'choose_from_most_used'      => __( 'Choose from the most used topics', 'narrato-for-writers' ),
            'not_found'                  => __( 'No topics found.',                 'narrato-for-writers' ),
            'menu_name'                  => __( 'Topics',                           'narrato-for-writers' ),
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

        register_taxonomy( 'narrato_topic', [ 'narrato_story' ], $args );
    }

    public function load_template( string $template ) : string {
        if ( is_tax( 'narrato_topic' ) ) {
            $custom = NARRATO_PATH . 'templates/taxonomy-topic.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }
}