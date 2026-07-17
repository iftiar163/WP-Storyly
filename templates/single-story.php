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
                    <?php if (is_user_logged_in() && ! empty($topics) && ! is_wp_error($topics)) :
                        $narrato_first_topic = $topics[0];
                        $narrato_is_following_topic = \Narrato\Social\Follows::is_following(
                            get_current_user_id(),
                            'topic',
                            $narrato_first_topic->term_id
                        );
                    ?>
                        <div class="narrato-topic-follow-wrap">
                            <button
                                class="narrato-follow-btn narrato-follow-btn-sm <?php echo $narrato_is_following_topic ? 'is-following' : ''; ?>"
                                data-follow-type="topic"
                                data-object-id="<?php echo esc_attr($narrato_first_topic->term_id); ?>">
                                <?php echo $narrato_is_following_topic
                                    ? esc_html__('Following topic', 'narrato-for-writers')
                                    : sprintf(
                                        /* translators: %s: topic name */
                                        esc_html__('Follow %s', 'narrato-for-writers'),
                                        esc_html($narrato_first_topic->name)
                                    ); ?>
                            </button>
                        </div>
                    <?php endif; ?>
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
                        <?php if (get_current_user_id() !== $narrato_author_id) :
                            $narrato_is_following_author = is_user_logged_in()
                                ? \Narrato\Social\Follows::is_following(get_current_user_id(), 'author', $narrato_author_id)
                                : false;
                        ?>
                            <?php if (is_user_logged_in()) : ?>
                                <button
                                    class="narrato-follow-btn narrato-follow-btn-sm <?php echo $narrato_is_following_author ? 'is-following' : ''; ?>"
                                    data-follow-type="author"
                                    data-object-id="<?php echo esc_attr($narrato_author_id); ?>">
                                    <?php echo $narrato_is_following_author
                                        ? esc_html__('Following', 'narrato-for-writers')
                                        : esc_html__('Follow', 'narrato-for-writers'); ?>
                                </button>
                            <?php else : ?>
                                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="narrato-follow-btn narrato-follow-btn-sm">
                                    <?php esc_html_e('Follow', 'narrato-for-writers'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
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

                <?php if (is_user_logged_in()) : ?>
                    <!-- Inline engagement (shown on mobile / narrow screens) -->
                    <div class="narrato-engagement-inline">
                        <button class="narrato-clap-btn" aria-label="<?php esc_attr_e('Clap for this story', 'narrato-for-writers'); ?>">
                            <span class="narrato-clap-icon">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm-1-13h2v6h-2zm0 8h2v2h-2z" />
                                    <path d="M8.5 8.5c.8-.8 2-.8 2.8 0l.7.7.7-.7c.8-.8 2-.8 2.8 0 .8.8.8 2 0 2.8L12 14.3l-3.5-3.5c-.8-.8-.8-2 0-2.8z" />
                                </svg>
                            </span>
                            <span class="narrato-clap-count">0</span>
                        </button>

                        <button class="narrato-bookmark-btn" aria-label="<?php esc_attr_e('Save this story', 'narrato-for-writers'); ?>">
                            <span class="narrato-bookmark-icon">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M5 3h14a1 1 0 0 1 1 1v17l-8-4-8 4V4a1 1 0 0 1 1-1z" />
                                </svg>
                            </span>
                            <span class="narrato-bookmark-label"><?php esc_html_e('Save story', 'narrato-for-writers'); ?></span>
                        </button>
                    </div>
                <?php endif; ?>

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
            'post_type'      => 'narrato_story',
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

        <?php if (is_user_logged_in()) : ?>
            <!-- Floating sidebar engagement -->
            <div class="narrato-engagement-sidebar" aria-label="<?php esc_attr_e('Story actions', 'narrato-for-writers'); ?>">
                <button class="narrato-clap-btn" aria-label="<?php esc_attr_e('Clap for this story', 'narrato-for-writers'); ?>">
                    <span class="narrato-clap-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M8.5 8.5c.8-.8 2-.8 2.8 0l.7.7.7-.7c.8-.8 2-.8 2.8 0 .8.8.8 2 0 2.8L12 14.3l-3.5-3.5c-.8-.8-.8-2 0-2.8z" />
                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z" />
                        </svg>
                    </span>
                    <span class="narrato-clap-count">0</span>
                </button>

                <button class="narrato-bookmark-btn" aria-label="<?php esc_attr_e('Save this story', 'narrato-for-writers'); ?>">
                    <span class="narrato-bookmark-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M5 3h14a1 1 0 0 1 1 1v17l-8-4-8 4V4a1 1 0 0 1 1-1z" />
                        </svg>
                    </span>
                    <span class="narrato-bookmark-label"><?php esc_html_e('Save story', 'narrato-for-writers'); ?></span>
                </button>
            </div>
        <?php endif; ?>

    </div><!-- .narrato-wrapper -->

<?php
endwhile;

get_footer();
