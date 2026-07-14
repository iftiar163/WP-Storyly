<?php

namespace Narrato;

defined('ABSPATH') || exit;

final class Activator
{
    public static function run(): void
    {
        self::create_tables();
        self::create_default_topics();
        flush_rewrite_rules();
    }

    public static function create_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Claps table
        $claps_table = $wpdb->prefix . 'narrato_claps';
        $sql_claps = "CREATE TABLE IF NOT EXISTS {$claps_table} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id     BIGINT(20) UNSIGNED NOT NULL,
            user_id     BIGINT(20) UNSIGNED NOT NULL,
            clap_count  TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   user_post (user_id, post_id),
            KEY          post_id (post_id)
        ) {$charset_collate};";

        dbDelta($sql_claps);

        // Follows table
        $follows_table = $wpdb->prefix . 'narrato_follows';
        $sql_follows = "CREATE TABLE IF NOT EXISTS {$follows_table} (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id      BIGINT(20) UNSIGNED NOT NULL,
            follow_type  ENUM('author','topic','story') NOT NULL,
            object_id    BIGINT(20) UNSIGNED NOT NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   user_object (user_id, follow_type, object_id),
            KEY          object_lookup (follow_type, object_id),
            KEY          user_id (user_id)
        ) {$charset_collate};";

        dbDelta($sql_follows);

        // Notifications Table
        $notif_table = $wpdb->prefix . 'narrato_notifications';
        $sql_notif = "CREATE TABLE IF NOT EXISTS {$notif_table} (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id      BIGINT(20) UNSIGNED NOT NULL,
            type         VARCHAR(32) NOT NULL,
            actor_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            object_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            is_read      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY          user_unread (user_id, is_read),
            KEY          created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql_notif);

        update_option('narrato_db_version', '1.2.0');
    }

    private static function create_default_topics(): void
    {
        if (!taxonomy_exists('narrato_topic')) {
            register_taxonomy('narrato_topic', ['narrato_story'], [
                'label'         => __('Topics', 'narrato-for-writers'),
                'public'        => true,
                'hierarchical'  => true,
                'show_ui'       => true,
                'show_in_rest'  => true,
            ]);
        }

        $default_topics = ['Technology', 'Health', 'Travel', 'Food', 'Culture'];
        foreach ($default_topics as $topic) {
            if (!term_exists($topic, 'narrato_topic')) {
                wp_insert_term($topic, 'narrato_topic');
            }
        }
    }
}
