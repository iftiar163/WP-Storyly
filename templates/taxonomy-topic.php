<?php
defined( 'ABSPATH' ) || exit;

get_header();

$narrato_topic = get_queried_object();
?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <header class="narrato-archive-header">
            <p class="narrato-topic-label">
                <?php esc_html_e( 'Topic', 'narrato-for-writers' ); ?>
            </p>
            <h1 class="narrato-archive-title">
                <?php echo esc_html( $narrato_topic->name ); ?>
            </h1>
            <?php if ( $narrato_topic->description ) : ?>
                <p class="narrato-topic-desc">
                    <?php echo esc_html( $narrato_topic->description ); ?>
                </p>
            <?php endif; ?>
            <p class="narrato-topic-count">
                <?php printf(
                    /* translators: %s: number of stories */
                    esc_html( _n( '%s story', '%s stories', $narrato_topic->count, 'narrato-for-writers' ) ),
                    esc_html( number_format_i18n( $narrato_topic->count ) )
                ); ?>
            </p>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="narrato-feed">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php include NARRATO_PATH . 'templates/partials/story-card.php'; ?>
                <?php endwhile; ?>
            </div>

            <nav class="narrato-pagination">
                <?php
                echo wp_kses_post( paginate_links( [
                    'prev_text' => esc_html__( '← Older', 'narrato-for-writers' ),
                    'next_text' => esc_html__( 'Newer →', 'narrato-for-writers' ),
                ] ) );
                ?>
            </nav>

        <?php else : ?>
            <p class="narrato-no-stories">
                <?php esc_html_e( 'No stories in this topic yet.', 'narrato-for-writers' ); ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();