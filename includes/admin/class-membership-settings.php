<?php

namespace Narrato\Admin;

defined('ABSPATH') || exit;

final class MembershipSettings {

    private const OPTION_GROUP = 'narrato_membership_settings';
    private const OPTION_NAME  = 'narrato_membership_options';
    private const PAGE_SLUG = 'narrato-membership-settings';

    public function register(): void {
        add_action( 'admin_menu', [$this, 'add_menu_page'] );
        add_action( 'admin_init', [$this, 'register_settings'] );
    }

    public function add_menu_page(): void {
        add_submenu_page(
            'edit.php?post_type=narrato_story',
            __('Membership Settings', 'narrato-for-writers'),
            __('Membership', 'narrato-for-writers'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function register_settings(): void {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default'           => $this->defaults(),
            ]
        );
    }

    //  Page render — plain form, no settings API sections needed
    //  since keys are grouped visually below.

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $o = self::get_options();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Membership Settings', 'narrato-for-writers' ); ?></h1>

            <?php settings_errors( self::OPTION_GROUP ); ?>

            <form method="post" action="options.php">
                <?php settings_fields( self::OPTION_GROUP ); ?>

                <h2><?php esc_html_e( 'Mode', 'narrato-for-writers' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Test Mode', 'narrato-for-writers' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[test_mode]" value="1" <?php checked( 1, $o['test_mode'] ); ?> />
                                <?php esc_html_e( 'Use test/sandbox credentials (recommended until you are ready to accept real payments)', 'narrato-for-writers' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Stripe', 'narrato-for-writers' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Find your API keys at dashboard.stripe.com/apikeys. Create two recurring Prices (monthly, yearly) and paste their IDs below.', 'narrato-for-writers' ); ?>
                </p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Test Publishable Key', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_test_publishable_key]" value="<?php echo esc_attr( $o['stripe_test_publishable_key'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Test Secret Key', 'narrato-for-writers' ); ?></th>
                        <td><input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_test_secret_key]" value="<?php echo esc_attr( $o['stripe_test_secret_key'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Test Webhook Signing Secret', 'narrato-for-writers' ); ?></th>
                        <td><input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_test_webhook_secret]" value="<?php echo esc_attr( $o['stripe_test_webhook_secret'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Publishable Key', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_live_publishable_key]" value="<?php echo esc_attr( $o['stripe_live_publishable_key'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Secret Key', 'narrato-for-writers' ); ?></th>
                        <td><input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_live_secret_key]" value="<?php echo esc_attr( $o['stripe_live_secret_key'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Webhook Signing Secret', 'narrato-for-writers' ); ?></th>
                        <td><input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_live_webhook_secret]" value="<?php echo esc_attr( $o['stripe_live_webhook_secret'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Monthly Price ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" placeholder="price_..." name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_price_monthly]" value="<?php echo esc_attr( $o['stripe_price_monthly'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Yearly Price ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" placeholder="price_..." name="<?php echo esc_attr( self::OPTION_NAME ); ?>[stripe_price_yearly]" value="<?php echo esc_attr( $o['stripe_price_yearly'] ); ?>" /></td>
                    </tr>
                </table>
                <p class="description">
                    <?php printf(
                        /* translators: %s: webhook URL */
                        esc_html__( 'Stripe webhook endpoint URL: %s', 'narrato-for-writers' ),
                        '<code>' . esc_url( rest_url( 'narrato/v1/webhooks/stripe' ) ) . '</code>'
                    ); ?>
                </p>

                <h2><?php esc_html_e( 'PayPal', 'narrato-for-writers' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Find your credentials at developer.paypal.com/dashboard/applications. Create two Billing Plans (monthly, yearly) and paste their IDs below.', 'narrato-for-writers' ); ?>
                </p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Sandbox Client ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_test_client_id]" value="<?php echo esc_attr( $o['paypal_test_client_id'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sandbox Client Secret', 'narrato-for-writers' ); ?></th>
                        <td><input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_test_client_secret]" value="<?php echo esc_attr( $o['paypal_test_client_secret'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sandbox Webhook ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_test_webhook_id]" value="<?php echo esc_attr( $o['paypal_test_webhook_id'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Client ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_live_client_id]" value="<?php echo esc_attr( $o['paypal_live_client_id'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Client Secret', 'narrato-for-writers' ); ?></th>
                        <td><input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_live_client_secret]" value="<?php echo esc_attr( $o['paypal_live_client_secret'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Webhook ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_live_webhook_id]" value="<?php echo esc_attr( $o['paypal_live_webhook_id'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Monthly Plan ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" placeholder="P-..." name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_plan_monthly]" value="<?php echo esc_attr( $o['paypal_plan_monthly'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Yearly Plan ID', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="regular-text" placeholder="P-..." name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paypal_plan_yearly]" value="<?php echo esc_attr( $o['paypal_plan_yearly'] ); ?>" /></td>
                    </tr>
                </table>
                <p class="description">
                    <?php printf(
                        /* translators: %s: webhook URL */
                        esc_html__( 'PayPal webhook endpoint URL: %s', 'narrato-for-writers' ),
                        '<code>' . esc_url( rest_url( 'narrato/v1/webhooks/paypal' ) ) . '</code>'
                    ); ?>
                </p>

                <h2><?php esc_html_e( 'Display Prices', 'narrato-for-writers' ); ?></h2>
                <p class="description"><?php esc_html_e( 'These are shown on the checkout page — they should match what you configured at your gateway(s).', 'narrato-for-writers' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Monthly Price (display only)', 'narrato-for-writers' ); ?></th>
                        <td>
                            <input type="text" class="small-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[display_price_monthly]" value="<?php echo esc_attr( $o['display_price_monthly'] ); ?>" />
                            <?php esc_html_e( 'per month', 'narrato-for-writers' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Yearly Price (display only)', 'narrato-for-writers' ); ?></th>
                        <td>
                            <input type="text" class="small-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[display_price_yearly]" value="<?php echo esc_attr( $o['display_price_yearly'] ); ?>" />
                            <?php esc_html_e( 'per year', 'narrato-for-writers' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Currency Symbol', 'narrato-for-writers' ); ?></th>
                        <td><input type="text" class="small-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[currency_symbol]" value="<?php echo esc_attr( $o['currency_symbol'] ); ?>" /></td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Settings', 'narrato-for-writers' ) ); ?>
            </form>
        </div>
        <?php
    }

    // Sanitization
    public function sanitize_options( array $input ): array {
        $clean = $this->defaults();

        $clean['test_mode'] = ! empty( $input['test_mode'] ) ? 1 : 0;

        $text_fields = [
            'stripe_test_publishable_key', 'stripe_test_secret_key', 'stripe_test_webhook_secret',
            'stripe_live_publishable_key', 'stripe_live_secret_key', 'stripe_live_webhook_secret',
            'stripe_price_monthly', 'stripe_price_yearly',
            'paypal_test_client_id', 'paypal_test_client_secret', 'paypal_test_webhook_id',
            'paypal_live_client_id', 'paypal_live_client_secret', 'paypal_live_webhook_id',
            'paypal_plan_monthly', 'paypal_plan_yearly',
            'display_price_monthly', 'display_price_yearly', 'currency_symbol',
        ];

        foreach ( $text_fields as $field ) {
            $clean[ $field ] = isset( $input[ $field ] ) ? sanitize_text_field( $input[ $field ] ) : '';
        }

        return $clean;
    }

    // Helpers
    public static function get_options(): array {
        return wp_parse_args(
            get_option( self::OPTION_NAME, [] ),
            (new self())->defaults()
        );
    }

    private function defaults(): array {

        return [
            'test_mode' => 1,

            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key'      => '',
            'stripe_test_webhook_secret'  => '',
            'stripe_live_publishable_key' => '',
            'stripe_live_secret_key'      => '',
            'stripe_live_webhook_secret'  => '',
            'stripe_price_monthly'        => '',
            'stripe_price_yearly'         => '',

            'paypal_test_client_id'       => '',
            'paypal_test_client_secret'   => '',
            'paypal_test_webhook_id'      => '',
            'paypal_live_client_id'       => '',
            'paypal_live_client_secret'   => '',
            'paypal_live_webhook_id'      => '',
            'paypal_plan_monthly'         => '',
            'paypal_plan_yearly'          => '',

            'display_price_monthly' => '5',
            'display_price_yearly'  => '50',
            'currency_symbol'       => '$',
        ];
    }
}