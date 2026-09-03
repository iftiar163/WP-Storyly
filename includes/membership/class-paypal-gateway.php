<?php

namespace Narrato\Membership;

defined( 'ABSPATH' ) || exit;

final class PaypalGateway implements GatewayInterface {

    public function get_slug(): string {
        return 'paypal';
    }

    public function is_configured(): bool {
        $keys = self::get_keys();
        return ! empty( $keys['client_id'] ) && ! empty( $keys['client_secret'] );
    }

    /* ----------------------------------------------------------
       Keys — test/live aware
    ---------------------------------------------------------- */

    public static function get_keys(): array {
        $options = \Narrato\Admin\MembershipSettings::get_options();
        $is_test = (bool) $options['test_mode'];

        return [
            'client_id'     => $is_test ? $options['paypal_test_client_id']     : $options['paypal_live_client_id'],
            'client_secret' => $is_test ? $options['paypal_test_client_secret'] : $options['paypal_live_client_secret'],
            'plan_monthly'  => $options['paypal_plan_monthly'],
            'plan_yearly'   => $options['paypal_plan_yearly'],
            'webhook_id'    => $is_test ? $options['paypal_test_webhook_id']    : $options['paypal_live_webhook_id'],
            'is_test'       => $is_test,
        ];
    }

    private static function api_base(): string {
        $keys = self::get_keys();
        return $keys['is_test']
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /* ----------------------------------------------------------
       OAuth2 access token (cached transient, ~9hr expiry)
    ---------------------------------------------------------- */

    private function get_access_token(): ?string {
        $cached = get_transient( 'narrato_paypal_token' );
        if ( $cached ) return $cached;

        $keys = self::get_keys();

        $response = wp_remote_post( self::api_base() . '/v1/oauth2/token', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( $keys['client_id'] . ':' . $keys['client_secret'] ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => [ 'grant_type' => 'client_credentials' ],
        ] );

        if ( is_wp_error( $response ) ) return null;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['access_token'] ) ) return null;

        set_transient( 'narrato_paypal_token', $data['access_token'], (int) ( $data['expires_in'] ?? 32400 ) - 60 );

