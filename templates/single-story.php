<?php
defined('ABSPATH') || exit;

get_header();

$storyly_options = \Storyly\Admin\Settings::get_options();

while (have_posts()) :
    the_post();
    $storyly_subtitle = get_post_meta(get_the_ID(), '_storyly_subtitle', true);
    $storyly_reading_time = get_post_meta(get_the_ID(), '_storyly_reading_time', true) ?: 1;
    $storyly_topics = get_the_terms(get_the_ID(), 'storyly_topic');
    $storyly_author_id = get_the_author_meta('ID');
?>
    <div class="storyly-wrapper">
        <article id="story-<?php the_ID(); ?>" <?php post_class('storyly-single'); ?>>

            <!-- Reading progress bar -->
            <?php if ($storyly_options['show_progress_bar']) : ?>
                <div class="storyly-progress-bar" id="storyly-progress"></div>
            <?php endif; ?>

            <div class="storyly-container">

                <!-- Topics -->
                <?php if (! empty($storyly_topics) && ! is_wp_error($storyly_topics)) : ?>
                    <div class="storyly-topics">
                        <?php foreach ($storyly_topics as $storyly_topic) : ?>
                            <a href="<?php echo esc_url(get_term_link($storyly_topic)); ?>"
                                class="storyly-topic-tag">
                                <?php echo esc_html($storyly_topic->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="storyly-title"><?php the_title(); ?></h1>

                <!-- Subtitle -->
                <?php if ($storyly_subtitle) : ?>
                    <p class="storyly-subtitle"><?php echo esc_html($storyly_subtitle); ?></p>
                <?php endif; ?>

                <!-- Author bar -->
                <div class="storyly-author-bar">
                    <a href="<?php echo esc_url(get_author_posts_url($storyly_author_id)); ?>">
                        <?php echo get_avatar($storyly_author_id, 44, '', '', ['class' => 'storyly-avatar']); ?>
                    </a>
                    <div class="storyly-author-info">
                        <a href="<?php echo esc_url(get_author_posts_url($storyly_author_id)); ?>"
                            class="storyly-author-name">
                            <?php the_author(); ?>
                        </a>
                        <div class="storyly-meta">
                            <span><?php echo esc_html(
                                        get_the_date('M j, Y')
                                    ); ?></span>
                            <span class="storyly-dot">·</span>
                            <span><?php printf(
                                        /* translators: %d: number of minutes */
                                        esc_html__('%d min read', 'storyly'),
                                        (int) $storyly_reading_time
                                    ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Featured image -->
                <?php if (has_post_thumbnail()) : ?>
                    <div class="storyly-cover">
                        <?php the_post_thumbnail('full', ['class' => 'storyly-cover-img']); ?>
                    </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="storyly-content">
                    <?php the_content(); ?>
                </div>

                <!-- Footer: topics -->
                <?php if (! empty($storyly_topics) && ! is_wp_error($storyly_topics)) : ?>
                    <div class="storyly-footer-topics">
                        <?php foreach ($storyly_topics as $storyly_topic) : ?>
                            <a href="<?php echo esc_url(get_term_link($storyly_topic)); ?>"
                                class="storyly-topic-pill">
                                <?php echo esc_html($storyly_topic->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Author bio box -->
                <?php if ($storyly_options['show_author_bio']) : ?>
                    <div class="storyly-author-box">
                        <?php echo get_avatar($storyly_author_id, 80, '', '', ['class' => 'storyly-avatar-lg']); ?>
                        <div class="storyly-author-box-info">
                            <a href="<?php echo esc_url(get_author_posts_url($storyly_author_id)); ?>"
                                class="storyly-author-box-name">
                                <?php the_author(); ?>
                            </a>
                            <p class="storyly-author-box-bio">
                                <?php echo esc_html(get_the_author_meta('description')); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- .storyly-container -->
        </article>

        <!-- Related stories -->
        <?php
        $storyly_current_id = get_the_ID();
        $storyly_related_args = [
            'post_type'      => 'story',
            'posts_per_page' => 4, // Fetch extra to account for current post
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true, // Performance optimization
        ];

        if (! empty($storyly_topics) && ! is_wp_error($storyly_topics)) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            $storyly_related_args['tax_query'] = [[
                'taxonomy' => 'storyly_topic',
                'field'    => 'term_id',
                'terms'    => wp_list_pluck($storyly_topics, 'term_id'),
            ]];
        }

        $storyly_related_posts = [];
        if ($storyly_options['show_related']) {
            $storyly_related = new WP_Query($storyly_related_args);
            if ($storyly_related->have_posts()) {
                foreach ($storyly_related->posts as $storyly_p) {
                    if ($storyly_p->ID !== $storyly_current_id) {
                        $storyly_related_posts[] = $storyly_p;
                        if (count($storyly_related_posts) === 3) break;
                    }
                }
            }
        }

        if (!empty($storyly_related_posts)) : 
            global $post;
        ?>
            <div class="storyly-related">
                <div class="storyly-container">
                    <h3 class="storyly-related-title">
                        <?php esc_html_e('More from Storyly', 'storyly'); ?>
                    </h3>
                    <div class="storyly-card-grid">
                        <?php foreach ($storyly_related_posts as $post) : setup_postdata($post); ?>
                            <?php include STORYLY_PATH . 'templates/partials/story-card.php'; ?>
                        <?php endforeach;
                        wp_reset_postdata(); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- .storyly-wrapper -->

<?php
endwhile;


wp_enqueue_script(
    'storyly-reading-progress',
    STORYLY_URL . 'assets/js/reading-progress.js',
    [],
    STORYLY_VERSION,
    true
);

get_footer();
