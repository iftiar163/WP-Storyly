<?php

namespace Storyly;

defined( 'ABSPATH' ) || exit;

final class Activator{
    public static function run() : void {
        self::create_default_topics();
        flush_rewrite_rules();
    }

    private static function create_default_topics() : void {
        if(!taxonomy_exists('storyly_topics')){
            register_taxonomy('storyly_topics', ['storyly'], [
                'label' => __('Topics', 'storyly'),
                'public' => true,
                'hierarchical' => true,
                'show_ui' => true,
                'show_in_rest' => true,
            ]);
        }

        $default_topics = ['Technology', 'Health', 'Travel', 'Food', 'Culture'];
        foreach($default_topics as $topic){
            if(!term_exists($topic, 'storyly_topics')){
                wp_insert_term($topic, 'storyly_topics');
            }
        }
    }
}