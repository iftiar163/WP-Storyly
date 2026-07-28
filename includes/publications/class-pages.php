<?php

namespace Narrato\Publications;

defined('ABSPATH') || exit;

final class Pages
{

    public function register(): void
    {
        add_action('init', [$this, 'register_rewrites']);
        add_action('query_vars', [$this, 'add_query_vars']);
        add_action('template_include', [$this, 'load_template'], 10);
    }

    public function register_rewrites(): void
    {
        add_rewrite_rule(
            '^my-publications/?$',
            'index.php?narrato_my_publications=1',
            'top'
        );

        add_rewrite_rule(
            '^publication-reviews/?$',
            'index.php?narrato_publication_reviews=1',
            'top'
        );
    }

    public function add_query_vars(array $vars): array
    {
        $vars[] = 'narrato_my_publications';
        $vars[] = 'narrato_publication_reviews';

        return $vars;
    }

    public function load_template(string $template): string
    {
        if (get_query_var('narrato_my_publications')) {
            $custom = NARRATO_PATH . 'templates/publication-dashboard.php';
            if (file_exists($custom)) return $custom;
        }

        if (get_query_var('narrato_publication_reviews')) {
            $custom = NARRATO_PATH . 'templates/publication-reviews.php';
            if (file_exists($custom)) return $custom;
        }

        return $template;
    }
}
