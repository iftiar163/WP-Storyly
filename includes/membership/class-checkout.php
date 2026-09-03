<?php

namespace Narrato\Membership;

defined( 'ABSPATH' ) || exit;

final class Checkout {

    private const REST_NS = 'narrato/v1';

    public function register(): void {
        add_action( 'rest_api_init', [$this, 'register_routes'] );
    }

    public function register_routes(): void {
        // POST /narrato/v1/membership/checkout
        register_rest_route( self::REST_NS, '/membership/checkout', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'start_checkout' ],
            'permission_callback' => fn() => is_user_logged_in(),
            'args'                => [
                'gateway' => [
                    'required'          => true,
                    'validate_callback' => fn( $v ) => in_array( $v, [ 'stripe', 'paypal' ], true ),
                ],
                'plan' => [
                    'required'          => true,
                    'validate_callback' => fn( $v ) => in_array( $v, [ 'monthly', 'yearly' ], true ),
                ],
            ],
        ] );

        // POST /narrato/v1/membership/paypal/confirm — client confirms after PayPal SDK approval
        register_rest_route( self::REST_NS, '/membership/paypal/confirm', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'confirm_paypal' ],
            'permission_callback' => fn() => is_user_logged_in(),
            'args'                => [
                'subscription_id' => [ 'required' => true ],
                 'plan'            => [
                    'required'          => true,
                    'validate_callback' => fn( $v ) => in_array( $v, [ 'monthly', 'yearly' ], true ),
                ],
            ],
        ] );

        // GET /narrato/v1/membership/status
        register_rest_route( self::REST_NS, '/membership/status', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_status' ],
            'permission_callback' => fn() => is_user_logged_in(),
        ] );

        // POST /narrato/v1/membership/cancel
        register_rest_route( self::REST_NS, '/membership/cancel', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'cancel_membership' ],
            'permission_callback' => fn() => is_user_logged_in(),
        ] );

        // Webhooks — no auth (verified via signature inside each gateway)
        register_rest_route( self::REST_NS, '/webhook/stripe', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_stripe_webhook' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::REST_NS, '/webhook/paypal', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_paypal_webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    private function get_gateway( string $slug ): ?GatewayInterface {
        return match ( $slug ) {
            'stripe' => new StripeGateway(),
            'paypal' => new PaypalGateway(),
            default  => null,
        };
    }

    // Start checkout
    public function start_checkout( \WP_REST_Request $request ): \WP_REST_Response {
        $gateway_slug = $request->get_param( 'gateway' );
        $plan         = $request->get_param( 'plan' );
        $user_id      = get_current_user_id();

        if( Membership::is_member( $user_id ) ) {
            return new \WP_REST_Response( [ 'error' => __( 'You are already a member.', 'narrato-for-writers' ) ], 409 );
        }

        $gateway = $this->get_gateway( $gateway_slug );
        if( ! $gateway || ! $gateway->is_configured() ){
            return new \WP_REST_Response( [ 'error' => __( 'This payment method is not available right now.', 'narrato-for-writers' ) ], 503 );
        }

        $result = $gateway->create_checkout_session( $user_id, $plan );

        if( isset( $result['error'] ) ) {
            return new \WP_REST_Response( $result, 400 );
        }

        return new \WP_REST_Response( $result, 200 );
    }

    // PayPal client-side confirmation
    public function confirm_paypal( \WP_REST_Request $request ): \WP_REST_Response {

        $user_id = get_current_user_id();
        $sub_id = sanitize_text_field( $request->get_param( 'subscription_id' ) );
        $plan = sanitize_text_field( $request->get_param( 'plan' ) );

        $gateway = new PaypalGateway();
        $result = $gateway->verify_and_activate_subscription( $user_id, $sub_id, $plan );

        if( isset( $result['error'] ) ) {
            return new \WP_REST_Response( $result, 400 );
        }
        return new \WP_REST_Response( $result, 200 );
    }

    // Status + cancel
    public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        $row     = Membership::get_membership_row( $user_id );

        return new \WP_REST_Response( [
            'is_member' => Membership::is_member( $user_id ),
            'plan'      => $row['plan'] ?? null,
            'gateway'   => $row['gateway'] ?? null,
            'status'    => $row['status'] ?? null,
            'renews_at' => $row['current_period_end'] ?? null,
        ], 200 );
    }

    public function cancel_membership( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        $row = Membership::get_membership_row( $user_id );

        if( ! $row ) {
            return new \WP_REST_Response( [ 'error' => __( 'You are not a member.', 'narrato-for-writers' ) ], 409 );
        }

        $gateway = $this->get_gateway( $row['gateway'] );
        if( ! $gateway ) {
            return new \WP_REST_Response( [ 'error' => __( 'Your payment method is not available right now.', 'narrato-for-writers' ) ], 503 );
        }

        $cancelled = $gateway->cancel_subscription( $row['gateway_sub_id'] );

        if( $cancelled ) {
            Membership::set_status( $user_id, 'cancelled' );
            return new \WP_REST_Response( [ 'success' => true ], 200 );
        }

        return new \WP_REST_Response( [ 'error' => __( 'Could not cancel your subscription.', 'narrato-for-writers' ) ], 500 );
    }

    // Webhooks
    public function handle_stripe_webhook( \WP_REST_Request $request ): \WP_REST_Response {
        $gateway = new StripeGateway();
        $handled = $gateway->handle_webhook( $request );

        return new \WP_REST_Response( [ 'received' => $handled ], $handled ? 200 : 400 );
    } 

    public function handle_paypal_webhook( \WP_REST_Request $request ): \WP_REST_Response {
        $gateway = new PaypalGateway();
        $handled = $gateway->handle_webhook( $request );

        return new \WP_REST_Response( [ 'received' => $handled ], $handled ? 200 : 400 );
    }
}