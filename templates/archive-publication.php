<?php
defined('ABSPATH') || exit;

get_header();
?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <header class="narrato-archive-header">
            <h1 class="narrato-archive-title"><?php esc_html_e('Publications', 'narrato-for-writers'); ?></h1>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/my-publications/')); ?>" class="narrato-follow-btn">
                    <?php esc_html_e('Manage My Publications', 'narrato-for-writers'); ?>
                </a>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="narrato-pub-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php include NARRATO_PATH . 'templates/partials/publication-card.php'; ?>
                <?php endwhile; ?>
            </div>

            <nav class="narrato-pagination">
                <?php echo paginate_links([
                    'prev_text' => esc_html__('← Older', 'narrato-for-writers'),
                    'next_text' => esc_html__('Newer →', 'narrato-for-writers'),
                ]); ?>
            </nav>
        <?php else : ?>
            <p class="narrato-no-stories"><?php esc_html_e('No publications yet.', 'narrato-for-writers'); ?></p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();