        return $data['access_token'];
    }

    /* ----------------------------------------------------------
       API request helper
    ---------------------------------------------------------- */

    private function request( string $method, string $endpoint, array $body = [] ): array {
        $token = $this->get_access_token();
        if ( ! $token ) {
            return [ 'error' => __( 'Could not authenticate with PayPal.', 'narrato-for-writers' ) ];
        }

        $args = [
            'method'  => $method,
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ];

        if ( ! empty( $body ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( self::api_base() . $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return [ 'error' => $data['message'] ?? __( 'PayPal request failed.', 'narrato-for-writers' ) ];
        }

        return $data ?: [];
    }

    /* ----------------------------------------------------------
       Create checkout session
       PayPal Smart Buttons create the subscription client-side via
       their JS SDK using the plan ID — we just hand back the plan ID
       and client ID here. The actual subscription is created by
       PayPal's SDK in the browser, then confirmed via webhook.
    ---------------------------------------------------------- */

    public function create_checkout_session( int $user_id, string $plan ): array {
        $keys    = self::get_keys();
        $plan_id = $plan === 'yearly' ? $keys['plan_yearly'] : $keys['plan_monthly'];

        if ( ! $plan_id ) {
            return [ 'error' => __( 'PayPal plan ID not configured for this plan.', 'narrato-for-writers' ) ];
        }

        // Store a short-lived mapping so the webhook can identify the user
        // once PayPal confirms the subscription (custom_id isn't always
        // reliable across all webhook events, so we double-track it here).
        set_transient( 'narrato_paypal_pending_' . $user_id, $plan, 15 * MINUTE_IN_SECONDS );

        return [
            'client_id' => $keys['client_id'],
            'plan_id'   => $plan_id,
            'user_id'   => $user_id,
            'plan'      => $plan,
        ];
    }

    public function cancel_subscription( string $gateway_sub_id ): bool {
        $result = $this->request( 'POST', '/v1/billing/subscriptions/' . $gateway_sub_id . '/cancel', [
            'reason' => 'Cancelled by user',
        ] );

        return ! isset( $result['error'] );
    }

    /* ----------------------------------------------------------
       Called from front-end JS after PayPal SDK confirms subscription
       creation client-side — we verify server-side before trusting it.
    ---------------------------------------------------------- */

    public function verify_and_activate_subscription( int $user_id, string $subscription_id, string $plan ): array {
        $sub = $this->request( 'GET', '/v1/billing/subscriptions/' . $subscription_id );

        if ( isset( $sub['error'] ) ) {
            return $sub;
        }

        if ( ( $sub['status'] ?? '' ) !== 'ACTIVE' && ( $sub['status'] ?? '' ) !== 'APPROVED' ) {
            return [ 'error' => __( 'Subscription is not active.', 'narrato-for-writers' ) ];
        }

        $period_end = $sub['billing_info']['next_billing_time'] ?? null;

        Membership::upsert_membership(
            $user_id,
            $plan,
            'paypal',
            $subscription_id,
            'active',
            $period_end ? gmdate( 'Y-m-d H:i:s', strtotime( $period_end ) ) : null
        );

        delete_transient( 'narrato_paypal_pending_' . $user_id );

        return [ 'success' => true ];
    }

    /* ----------------------------------------------------------
       Webhook handling
    ---------------------------------------------------------- */

    public function handle_webhook( \WP_REST_Request $request ): bool {
        $payload = $request->get_body();
        $event   = json_decode( $payload, true );

        if ( ! $event || ! $this->verify_webhook( $request ) ) {
            return false;
        }

        switch ( $event['event_type'] ) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $this->handle_subscription_activated( $event['resource'] );
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                $this->handle_subscription_ended( $event['resource'] );
                break;

            case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
                $this->handle_payment_failed( $event['resource'] );
                break;

            case 'PAYMENT.SALE.COMPLETED':
                $this->handle_payment_completed( $event['resource'] );
                break;
        }

        return true;
    }

    private function verify_webhook( \WP_REST_Request $request ): bool {
        $keys = self::get_keys();
        if ( ! $keys['webhook_id'] ) return false;

        $body = [
            'transmission_id'   => $request->get_header( 'paypal-transmission-id' ),
            'transmission_time' => $request->get_header( 'paypal-transmission-time' ),
            'cert_url'          => $request->get_header( 'paypal-cert-url' ),
            'auth_algo'         => $request->get_header( 'paypal-auth-algo' ),
            'transmission_sig'  => $request->get_header( 'paypal-transmission-sig' ),
            'webhook_id'        => $keys['webhook_id'],
            'webhook_event'     => json_decode( $request->get_body(), true ),
        ];

        $result = $this->request( 'POST', '/v1/notifications/verify-webhook-signature', $body );

        return ( $result['verification_status'] ?? '' ) === 'SUCCESS';
    }

    private function handle_subscription_activated( array $resource ): void {
        $sub_id  = $resource['id'] ?? '';
        $plan_id = $resource['plan_id'] ?? '';
        $keys    = self::get_keys();

        $plan = $plan_id === $keys['plan_yearly'] ? 'yearly' : 'monthly';

        // Custom ID carries the WP user ID if we set it during creation
        $user_id = (int) ( $resource['custom_id'] ?? 0 );
        if ( ! $user_id ) return;

        $period_end = $resource['billing_info']['next_billing_time'] ?? null;

        Membership::upsert_membership(
            $user_id,
            $plan,
            'paypal',
            $sub_id,
            'active',
            $period_end ? gmdate( 'Y-m-d H:i:s', strtotime( $period_end ) ) : null
        );
    }

    private function handle_subscription_ended( array $resource ): void {
        $sub_id  = $resource['id'] ?? '';
        $user_id = Membership::find_user_by_subscription( 'paypal', $sub_id );
        if ( $user_id ) {
            Membership::set_status( $user_id, 'cancelled' );
        }
    }

    private function handle_payment_failed( array $resource ): void {
        $sub_id  = $resource['id'] ?? '';
        $user_id = Membership::find_user_by_subscription( 'paypal', $sub_id );
        if ( $user_id ) {
            Membership::set_status( $user_id, 'past_due' );
        }
    }

    private function handle_payment_completed( array $resource ): void {
        $sub_id = $resource['billing_agreement_id'] ?? null;
        if ( ! $sub_id ) return;

        $user_id = Membership::find_user_by_subscription( 'paypal', $sub_id );
        if ( ! $user_id ) return;

        Membership::log_transaction(
            $user_id,
            'paypal',
            $resource['id'] ?? '',
            (float) ( $resource['amount']['total'] ?? 0 ),
            $resource['amount']['currency'] ?? 'USD',
            'succeeded',
            'monthly' // best-effort; exact plan not always in this event
        );
    }
}