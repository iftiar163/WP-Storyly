<?php

namespace Narrato;

defined( 'ABSPATH' ) || exit;

final class Activator{
    public static function run() : void {
        self::create_default_topics();
        flush_rewrite_rules();
    }

    private static function create_default_topics() : void {
        if(!taxonomy_exists('narrato_topic')){
            register_taxonomy('narrato_topic', ['story'], [
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