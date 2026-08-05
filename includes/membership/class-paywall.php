<?php

namespace Narrato\Membership;

defined('ABSPATH') || exit;

final class Paywall
{

    private const META_KEY_READS = 'narrato_metered_reads';

    public function register(): void
    {
        add_action('template_redirect', [$this, 'track_metered_read']);
    }

    // Core check — called from single-story.php

    public static function can_read(int $post_id, int $user_id = 0): bool
    {

        $paywall_type = get_post_meta($post_id, '_narrato_paywall_type', true) ?: 'none';

        if ($paywall_type === 'none') return true;

        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        // Member can always read
        if (Membership::is_member($user_id)) {
            return true;
        }

        // Story author can always read their own story
        if ($user_id && (int) get_post_field('post_author', $post_id) === $user_id) {
            return true;
        }

        if ($paywall_type === 'hard') {
            return false;
        }

        if ($paywall_type === 'metered') {
            return self::has_metered_reads_remaining($post_id);
        }

        return true;
    }

    public static function get_paywall_type(int $post_id): string
    {
        return get_post_meta($post_id, '_narrato_paywall_type', true) ?: 'none';
    }

    // Metered read tracking
    private static function current_period_key(): string
    {
        return gmdate('Y-m');
    }

    private static function get_read_story_ids(): array
    {
        $user_id = get_current_user_id();
        $period = self::current_period_key();

        if ($user_id) {
            $data = get_user_meta($user_id, self::META_KEY_READS, true);
            $data = is_array($data) ? $data : [];
            return $data[$period] ?? [];
        }

        // Guest - Cookie based
        if (empty($_COOKIE['narrato_metered'])) {
            return [];
        }

        $decoded = json_decode(stripslashes($_COOKIE['narrato_metered']), true);
        if (! is_array($decoded) || ($decoded['period'] ?? '') !== $period) {
            return [];
        }

        return array_map('intval', $decoded['ids'] ?? []);
    }

    private static function save_read_story_ids(array $ids): void
    {
        $user_id = get_current_user_id();
        $period = self::current_period_key();

        if ($user_id) {
            $data = get_user_meta($user_id, self::META_KEY_READS, true);
            $data = is_array($data) ? $data : [];

            $data = [$period => $ids];
            update_user_meta($user_id, self::META_KEY_READS, $data);
            return;
        }

        // Guest — cookie, 32 days
        setcookie(
            'narrato_metered',
            wp_json_encode(['period' => $period, 'ids' => $ids]),
            time() + (32 * DAY_IN_SECONDS),
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }

    public static function has_metered_reads_remaining(int $post_id): bool
    {
        $free_limit = (int) \Narrato\Admin\Settings::get_options()['metered_free_reads'];
        $read_ids = self::get_read_story_ids();

        // Already read this story this period
        if (in_array($post_id, $read_ids, true)) {
            return true;
        }

        return count($read_ids) < $free_limit;
    }

    public static function get_reads_remaining(): int
    {
        $free_limit = (int) \Narrato\Admin\Settings::get_options()['metered_free_reads'];
        $read_ids   = self::get_read_story_ids();
        return max(0, $free_limit - count($read_ids));
    }

    public function track_metered_read(): void
    {
        if (! is_singular('narrato-story')) return;

        $post_id = get_the_ID();
        $paywall_type = self::get_paywall_type($post_id);

        if ($paywall_type !== 'metered') return;
        if (Membership::is_member()) return;

        $user_id = get_current_user_id();
        if ($user_id && (int) get_post_field('post_author', $post_id) === $user_id) return;

        $read_ids = self::get_read_story_ids();

        if (! in_array($post_id, $read_ids, true)) {
            $read_ids[] = $post_id;
            self::save_read_story_ids($read_ids);
        }
    }
}
