<?php

use App\Models\WhmcsAccount;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Billing Invoices')] class extends Component {
    use WithPagination;

    /** @var array<int, array<string, mixed>> */
    public array $invoices = [];

    public bool $isLinked = false;

    public int $perPage = 10;

    public function mount(): void
    {
        $account = Auth::user()->whmcsAccount;

        if (! $account) {
            $this->isLinked = false;

            return;
        }

        $this->isLinked = true;

        $cacheKey = 'whmcs-invoices:' . Auth::id();
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $this->invoices = $cached;

            return;
        }

        try {
            $api = app(WhmcsApi::class);
            $invoices = $api->getInvoices($account->whmcs_user_id);
        } catch (WhmcsApiException) {
            $invoices = [];
        }

        usort($invoices, fn($a, $b) => strcmp((string) ($b['id'] ?? 0), (string) ($a['id'] ?? 0)));

        $this->invoices = array_slice($invoices, 0, 50);

        Cache::put($cacheKey, $this->invoices, now()->addMinutes(10));
    }

    public function paginatedInvoices(): LengthAwarePaginator
    {
        $items = collect($this->invoices);
        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $this->perPage)->values(),
            $items->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }

    public function formatMoney(null|string|int|float $amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return number_format((float) $amount, 2) . ' ' . config('app.currency_code', 'BDT');
    }

    public function formatDate(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function invoiceStatusClass(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'paid' => 'client-badge client-badge-green',
            'unpaid' => 'client-badge client-badge-yellow',
            'overdue', 'collections' => 'client-badge client-badge-red border border-rose-300/30 bg-rose-400/10 text-rose-300',
            'cancelled', 'refunded' => 'client-badge client-badge-red border border-rose-300/30 bg-rose-400/10 text-rose-300',
            default => 'client-badge client-badge-blue',
        };
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <livewire:shared.font-toast-notification />
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="sidebarOpen = true"
                                class="cursor-pointer flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <span class="cursor-pointer material-symbols-outlined">menu</span>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Billing</p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">Invoices</h1>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 backdrop-blur-xl">
                            <span
                                class="material-symbols-outlined {{ $isLinked ? 'text-emerald-300' : 'text-cyan-300' }}">
                                {{ $isLinked ? 'link' : 'link_off' }}
                            </span>
                            <div>
                                <p class="text-xs text-blue-100/45">Billing Account</p>
                                <p class="text-sm font-semibold {{ $isLinked ? 'text-emerald-300' : 'text-blue-100/70' }}">
                                    {{ $isLinked ? 'Linked' : 'Not linked' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if (! $isLinked)
                    {{-- Not linked state --}}
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-12 text-center shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                            <span class="material-symbols-outlined text-3xl">link_off</span>
                        </div>
                        <h2 class="mt-6 text-2xl font-bold text-white">No billing account linked</h2>
                        <p class="mt-3 text-sm text-blue-100/55">
                            Link your WHMCS billing account to view invoices, services, and billing details.
                        </p>
                        <a href="{{ route('account.link-whmcs') }}" wire:navigate
                            class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 hover:shadow-cyan-500/35">
                            <span class="material-symbols-outlined text-lg">link</span>
                            Link Billing Account
                        </a>
                    </div>
                    @else
                    {{-- Invoices table --}}
                    <div
                        class="overflow-hidden rounded-[28px] border border-white/10 bg-white/8 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="flex items-center justify-between p-6 pb-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Billing Data
                                </p>
                                <h2 class="mt-1 text-xl font-bold text-white">All Invoices</h2>
                            </div>
                            <span class="text-xs text-blue-100/45">{{ count($invoices) }} total</span>
                        </div>

                        @if (count($invoices))
                        <div class="overflow-x-auto px-2 pb-4">
                            <table class="w-full min-w-[680px] text-left text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-blue-100/40">
                                        <th class="px-4 py-3 font-semibold">Invoice</th>
                                        <th class="px-4 py-3 font-semibold">Created</th>
                                        <th class="px-4 py-3 font-semibold">Due Date</th>
                                        <th class="px-4 py-3 font-semibold">Total</th>
                                        <th class="px-4 py-3 font-semibold">Status</th>
                                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->paginatedInvoices() as $invoice)
                                    <tr class="border-t border-white/8 hover:bg-white/4">
                                        <td class="px-4 py-4 font-semibold text-white">
                                            #{{ data_get($invoice, 'id') }}
                                        </td>
                                        <td class="px-4 py-4 text-blue-100/65">
                                            {{ $this->formatDate(data_get($invoice, 'date')) }}
                                        </td>
                                        <td class="px-4 py-4 text-blue-100/65">
                                            {{ $this->formatDate(data_get($invoice, 'duedate')) }}
                                        </td>
                                        <td class="px-4 py-4 font-semibold text-white">
                                            {{ $this->formatMoney(data_get($invoice, 'total')) }}
                                        </td>
                                        <td class="px-4 py-4">
                                            <span
                                                class="{{ $this->invoiceStatusClass(data_get($invoice, 'status')) }}">
                                                {{ ucfirst(strtolower((string) data_get($invoice, 'status', 'Unknown'))) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                @if (data_get($invoice, 'invoiceurl'))
                                                <a href="{{ data_get($invoice, 'invoiceurl') }}" target="_blank" rel="noopener"
                                                    class="cursor-pointer inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/8 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/12">
                                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                                    View
                                                </a>
                                                @endif
                                                <a href="{{ route('account.invoice.download', data_get($invoice, 'id')) }}" target="_blank" rel="noopener"
                                                    class="cursor-pointer inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/8 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/12">
                                                    <span class="material-symbols-outlined text-sm">download</span>
                                                    PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($this->paginatedInvoices()->hasPages())
                        <div class="px-6 pb-5">
                            {{ $this->paginatedInvoices()->links() }}
                        </div>
                        @endif
                        @else
                        <div class="px-6 pb-6">
                            <div
                                class="rounded-2xl border border-white/10 bg-white/4 px-5 py-8 text-center text-sm text-blue-100/55">
                                No invoices found on this billing account.
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>