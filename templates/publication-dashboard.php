<?php
defined('ABSPATH') || exit;

get_header();

if (! is_user_logged_in()) :
    get_header();
?>
    <div class="narrato-wrapper">
        <div class="narrato-container">
            <div class="narrato-login-notice">
                <p><?php esc_html_e('Please log in to manage your publications.', 'narrato-for-writers'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(home_url('/my-publications/'))); ?>">
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

// Publications this user edits
$narrato_my_pubs = new WP_Query([
    'post_type'      => 'narrato_publication',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => [[
        'key'     => '_narrato_pub_editors',
        'value'   => sprintf(':%d;', $narrato_user_id),
        'compare' => 'LIKE',
    ]],
]);

// This user's own stories, to offer for submission
$narrato_my_stories = get_posts([
    'post_type'      => 'narrato_story',
    'post_status'    => 'publish',
    'author'         => $narrato_user_id,
    'posts_per_page' => 50,
]);
?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <header class="narrato-archive-header">
            <h1 class="narrato-archive-title"><?php esc_html_e('My Publications', 'narrato-for-writers'); ?></h1>
        </header>

        <div class="narrato-pub-dashboard-tabs">
            <button class="narrato-tab-btn is-active" data-tab="manage"><?php esc_html_e('My Publications', 'narrato-for-writers'); ?></button>
            <button class="narrato-tab-btn" data-tab="submit"><?php esc_html_e('Submit a Story', 'narrato-for-writers'); ?></button>
            <button class="narrato-tab-btn" data-tab="status"><?php esc_html_e('My Submissions', 'narrato-for-writers'); ?></button>
            <button class="narrato-tab-btn" data-tab="create"><?php esc_html_e('Create New', 'narrato-for-writers'); ?></button>
        </div>

        <!-- TAB: Manage existing publications -->
        <div class="narrato-tab-panel is-active" data-tab-panel="manage">
            <?php if ($narrato_my_pubs->have_posts()) : ?>
                <div class="narrato-pub-grid">
                    <?php while ($narrato_my_pubs->have_posts()) : $narrato_my_pubs->the_post(); ?>
                        <?php
                        $narrato_open = (bool) get_post_meta(get_the_ID(), '_narrato_pub_open_submissions', true);
                        ?>
                        <div class="narrato-pub-manage-card">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <label class="narrato-toggle-row">
                                <input
                                    type="checkbox"
                                    class="narrato-open-submissions-toggle"
                                    data-pub-id="<?php the_ID(); ?>"
                                    <?php checked($narrato_open); ?> />
                                <?php esc_html_e('Open for submissions', 'narrato-for-writers'); ?>
                            </label>
                            <a href="<?php echo esc_url(home_url('/publication-reviews/')); ?>" class="narrato-pub-review-link">
                                <?php esc_html_e('Review submissions →', 'narrato-for-writers'); ?>
                            </a>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="narrato-no-stories"><?php esc_html_e("You don't manage any publications yet.", 'narrato-for-writers'); ?></p>
            <?php endif; ?>
        </div>

        <!-- TAB: Submit a story -->
        <div class="narrato-tab-panel" data-tab-panel="submit">
            <?php if (empty($narrato_my_stories)) : ?>
                <p class="narrato-no-stories"><?php esc_html_e("You haven't written any stories yet.", 'narrato-for-writers'); ?></p>
            <?php else : ?>
                <form id="narrato-submit-form" class="narrato-submit-form">
                    <label>
                        <?php esc_html_e('Select your story', 'narrato-for-writers'); ?>
                        <select name="story_id" required>
                            <option value=""><?php esc_html_e('— Choose a story —', 'narrato-for-writers'); ?></option>
                            <?php foreach ($narrato_my_stories as $narrato_story) : ?>
                                <option value="<?php echo esc_attr($narrato_story->ID); ?>">
                                    <?php echo esc_html($narrato_story->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <?php esc_html_e('Select a publication', 'narrato-for-writers'); ?>
                        <select name="pub_id" required>
                            <option value=""><?php esc_html_e('— Choose a publication —', 'narrato-for-writers'); ?></option>
                            <?php
                            $narrato_open_pubs = new WP_Query([
                                'post_type'      => 'narrato_publication',
                                'post_status'    => 'publish',
                                'posts_per_page' => -1,
                                'meta_query'     => [[
                                    'key'   => '_narrato_pub_open_submissions',
                                    'value' => '1',
                                ]],
                            ]);
                            while ($narrato_open_pubs->have_posts()) : $narrato_open_pubs->the_post();
                            ?>
                                <option value="<?php the_ID(); ?>"><?php the_title(); ?></option>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        </select>
                    </label>

                    <button type="submit" class="narrato-follow-btn">
                        <?php esc_html_e('Submit for Review', 'narrato-for-writers'); ?>
                    </button>
                    <p class="narrato-submit-result" role="status"></p>
                </form>
            <?php endif; ?>
        </div>

        <!-- TAB: My submission statuses -->
        <div class="narrato-tab-panel" data-tab-panel="status">
            <div id="narrato-my-submissions-list">
                <p class="narrato-loading"><?php esc_html_e('Loading…', 'narrato-for-writers'); ?></p>
            </div>
        </div>

        <!-- TAB: Create new publication -->
        <div class="narrato-tab-panel" data-tab-panel="create">
            <p class="narrato-create-pub-notice">
                <?php printf(
                    /* translators: %s: link to wp-admin new publication screen */
                    wp_kses_post(__('To create a new publication, go to <a href="%s">Add New Publication</a> in your dashboard.', 'narrato-for-writers')),
                    esc_url(admin_url('post-new.php?post_type=narrato_publication'))
                ); ?>
            </p>
        </div>

    </div>
</div>

<?php get_footer();
