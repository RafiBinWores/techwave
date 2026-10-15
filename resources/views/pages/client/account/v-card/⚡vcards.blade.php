<?php

use App\Models\Vcard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My vCards')] class extends Component {
    public function mount(): void
    {
        abort_if(!Auth::check(), 403);
    }

    public function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function timeAgo($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->diffForHumans();
    }

    public function deleteVcard(int $id): void
    {
        $vcard = Vcard::query()->where('user_id', auth()->id())->findOrFail($id);
        $vcard->delete();

        $this->dispatch('toast', type: 'success', message: 'vCard deleted successfully.');
    }

    public function with(): array
    {
        $userId = Auth::id();

        $vcards = Vcard::query()
            ->where('user_id', $userId)
            ->withCount('scans')
            ->latest()
            ->get()
            ->map(function (Vcard $vcard): array {
                $weeklyScans = $vcard
                    ->scans()
                    ->where('created_at', '>=', now()->startOfWeek())
                    ->count();

                $publicUrl = route('vcard.public.show', ['slug' => $vcard->slug]);

                $firstScan = $vcard->scans()->oldest()->first();

                return [
                    'id' => $vcard->id,
                    'name' => $vcard->name ?: $vcard->full_name,
                    'slug' => $vcard->slug,
                    'is_active' => (bool) $vcard->is_active,
                    'scans_count' => (int) ($vcard->scans_count ?? 0),
                    'weekly_scans' => $weeklyScans,
                    'created_at' => $vcard->created_at,
                    'first_scan_at' => $firstScan?->created_at,
                    'public_url' => $publicUrl,
                ];
            })->toArray();

        $totalVcards = count($vcards);
        $totalScans = array_sum(array_column($vcards, 'scans_count'));
        $activeCount = count(array_filter($vcards, fn($v) => $v['is_active']));

        return [
            'vcards' => $vcards,
            'totalVcards' => $totalVcards,
            'totalScans' => $totalScans,
            'activeCount' => $activeCount,
        ];
    }
};
?>

