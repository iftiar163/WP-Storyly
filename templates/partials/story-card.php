<?php
defined( 'ABSPATH' ) || exit;

$storyly_reading_time = get_post_meta( get_the_ID(), '_storyly_reading_time', true ) ?: 1;
$storyly_subtitle     = get_post_meta( get_the_ID(), '_storyly_subtitle', true );
$storyly_topics       = get_the_terms( get_the_ID(), 'storyly_topic' );
$storyly_author_id    = get_the_author_meta( 'ID' );
?>

<article <?php post_class( 'storyly-card' ); ?>>
    <div class="storyly-card-body">

        <!-- Author row -->
        <div class="storyly-card-author">
            <?php echo get_avatar( $storyly_author_id, 24, '', '', [ 'class' => 'storyly-avatar-sm' ] ); ?>
            <a href="<?php echo esc_url( get_author_posts_url( $storyly_author_id ) ); ?>"
               class="storyly-card-author-name">
                <?php the_author(); ?>
            </a>
        </div>

        <div class="storyly-card-inner">
            <div class="storyly-card-text">
                <!-- Title -->
                <h2 class="storyly-card-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <!-- Subtitle / Excerpt -->
                <?php if ( $storyly_subtitle ) : ?>
                    <p class="storyly-card-subtitle">
                        <?php echo esc_html( wp_trim_words( $storyly_subtitle, 20 ) ); ?>
                    </p>
                <?php elseif ( has_excerpt() ) : ?>
                    <p class="storyly-card-subtitle">
                        <?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?>
                    </p>
                <?php endif; ?>

                <!-- Meta row -->
                <div class="storyly-card-meta">
                    <?php if ( ! empty( $storyly_topics ) && ! is_wp_error( $storyly_topics ) ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $storyly_topics[0] ) ); ?>"
                           class="storyly-card-topic">
                            <?php echo esc_html( $storyly_topics[0]->name ); ?>
                        </a>
                        <span class="storyly-dot">·</span>
                    <?php endif; ?>
                    <span><?php echo esc_html( get_the_date( 'M j' ) ); ?></span>
                    <span class="storyly-dot">·</span>
                    <span><?php printf(
                        /* translators: %d: number of minutes */
                        esc_html__( '%d min read', 'storyly' ),
                        (int) $storyly_reading_time
                    ); ?></span>
                </div>
            </div>

            <!-- Thumbnail -->
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>" class="storyly-card-thumb-link">
                    <?php the_post_thumbnail( 'medium', [ 'class' => 'storyly-card-thumb' ] ); ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</article>