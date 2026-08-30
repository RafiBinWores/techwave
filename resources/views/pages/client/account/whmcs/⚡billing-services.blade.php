<?php

use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Billing Services')] class extends Component {
    /** @var array<int, array<string, mixed>> */
    public array $services = [];

    public bool $isLinked = false;

    public function mount(): void
    {
        $account = Auth::user()->whmcsAccount;

        if (! $account) {
            $this->isLinked = false;

            return;
        }

        $this->isLinked = true;

        $cacheKey = 'whmcs-services:' . Auth::id();
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $this->services = $cached;

            return;
        }

        try {
            $api = app(WhmcsApi::class);
            $services = $api->getClientProductsXml((int) $account->whmcs_client_id);
        } catch (WhmcsApiException) {
            $services = [];
        }

        $this->services = $services;

        Cache::put($cacheKey, $this->services, now()->addMinutes(10));
    }

    public function formatDate(?string $date): string
    {
        if (! $date || $date === '0000-00-00') {
            return '-';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function formatMoney(null|string|int|float $amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return number_format((float) $amount, 2) . ' ' . config('app.currency_code', 'BDT');
    }

    public function serviceStatusClass(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'active' => 'client-badge client-badge-green',
            'pending' => 'client-badge client-badge-yellow',
            'suspended' => 'client-badge client-badge-red border border-rose-300/30 bg-rose-400/10 text-rose-300',
            'terminated', 'cancelled' => 'client-badge client-badge-red border border-rose-300/30 bg-rose-400/10 text-rose-300',
            default => 'client-badge client-badge-blue',
        };
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <livewire:shared.font-toast-notification />
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
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
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">Services</h1>
                            </div>
                        </div>

                        <!-- <div
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
                        </div> -->
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
                            Link your WHMCS billing account to view services, invoices, and billing details.
                        </p>
                        <a href="{{ route('account.link-whmcs') }}" wire:navigate
                            class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-linear-to-r from-cyan-400 to-blue-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 hover:shadow-cyan-500/35">
                            <span class="material-symbols-outlined text-lg">link</span>
                            Link Billing Account
                        </a>
                    </div>
                    @else
                    {{-- Services list --}}
                    <div
                        class="rounded-2xl border border-white/10 bg-white/8 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">WHMCS</p>
                                <h2 class="mt-1 text-xl font-bold text-white">Billing Services</h2>
                            </div>
                            <span class="text-xs text-blue-100/45">{{ count($this->services) }} total</span>
                        </div>

                        @if (count($this->services))
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-175 text-left text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-blue-100/40">
                                        <th class="px-4 py-3 font-semibold">Service</th>
                                        <th class="px-4 py-3 font-semibold">Billing Cycle</th>
                                        <th class="px-4 py-3 font-semibold">Amount</th>
                                        <th class="px-4 py-3 font-semibold">Next Due</th>
                                        <th class="px-4 py-3 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->services as $service)
                                        @php
                                            $serviceId = data_get($service, 'id');
                                            $serviceName = data_get($service, 'translated_name') ?: data_get($service, 'name') ?: 'Service';
                                            $groupName = data_get($service, 'translated_groupname') ?: data_get($service, 'groupname') ?: '';
                                            $domain = data_get($service, 'domain');
                                            $billingCycle = data_get($service, 'billingcycle');
                                            $nextDueDate = data_get($service, 'nextduedate');
                                            $recurringAmount = data_get($service, 'recurringamount');
                                            $serviceStatus = data_get($service, 'status', 'Active');
                                        @endphp

                                        <tr class="border-t border-white/8 hover:bg-white/4 cursor-pointer" onclick="window.open('{{ route('account.whmcs.service-detail', (int) $serviceId) }}', '_blank')">
                                            <td class="px-4 py-3">
                                                @if ($groupName)
                                                    <p class="text-xs text-blue-100/45">{{ $groupName }}</p>
                                                @endif
                                                <p class="font-semibold text-white">{{ $serviceName }}</p>
                                                @if ($domain)
                                                    <p class="mt-0.5 text-xs text-cyan-200/70">{{ $domain }}</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-blue-100/65">
                                                {{ $billingCycle ? ucfirst((string) $billingCycle) : 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-white">
                                                {{ $this->formatMoney($recurringAmount) }}
                                            </td>
                                            <td class="px-4 py-3 text-blue-100/65">
                                                {{ $this->formatDate($nextDueDate) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="{{ $this->serviceStatusClass($serviceStatus) }}">
                                                    {{ ucfirst(strtolower((string) $serviceStatus)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="rounded-2xl border border-white/10 bg-white/4 px-5 py-8 text-center text-sm text-blue-100/55">
                            No services found on this billing account.
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>