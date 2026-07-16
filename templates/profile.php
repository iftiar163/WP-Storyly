<?php

defined('ABSPATH') || exit;

get_header();

$narrato_username = get_query_var('narrato_profile');
$narrato_user = get_user_by('slug', $narrato_username);

if (! $narrato_user) {
    get_template_part('404');
    get_footer();
    return;
}

$narrato_user_id      = $narrato_user->ID;
$narrato_story_count  = count_user_posts($narrato_user_id, 'narrato_story', true);
$narrato_follower_cnt = \Narrato\Social\Follows::get_count('author', $narrato_user_id);
$narrato_is_following = is_user_logged_in()
    ? \Narrato\Social\Follows::is_following(get_current_user_id(), 'author', $narrato_user_id)
    : false;
$narrato_is_self      = get_current_user_id() === $narrato_user_id;

?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <header class="narrato-profile-header">
            <?php echo get_avatar($narrato_user_id, 96, '', '', ['class' => 'narrato-profile-avatar']); ?>

            <h1 class="narrato-profile-name"><?php echo esc_html($narrato_user->display_name); ?></h1>

            <?php if ($narrato_user->description) : ?>
                <p class="narrato-profile-bio"><?php echo esc_html($narrato_user->description); ?></p>
            <?php endif; ?>

            <div class="narrato-profile-stats">
                <span>
                    <strong><?php echo esc_html(number_format_i18n($narrato_story_count)); ?></strong>
                    <?php esc_html_e('Stories', 'narrato-for-writers'); ?>
                </span>
                <span class="narrato-dot">·</span>
                <span class="narrato-follower-count">
                    <strong><?php echo esc_html(number_format_i18n($narrato_follower_cnt)); ?></strong>
                    <?php esc_html_e('Followers', 'narrato-for-writers'); ?>
                </span>
            </div>

            <?php if (is_user_logged_in() && ! $narrato_is_self) : ?>
                <button
                    class="narrato-follow-btn <?php echo $narrato_is_following ? 'is-following' : ''; ?>"
                    data-follow-type="author"
                    data-object-id="<?php echo esc_attr($narrato_user_id); ?>">
                    <?php echo $narrato_is_following
                        ? esc_html__('Following', 'narrato-for-writers')
                        : esc_html__('Follow', 'narrato-for-writers'); ?>
                </button>
            <?php elseif (! is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="narrato-follow-btn">
                    <?php esc_html_e('Follow', 'narrato-for-writers'); ?>
                </a>
            <?php endif; ?>
        </header>

        <?php
        $narrato_query = new WP_Query([
            'post_type'      => 'narrato_story',
            'post_status'    => 'publish',
            'author'         => $narrato_user_id,
            'posts_per_page' => 10,
            'paged'          => (int) (get_query_var('paged') ?: 1),
        ]);

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
                <?php esc_html_e('No stories published yet.', 'narrato-for-writers'); ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer();