<div x-data="{ sidebarOpen: false, confirmDeleteId: null, confirmDeleteName: '' }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-2xl backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                <livewire:shared.user-sidebar />

                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="sidebarOpen = true" aria-label="Open sidebar" class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/5 lg:hidden">
                                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
                            </button>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">My <span class="text-cyan-200">vCards</span></h1>
                                <p class="mt-2 text-sm text-blue-100/50">Your digital business cards activity.</p>
                            </div>
                        </div>
                        <a href="{{ route('client.tools.vcard-generator') }}" wire:navigate class="rounded-xl bg-linear-to-r from-cyan-600 to-blue-600 px-4 py-2.5 text-xs font-semibold text-white hidden md:block">Create vCard</a>
                    </div>

                    <div class="mb-6 grid gap-3 grid-cols-3">
                        @foreach (['Total vCards' => $totalVcards, 'Total scans' => $totalScans, 'Active vCards' => $activeCount] as $label => $value)
                        <div class="rounded-xl md:rounded-2xl border border-white/10 bg-white/5 p-2.5 md:p-5">
                            <p class="text-xs font-medium text-blue-100/50">{{ $label }}</p>
                            <p class="mt-2 text-lg md:text-3xl font-bold text-cyan-100">{{ number_format($value) }}</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-xl border border-white/10 bg-white/3">
                        <div class="overflow-x-auto" role="region" aria-label="vCard records" tabindex="0">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <caption class="sr-only">Your vCards, status, scan counts, and actions</caption>
                                <thead class="border-b border-white/10 bg-white/5 text-[11px] font-medium uppercase tracking-wide text-blue-100/45">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-medium">vCard details</th>
                                        <th scope="col" class="px-3 py-3 font-medium">Status</th>
                                        <th scope="col" class="px-3 py-3 text-right font-medium whitespace-nowrap">Total scans</th>
                                        <th scope="col" class="px-3 py-3 text-right font-medium whitespace-nowrap">This week</th>
                                        <th scope="col" class="px-4 py-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    @forelse ($vcards as $vcard)
                                    <tr wire:key="vcard-record-{{ $vcard['id'] }}" class="transition-colors hover:bg-white/5">
                                        <td class="max-w-xs px-4 py-3 align-middle">
                                            <p class="truncate font-medium text-white" title="{{ $vcard['name'] }}">{{ $vcard['name'] }}</p>
                                            <a href="{{ $vcard['public_url'] }}" target="_blank" rel="noopener" class="mt-1 block truncate text-[11px] text-blue-100/45 hover:text-cyan-200">{{ $vcard['public_url'] }}</a>
                                            <p class="mt-1 text-[10px] text-blue-100/35" title="First scan: {{ $vcard['first_scan_at'] ? $this->formatDate($vcard['first_scan_at']) : 'No scans yet' }}">Created {{ $this->formatDate($vcard['created_at']) }} · First scan {{ $vcard['first_scan_at'] ? $this->timeAgo($vcard['first_scan_at']) : '—' }}</p>
                                        </td>
                                        <td class="px-3 py-3 align-middle">
                                            <span @class(['rounded px-2 py-1 text-[10px] font-medium', 'bg-emerald-400/10 text-emerald-200'=> $vcard['is_active'], 'bg-slate-400/10 text-slate-300' => !$vcard['is_active']])>{{ $vcard['is_active'] ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-right align-middle font-semibold tabular-nums text-cyan-100">{{ number_format($vcard['scans_count']) }}</td>
                                        <td class="px-3 py-3 text-right align-middle font-medium tabular-nums text-blue-100/70">{{ number_format($vcard['weekly_scans']) }}</td>
                                        <td class="px-4 py-3 text-right align-middle">
                                            <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                                <div x-data="qrRecordDownloader(@js(route('account.vcards.qr-image', $vcard['id'])))">
                                                    <button type="button" @click="download()" :disabled="busy" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-cyan-300/15 bg-cyan-400/10 px-3 py-1.5 text-xs font-medium text-cyan-100 hover:bg-cyan-400/20 disabled:opacity-50">
                                                        <span class="material-symbols-outlined text-base" :class="{ 'animate-spin': busy }" aria-hidden="true" x-text="busy ? 'progress_activity' : 'download'">download</span>
                                                        <span x-text="busy ? 'Downloading…' : 'Download PNG'">Download PNG</span>
                                                    </button>
                                                    <p x-show="error" x-text="error" role="alert" style="display:none" class="mt-1 max-w-44 whitespace-normal text-xs text-red-300"></p>
                                                </div>
                                                <a href="{{ route('client.tools.vcard-generator', ['editVcard' => $vcard['id']]) }}" aria-label="Edit {{ $vcard['name'] }}" title="Edit vCard" class="grid h-9 w-9 place-items-center rounded-lg border border-white/10 bg-white/5 text-blue-100/65 hover:bg-white/10 hover:text-white">
                                                    <span class="material-symbols-outlined text-base" aria-hidden="true">edit</span>
                                                </a>
                                                <button type="button" @click="confirmDeleteId = {{ $vcard['id'] }}; confirmDeleteName = @js($vcard['name'])" aria-label="Delete {{ $vcard['name'] }}" title="Delete vCard" class="grid h-9 w-9 place-items-center rounded-lg border border-red-400/15 bg-red-400/5 text-red-200/75 hover:bg-red-400/15">
                                                    <span class="material-symbols-outlined text-base" aria-hidden="true">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-12 text-center">
                                            <span class="material-symbols-outlined text-4xl text-cyan-200/50" aria-hidden="true">contact_page</span>
                                            <p class="mt-3 font-medium text-white">No vCards yet</p>
                                            <p class="mt-1 text-xs text-blue-100/45">Create your first digital business card to get started.</p>
                                            <a href="{{ route('client.tools.vcard-generator') }}" wire:navigate class="mt-4 inline-flex rounded-lg bg-cyan-400/10 px-4 py-2 text-xs font-medium text-cyan-100 hover:bg-cyan-400/20">Create vCard</a>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Popup --}}
    <div x-show="confirmDeleteId"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 backdrop-blur-sm"
        style="display: none;">
        <div class="absolute inset-0" @click="confirmDeleteId = null"></div>
        <div x-show="confirmDeleteId" x-transition.scale.origin.center
            class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-2xl">
            <div class="text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-red-400/20 bg-red-400/10 text-red-300">
                    <span class="material-symbols-outlined text-2xl">delete</span>
                </div>
                <h3 class="mt-4 text-lg font-bold text-white">Delete vCard</h3>
                <p class="mt-2 text-sm text-blue-100/60">
                    Are you sure you want to delete <strong class="text-white" x-text="confirmDeleteName"></strong>? This cannot be undone.
                </p>
            </div>
            <div class="mt-6 flex items-center gap-3">
                <button type="button" @click="confirmDeleteId = null"
                    class="flex-1 rounded-xl border border-white/10 bg-white/8 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/12">
                    Cancel
                </button>
                <button type="button"
                    @click="$wire.deleteVcard(confirmDeleteId); confirmDeleteId = null"
                    class="flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                    Delete
                </button>
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