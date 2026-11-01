<?php

namespace App\Support\Seo;

use App\Models\ToolCategory;
use App\Models\ToolPlan;
use App\Models\ToolSubscription;
use App\Models\User;

class Entitlements
{
    public function premium(?User $user): bool
    {
        return $this->subscription($user) !== null;
    }

    private function subscription(?User $user): ?ToolSubscription
    {
        $category = ToolCategory::where('slug', 'seo-tools')->first();

        return $user && $category
            ? $user->toolSubscriptions()->with('toolPlan')->where('tool_category_id', $category->id)->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest()->first()
            : null;
    }

    public function dailyLimit(?User $user): int
    {
        return max(0, (int) ($this->subscription($user)?->toolPlan?->max_file_upload ?? $this->freeDailyLimit()));
    }

    public function freeDailyLimit(): int
    {
        return max(0, (int) (ToolCategory::where('slug', 'seo-tools')->value('free_max_file_upload') ?? 3));
    }

    public function upgradePlan(): ?ToolPlan
    {
        return ToolCategory::where('slug', 'seo-tools')->first()?->activePlans()->first();
    }
}
