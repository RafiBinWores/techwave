<?php

use App\Models\TrackedQrCode;
use App\Support\QrCode\ScanTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('QR Code Records')] class extends Component {
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function with(): array
    {
        abort_unless(auth()->check(), 403);
        $userId = auth()->id();
        $today = now()->timezone(config('qr.scan_timezone'))->toDateString();
        $records = TrackedQrCode::query()->where('user_id', $userId)
            ->withSum('dailyScans as total_scans', 'scan_count')
            ->withSum(['dailyScans as today_scans' => fn ($query) => $query->where('scan_date', $today)], 'scan_count')
            ->latest()->orderByDesc('id')->paginate(12);
        $totals = DB::table('qr_daily_scans')
            ->join('tracked_qr_codes', 'tracked_qr_codes.id', '=', 'qr_daily_scans.tracked_qr_code_id')
            ->where('tracked_qr_codes.user_id', $userId)
            ->selectRaw('SUM(scan_count) as total, SUM(CASE WHEN scan_date = ? THEN scan_count ELSE 0 END) as today', [$today])
            ->first();

        return [
            'records' => $records,
            'totalCodes' => $records->total(),
            'totalScans' => (int) ($totals->total ?? 0),
            'todayScans' => (int) ($totals->today ?? 0),
            'premium' => app(ScanTracker::class)->premium(auth()->user()),
        ];
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/6 shadow-2xl backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">
                <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" style="display:none;"></div>
                <livewire:shared.user-sidebar />
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="sidebarOpen = true" aria-label="Open sidebar" class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/5 lg:hidden">
                                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
                            </button>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">QR Code <span class="text-cyan-200">Records</span></h1>
                                <p class="mt-2 text-sm text-blue-100/50">Your QR codes and their scan activity.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-xs font-medium text-blue-100/70 hover:bg-white/10 disabled:opacity-50">Refresh</button>
                            <a href="{{ route('client.tools.qr-code-generator') }}" wire:navigate class="rounded-xl bg-linear-to-r from-cyan-600 to-blue-600 px-4 py-2.5 text-xs font-semibold text-white">Create QR code</a>
                        </div>
                    </div>

                    <div class="mb-6 grid gap-3 sm:grid-cols-3">
                        @foreach (['QR codes' => $totalCodes, 'Total scans' => $totalScans, 'Scans today' => $todayScans] as $label => $value)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                            <p class="text-xs font-medium text-blue-100/50">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-bold text-cyan-100">{{ number_format($value) }}</p>
                        </div>
                        @endforeach
                    </div>

                    <p class="mb-5 text-xs leading-5 text-blue-100/45">
                        {{ $premium ? 'Your premium plan includes unlimited scans.' : 'Each free QR code allows '.config('qr.free_daily_scan_limit').' scans per day.' }}
                        Today follows {{ config('qr.scan_timezone') }} time. Opening a QR scan link counts as a scan.
                    </p>

                    <div class="space-y-3">
                        @forelse ($records as $record)
                        <article wire:key="qr-record-{{ $record->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 sm:p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="rounded-md bg-cyan-400/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-cyan-200">{{ $record->content_type }}</span>
                                        <span class="text-xs text-blue-100/35">{{ $record->created_at->timezone(config('qr.scan_timezone'))->format('j M Y, g:i A') }}</span>
                                    </div>
                                    <h2 class="break-words text-sm font-semibold text-white">{{ $record->content_type === 'wifi' ? 'Wi-Fi QR code' : Str::limit($record->content, 90) }}</h2>
                                    <label class="mt-3 block">
                                        <span class="text-[10px] text-blue-100/40">Scan link — select to copy</span>
                                        <input readonly @focus="$event.target.select()" value="{{ route('qr.scan', ['token' => $record->token]) }}" aria-label="Scan link for QR {{ $record->id }}"
                                            class="mt-1 w-full min-w-0 rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-xs text-blue-100/60 outline-none focus:border-cyan-300/40">
                                    </label>
                                </div>
                                <div class="grid shrink-0 grid-cols-2 gap-3 sm:w-44">
                                    <div class="rounded-xl bg-black/20 p-3 text-center">
                                        <p class="text-xl font-bold text-cyan-200">{{ number_format((int) $record->total_scans) }}</p>
                                        <p class="mt-1 text-[10px] text-blue-100/45">Total scans</p>
                                    </div>
                                    <div class="rounded-xl bg-black/20 p-3 text-center">
                                        <p class="text-xl font-bold text-white">{{ number_format((int) $record->today_scans) }}</p>
                                        <p class="mt-1 text-[10px] text-blue-100/45">Today</p>
                                    </div>
                                </div>
                            </div>
                        </article>
                        @empty
                        <div class="rounded-2xl border border-dashed border-white/15 bg-white/3 px-5 py-14 text-center">
                            <span class="material-symbols-outlined text-5xl text-cyan-200/50" aria-hidden="true">qr_code_2</span>
                            <h2 class="mt-4 text-lg font-semibold">No QR codes yet</h2>
                            <p class="mt-2 text-sm text-blue-100/45">Create a QR code while signed in to see its records here.</p>
                        </div>
                        @endforelse
                    </div>
                    <div class="mt-6">{{ $records->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
