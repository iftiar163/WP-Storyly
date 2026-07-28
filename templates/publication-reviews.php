<?php
defined('ABSPATH') || exit;

get_header();

if (! is_user_logged_in()) :
?>
    <div class="narrato-wrapper">
        <div class="narrato-container">
            <div class="narrato-login-notice">
                <p><?php esc_html_e('Please log in to review submissions.', 'narrato-for-writers'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(home_url('/publication-reviews/'))); ?>">
                    <?php esc_html_e('Log in', 'narrato-for-writers'); ?>
                </a>
            </div>
        </div>
    </div>
<?php
    get_footer();
    return;
endif;

$narrato_user_id = get_current_user_id();
$narrato_pub_ids  = \Narrato\Publications\Editors::get_editors_publications($narrato_user_id);
?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <header class="narrato-archive-header">
            <h1 class="narrato-archive-title"><?php esc_html_e('Submission Reviews', 'narrato-for-writers'); ?></h1>
        </header>

        <?php if (empty($narrato_pub_ids)) : ?>
            <p class="narrato-no-stories">
                <?php esc_html_e("You aren't an editor of any publication.", 'narrato-for-writers'); ?>
            </p>
        <?php else : ?>
            <div
                id="narrato-review-queue"
                data-pub-ids="<?php echo esc_attr(implode(',', $narrato_pub_ids)); ?>">
                <p class="narrato-loading"><?php esc_html_e('Loading submissions…', 'narrato-for-writers'); ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();
