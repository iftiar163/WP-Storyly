<?php

namespace Narrato\Membership;

defined('ABSPATH') || exit;

interface GatewayInterface
{
    /**
     * Returns the gateway's machine name — 'stripe' or 'paypal'.
     */
    public function get_slug(): string;

    /**
     * Whether this gateway is configured (has API keys set) and usable.
     */
    public function is_configured(): bool;

    /**
     * Create a subscription/checkout session for the given user + plan.
     * Returns an array of data the front-end needs to render its widget
     * (e.g. Stripe client secret, or PayPal plan ID).
     */
    public function create_checkout_session(int $user_id, string $plan): array;

    /**
     * Cancel an active subscription at the gateway.
     */
    public function cancel_subscription(string $gateway_sub_id): bool;

    /**
     * Verify and handle an incoming webhook request.
     * Returns true if handled successfully.
     */
    public function handle_webhook(\WP_REST_Request $request): bool;
}
