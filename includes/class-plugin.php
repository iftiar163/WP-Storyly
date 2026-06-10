<?php

namespace Storyly;

defined('ABSPATH') || exit;

final class Plugin
{
    public function init(): void
    {
        // Initialize plugin components here
        $this->register_services();
    }

    private function register_services(): void
    {
        (new CPT\Story())->register();
        (new Taxonomy\Topic())->register();
        (new Meta\Story())->register();
        (new Assets())->register();
        (new Admin\Settings())->register();
    }
}
