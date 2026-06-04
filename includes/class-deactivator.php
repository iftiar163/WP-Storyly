<?php

namespace WPStoryly;

defined( 'ABSPATH' ) || exit;

final class Deactivator {
    public static function run() : void {
        flush_rewrite_rules();
    }
}