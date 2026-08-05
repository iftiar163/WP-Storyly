<?php

namespace Narrato;

defined('ABSPATH') || exit;

final class MembershipBootstrap
{

    public function register(): void
    {
        (new Membership\Membership())->register();
        (new Membership\Paywall())->register();
    }
}
