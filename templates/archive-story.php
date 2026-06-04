<?php
defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="storyly-wrapper">
    <div class="storyly-container">

        <header class="storyly-archive-header">
            <h1 class="storyly-archive-title">
                <?php esc_html_e( 'Stories', 'wp-storyly' ); ?>
            </h1>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="storyly-feed">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php include WP_STORYLY_PATH . 'templates/partials/story-card.php'; ?>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <nav class="storyly-pagination">
                <?php
                echo paginate_links( [
                    'prev_text' => esc_html__( '← Older', 'wp-storyly' ),
                    'next_text' => esc_html__( 'Newer →', 'wp-storyly' ),
                ] );
                ?>
            </nav>

        <?php else : ?>
            <p class="storyly-no-stories">
                <?php esc_html_e( 'No stories published yet.', 'wp-storyly' ); ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();