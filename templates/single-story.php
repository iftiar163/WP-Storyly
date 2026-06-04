<?php
defined( 'ABSPATH' ) || exit;

get_header();

while(have_posts()) :
    the_post();
    $subtitle = get_post_meta(get_the_ID(), '_storyly_subtitle', true);
    $reading_time = get_post_meta(get_the_ID(), '_storyly_reading_time', true) ? : 1;
    $topics = get_the_terms(get_the_ID(), 'storyly_topic');
    $author_id = get_the_author_meta('ID');
    ?>
        <div class="storyly-wrapper">
    <article id="story-<?php the_ID(); ?>" <?php post_class( 'storyly-single' ); ?>>

        <!-- Reading progress bar -->
        <div class="storyly-progress-bar" id="storyly-progress"></div>

        <div class="storyly-container">

            <!-- Topics -->
            <?php if ( ! empty( $topics ) && ! is_wp_error( $topics ) ) : ?>
            <div class="storyly-topics">
                <?php foreach ( $topics as $topic ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $topic ) ); ?>"
                       class="storyly-topic-tag">
                        <?php echo esc_html( $topic->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Title -->
            <h1 class="storyly-title"><?php the_title(); ?></h1>

            <!-- Subtitle -->
            <?php if ( $subtitle ) : ?>
                <p class="storyly-subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>

            <!-- Author bar -->
            <div class="storyly-author-bar">
                <a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
                    <?php echo get_avatar( $author_id, 44, '', '', [ 'class' => 'storyly-avatar' ] ); ?>
                </a>
                <div class="storyly-author-info">
                    <a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"
                       class="storyly-author-name">
                        <?php the_author(); ?>
                    </a>
                    <div class="storyly-meta">
                        <span><?php echo esc_html(
                            get_the_date( 'M j, Y' )
                        ); ?></span>
                        <span class="storyly-dot">·</span>
                        <span><?php printf(
                            /* translators: %d: number of minutes */
                            esc_html__( '%d min read', 'wp-storyly' ),
                            (int) $reading_time
                        ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Featured image -->
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="storyly-cover">
                    <?php the_post_thumbnail( 'full', [ 'class' => 'storyly-cover-img' ] ); ?>
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="storyly-content">
                <?php the_content(); ?>
            </div>

            <!-- Footer: topics -->
            <?php if ( ! empty( $topics ) && ! is_wp_error( $topics ) ) : ?>
            <div class="storyly-footer-topics">
                <?php foreach ( $topics as $topic ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $topic ) ); ?>"
                       class="storyly-topic-pill">
                        <?php echo esc_html( $topic->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Author bio box -->
            <div class="storyly-author-box">
                <?php echo get_avatar( $author_id, 80, '', '', [ 'class' => 'storyly-avatar-lg' ] ); ?>
                <div class="storyly-author-box-info">
                    <a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"
                       class="storyly-author-box-name">
                        <?php the_author(); ?>
                    </a>
                    <p class="storyly-author-box-bio">
                        <?php echo esc_html( get_the_author_meta( 'description' ) ); ?>
                    </p>
                </div>
            </div>

        </div><!-- .storyly-container -->
    </article>

    <!-- Related stories -->
    <?php
    $related_args = [
        'post_type'      => 'story',
        'posts_per_page' => 3,
        'post__not_in'   => [ get_the_ID() ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ( ! empty( $topics ) && ! is_wp_error( $topics ) ) {
        $related_args['tax_query'] = [ [
            'taxonomy' => 'storyly_topic',
            'field'    => 'term_id',
            'terms'    => wp_list_pluck( $topics, 'term_id' ),
        ] ];
    }

    $related = new WP_Query( $related_args );

    if ( $related->have_posts() ) : ?>
        <div class="storyly-related">
            <div class="storyly-container">
                <h3 class="storyly-related-title">
                    <?php esc_html_e( 'More from WP Storyly', 'wp-storyly' ); ?>
                </h3>
                <div class="storyly-card-grid">
                    <?php while ( $related->have_posts() ) : $related->the_post(); ?>
                        <?php include WP_STORYLY_PATH . 'templates/partials/story-card.php'; ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div><!-- .storyly-wrapper -->

    <?php
    endwhile;


wp_enqueue_script(
    'storyly-reading-progress',
    WP_STORYLY_URL . 'assets/js/reading-progress.js',
    [],
    WP_STORYLY_VERSION,
    true
);

get_footer();