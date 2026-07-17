<?php
defined('ABSPATH') || exit;

get_header();
?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <?php if (! is_user_logged_in()) : ?>
            <div class="narrato-login-notice">
                <p><?php esc_html_e('Please log in to see stories from writers you follow.', 'narrato-for-writers'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(home_url('/following/'))); ?>">
                    <?php esc_html_e('Log in', 'narrato-for-writers'); ?>
                </a>
            </div>

        <?php else :
            $narrato_user_id    = get_current_user_id();
            $narrato_author_ids = \Narrato\Social\Follows::get_followed_author_ids($narrato_user_id);
            $narrato_topic_ids  = \Narrato\Social\Follows::get_followed_topic_ids($narrato_user_id);
        ?>

            <header class="narrato-archive-header">
                <h1 class="narrato-archive-title"><?php esc_html_e('Following', 'narrato-for-writers'); ?></h1>
                <p class="narrato-topic-desc">
                    <?php esc_html_e('Stories from writers and topics you follow.', 'narrato-for-writers'); ?>
                </p>
            </header>

            <?php if (empty($narrato_author_ids) && empty($narrato_topic_ids)) : ?>
                <div class="narrato-bookmarks-empty">
                    <p><?php esc_html_e("You're not following any writers or topics yet.", 'narrato-for-writers'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('narrato_story')); ?>">
                        <?php esc_html_e('Discover stories', 'narrato-for-writers'); ?>
                    </a>
                </div>

                <?php else :
                $narrato_tax_query = ['relation' => 'OR'];
                $narrato_meta_ok   = false;

                $narrato_args = [
                    'post_type'      => 'narrato_story',
                    'post_status'    => 'publish',
                    'posts_per_page' => 10,
                    'paged'          => (int) (get_query_var('paged') ?: 1),
                ];

                if (! empty($narrato_author_ids) && ! empty($narrato_topic_ids)) {
                    // Authors OR topics
                    $narrato_args['author__in'] = $narrato_author_ids;
                    $narrato_args['tax_query']  = [[
                        'taxonomy' => 'narrato_topic',
                        'field'    => 'term_id',
                        'terms'    => $narrato_topic_ids,
                    ]];
                    // WP_Query can't OR across author + tax natively, so we merge via post__in
                    $narrato_by_author = get_posts([
                        'post_type'      => 'narrato_story',
                        'post_status'    => 'publish',
                        'author__in'     => $narrato_author_ids,
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                    ]);
                    $narrato_by_topic = get_posts([
                        'post_type'      => 'narrato_story',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'tax_query'      => [[
                            'taxonomy' => 'narrato_topic',
                            'field'    => 'term_id',
                            'terms'    => $narrato_topic_ids,
                        ]],
                    ]);
                    $narrato_ids = array_unique(array_merge($narrato_by_author, $narrato_by_topic));
                    unset($narrato_args['author__in'], $narrato_args['tax_query']);
                    $narrato_args['post__in'] = ! empty($narrato_ids) ? $narrato_ids : [0];
                    $narrato_args['orderby']  = 'date';
                    $narrato_args['order']    = 'DESC';
                } elseif (! empty($narrato_author_ids)) {
                    $narrato_args['author__in'] = $narrato_author_ids;
                } else {
                    $narrato_args['tax_query'] = [[
                        'taxonomy' => 'narrato_topic',
                        'field'    => 'term_id',
                        'terms'    => $narrato_topic_ids,
                    ]];
                }

                $narrato_query = new WP_Query($narrato_args);

                if ($narrato_query->have_posts()) : ?>
                    <div class="narrato-feed">
                        <?php while ($narrato_query->have_posts()) : $narrato_query->the_post(); ?>
                            <?php include NARRATO_PATH . 'templates/partials/story-card.php'; ?>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    </div>

                    <?php if ($narrato_query->max_num_pages > 1) : ?>
                        <nav class="narrato-pagination">
                            <?php echo paginate_links([
                                'total'     => $narrato_query->max_num_pages,
                                'prev_text' => esc_html__('← Older', 'narrato-for-writers'),
                                'next_text' => esc_html__('Newer →', 'narrato-for-writers'),
                            ]); ?>
                        </nav>
                    <?php endif; ?>

                <?php else : ?>
                    <p class="narrato-no-stories">
                        <?php esc_html_e('No new stories from your follows yet.', 'narrato-for-writers'); ?>
                    </p>
            <?php endif;
            endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php get_footer();
