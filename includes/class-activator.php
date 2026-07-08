<?php

namespace Narrato;

defined( 'ABSPATH' ) || exit;

final class Activator{
    public static function run() : void {
        self::create_tables();
        self::create_default_topics();
        flush_rewrite_rules();
    }

    public static function create_tables() : void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'narrato_claps';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('narrato_db_version', '1.1.0');

    }

    private static function create_default_topics() : void {
        if(!taxonomy_exists('narrato_topic')){
            register_taxonomy('narrato_topic', ['narrato_story'], [
                'label' => __('Topics', 'narrato-for-writers'),
                'public' => true,
                'hierarchical' => true,
                'show_ui' => true,
                'show_in_rest' => true,
            ]);
        }

        $default_topics = ['Technology', 'Health', 'Travel', 'Food', 'Culture'];
        foreach($default_topics as $topic){
            if(!term_exists($topic, 'narrato_topic')){
                wp_insert_term($topic, 'narrato_topic');
            }
        }
    }
}