<?php

namespace Narrato\Membership;

defined( 'ABSPATH' ) || exit;

final class StripeGateway implements GatewayInterface {

    private const API_BASE = 'https://api.stripe.com/v1';

    public function get_slug(): string {
        return 'stripe';
    }

    public function is_configured(): bool {
        $keys = self::get_keys();
        return ! empty( $keys['secret_key'] ) && ! empty( $keys['publishable_key'] );
    }

    /* ----------------------------------------------------------
       Keys — pulled from options, test/live aware
    ---------------------------------------------------------- */

    public static function get_keys(): array {
        $options  = \Narrato\Admin\MembershipSettings::get_options();
        $is_test  = (bool) $options['test_mode'];

        return [
            'secret_key'      => $is_test ? $options['stripe_test_secret_key']      : $options['stripe_live_secret_key'],
            'publishable_key' => $is_test ? $options['stripe_test_publishable_key'] : $options['stripe_live_publishable_key'],
            'webhook_secret'  => $is_test ? $options['stripe_test_webhook_secret']  : $options['stripe_live_webhook_secret'],
            'price_monthly'   => $options['stripe_price_monthly'],
            'price_yearly'    => $options['stripe_price_yearly'],
        ];
    }

    /* ----------------------------------------------------------
       API request helper
    ---------------------------------------------------------- */

    private function request( string $method, string $endpoint, array $body = [] ): array {
        $keys = self::get_keys();

        $args = [
            'method'  => $method,
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $keys['secret_key'],
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
        ];

        if ( ! empty( $body ) ) {
            $args['body'] = http_build_query( $body );
        }

        $response = wp_remote_request( self::API_BASE . $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return [ 'error' => $data['error']['message'] ?? __( 'Stripe request failed.', 'narrato-for-writers' ) ];
        }

        return $data ?: [];
    }

    /* ----------------------------------------------------------
       Ensure a Stripe Customer exists for this WP user
    ---------------------------------------------------------- */

    private function get_or_create_customer( int $user_id ): ?string {
        $existing = get_user_meta( $user_id, '_narrato_stripe_customer_id', true );
        if ( $existing ) return $existing;

        $user = get_userdata( $user_id );
        if ( ! $user ) return null;

        $result = $this->request( 'POST', '/customers', [
            'email'    => $user->user_email,
            'name'     => $user->display_name,
            'metadata' => [ 'wp_user_id' => $user_id ],
        ] );

        if ( isset( $result['error'] ) || empty( $result['id'] ) ) {
            return null;
        }

        update_user_meta( $user_id, '_narrato_stripe_customer_id', $result['id'] );
        return $result['id'];
    }

    /* ----------------------------------------------------------
       Create checkout session — returns a PaymentIntent / Subscription
       client_secret for Stripe Elements to confirm on the front-end
    ---------------------------------------------------------- */

    public function create_checkout_session( int $user_id, string $plan ): array {
        $keys = self::get_keys();

        $price_id = $plan === 'yearly' ? $keys['price_yearly'] : $keys['price_monthly'];
        if ( ! $price_id ) {
            return [ 'error' => __( 'Stripe price ID not configured for this plan.', 'narrato-for-writers' ) ];
        }

        $customer_id = $this->get_or_create_customer( $user_id );
        if ( ! $customer_id ) {
            return [ 'error' => __( 'Could not create Stripe customer.', 'narrato-for-writers' ) ];
        }

        $result = $this->request( 'POST', '/subscriptions', [
            'customer'                 => $customer_id,
            'items'                    => [ [ 'price' => $price_id ] ],
            'payment_behavior'         => 'default_incomplete',
            'payment_settings'         => [ 'save_default_payment_method' => 'on_subscription' ],
            'expand'                   => [ 'latest_invoice.payment_intent' ],
            'metadata'                 => [ 'wp_user_id' => $user_id, 'plan' => $plan ],
        ] );

        if ( isset( $result['error'] ) ) {
            return $result;
        }

        $client_secret = $result['latest_invoice']['payment_intent']['client_secret'] ?? null;
        if ( ! $client_secret ) {
            return [ 'error' => __( 'Could not initialize payment.', 'narrato-for-writers' ) ];
        }

        return [
            'client_secret'   => $client_secret,
            'publishable_key' => $keys['publishable_key'],
            'subscription_id' => $result['id'],
        ];
    }

