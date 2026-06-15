<?php
namespace Narrato\CPT;

defined( 'ABSPATH' ) || exit;

final class Story {
    public function register() : void {
        add_action('init', [$this, 'register_post_type']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_post_type(): void {
    $labels = [
        'name'                  => __( 'Stories',                   'narrato-for-writers' ),
        'singular_name'         => __( 'Story',                     'narrato-for-writers' ),
        'add_new'               => __( 'New Story',                 'narrato-for-writers' ),
        'add_new_item'          => __( 'Add New Story',             'narrato-for-writers' ),
        'edit_item'             => __( 'Edit Story',                'narrato-for-writers' ),
        'new_item'              => __( 'New Story',                 'narrato-for-writers' ),
        'view_item'             => __( 'View Story',                'narrato-for-writers' ),
        'view_items'            => __( 'View Stories',              'narrato-for-writers' ),
        'search_items'          => __( 'Search Stories',            'narrato-for-writers' ),
        'not_found'             => __( 'No stories found.',         'narrato-for-writers' ),
        'not_found_in_trash'    => __( 'No stories found in Trash.','narrato-for-writers' ),
        'all_items'             => __( 'All Stories',               'narrato-for-writers' ),
        'menu_name'             => __( 'Stories',                   'narrato-for-writers' ),
        'name_admin_bar'        => __( 'Story',                     'narrato-for-writers' ),
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

    public function load_template( string $template) : string {
        if(is_singular('story')){
            $custom = NARRATO_PATH . 'templates/single-story.php';
            if(file_exists($custom)){
                return $custom;
            }
        }

        if(is_post_type_archive('story')){
            $custom = NARRATO_PATH . 'templates/archive-story.php';
            if(file_exists($custom)){
                return $custom;
            }
        }

        return $template;
    }
}