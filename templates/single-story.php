<?php
defined('ABSPATH') || exit;

get_header();

$narrato_options = \Narrato\Admin\Settings::get_options();

while (have_posts()) :
    the_post();
    $narrato_subtitle = get_post_meta(get_the_ID(), '_narrato_subtitle', true);
    $narrato_reading_time = get_post_meta(get_the_ID(), '_narrato_reading_time', true) ?: 1;
    $narrato_topics = get_the_terms(get_the_ID(), 'narrato_topic');
    $narrato_author_id = get_the_author_meta('ID');
?>
    <div class="narrato-wrapper">
        <article id="story-<?php the_ID(); ?>" <?php post_class('narrato-single'); ?>>

            <!-- Reading progress bar -->
            <?php if ($narrato_options['show_progress_bar']) : ?>
                <div class="narrato-progress-bar" id="narrato-progress"></div>
            <?php endif; ?>

            <div class="narrato-container">

                <!-- Topics -->
                <?php if (! empty($narrato_topics) && ! is_wp_error($narrato_topics)) : ?>
                    <div class="narrato-topics">
                        <?php foreach ($narrato_topics as $narrato_topic) : ?>
                            <a href="<?php echo esc_url(get_term_link($narrato_topic)); ?>"
                                class="narrato-topic-tag">
                                <?php echo esc_html($narrato_topic->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="narrato-title"><?php the_title(); ?></h1>

                <!-- Subtitle -->
                <?php if ($narrato_subtitle) : ?>
                    <p class="narrato-subtitle"><?php echo esc_html($narrato_subtitle); ?></p>
                <?php endif; ?>

                <!-- Author bar -->
                <div class="narrato-author-bar">
                    <a href="<?php echo esc_url(get_author_posts_url($narrato_author_id)); ?>">
                        <?php echo get_avatar($narrato_author_id, 44, '', '', ['class' => 'narrato-avatar']); ?>
                    </a>
                    <div class="narrato-author-info">
                        <a href="<?php echo esc_url(get_author_posts_url($narrato_author_id)); ?>"
                            class="narrato-author-name">
                            <?php the_author(); ?>
                        </a>
                        <div class="narrato-meta">
                            <span><?php echo esc_html(
                                        get_the_date('M j, Y')
                                    ); ?></span>
                            <span class="narrato-dot">·</span>
                            <span><?php printf(
                                        /* translators: %d: number of minutes */
                                        esc_html__('%d min read', 'narrato-for-writers'),
                                        (int) $narrato_reading_time
                                    ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Featured image -->
                <?php if (has_post_thumbnail()) : ?>
                    <div class="narrato-cover">
                        <?php the_post_thumbnail('full', ['class' => 'narrato-cover-img']); ?>
                    </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="narrato-content">
                    <?php the_content(); ?>
                </div>

                <!-- Footer: topics -->
                <?php if (! empty($narrato_topics) && ! is_wp_error($narrato_topics)) : ?>
                    <div class="narrato-footer-topics">
                        <?php foreach ($narrato_topics as $narrato_topic) : ?>
                            <a href="<?php echo esc_url(get_term_link($narrato_topic)); ?>"
                                class="narrato-topic-pill">
                                <?php echo esc_html($narrato_topic->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Author bio box -->
                <?php if ($narrato_options['show_author_bio']) : ?>
                    <div class="narrato-author-box">
                        <?php echo get_avatar($narrato_author_id, 80, '', '', ['class' => 'narrato-avatar-lg']); ?>
                        <div class="narrato-author-box-info">
                            <a href="<?php echo esc_url(get_author_posts_url($narrato_author_id)); ?>"
                                class="narrato-author-box-name">
                                <?php the_author(); ?>
                            </a>
                            <p class="narrato-author-box-bio">
                                <?php echo esc_html(get_the_author_meta('description')); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- .narrato-container -->
        </article>

        <!-- Related stories -->
        <?php
        $narrato_current_id = get_the_ID();
        $narrato_related_args = [
            'post_type'      => 'story',
            'posts_per_page' => 4, // Fetch extra to account for current post
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true, // Performance optimization
        ];

        if (! empty($narrato_topics) && ! is_wp_error($narrato_topics)) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            $narrato_related_args['tax_query'] = [[
                'taxonomy' => 'narrato_topic',
                'field'    => 'term_id',
                'terms'    => wp_list_pluck($narrato_topics, 'term_id'),
            ]];
        }

        $narrato_related_posts = [];
        if ($narrato_options['show_related']) {
            $narrato_related = new WP_Query($narrato_related_args);
            if ($narrato_related->have_posts()) {
                foreach ($narrato_related->posts as $narrato_p) {
                    if ($narrato_p->ID !== $narrato_current_id) {
                        $narrato_related_posts[] = $narrato_p;
                        if (count($narrato_related_posts) === 3) break;
                    }
                }
            }
        }

        if (!empty($narrato_related_posts)) :
            global $post;
        ?>
            <div class="narrato-related">
                <div class="narrato-container">
                    <h3 class="narrato-related-title">
                        <?php esc_html_e('More Stories', 'narrato-for-writers'); ?>
                    </h3>
                    <div class="narrato-card-grid">
                        <?php foreach ($narrato_related_posts as $post) : setup_postdata($post); ?>
                            <?php include NARRATO_PATH . 'templates/partials/story-card.php'; ?>
                        <?php endforeach;
                        wp_reset_postdata(); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- .narrato-wrapper -->

<?php
endwhile;


wp_enqueue_script(
    'narrato-reading-progress',
    NARRATO_URL . 'assets/js/reading-progress.js',
    [],
    NARRATO_VERSION,
    true
);

get_footer();
