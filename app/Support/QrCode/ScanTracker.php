<?php

namespace App\Support\QrCode;

use App\Models\ToolCategory;
use App\Models\TrackedQrCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScanTracker
{
    public function premium(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        $category = ToolCategory::query()->where('slug', 'business-tools')->first();
        if (! $category) {
            return false;
        }

        return $user->toolSubscriptions()
            ->where('tool_category_id', $category->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    public function link(string $editorToken, string $type, string $content, ?int $userId): string
    {
        return route('qr.scan', ['token' => $this->code($editorToken, $type, $content, $userId)->token]);
    }

    public function code(string $editorToken, string $type, string $content, ?int $userId): TrackedQrCode
    {
        // Editing the content creates a different code; design edits preserve the original scan link.
        $token = substr(hash_hmac('sha256', json_encode([$editorToken, $type, $content, $userId]), config('app.key')), 0, 40);

        return TrackedQrCode::firstOrCreate(['token' => $token], [
            'user_id' => $userId, 'content_type' => $type, 'content' => $content,
        ]);

    }

    public function record(TrackedQrCode $code): bool
    {
        $date = now()->timezone(config('qr.scan_timezone'))->toDateString();
        $premium = $this->premium($code->user);

        return DB::transaction(function () use ($code, $date, $premium) {
            DB::table('qr_daily_scans')->insertOrIgnore([
                'tracked_qr_code_id' => $code->id, 'scan_date' => $date, 'scan_count' => 0,
            ]);
            $counter = DB::table('qr_daily_scans')->where('tracked_qr_code_id', $code->id)->where('scan_date', $date);
            if (! $premium) {
                $counter->where('scan_count', '<', max(0, (int) config('qr.free_daily_scan_limit')));
            }

            return $counter->increment('scan_count') === 1;
        }, 3);
    }
}
