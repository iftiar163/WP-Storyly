<?php
namespace WPStoryly\CPT;

defined( 'ABSPATH' ) || exit;

final class Story {
    public function register() : void {
        add_action('init', [$this, 'register_post_type']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_post_type(): void {
    $labels = [
        'name'                  => __( 'Stories',                   'wp-storyly' ),
        'singular_name'         => __( 'Story',                     'wp-storyly' ),
        'add_new'               => __( 'New Story',                 'wp-storyly' ),
        'add_new_item'          => __( 'Add New Story',             'wp-storyly' ),
        'edit_item'             => __( 'Edit Story',                'wp-storyly' ),
        'new_item'              => __( 'New Story',                 'wp-storyly' ),
        'view_item'             => __( 'View Story',                'wp-storyly' ),
        'view_items'            => __( 'View Stories',              'wp-storyly' ),
        'search_items'          => __( 'Search Stories',            'wp-storyly' ),
        'not_found'             => __( 'No stories found.',         'wp-storyly' ),
        'not_found_in_trash'    => __( 'No stories found in Trash.','wp-storyly' ),
        'all_items'             => __( 'All Stories',               'wp-storyly' ),
        'menu_name'             => __( 'Stories',                   'wp-storyly' ),
        'name_admin_bar'        => __( 'Story',                     'wp-storyly' ),
    ];

    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_rest'        => true,   // Required for Gutenberg
        'query_var'           => true,
        'rewrite'             => [ 'slug' => 'stories', 'with_front' => false ],
        'capability_type'     => 'post',
        'has_archive'         => 'stories',
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-edit-page',
        'supports'            => [
            'title',
            'editor',
            'author',
            'thumbnail',
            'excerpt',
            'revisions',
            'custom-fields',  // Required for meta fields in REST
        ],
    ];

        register_post_type( 'story', $args );
    }

    public function load_templates( string $template) : string {
        if(is_singular('story')){
            $custom = WP_STORYLY_PATH . 'templates/single-story.php';
            if(file_exists($custom)){
                return $custom;
            }
        }

        if(is_post_type_archive('story')){
            $custom = WP_STORYLY_PATH . 'templates/archive-story.php';
            if(file_exists($custom)){
                return $custom;
            }
        }

        return $template;
    }
}