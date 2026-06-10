<?php
defined( 'ABSPATH' ) || exit;

get_header();

$storyly_topic = get_queried_object();
?>

<div class="storyly-wrapper">
    <div class="storyly-container">

        <header class="storyly-archive-header">
            <p class="storyly-topic-label">
                <?php esc_html_e( 'Topic', 'storyly' ); ?>
            </p>
            <h1 class="storyly-archive-title">
                <?php echo esc_html( $storyly_topic->name ); ?>
            </h1>
            <?php if ( $storyly_topic->description ) : ?>
                <p class="storyly-topic-desc">
                    <?php echo esc_html( $storyly_topic->description ); ?>
                </p>
            <?php endif; ?>
            <p class="storyly-topic-count">
                <?php printf(
                    /* translators: %s: number of stories */
                    esc_html( _n( '%s story', '%s stories', $storyly_topic->count, 'storyly' ) ),
                    esc_html( number_format_i18n( $storyly_topic->count ) )
                ); ?>
            </p>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="storyly-feed">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php include STORYLY_PATH . 'templates/partials/story-card.php'; ?>
                <?php endwhile; ?>
            </div>

            <nav class="storyly-pagination">
                <?php
                echo wp_kses_post( paginate_links( [
                    'prev_text' => esc_html__( '← Older', 'storyly' ),
                    'next_text' => esc_html__( 'Newer →', 'storyly' ),
                ] ) );
                ?>
            </nav>

        <?php else : ?>
            <p class="storyly-no-stories">
                <?php esc_html_e( 'No stories in this topic yet.', 'storyly' ); ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();