<?php

namespace Narrato\Membership;

defined('ABSPATH') || exit;

final class Membership
{

    public function register(): void {}

    // Static Helpers
    public static function is_member(int $user_id = 0): bool
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }
        if (! $user_id) return false;

        $row = self::get_membership_row($user_id);
        if (! $row) return false;

        if ($row['status'] !== 'active') return false;

        // Belt-and-suspenders: if period end has passed and we haven't
        // heard from a webhook yet, treat as expired.
        if ($row['current_period_end'] && strtotime($row['current_period_end']) < current_time('timestamp', true)) {
            return false;
        }

        return true;
    }

    public static function get_membership_row(int $user_id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_memberships';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        return $row ?: null;
    }

    public static function upsert_membership(int $user_id, string $plan, string $gateway, string $gateway_sub_id, string $status, ?string $period_end): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_memberships';

        $existing = self::get_membership_row($user_id);

        $data = [
            'user_id'             => $user_id,
            'plan'                => $plan,
            'gateway'             => $gateway,
            'gateway_sub_id'      => $gateway_sub_id,
            'status'              => $status,
            'current_period_end'  => $period_end,
        ];

        if ($existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update($table, $data, ['user_id' => $user_id]);
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert($table, $data);
        }
    }

    public static function set_status(int $user_id, string $status): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_memberships';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update($table, ['status' => $status], ['user_id' => $user_id]);
    }

    public static function find_user_by_subscription(string $gateway, string $gateway_sub_id): ?int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_memberships';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE gateway = %s AND gateway_sub_id = %s",
            $gateway,
            $gateway_sub_id
        ));

        return $user_id ? (int) $user_id : null;
    }

    public static function log_transaction(int $user_id, string $gateway, string $gateway_txn_id, float $amount, string $currency, string $status, string $plan): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'narrato_transactions';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert($table, [
            'user_id'        => $user_id,
            'gateway'        => $gateway,
            'gateway_txn_id' => $gateway_txn_id,
            'amount'         => $amount,
            'currency'       => $currency,
            'status'         => $status,
            'plan'           => $plan,
        ]);
    }
}
