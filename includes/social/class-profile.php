<?php

namespace Narrato\Social;

defined('ABSPATH') || exit;

final class Profile
{

    public function register(): void
    {
        add_action('init',             [$this, 'register_rewrites']);
        add_filter('query_vars',       [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_rewrites(): void
    {
        add_rewrite_rule(
            '^profile/([^/]+)/?$',
            'index.php?narrato_profile=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^following/?$',
            'index.php?narrato_following=1',
            'top'
        );
    }

    public function add_query_vars(array $vars): array
    {
        $vars[] = 'narrato_profile';
        $vars[] = 'narrato_following';
        return $vars;
    }

    public function load_template(string $template): string
    {
        if (get_query_var('narrato_profile')) {
            $custom = NARRATO_PATH . 'templates/profile.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }

        if (get_query_var('narrato_following')) {
            $custom = NARRATO_PATH . 'templates/following-feed.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }

        return $template;
    }

    public static function get_url(\WP_User $user): string
    {
        return home_url('/profile/' . $user->user_nicename . '/');
    }
}
