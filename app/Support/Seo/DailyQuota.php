<?php

namespace App\Support\Seo;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DailyQuota
{
    public function __construct(private Entitlements $entitlements) {}

    public function reserve(?User $user, string $ip): void
    {
        $premium = $this->entitlements->premium($user);
        $actors = $this->actors($user, $ip, $premium);
        $limit = $this->entitlements->dailyLimit($user);
        $date = $this->date();
        DB::transaction(function () use ($actors, $limit, $date) {
            foreach ($actors as $actor) {
                DB::table('seo_daily_usages')->insertOrIgnore(['actor_key' => $actor, 'usage_date' => $date, 'checks' => 0]);
                $updated = DB::table('seo_daily_usages')->where('actor_key', $actor)->where('usage_date', $date)->where('checks', '<', $limit)->increment('checks');
                if ($updated !== 1) {
                    throw new SeoException('Your daily SEO check limit has been reached. Your allowance resets at midnight.');
                }
            }
        }, 3);
    }

    public function remaining(?User $user, string $ip): int
    {
        $premium = $this->entitlements->premium($user);
        $used = DB::table('seo_daily_usages')->whereIn('actor_key', $this->actors($user, $ip, $premium))->where('usage_date', $this->date())->max('checks') ?? 0;

        return max(0, $this->entitlements->dailyLimit($user) - $used);
    }

    public function resetAt(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->addDay()->startOfDay();
    }

    private function date(): string
    {
        return CarbonImmutable::now(config('app.timezone'))->toDateString();
    }

    private function actors(?User $user, string $ip, bool $premium): array
    {
        $actors = $user ? ['user:'.$user->id] : [];
        if (! $premium) {
            $actors[] = 'ip:'.hash_hmac('sha256', $ip, config('app.key'));
        }
        sort($actors);

        return $actors;
    }
}
