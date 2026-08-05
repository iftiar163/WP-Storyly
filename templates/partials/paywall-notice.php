<?php
defined('ABSPATH') || exit;

// Fallback in case this partial is ever included without the variable pre-set
if (! isset($narrato_paywall_type)) {
    $narrato_paywall_type = \Narrato\Membership\Paywall::get_paywall_type(get_the_ID());
}

$narrato_reads_remaining = \Narrato\Membership\Paywall::get_reads_remaining();
?>

<div class="narrato-paywall-notice">
    <div class="narrato-paywall-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 1a5 5 0 0 0-5 5v3H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V11a2 2 0 0 0-2-2h-2V6a5 5 0 0 0-5-5zm-3 8V6a3 3 0 0 1 6 0v3z" />
        </svg>
    </div>

    <h3 class="narrato-paywall-title">
        <?php
        if ($narrato_paywall_type === 'hard') {
            esc_html_e('This is a members-only story', 'narrato-for-writers');
        } else {
            esc_html_e("You've reached your free story limit", 'narrato-for-writers');
        }
        ?>
    </h3>

    <p class="narrato-paywall-desc">
        <?php
        if ($narrato_paywall_type === 'hard') {
            esc_html_e('Become a member to read this story and support the writer.', 'narrato-for-writers');
        } else {
            printf(
                /* translators: %d: free reads per month */
                esc_html__('You get %d free stories a month. Become a member for unlimited access.', 'narrato-for-writers'),
                (int) \Narrato\Admin\Settings::get_options()['metered_free_reads']
            );
        }
        ?>
    </p>

    <a href="<?php echo esc_url(home_url('/membership/')); ?>" class="narrato-follow-btn narrato-paywall-cta">
        <?php esc_html_e('Become a Member', 'narrato-for-writers'); ?>
    </a>

    <?php if (! is_user_logged_in()) : ?>
        <p class="narrato-paywall-login">
            <?php printf(
                /* translators: %s: login link */
                wp_kses_post(__('Already a member? <a href="%s">Log in</a>', 'narrato-for-writers')),
                esc_url(wp_login_url(get_permalink()))
            ); ?>
        </p>
    <?php endif; ?>
</div>