<?php
defined( 'ABSPATH' ) || exit;

get_header();

if ( ! is_user_logged_in() ) :
    ?>
    <div class="narrato-wrapper">
        <div class="narrato-container">
            <div class="narrato-login-notice">
                <p><?php esc_html_e( 'Please log in to become a member.', 'narrato-for-writers' ); ?></p>
                <a href="<?php echo esc_url( wp_login_url( home_url( '/membership/' ) ) ); ?>">
                    <?php esc_html_e( 'Log in', 'narrato-for-writers' ); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
    get_footer();
    return;
endif;

$narrato_options   = \Narrato\Admin\MembershipSettings::get_options();
$narrato_is_member = \Narrato\Membership\Membership::is_member();
$narrato_row       = \Narrato\Membership\Membership::get_membership_row( get_current_user_id() );

$narrato_stripe_ready = ( new \Narrato\Membership\StripeGateway() )->is_configured();
$narrato_paypal_ready = ( new \Narrato\Membership\PaypalGateway() )->is_configured();
?>

<div class="narrato-wrapper">
    <div class="narrato-container narrato-membership-container">

        <?php if ( $narrato_is_member ) : ?>

            <!-- Already a member -->
            <div class="narrato-membership-active">
                <h1><?php esc_html_e( "You're a member! 🎉", 'narrato-for-writers' ); ?></h1>
                <p>
                    <?php printf(
                        /* translators: 1: plan name, 2: renewal date */
                        esc_html__( 'You are on the %1$s plan. Renews on %2$s.', 'narrato-for-writers' ),
                        esc_html( ucfirst( $narrato_row['plan'] ?? '' ) ),
                        esc_html( $narrato_row['current_period_end'] ? date_i18n( 'M j, Y', strtotime( $narrato_row['current_period_end'] ) ) : '—' )
                    ); ?>
                </p>
                <button id="narrato-cancel-membership" class="narrato-follow-btn narrato-follow-btn-sm">
                    <?php esc_html_e( 'Cancel Membership', 'narrato-for-writers' ); ?>
                </button>
                <p class="narrato-cancel-result" role="status"></p>
            </div>

        <?php else : ?>

            <header class="narrato-membership-header">
                <h1><?php esc_html_e( 'Become a Member', 'narrato-for-writers' ); ?></h1>
                <p><?php esc_html_e( 'Unlock every members-only story and support the writers you love.', 'narrato-for-writers' ); ?></p>
            </header>

            <!-- Plan picker -->
            <div class="narrato-plan-picker">
                <label class="narrato-plan-option">
                    <input type="radio" name="narrato_plan" value="monthly" checked />
                    <span class="narrato-plan-name"><?php esc_html_e( 'Monthly', 'narrato-for-writers' ); ?></span>
                    <span class="narrato-plan-price">
                        <?php echo esc_html( $narrato_options['currency_symbol'] . $narrato_options['display_price_monthly'] ); ?>
                        <small>/<?php esc_html_e( 'mo', 'narrato-for-writers' ); ?></small>
                    </span>
                </label>
                <label class="narrato-plan-option">
                    <input type="radio" name="narrato_plan" value="yearly" />
                    <span class="narrato-plan-name"><?php esc_html_e( 'Yearly', 'narrato-for-writers' ); ?></span>
                    <span class="narrato-plan-price">
                        <?php echo esc_html( $narrato_options['currency_symbol'] . $narrato_options['display_price_yearly'] ); ?>
                        <small>/<?php esc_html_e( 'yr', 'narrato-for-writers' ); ?></small>
                    </span>
                    <span class="narrato-plan-badge"><?php esc_html_e( 'Best value', 'narrato-for-writers' ); ?></span>
                </label>
            </div>

            <!-- Gateway tabs -->
            <?php if ( ! $narrato_stripe_ready && ! $narrato_paypal_ready ) : ?>
                <p class="narrato-no-stories">
                    <?php esc_html_e( 'Payments are not configured yet. Please check back soon.', 'narrato-for-writers' ); ?>
                </p>
            <?php else : ?>
                <div class="narrato-gateway-tabs">
                    <?php if ( $narrato_stripe_ready ) : ?>
                        <button class="narrato-gateway-tab is-active" data-gateway="stripe"><?php esc_html_e( 'Pay with Card', 'narrato-for-writers' ); ?></button>
                    <?php endif; ?>
                    <?php if ( $narrato_paypal_ready ) : ?>
                        <button class="narrato-gateway-tab <?php echo ! $narrato_stripe_ready ? 'is-active' : ''; ?>" data-gateway="paypal"><?php esc_html_e( 'Pay with PayPal', 'narrato-for-writers' ); ?></button>
                    <?php endif; ?>
                </div>

                <?php if ( $narrato_stripe_ready ) : ?>
                    <div class="narrato-gateway-panel is-active" data-gateway-panel="stripe">
                        <div id="narrato-stripe-card-element"></div>
                        <div id="narrato-stripe-errors" class="narrato-checkout-error" role="alert"></div>
                        <button id="narrato-stripe-submit" class="narrato-follow-btn narrato-checkout-submit">
                            <?php esc_html_e( 'Subscribe', 'narrato-for-writers' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( $narrato_paypal_ready ) : ?>
                    <div class="narrato-gateway-panel <?php echo ! $narrato_stripe_ready ? 'is-active' : ''; ?>" data-gateway-panel="paypal">
                        <div id="narrato-paypal-buttons"></div>
                        <div id="narrato-paypal-errors" class="narrato-checkout-error" role="alert"></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php get_footer();