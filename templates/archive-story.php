<?php
defined('ABSPATH') || exit;

get_header();
?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <header class="narrato-archive-header">
            <h1 class="narrato-archive-title">
                <?php esc_html_e('Stories', 'narrato-for-writers'); ?>
            </h1>
        </header>

        <?php if (have_posts()) : ?>
            <div class="narrato-feed">
                <?php while (have_posts()) : the_post(); ?>
                    <?php include NARRATO_PATH . 'templates/partials/story-card.php'; ?>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <nav class="narrato-pagination">
                <?php
                echo wp_kses_post(paginate_links([
                    'prev_text' => esc_html__('← Older', 'narrato-for-writers'),
                    'next_text' => esc_html__('Newer →', 'narrato-for-writers'),
                ]));
                ?>
            </nav>

        <?php else : ?>
            <p class="narrato-no-stories">
                <?php esc_html_e('No stories published yet.', 'narrato-for-writers'); ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();
