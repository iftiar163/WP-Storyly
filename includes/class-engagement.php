<?php

namespace Narrato;

defined( 'ABSPATH' ) || exit;

final class Engagement {
    
    public function register() : void {
        (new Engagement\Claps())->register();
        (new Engagement\Bookmarks())->register();
    }
}