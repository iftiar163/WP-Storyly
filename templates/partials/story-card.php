<?php
defined('ABSPATH') || exit;

$narrato_reading_time = get_post_meta(get_the_ID(), '_narrato_reading_time', true) ?: 1;
$narrato_subtitle     = get_post_meta(get_the_ID(), '_narrato_subtitle', true);
$narrato_topics       = get_the_terms(get_the_ID(), 'narrato_topic');
$narrato_author_id    = get_the_author_meta('ID');
?>

<article <?php post_class('narrato-card'); ?>>
    <div class="narrato-card-body">

        <!-- Author row -->
        <div class="narrato-card-author">
            <?php echo get_avatar($narrato_author_id, 24, '', '', ['class' => 'narrato-avatar-sm']); ?>
            <a href="<?php echo esc_url(get_author_posts_url($narrato_author_id)); ?>"
                class="narrato-card-author-name">
                <?php the_author(); ?>
            </a>

            <?php if (is_user_logged_in() && get_current_user_id() !== $narrato_author_id) :
                $narrato_card_following = \Narrato\Social\Follows::is_following(
                    get_current_user_id(),
                    'author',
                    $narrato_author_id
                );
            ?>
                <button
                    class="narrato-follow-btn narrato-follow-btn-xs <?php echo $narrato_card_following ? 'is-following' : ''; ?>"
                    data-follow-type="author"
                    data-object-id="<?php echo esc_attr($narrato_author_id); ?>">
                    <?php echo $narrato_card_following
                        ? esc_html__('Following', 'narrato-for-writers')
                        : esc_html__('Follow', 'narrato-for-writers'); ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="narrato-card-inner">
            <div class="narrato-card-text">
                <!-- Title -->
                <h2 class="narrato-card-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <!-- Subtitle / Excerpt -->
                <?php if ($narrato_subtitle) : ?>
                    <p class="narrato-card-subtitle">
                        <?php echo esc_html(wp_trim_words($narrato_subtitle, 20)); ?>
                    </p>
                <?php elseif (has_excerpt()) : ?>
                    <p class="narrato-card-subtitle">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?>
                    </p>
                <?php endif; ?>

                <!-- Meta row -->
                <div class="narrato-card-meta">
                    <?php if (! empty($narrato_topics) && ! is_wp_error($narrato_topics)) : ?>
                        <a href="<?php echo esc_url(get_term_link($narrato_topics[0])); ?>"
                            class="narrato-card-topic">
                            <?php echo esc_html($narrato_topics[0]->name); ?>
                        </a>
                        <span class="narrato-dot">·</span>
                    <?php endif; ?>
                    <span><?php echo esc_html(get_the_date('M j')); ?></span>
                    <span class="narrato-dot">·</span>
                    <span><?php printf(
                                /* translators: %d: number of minutes */
                                esc_html__('%d min read', 'narrato-for-writers'),
                                (int) $narrato_reading_time
                            ); ?></span>
                    <?php
                    $narrato_clap_total = (int) get_post_meta(get_the_ID(), '_narrato_clap_total', true);
                    if ($narrato_clap_total > 0) : ?>
                        <span class="narrato-dot">·</span>
                        <span class="narrato-card-claps">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8.5 8.5c.8-.8 2-.8 2.8 0l.7.7.7-.7c.8-.8 2-.8 2.8 0 .8.8.8 2 0 2.8L12 14.3l-3.5-3.5c-.8-.8-.8-2 0-2.8z" />
                            </svg>
                            <?php echo number_format_i18n($narrato_clap_total); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thumbnail -->
            <?php if (has_post_thumbnail()) : ?>
                <a href="<?php the_permalink(); ?>" class="narrato-card-thumb-link">
                    <?php the_post_thumbnail('medium', ['class' => 'narrato-card-thumb']); ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</article>