<?php

namespace Narrato;

defined('ABSPATH') || exit;

final class Social
{

    public function register(): void
    {
        (new Social\Follows())->register();
        (new Social\Notifications())->register();
        (new Social\Profile())->register();
    }
}