    public function cancel_subscription( string $gateway_sub_id ): bool {
        $result = $this->request( 'DELETE', '/subscriptions/' . $gateway_sub_id );
        return ! isset( $result['error'] );
    }

    /* ----------------------------------------------------------
       Webhook handling
    ---------------------------------------------------------- */

    public function handle_webhook( \WP_REST_Request $request ): bool {
        $payload    = $request->get_body();
        $sig_header = $request->get_header( 'stripe-signature' );
        $keys       = self::get_keys();

        if ( ! $this->verify_signature( $payload, $sig_header, $keys['webhook_secret'] ) ) {
            return false;
        }

        $event = json_decode( $payload, true );
        if ( ! $event ) return false;

        switch ( $event['type'] ) {
            case 'invoice.paid':
                $this->handle_invoice_paid( $event['data']['object'] );
                break;

            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $this->handle_subscription_updated( $event['data']['object'] );
                break;

            case 'invoice.payment_failed':
                $this->handle_payment_failed( $event['data']['object'] );
                break;
        }

        return true;
    }

    private function verify_signature( string $payload, ?string $sig_header, string $webhook_secret ): bool {
        if ( ! $sig_header || ! $webhook_secret ) return false;

        $parts = [];
        foreach ( explode( ',', $sig_header ) as $part ) {
            [ $key, $value ] = array_pad( explode( '=', $part, 2 ), 2, '' );
            $parts[ $key ] = $value;
        }

        if ( empty( $parts['t'] ) || empty( $parts['v1'] ) ) return false;

        $signed_payload    = $parts['t'] . '.' . $payload;
        $expected_signature = hash_hmac( 'sha256', $signed_payload, $webhook_secret );

        return hash_equals( $expected_signature, $parts['v1'] );
    }

    private function handle_invoice_paid( array $invoice ): void {
        $sub_id = $invoice['subscription'] ?? null;
        if ( ! $sub_id ) return;

        $sub = $this->request( 'GET', '/subscriptions/' . $sub_id );
        if ( isset( $sub['error'] ) ) return;

        $user_id = (int) ( $sub['metadata']['wp_user_id'] ?? 0 );
        $plan    = $sub['metadata']['plan'] ?? 'monthly';

        if ( ! $user_id ) return;

        Membership::upsert_membership(
            $user_id,
            $plan,
            'stripe',
            $sub_id,
            'active',
            gmdate( 'Y-m-d H:i:s', $sub['current_period_end'] )
        );

        Membership::log_transaction(
            $user_id,
            'stripe',
            $invoice['id'],
            ( $invoice['amount_paid'] ?? 0 ) / 100,
            strtoupper( $invoice['currency'] ?? 'usd' ),
            'succeeded',
            $plan
        );
    }

    private function handle_subscription_updated( array $sub ): void {
        $user_id = Membership::find_user_by_subscription( 'stripe', $sub['id'] );
        if ( ! $user_id ) return;

        $status = $sub['status'] === 'active' ? 'active'
            : ( $sub['status'] === 'canceled' ? 'cancelled' : 'past_due' );

        Membership::set_status( $user_id, $status );
    }

    private function handle_payment_failed( array $invoice ): void {
        $sub_id = $invoice['subscription'] ?? null;
        if ( ! $sub_id ) return;

        $user_id = Membership::find_user_by_subscription( 'stripe', $sub_id );
        if ( $user_id ) {
            Membership::set_status( $user_id, 'past_due' );
        }
    }
}