<?php

defined( 'ABSPATH' ) || exit;

get_header();

?>

<div class="narrato-wrapper">
    <div class="narrato-container">

        <?php if ( ! is_user_logged_in() ) : ?>
            <div class="narrato-login-notice">
                <p><?php esc_html_e( 'Please log in to view your bookmarks.', 'narrato-for-writers' ); ?></p>
                <a href="<?php echo esc_url( wp_login_url( home_url( '/my-bookmarks/' ) ) ); ?>">
                    <?php esc_html_e( 'Log in', 'narrato-for-writers' ); ?>
                </a>
            </div>

        <?php else :
            $narrato_user_id   = get_current_user_id();
            $narrato_bookmark_ids = \Narrato\Engagement\Bookmarks::get_user_bookmarks( $narrato_user_id );
            $narrato_count     = count( $narrato_bookmark_ids );
        ?>

            <header class="narrato-bookmarks-header">
                <h1 class="narrato-bookmarks-title">
                    <?php esc_html_e( 'Your Bookmarks', 'narrato-for-writers' ); ?>
                </h1>
                <p class="narrato-bookmarks-count">
                    <?php printf(
                        /* translators: %d: number of bookmarks */
                        esc_html( _n( '%d saved story', '%d saved stories', $narrato_count, 'narrato-for-writers' ) ),
                        (int) $narrato_count
                    ); ?>
                </p>
            </header>

            <?php if ( empty( $narrato_bookmark_ids ) ) : ?>
                <div class="narrato-bookmarks-empty">
                    <p><?php esc_html_e( 'You haven\'t saved any stories yet.', 'narrato-for-writers' ); ?></p>
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'narrato_story' ) ); ?>">
                        <?php esc_html_e( 'Browse stories', 'narrato-for-writers' ); ?>
                    </a>
                </div>

            <?php else :
                $narrato_query = new WP_Query( [
                    'post_type'      => 'narrato_story',
                    'post_status'    => 'publish',
                    'post__in'       => $narrato_bookmark_ids,
                    'orderby'        => 'post__in',
                    'posts_per_page' => -1,
                    'no_found_rows'  => true,
                ] );

                if ( $narrato_query->have_posts() ) :
            ?>
                <div class="narrato-feed">
                    <?php while ( $narrato_query->have_posts() ) : $narrato_query->the_post(); ?>
                        <?php include NARRATO_PATH . 'templates/partials/story-card.php'; ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php endif; endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php get_footer();