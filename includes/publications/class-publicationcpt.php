<?php

namespace Narrato\Publications;

defined("ABSPATH") || exit;

final class PublicationCPT
{

    public function register(): void
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_meta']);
        add_filter('template_include', [$this, 'load_templates']);
    }

    public function register_post_type(): void
    {
        $labels = [
            'name'               => __('Publications', 'narrato-for-writers'),
            'singular_name'      => __('Publication', 'narrato-for-writers'),
            'add_new_item'       => __('Add New Publication', 'narrato-for-writers'),
            'edit_item'          => __('Edit Publication', 'narrato-for-writers'),
            'view_item'          => __('View Publication', 'narrato-for-writers'),
            'search_items'       => __('Search Publications', 'narrato-for-writers'),
            'not_found'          => __('No publications found.', 'narrato-for-writers'),
            'all_items'          => __('All Publications', 'narrato-for-writers'),
            'menu_name'          => __('Publications', 'narrato-for-writers'),
        ];

        register_post_type('narrato_publication', [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'publication', 'with_front' => false],
            'has_archive'        => 'publications',
            'capability_type'    => 'post',
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-book-alt',
            'supports'           => ['title', 'editor', 'author', 'custom-fields'],
        ]);
    }

    public function register_meta(): void
    {
        register_post_meta('narrato_publication', '_narrato_pub_description', [
            'type'              => 'string',
            'single'            => true,
            'default'           => '',
            'sanitize_callback' => 'sanitize_textarea_field',
            'auth_callback'     => fn() => current_user_can('edit_posts'),
            'show_in_rest'      => true,
        ]);

        register_post_meta('narrato_publication', '_narrato_pub_editors', [
            'type'              => 'array',
            'single'            => true,
            'default'           => [],
            'show_in_rest'      => [
                'schema' => [
                    'type'  => 'array',
                    'items' => ['type' => 'integer'],
                ],
            ],
            'auth_callback'     => fn() => current_user_can('edit_posts'),
        ]);

        register_post_meta('narrato_publication', '_narrato_pub_open_submissions', [
            'type'              => 'boolean',
            'single'            => true,
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback'     => fn() => current_user_can('edit_posts'),
            'show_in_rest'      => true,
        ]);
    }

    public function load_templates(string $template): string
    {
        if (is_singular('narrato_publication')) {
            $custom = NARRATO_PATH . 'templates/single-publication.php';
            if (file_exists($custom)) return $custom;
        }

        if (is_post_type_archive('narrato_publication')) {
            $custom = NARRATO_PATH . 'templates/archive-publication.php';
            if (file_exists($custom)) return $custom;
        }

        return $template;
    }
}
