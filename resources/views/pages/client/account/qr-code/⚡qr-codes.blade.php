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
            ->select(['id', 'token', 'user_id', 'content_type', 'content', 'created_at'])
            ->selectRaw('svg IS NOT NULL as has_saved_design')
            ->withSum('dailyScans as total_scans', 'scan_count')
            ->withSum(['dailyScans as today_scans' => fn($query) => $query->where('scan_date', $today)], 'scan_count')
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
                        <div class="hidden md:flex">
                            <a href="{{ route('client.tools.qr-code-generator') }}" wire:navigate class="rounded-xl bg-linear-to-r from-cyan-600 to-blue-600 px-4 py-2.5 text-xs font-semibold text-white">Create QR code</a>
                        </div>
                    </div>

                    <div class="mb-6 grid gap-3 grid-cols-3">
                        @foreach (['QR codes' => $totalCodes, 'Total scans' => $totalScans, 'Scans today' => $todayScans] as $label => $value)
                        <div class="rounded-xl md:rounded-2xl border border-white/10 bg-white/5 p-2.5 md:p-5">
                            <p class="text-xs font-medium text-blue-100/50">{{ $label }}</p>
                            <p class="mt-2 text-lg md:text-3xl font-bold text-cyan-100">{{ number_format($value) }}</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-xl border border-white/10 bg-white/3">
                        <div class="overflow-x-auto" role="region" aria-label="QR code records" tabindex="0">
                            <table class="w-full min-w-[600px] text-left text-sm">
                                <caption class="sr-only">Your QR codes, scan counts, and downloads</caption>
                                <thead class="border-b border-white/10 bg-white/5 text-[11px] font-medium uppercase tracking-wide text-blue-100/45">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-medium">QR details</th>
                                        <th scope="col" class="w-24 px-3 py-3 text-right font-medium whitespace-nowrap">Total scans</th>
                                        <th scope="col" class="w-20 px-3 py-3 text-right font-medium">Today</th>
                                        <th scope="col" class="w-40 px-4 py-3 text-right font-medium">Download</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    @forelse ($records as $record)
                                    <tr wire:key="qr-record-{{ $record->id }}" class="transition-colors hover:bg-white/5">
                                        <td class="max-w-xs px-4 py-3 align-middle">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded bg-cyan-400/10 px-1.5 py-0.5 text-[10px] font-medium uppercase text-cyan-200">{{ $record->content_type }}</span>
                                                <span class="text-[11px] text-blue-100/35">{{ $record->created_at->timezone(config('qr.scan_timezone'))->format('j M Y, g:i A') }}</span>
                                            </div>
                                            <p class="mt-1 truncate text-sm font-medium text-white" title="{{ $record->content_type === 'wifi' ? 'Wi-Fi QR code' : $record->content }}">{{ $record->content_type === 'wifi' ? 'Wi-Fi QR code' : Str::limit($record->content, 90) }}</p>
                                            <input readonly @focus="$event.target.select()" value="{{ route('qr.scan', ['token' => $record->token]) }}" aria-label="Scan link for QR {{ $record->id }}" title="Select to copy scan link"
                                                class="mt-1 block w-full min-w-0 truncate rounded border border-transparent bg-transparent py-0.5 text-[11px] text-blue-100/40 outline-none focus:border-cyan-300/40 focus:bg-black/20">
                                        </td>
                                        <td class="px-3 py-3 text-right align-middle font-semibold tabular-nums text-cyan-100">{{ number_format((int) $record->total_scans) }}</td>
                                        <td class="px-3 py-3 text-right align-middle font-medium tabular-nums text-blue-100/70">{{ number_format((int) $record->today_scans) }}</td>
                                        <td class="px-4 py-3 text-right align-middle" x-data="qrRecordDownloader(@js(route('account.qr-codes.image', $record->id)))">
                                            <button type="button" @click="download()" :disabled="busy" aria-label="Download QR {{ $record->id }} as PNG" class="inline-flex min-h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-cyan-300/15 bg-cyan-400/10 px-3 py-2 text-xs font-medium text-cyan-100 transition hover:bg-cyan-400/20 focus-visible:outline-2 focus-visible:outline-cyan-300 disabled:opacity-50">
                                                <span class="material-symbols-outlined text-base" :class="{ 'animate-spin': busy }" aria-hidden="true" x-text="busy ? 'progress_activity' : 'download'">download</span>
                                                <span x-text="busy ? 'Downloading…' : 'Download PNG'">Download PNG</span>
                                            </button>
                                            @unless ($record->has_saved_design)
                                            <p title="Original design unavailable for this older record" class="mt-1 text-[10px] text-blue-100/35">Standard design</p>
                                            @endunless
                                            <p x-show="error" x-text="error" role="alert" style="display:none" class="mt-1 max-w-40 whitespace-normal text-xs text-red-300"></p>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-12 text-center">
                                            <span class="material-symbols-outlined text-4xl text-cyan-200/50" aria-hidden="true">qr_code_2</span>
                                            <p class="mt-3 font-medium text-white">No QR codes yet</p>
                                            <p class="mt-1 text-xs text-blue-100/45">Create a QR code while signed in to see its records here.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-6">{{ $records->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@assets
<script>
    @php
    echo file_get_contents(resource_path('js/qr-designer.js'));
    @endphp
</script>
@endassets