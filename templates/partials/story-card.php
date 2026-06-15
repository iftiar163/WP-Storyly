<?php
defined( 'ABSPATH' ) || exit;

$narrato_reading_time = get_post_meta( get_the_ID(), '_narrato_reading_time', true ) ?: 1;
$narrato_subtitle     = get_post_meta( get_the_ID(), '_narrato_subtitle', true );
$narrato_topics       = get_the_terms( get_the_ID(), 'narrato_topic' );
$narrato_author_id    = get_the_author_meta( 'ID' );
?>

<article <?php post_class( 'narrato-card' ); ?>>
    <div class="narrato-card-body">

        <!-- Author row -->
        <div class="narrato-card-author">
            <?php echo get_avatar( $narrato_author_id, 24, '', '', [ 'class' => 'narrato-avatar-sm' ] ); ?>
            <a href="<?php echo esc_url( get_author_posts_url( $narrato_author_id ) ); ?>"
               class="narrato-card-author-name">
                <?php the_author(); ?>
            </a>
        </div>

        <div class="narrato-card-inner">
            <div class="narrato-card-text">
                <!-- Title -->
                <h2 class="narrato-card-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <!-- Subtitle / Excerpt -->
                <?php if ( $narrato_subtitle ) : ?>
                    <p class="narrato-card-subtitle">
                        <?php echo esc_html( wp_trim_words( $narrato_subtitle, 20 ) ); ?>
                    </p>
                <?php elseif ( has_excerpt() ) : ?>
                    <p class="narrato-card-subtitle">
                        <?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?>
                    </p>
                <?php endif; ?>

                <!-- Meta row -->
                <div class="narrato-card-meta">
                    <?php if ( ! empty( $narrato_topics ) && ! is_wp_error( $narrato_topics ) ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $narrato_topics[0] ) ); ?>"
                           class="narrato-card-topic">
                            <?php echo esc_html( $narrato_topics[0]->name ); ?>
                        </a>
                        <span class="narrato-dot">·</span>
                    <?php endif; ?>
                    <span><?php echo esc_html( get_the_date( 'M j' ) ); ?></span>
                    <span class="narrato-dot">·</span>
                    <span><?php printf(
                        /* translators: %d: number of minutes */
                        esc_html__( '%d min read', 'narrato-for-writers' ),
                        (int) $narrato_reading_time
                    ); ?></span>
                </div>
            </div>

            <!-- Thumbnail -->
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>" class="narrato-card-thumb-link">
                    <?php the_post_thumbnail( 'medium', [ 'class' => 'narrato-card-thumb' ] ); ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</article>