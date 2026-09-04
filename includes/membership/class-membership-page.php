<?php

namespace Narrato\Membership;

defined( 'ABSPATH' ) || exit;

final class MembershipPage {

    public function register(): void {
        add_action( 'init', [$this, 'register_rewrite'] );
        add_filter( 'query_vars', [$this, 'add_query_var'] );
        add_filter( 'template_include', [$this, 'template_include'] );
    }

    public function register_rewrite(): void {
        add_rewrite_rule(
            '^membership/?$',
            'index.php?narrato_membership=1',
            'top'
        );
    }

    public function add_query_var( array $vars ): array {
        $vars[] = 'narrato_membership';
        return $vars;
    }

    public function template_include( string $template ): string {
        if( get_query_var('narrato_membership') ) {
            $custom = NARRATO_PATH . 'templates/membership-checkout.php';
            if( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }
}