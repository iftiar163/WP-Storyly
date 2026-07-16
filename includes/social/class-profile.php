<?php

namespace Narrato\Social;

defined('ABSPATH') || exit;

final class Profile
{

    public function register(): void
    {
        add_action('init', [$this, 'register_rewrite']);
        add_filter('query_vars', [$this, 'add_query_var']);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_rewrite(): void
    {
        add_rewrite_rule(
            '^profile/([^/]+)/?$',
            'index.php?narrato_profile=$matches[1]',
            'top'
        );
    }

    public function add_query_var(array $vars): array
    {
        $vars[] = 'narrato_profile';
        return $vars;
    }

    public function load_template(string $template): string
    {
        $username = get_query_var('narrato_profile');

        if ($username) {
            $custom = NARRATO_PATH . 'templates/profile.php';
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
