<?php
defined('ABSPATH') || exit;

$narrato_pub_id   = get_the_ID();
$narrato_pub_desc = get_post_meta($narrato_pub_id, '_narrato_pub_description', true);
$narrato_story_count = (new WP_Query([
    'post_type'      => 'narrato_story',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'meta_query'     => [['key' => '_narrato_published_in', 'value' => $narrato_pub_id]],
]))->found_posts;
?>

<a href="<?php the_permalink(); ?>" class="narrato-pub-card">
    <?php if (has_post_thumbnail()) : ?>
        <div class="narrato-pub-card-logo">
            <?php the_post_thumbnail('thumbnail'); ?>
        </div>
    <?php endif; ?>
    <div class="narrato-pub-card-body">
        <h3 class="narrato-pub-card-title"><?php the_title(); ?></h3>
        <?php if ($narrato_pub_desc) : ?>
            <p class="narrato-pub-card-desc"><?php echo esc_html(wp_trim_words($narrato_pub_desc, 16)); ?></p>
        <?php endif; ?>
        <span class="narrato-pub-card-count">
            <?php printf(
                esc_html(_n('%d story', '%d stories', $narrato_story_count, 'narrato-for-writers')),
                (int) $narrato_story_count
            ); ?>
        </span>
    </div>
</a>