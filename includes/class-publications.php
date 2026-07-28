<?php

namespace Narrato;

defined('ABSPATH') || exit;

final class Publications
{

    public function register(): void
    {
        (new Publications\PublicationCPT())->register();
        (new Publications\Editors())->register();
        (new Publications\Submissions())->register();
    }
}
