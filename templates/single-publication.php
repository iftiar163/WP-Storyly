<?php
defined('ABSPATH') || exit;

get_header();

while (have_posts()) : the_post();

    $narrato_pub_id      = get_the_ID();
    $narrato_description = get_post_meta($narrato_pub_id, '_narrato_pub_description', true);
    $narrato_editor_ids  = \Narrato\Publications\Editors::get_editor_ids($narrato_pub_id);
    $narrato_open_sub    = (bool) get_post_meta($narrato_pub_id, '_narrato_pub_open_submissions', true);
?>

    <div class="narrato-wrapper">
        <div class="narrato-container">

            <header class="narrato-pub-header">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="narrato-pub-logo">
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </div>
                <?php endif; ?>

                <h1 class="narrato-pub-title"><?php the_title(); ?></h1>

                <?php if ($narrato_description) : ?>
                    <p class="narrato-pub-description"><?php echo esc_html($narrato_description); ?></p>
                <?php endif; ?>

                <?php if (! empty($narrato_editor_ids)) : ?>
                    <div class="narrato-pub-editors">
                        <span class="narrato-pub-editors-label"><?php esc_html_e('Edited by', 'narrato-for-writers'); ?></span>
                        <?php foreach ($narrato_editor_ids as $narrato_editor_id) :
                            $narrato_editor = get_userdata($narrato_editor_id);
                            if (! $narrato_editor) continue;
                        ?>
                            <a href="<?php echo esc_url(\Narrato\Social\Profile::get_url($narrato_editor)); ?>" class="narrato-pub-editor-chip">
                                <?php echo get_avatar($narrato_editor_id, 20); ?>
                                <?php echo esc_html($narrato_editor->display_name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($narrato_open_sub && is_user_logged_in()) : ?>
                    <p class="narrato-pub-open-badge">
                        <?php esc_html_e('✓ Open for submissions', 'narrato-for-writers'); ?>
                    </p>
                <?php endif; ?>
            </header>

            <?php
            $narrato_stories = new WP_Query([
                'post_type'      => 'narrato_story',
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'paged'          => (int) (get_query_var('paged') ?: 1),
                'meta_query'     => [[
                    'key'   => '_narrato_published_in',
                    'value' => $narrato_pub_id,
                ]],
            ]);

            if ($narrato_stories->have_posts()) : ?>
                <div class="narrato-feed">
                    <?php while ($narrato_stories->have_posts()) : $narrato_stories->the_post(); ?>
                        <?php include NARRATO_PATH . 'templates/partials/story-card.php'; ?>
                    <?php endwhile;
                    wp_reset_postdata(); ?>
                </div>

                <?php if ($narrato_stories->max_num_pages > 1) : ?>
                    <nav class="narrato-pagination">
                        <?php echo paginate_links([
                            'total'     => $narrato_stories->max_num_pages,
                            'prev_text' => esc_html__('← Older', 'narrato-for-writers'),
                            'next_text' => esc_html__('Newer →', 'narrato-for-writers'),
                        ]); ?>
                    </nav>
                <?php endif; ?>
            <?php else : ?>
                <p class="narrato-no-stories">
                    <?php esc_html_e('No stories published in this publication yet.', 'narrato-for-writers'); ?>
                </p>
            <?php endif; ?>

        </div>
    </div>

<?php
endwhile;

get_footer();
