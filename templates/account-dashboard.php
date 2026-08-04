<?php
defined('ABSPATH') || exit;

get_header();

if (! is_user_logged_in()) :
?>
    <div class="narrato-wrapper">
        <div class="narrato-container">
            <div class="narrato-login-notice">
                <p><?php esc_html_e('Please log in to view your account.', 'narrato-for-writers'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(home_url('/narrato-profile/'))); ?>">
                    <?php esc_html_e('Log in', 'narrato-for-writers'); ?>
                </a>
            </div>
        </div>
    </div>
<?php
    get_footer();
    return;
endif;

$narrato_user_id = get_current_user_id();
$narrato_user    = wp_get_current_user();

$narrato_story_count = count_user_posts($narrato_user_id, 'narrato_story', true);
$narrato_follower_cnt = \Narrato\Social\Follows::get_count('author', $narrato_user_id);
$narrato_following_authors = count(\Narrato\Social\Follows::get_followed_author_ids($narrato_user_id));
$narrato_following_topics  = count(\Narrato\Social\Follows::get_followed_topic_ids($narrato_user_id));
$narrato_bookmark_count = count(\Narrato\Engagement\Bookmarks::get_user_bookmarks($narrato_user_id));
$narrato_pub_count = count(\Narrato\Publications\Editors::get_editors_publications($narrato_user_id));
?>

<div class="narrato-wrapper">
    <div class="narrato-container narrato-account-container">

        <header class="narrato-account-header">
            <?php echo get_avatar($narrato_user_id, 72, '', '', ['class' => 'narrato-account-avatar']); ?>
            <div>
                <h1 class="narrato-account-name"><?php echo esc_html($narrato_user->display_name); ?></h1>
                <a href="<?php echo esc_url(\Narrato\Social\Profile::get_url($narrato_user)); ?>" class="narrato-account-public-link">
                    <?php esc_html_e('View public profile →', 'narrato-for-writers'); ?>
                </a>
            </div>
        </header>

        <!-- Stats bar -->
        <div class="narrato-account-stats">
            <a href="<?php echo esc_url(get_author_posts_url($narrato_user_id)); ?>" class="narrato-stat-box">
                <strong><?php echo esc_html(number_format_i18n($narrato_story_count)); ?></strong>
                <span><?php esc_html_e('Stories', 'narrato-for-writers'); ?></span>
            </a>
            <a href="<?php echo esc_url(\Narrato\Social\Profile::get_url($narrato_user)); ?>" class="narrato-stat-box">
                <strong><?php echo esc_html(number_format_i18n($narrato_follower_cnt)); ?></strong>
                <span><?php esc_html_e('Followers', 'narrato-for-writers'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/following/')); ?>" class="narrato-stat-box">
                <strong><?php echo esc_html(number_format_i18n($narrato_following_authors + $narrato_following_topics)); ?></strong>
                <span><?php esc_html_e('Following', 'narrato-for-writers'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/my-bookmarks/')); ?>" class="narrato-stat-box">
                <strong><?php echo esc_html(number_format_i18n($narrato_bookmark_count)); ?></strong>
                <span><?php esc_html_e('Bookmarks', 'narrato-for-writers'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/my-publications/')); ?>" class="narrato-stat-box">
                <strong><?php echo esc_html(number_format_i18n($narrato_pub_count)); ?></strong>
                <span><?php esc_html_e('Publications', 'narrato-for-writers'); ?></span>
            </a>
        </div>

        <div class="narrato-account-grid">

            <!-- Left column: profile edit + quick links -->
            <div class="narrato-account-col">

                <section class="narrato-account-section">
                    <h2><?php esc_html_e('Edit Profile', 'narrato-for-writers'); ?></h2>
                    <form id="narrato-account-form" class="narrato-account-form">
                        <label>
                            <?php esc_html_e('Display name', 'narrato-for-writers'); ?>
                            <input type="text" name="display_name" value="<?php echo esc_attr($narrato_user->display_name); ?>" />
                        </label>
                        <label>
                            <?php esc_html_e('Bio', 'narrato-for-writers'); ?>
                            <textarea name="bio" rows="4"><?php echo esc_textarea(get_the_author_meta('description', $narrato_user_id)); ?></textarea>
                        </label>
                        <p class="narrato-account-gravatar-note">
                            <?php printf(
                                /* translators: %s: link to gravatar.com */
                                wp_kses_post(__('Profile photos are powered by Gravatar. <a href="%s" target="_blank" rel="noopener">Update your photo at Gravatar.com</a>.', 'narrato-for-writers')),
                                'https://gravatar.com'
                            ); ?>
                        </p>
                        <button type="submit" class="narrato-follow-btn"><?php esc_html_e('Save Changes', 'narrato-for-writers'); ?></button>
                        <p class="narrato-account-save-result" role="status"></p>
                    </form>
                </section>

                <section class="narrato-account-section">
                    <h2><?php esc_html_e('Quick Links', 'narrato-for-writers'); ?></h2>
                    <ul class="narrato-account-links">
                        <li><a href="<?php echo esc_url(home_url('/my-bookmarks/')); ?>"><?php esc_html_e('My Bookmarks', 'narrato-for-writers'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/following/')); ?>"><?php esc_html_e('Following Feed', 'narrato-for-writers'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/my-publications/')); ?>"><?php esc_html_e('My Publications', 'narrato-for-writers'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/publication-reviews/')); ?>"><?php esc_html_e('Publication Reviews', 'narrato-for-writers'); ?></a></li>
                        <li><a href="<?php echo esc_url(admin_url('post-new.php?post_type=narrato_story')); ?>"><?php esc_html_e('Write a New Story', 'narrato-for-writers'); ?></a></li>
                        <li><a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"><?php esc_html_e('Log Out', 'narrato-for-writers'); ?></a></li>
                    </ul>
                </section>

            </div>

            <!-- Right column: notifications + activity -->
            <div class="narrato-account-col">

                <section class="narrato-account-section">
                    <h2><?php esc_html_e('Recent Notifications', 'narrato-for-writers'); ?></h2>
                    <div id="narrato-account-notifications">
                        <p class="narrato-loading"><?php esc_html_e('Loading…', 'narrato-for-writers'); ?></p>
                    </div>
                </section>

                <section class="narrato-account-section">
                    <h2><?php esc_html_e('Your Recent Stories', 'narrato-for-writers'); ?></h2>
                    <?php
                    $narrato_recent = get_posts([
                        'post_type'      => 'narrato_story',
                        'author'         => $narrato_user_id,
                        'posts_per_page' => 5,
                        'post_status'    => ['publish', 'draft', 'pending'],
                    ]);

                    if ($narrato_recent) : ?>
                        <ul class="narrato-account-links">
                            <?php foreach ($narrato_recent as $narrato_story) : ?>
                                <li>
                                    <a href="<?php echo esc_url(get_edit_post_link($narrato_story->ID)); ?>">
                                        <?php echo esc_html($narrato_story->post_title ?: __('(untitled)', 'narrato-for-writers')); ?>
                                    </a>
                                    <span class="narrato-account-story-status"><?php echo esc_html(ucfirst($narrato_story->post_status)); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="narrato-no-stories"><?php esc_html_e("You haven't written any stories yet.", 'narrato-for-writers'); ?></p>
                    <?php endif; ?>
                </section>

            </div>

        </div>

    </div>
</div>

<?php get_footer();
