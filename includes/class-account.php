<?php

namespace Narrato;

defined('ABSPATH') || exit;

final class Account
{

    public function register(): void
    {
        add_action('init', [$this, 'register_rewrite']);
        add_filter('query_vars', [$this, 'add_query_var']);
        add_filter('template_include', [$this, 'load_template'], 99);
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_rewrite(): void
    {
        add_rewrite_rule(
            '^narrato-profile/?$',
            'index.php?narrato_account=1',
            'top'
        );
    }

    public function add_query_var(array $vars): array
    {
        $vars[] = 'narrato_account';
        return $vars;
    }

    public function load_template(string $template): string
    {
        if (get_query_var('narrato_account')) {
            $custom = NARRATO_PATH . 'templates/account-dashboard.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }

        return $template;
    }

    public function register_routes(): void
    {
        register_rest_route('narrato/v1', '/account/profile', [
            'methods'             => 'POST',
            'callback'            => [$this, 'update_profile'],
            'permission_callback' => fn() => is_user_logged_in(),
            'args'                => [
                'display_name' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'bio' => [
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);
    }

    public function update_profile(\WP_REST_Request $request): \WP_REST_Response
    {
        $user_id = get_current_user_id();
        $display_name = $request->get_param('display_name');
        $bio = $request->get_param('bio');

        if ($display_name) {
            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $display_name,
            ]);
        }

        update_user_meta($user_id, 'description', $bio ?? '');

        return new \WP_REST_Response(['success' => true], 200);
    }

    public static function get_url(): string
    {
        return home_url('/narrato-profile/');
    }
}
