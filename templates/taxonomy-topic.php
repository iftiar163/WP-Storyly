<?php
defined( 'ABSPATH' ) || exit;

get_header();

$topic = get_queried_object();
?>

<div class="storyly-wrapper">
    <div class="storyly-container">

        <header class="storyly-archive-header">
            <p class="storyly-topic-label">
                <?php esc_html_e( 'Topic', 'wp-storyly' ); ?>
            </p>
            <h1 class="storyly-archive-title">
                <?php echo esc_html( $topic->name ); ?>
            </h1>
            <?php if ( $topic->description ) : ?>
                <p class="storyly-topic-desc">
                    <?php echo esc_html( $topic->description ); ?>
                </p>
            <?php endif; ?>
            <p class="storyly-topic-count">
                <?php printf(
                    esc_html( _n( '%s story', '%s stories', $topic->count, 'wp-storyly' ) ),
                    number_format_i18n( $topic->count )
                ); ?>
            </p>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="storyly-feed">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php include WP_STORYLY_PATH . 'templates/partials/story-card.php'; ?>
                <?php endwhile; ?>
            </div>

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
                <?php esc_html_e( 'No stories in this topic yet.', 'wp-storyly' ); ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();