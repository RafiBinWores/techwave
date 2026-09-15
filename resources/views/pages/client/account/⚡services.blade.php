<?php

use App\Models\Booking;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Services')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $activeTab = 'services';

    public array $offerPrice = [];

    public array $negotiationNote = [];

    public function mount(): void
    {
        abort_if(! Auth::check(), 403);

        $tab = request()->query('tab', 'services');

        $this->activeTab = in_array($tab, ['services', 'plans', 'pending'], true) ? $tab : 'services';
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['services', 'plans', 'pending'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    public function formatDate($date): string
    {
        if (! $date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function statusClass(?string $status): string
    {
        return match ($status) {
            'paid' => 'border-emerald-300/20 bg-emerald-400/10 text-emerald-200',
            'active' => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
            'completed' => 'border-purple-300/20 bg-purple-400/10 text-purple-200',
            'awaiting_payment' => 'border-amber-300/20 bg-amber-400/10 text-amber-200',
            'pending' => 'border-slate-300/20 bg-slate-400/10 text-slate-200',
            'cancelled' => 'border-rose-300/20 bg-rose-400/10 text-rose-200',
            default => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
        };
    }

    public function bookingStatusClass(?string $status): string
    {
        return match ($status) {
            'pending' => 'border-amber-300/20 bg-amber-400/10 text-amber-200',
            'quoted' => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
            'accepted' => 'border-emerald-300/20 bg-emerald-400/10 text-emerald-200',
            'converted' => 'border-purple-300/20 bg-purple-400/10 text-purple-200',
            'rejected' => 'border-rose-300/20 bg-rose-400/10 text-rose-200',
            'cancelled' => 'border-slate-300/20 bg-slate-400/10 text-slate-200',
            default => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
        };
    }

    public function statusLabel(?string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status ?: 'pending'));
    }

    public function billingLabel(?string $billing): string
    {
        return match ($billing) {
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'one_time' => 'One-time',
            'custom' => 'Custom',
            default => 'Negotiable',
        };
    }

    public function displayAmount($item): string
    {
        $amount = $item->amount ?? ($item->final_price ?? ($item->quoted_price ?? ($item->requested_price ?? ($item->plan_price ?? 0))));

        if (! $amount || (float) $amount <= 0) {
            return 'Negotiable';
        }

        $currency = ($item->currency ?? 'BDT') === 'BDT' ? '৳' : $item->currency . ' ';

        return $currency . number_format((float) $amount, 2);
    }

    public function orderTitle($order): string
    {
        if ($order->order_type === 'pricing_plan') {
            return $order->pricingPlan?->title ?? ($order->plan_name ?? 'IT Plan');
        }

        return $order->service?->card_title ?? ($order->servicePlan?->name ?? ($order->plan_name ?? 'Service'));
    }

    public function orderDescription($order): ?string
    {
        if ($order->order_type === 'pricing_plan') {
            return $order->pricingPlan?->description;
        }

        return $order->servicePlan?->description ?? ($order->service?->short_description ?? null);
    }

    public function bookingTitle($booking): string
    {
        if ($booking->booking_type === 'pricing_plan') {
            return $booking->pricingPlan?->title ?? ($booking->plan_name ?? 'IT Plan Booking');
        }

        return $booking->service?->card_title ?? ($booking->servicePlan?->name ?? ($booking->plan_name ?? 'Service Booking'));
    }

    public function bookingDescription($booking): ?string
    {
        if ($booking->booking_type === 'pricing_plan') {
            return $booking->pricingPlan?->description;
        }

        return $booking->servicePlan?->description ?? ($booking->service?->short_description ?? null);
    }

    public function acceptQuotation(int $bookingId): void
    {
        $booking = Booking::query()->findOrFail($bookingId);

        abort_unless($booking->user_id === Auth::id(), 403);

        if ($booking->status !== 'quoted') {
            $this->dispatch('toast', message: 'Only quoted bookings can be accepted.', type: 'error');

            return;
        }

        $booking->update([
            'status' => 'accepted',
            'client_responded_at' => now(),
        ]);

        $this->dispatch('toast', message: 'Quotation accepted. Your booking is now confirmed.', type: 'success');
    }

    public function declineQuotation(int $bookingId): void
    {
        $booking = Booking::query()->findOrFail($bookingId);

        abort_unless($booking->user_id === Auth::id(), 403);

        if ($booking->status !== 'quoted') {
            $this->dispatch('toast', message: 'Only quoted bookings can be declined.', type: 'error');

            return;
        }

        $history = $this->appendNegotiationEntry($booking, 'You declined this quotation.');

        $booking->update([
            'status' => 'rejected',
            'negotiation_note' => $history,
            'client_responded_at' => now(),
        ]);

        $this->unsetNegotiationInputs($bookingId);

        $this->dispatch('toast', message: 'Quotation declined.', type: 'success');
    }

    public function sendNegotiation(int $bookingId): void
    {
        $booking = Booking::query()->findOrFail($bookingId);

        abort_unless($booking->user_id === Auth::id(), 403);

        if ($booking->status !== 'quoted') {
            $this->dispatch('toast', message: 'You can only negotiate on a quoted booking.', type: 'error');

            return;
        }

        $offer = $this->offerPrice[$bookingId] ?? null;
        $note = trim((string) ($this->negotiationNote[$bookingId] ?? ''));

        if ($offer !== null && $offer !== '') {
            if (! is_numeric($offer) || (float) $offer < 0) {
                $this->dispatch('toast', message: 'Please enter a valid offer price.', type: 'error');

                return;
            }

            $offer = (float) $offer;
        } else {
            $offer = null;
        }

        if ($offer === null && $note === '') {
            $this->dispatch('toast', message: 'Add a comment or an offer price to negotiate.', type: 'error');

            return;
        }

        $entry = $note !== ''
            ? 'You: ' . $note
            : 'You proposed an offer price.';

        $booking->update([
            'offer_price' => $offer,
            'negotiation_note' => $this->appendNegotiationEntry($booking, $entry),
            'client_responded_at' => now(),
        ]);

        $this->unsetNegotiationInputs($bookingId);

        $this->dispatch('toast', message: 'Negotiation sent. The team will respond shortly.', type: 'success');
    }

    private function appendNegotiationEntry(Booking $booking, string $entry): string
    {
        $timestamp = now()->format('M d, Y h:i A');

        $line = "[{$timestamp}] {$entry}";

        return $booking->negotiation_note
            ? $booking->negotiation_note . "\n" . $line
            : $line;
    }

    private function unsetNegotiationInputs(int $bookingId): void
    {
        unset($this->offerPrice[$bookingId], $this->negotiationNote[$bookingId]);
    }

    public function updatePlanUrl($order): string
    {
        $pricingPlan = $order->pricingPlan;

        if (! $pricingPlan) {
            return Route::has('client.pricing') ? route('client.pricing') : url('/pricing');
        }

        $billing = in_array($order->billing_cycle, ['monthly', 'yearly'], true) ? $order->billing_cycle : 'monthly';

        if (Route::has('client.checkout.pricing')) {
            return route('client.checkout.pricing', [
                'pricingPlan' => $pricingPlan,
                'billing' => $billing,
            ]);
        }

        return url('/checkout/pricing/' . $pricingPlan->id . '?billing=' . $billing);
    }

    public function with(): array
    {
        $userId = Auth::id();

        $serviceOrders = Order::query()
            ->with(['service.category', 'servicePlan', 'booking'])
            ->where('user_id', $userId)
            ->where('order_type', 'service')
            ->whereNotIn('status', ['pending', 'awaiting_payment'])
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('order_no', 'like', '%' . $this->search . '%')
                        ->orWhere('billing_cycle', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhere('plan_name', 'like', '%' . $this->search . '%')
                        ->orWhere('message', 'like', '%' . $this->search . '%')
                        ->orWhere('user_note', 'like', '%' . $this->search . '%')
                        ->orWhereHas('service', function ($serviceQuery) {
                            $serviceQuery->where('card_title', 'like', '%' . $this->search . '%')->orWhere('detail_title', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('servicePlan', function ($servicePlanQuery) {
                            $servicePlanQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate(8);

        $pricingOrders = Order::query()
            ->with(['pricingPlan', 'booking'])
            ->where('user_id', $userId)
            ->where('order_type', 'pricing_plan')
            ->whereNotIn('status', ['pending', 'awaiting_payment'])
            ->latest()
            ->get();

        $serviceBookings = Booking::query()
            ->with(['service', 'servicePlan', 'order'])
            ->where('user_id', $userId)
            ->where('booking_type', 'service')
            ->whereDoesntHave('order')
            ->latest()
            ->get();

        $pricingBookings = Booking::query()
            ->with(['pricingPlan', 'order'])
            ->where('user_id', $userId)
            ->where('booking_type', 'pricing_plan')
            ->whereDoesntHave('order')
            ->latest()
            ->get();

        $pendingOrders = Order::query()
            ->with(['service.category', 'servicePlan', 'pricingPlan', 'booking'])
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->latest()
            ->get();

        $pendingItems = $pendingOrders
            ->map(fn($order) => ['type' => 'order', 'item' => $order])
            ->concat($serviceBookings->map(fn($booking) => ['type' => 'service_booking', 'item' => $booking]))
            ->concat($pricingBookings->map(fn($booking) => ['type' => 'plan_booking', 'item' => $booking]))
            ->sortByDesc(fn($entry) => $entry['item']->created_at)
            ->values();

        $totalServices = Order::query()
            ->where('user_id', $userId)
            ->where('order_type', 'service')
            ->whereNotIn('status', ['pending', 'awaiting_payment'])
            ->count();

        $totalPlans = Order::query()
            ->where('user_id', $userId)
            ->where('order_type', 'pricing_plan')
            ->whereNotIn('status', ['pending', 'awaiting_payment'])
            ->count();

        $pendingCount = $pendingOrders->count() + $serviceBookings->count() + $pricingBookings->count();

        return [
            'serviceOrders' => $serviceOrders,
            'serviceBookings' => $serviceBookings,

            'pricingOrders' => $pricingOrders,
            'pricingBookings' => $pricingBookings,

            'pendingOrders' => $pendingOrders,
            'pendingItems' => $pendingItems,

            'totalServices' => $totalServices,
            'totalPlans' => $totalPlans,
            'pendingCount' => $pendingCount,
        ];
    }
};
?>

<div x-data="{
    sidebarOpen: false,
    showToast: {{ session('success') ? 'true' : 'false' }}
    }" class="relative min-h-screen text-white">
    @if (session('success'))
    <div x-show="showToast" x-init="setTimeout(() => showToast = false, 4500)" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="fixed right-5 top-5 z-100 max-w-md rounded-3xl border border-emerald-300/20 bg-emerald-500/15 p-4 text-emerald-50 shadow-[0_18px_60px_rgba(0,0,0,0.35)] backdrop-blur-2xl">
        <div class="flex items-start gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-emerald-300/20 bg-emerald-400/15 text-emerald-200">
                <span class="material-symbols-outlined">check_circle</span>
            </div>

            <div class="min-w-0">
                <p class="font-bold text-white">Success</p>
                <p class="mt-1 text-sm leading-6 text-emerald-50/80">
                    {{ session('success') }}
                </p>
            </div>

            <button type="button" @click="showToast = false"
                class="ml-2 cursor-pointer rounded-xl p-1 text-emerald-100/70 transition hover:bg-white/10 hover:text-white">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
    </div>
    @endif

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                <livewire:shared.user-sidebar />

                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="sidebarOpen = true"
                                class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <span class="material-symbols-outlined">menu</span>
                            </button>

                            <div>
                                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
                                    My
                                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">Services</span>
                                </h1>
                                <p class="mt-2 text-sm text-blue-100/50">
                                    Manage your active services and plans.
                                </p>
                            </div>
                        </div>

                        @if ($pendingCount > 0)
                        <a href="{{ route('account.services', ['tab' => 'pending']) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-2xl border border-amber-300/25 bg-amber-400/10 px-4 py-2.5 text-sm font-bold text-amber-100 transition hover:bg-amber-400/20">
                            <span class="material-symbols-outlined text-lg">schedule</span>
                            {{ $pendingCount }} pending {{ $pendingCount > 1 ? 'requests' : 'request' }}
                        </a>
                        @endif
                    </div>

                    <div
                        class="mb-6 rounded-2xl border border-white/10 bg-white/8 p-4 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="flex flex-nowrap gap-1.5 overflow-x-auto pb-1 sm:gap-2 sm:overflow-visible">
                            <button type="button" wire:click="setTab('services')"
                                @class([ 'inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-bold transition sm:gap-2 sm:px-3.5 sm:py-2 sm:text-sm' , 'border-cyan-300/50 bg-cyan-400/10 text-white'=> $activeTab === 'services',
                                'border-white/10 bg-white/6 text-blue-100/65 hover:bg-white/10 hover:text-white' => $activeTab !== 'services',
                                ])>
                                <span class="material-symbols-outlined hidden text-base sm:inline-block sm:text-lg">design_services</span>
                                <span class="whitespace-nowrap">Services</span>

                                <span
                                    class="rounded-full border border-white/10 bg-white/8 px-1.5 py-0.5 text-[10px] font-semibold sm:px-2 sm:text-[10px]">
                                    {{ $totalServices }}
                                </span>
                            </button>

                            <button type="button" wire:click="setTab('plans')"
                                @class([ 'inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-bold transition sm:gap-2 sm:px-3.5 sm:py-2 sm:text-sm' , 'border-blue-300/50 bg-blue-400/10 text-white'=> $activeTab === 'plans',
                                'border-white/10 bg-white/6 text-blue-100/65 hover:bg-white/10 hover:text-white' => $activeTab !== 'plans',
                                ])>
                                <span class="material-symbols-outlined hidden text-base sm:inline-block sm:text-lg">workspace_premium</span>
                                <span class="whitespace-nowrap">IT Plans</span>

                                <span
                                    class="rounded-full border border-white/10 bg-white/8 px-1.5 py-0.5 text-[10px] font-semibold sm:px-2 sm:text-[10px]">
                                    {{ $totalPlans }}
                                </span>
                            </button>

                            <button type="button" wire:click="setTab('pending')"
                                @class([ 'inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-bold transition sm:gap-2 sm:px-3.5 sm:py-2 sm:text-sm' , 'border-amber-300/50 bg-amber-400/10 text-white'=> $activeTab === 'pending',
                                'border-white/10 bg-white/6 text-blue-100/65 hover:bg-white/10 hover:text-white' => $activeTab !== 'pending',
                                ])>
                                <span class="material-symbols-outlined hidden text-base sm:inline-block sm:text-lg">schedule</span>
                                <span class="whitespace-nowrap">Pending</span>

                                <span
                                    class="rounded-full border px-1.5 py-0.5 text-[10px] font-semibold sm:px-2 sm:text-[10px] {{ $pendingCount ? 'border-amber-300/30 bg-amber-400/15 text-amber-200' : 'border-white/10 bg-white/8' }}">
                                    {{ $pendingCount }}
                                </span>
                            </button>
                        </div>

                        @if ($activeTab === 'services')
                        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_180px_auto]">
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.400ms="search"
                                    placeholder="Search service, status, billing cycle..."
                                    class="h-10 w-full rounded-2xl border border-white/10 bg-white/8 pl-11 pr-4 text-sm text-white placeholder:text-blue-100/35 outline-none backdrop-blur-xl focus:border-cyan-300/40">

                                <span
                                    class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-blue-100/45">
                                    search
                                </span>
                            </div>

                            <select wire:model.live="status"
                                class="h-10 w-full rounded-2xl border border-white/10 bg-white/8 px-4 text-sm text-white outline-none backdrop-blur-xl focus:border-cyan-300/40">
                                <option value="" class="bg-slate-900">All Status</option>
                                <option value="paid" class="bg-slate-900">Paid</option>
                                <option value="active" class="bg-slate-900">Active</option>
                                <option value="completed" class="bg-slate-900">Completed</option>
                                <option value="cancelled" class="bg-slate-900">Cancelled</option>
                            </select>

                            <button type="button" wire:click="clearFilters"
                                class="inline-flex h-10 cursor-pointer items-center justify-center rounded-2xl border border-white/10 bg-white/8 px-5 text-sm font-semibold text-white transition hover:bg-white/12">
                                Clear
                            </button>
                        </div>
                        @endif
                    </div>

                    @if ($activeTab === 'services')
                    <div
                        class="rounded-[28px] border border-white/10 bg-white/8 p-5 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="mb-5">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Service Orders</p>
                            <h2 class="mt-1 text-xl font-bold text-white">Your services</h2>
                        </div>

                        @if ($serviceOrders->count())
                        <div class="divide-y divide-white/10 overflow-hidden rounded-2xl border border-white/10">
                            @foreach ($serviceOrders as $order)
                            @php
                            $serviceName = $this->orderTitle($order);
                            @endphp

                            <div wire:key="service-order-{{ $order->id }}"
                                class="flex flex-col gap-3 bg-white/5 p-4 transition hover:bg-white/10 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                        <span class="material-symbols-outlined text-xl">design_services</span>
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-bold text-white">
                                            {{ $serviceName }}
                                        </h3>

                                        <p class="mt-0.5 text-xs leading-5 text-blue-100/50">
                                            {{ $this->formatDate($order->created_at) }} · {{ $this->billingLabel($order->billing_cycle) }} · {{ $order->order_no ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-3 pl-14 sm:justify-end sm:pl-0">
                                    <span class="text-sm font-semibold text-blue-100/70">
                                        {{ $this->displayAmount($order) }}
                                    </span>

                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $this->statusClass($order->status) }}">
                                        {{ $this->statusLabel($order->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-5">
                            {{ $serviceOrders->links() }}
                        </div>
                        @else
                        <div
                            class="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-white/15 bg-white/5 px-6 py-12 text-center">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                <span class="material-symbols-outlined text-3xl">inventory_2</span>
                            </div>

                            <h3 class="mt-4 text-lg font-bold text-white">No services found</h3>

                            <p class="mt-1 max-w-md text-sm leading-6 text-blue-100/55">
                                You do not have any confirmed services yet. Once admin confirms your booking, it will
                                appear here.
                            </p>

                            @if ($search || $status)
                            <button type="button" wire:click="clearFilters"
                                class="mt-4 inline-flex cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/8 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/12">
                                Clear Filters
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif

                    @if ($activeTab === 'plans')
                    <div
                        class="rounded-[28px] border border-white/10 bg-white/8 p-5 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">IT Plans</p>
                                <h2 class="mt-1 text-xl font-bold text-white">Your plan orders</h2>
                            </div>

                            @if (Route::has('client.pricing'))
                            <a href="{{ route('client.pricing') }}" wire:navigate
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-blue-300/25 bg-blue-400/10 px-4 py-2.5 text-sm font-semibold text-blue-100 transition hover:bg-blue-400/20">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Browse Plans
                            </a>
                            @endif
                        </div>

                        @if ($pricingOrders->count())
                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach ($pricingOrders as $order)
                            @php
                            $plan = $order->pricingPlan;
                            $planTitle = $this->orderTitle($order);
                            $planDescription = $this->orderDescription($order);
                            @endphp

                            <div wire:key="plan-order-{{ $order->id }}"
                                class="group rounded-[24px] border border-white/10 bg-white/7 p-5 transition hover:bg-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-400/10 text-blue-200">
                                            <span class="material-symbols-outlined text-xl">workspace_premium</span>
                                        </div>

                                        <div class="min-w-0">
                                            <h3 class="text-base font-bold text-white">
                                                {{ $planTitle }}
                                            </h3>

                                            @if ($planDescription)
                                            <p class="mt-1 line-clamp-1 text-xs leading-5 text-blue-100/50">
                                                {{ $planDescription }}
                                            </p>
                                            @endif
                                        </div>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $this->statusClass($order->status) }}">
                                        {{ $this->statusLabel($order->status) }}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                                    <div class="rounded-xl border border-white/10 bg-white/6 p-3">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">Order No</p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            {{ $order->order_no ?? 'N/A' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/6 p-3">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">Price</p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            {{ $this->displayAmount($order) }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/6 p-3">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">Billing</p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            {{ $this->billingLabel($order->billing_cycle) }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/6 p-3">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">Created</p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            {{ $this->formatDate($order->created_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col gap-2.5 sm:flex-row">
                                    <a href="{{ $this->updatePlanUrl($order) }}" wire:navigate
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-cyan-300/25 bg-cyan-400/10 px-4 py-2.5 text-sm font-bold text-cyan-100 transition hover:bg-cyan-400/20">
                                        <span class="material-symbols-outlined text-lg">upgrade</span>
                                        Update Plan
                                    </a>

                                    @if ($order->email)
                                    <a href="mailto:{{ $order->email }}"
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/8 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/12">
                                        <span class="material-symbols-outlined text-lg">support_agent</span>
                                        Contact Support
                                    </a>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div
                            class="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-white/15 bg-white/5 px-6 py-12 text-center">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-400/10 text-blue-200">
                                <span class="material-symbols-outlined text-3xl">workspace_premium</span>
                            </div>

                            <h3 class="mt-4 text-lg font-bold text-white">No IT plans found</h3>

                            <p class="mt-1 max-w-md text-sm leading-6 text-blue-100/55">
                                Your purchased plans and booking requests will appear here.
                            </p>

                            @if (Route::has('client.pricing'))
                            <a href="{{ route('client.pricing') }}" wire:navigate
                                class="mt-4 inline-flex items-center justify-center gap-2 rounded-full border border-blue-300/25 bg-blue-400/10 px-5 py-2.5 text-sm font-semibold text-blue-100 transition hover:bg-blue-400/20">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Browse Plans
                            </a>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif

                    @if ($activeTab === 'pending')
                    <div
                        class="rounded-none border-0 bg-transparent p-0 shadow-none backdrop-blur-none sm:rounded-[28px] sm:border sm:border-white/10 sm:bg-white/8 sm:p-5 sm:shadow-[0_16px_50px_rgba(0,0,0,0.18)] sm:backdrop-blur-2xl">
                        <div class="mb-5">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Pending Requests</p>
                            <h2 class="mt-1 text-xl font-bold text-white">Awaiting confirmation</h2>
                            <p class="mt-1 text-sm leading-6 text-blue-100/55">
                                Bookings waiting for a quote, orders awaiting payment, or requests being processed by our
                                team.
                            </p>
                        </div>

                        @if ($pendingItems->count())
                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach ($pendingItems as $entry)
                            @php
                            $item = $entry['item'];
                            $isOrder = $entry['type'] === 'order';
                            $title = $isOrder ? $this->orderTitle($item) : $this->bookingTitle($item);
                            $refLabel = $isOrder ? 'Order No' : 'Booking No';
                            $refValue = $isOrder ? ($item->order_no ?? 'N/A') : ($item->booking_no ?? 'N/A');
                            $statusBadge = $isOrder ? $this->statusClass($item->status) : $this->bookingStatusClass($item->status);
                            [$icon, $iconStyle] = match ($entry['type']) {
                            'order' => ['receipt_long', 'border-slate-300/20 bg-slate-400/10 text-slate-200'],
                            'service_booking' => ['contract', 'border-amber-300/20 bg-amber-400/10 text-amber-200'],
                            'plan_booking' => ['workspace_premium', 'border-violet-300/20 bg-violet-400/10 text-violet-200'],
                            default => ['receipt_long', 'border-white/10 bg-white/8 text-blue-100/55'],
                            };
                            @endphp

                            <div wire:key="pending-{{ $entry['type'] }}-{{ $item->id }}"
                                class="group rounded-[24px] border border-white/10 bg-white/7 p-5 transition hover:bg-white/10">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border {{ $iconStyle }} sm:rounded-2xl">
                                            <span class="material-symbols-outlined text-xl">{{ $icon }}</span>
                                        </div>

                                        <div class="min-w-0">
                                            <h3 class="text-base font-bold text-white">
                                                {{ $title }}
                                            </h3>
                                        </div>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $statusBadge }}">
                                        {{ $this->statusLabel($item->status) }}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                    <div class="rounded-lg border border-white/10 bg-white/6 p-3 sm:rounded-xl">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">{{ $refLabel }}</p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            {{ $refValue }}
                                        </p>
                                    </div>

                                    <div class="rounded-lg border border-white/10 bg-white/6 p-3 sm:rounded-xl">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">Billing</p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            {{ $this->billingLabel($item->billing_cycle) }}
                                        </p>
                                    </div>

                                    <div class="rounded-lg border border-white/10 bg-white/6 p-3 sm:rounded-xl">
                                        <p class="text-[10px] uppercase tracking-wider text-blue-100/40">
                                            {{ ! $isOrder && $item->quoted_price ? 'Quoted Price' : ($isOrder ? 'Amount' : 'Requested') }}
                                        </p>
                                        <p class="mt-1 text-xs font-bold text-white">
                                            @if (! $isOrder && $item->quoted_price)
                                            ৳{{ number_format((float) $item->quoted_price, 2) }}
                                            @elseif (! $isOrder && $item->requested_price)
                                            ৳{{ number_format((float) $item->requested_price, 2) }}
                                            @else
                                            {{ $isOrder ? $this->displayAmount($item) : 'Negotiable' }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if ($item->user_note || $item->admin_note)
                                <div class="mt-3 rounded-lg border border-white/10 bg-white/6 p-3 sm:rounded-xl">
                                    <p class="mt-1 text-xs leading-5 text-blue-50/75">
                                        {{ $item->admin_note ?: $item->user_note }}
                                    </p>
                                </div>
                                @endif

                                @if (! $isOrder && $item->status === 'quoted')
                                <div
                                    class="mt-4 rounded-lg border border-amber-300/20 bg-amber-400/10 p-4 sm:rounded-2xl">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-xs font-bold uppercase tracking-wider text-amber-100/60">
                                            Quotation Ready
                                        </p>

                                        @if ($item->quoted_price)
                                        <p class="text-sm font-bold text-white">
                                            ৳{{ number_format((float) $item->quoted_price, 2) }}
                                        </p>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-amber-50/85">
                                        Accept to confirm, send a counter offer, or decline.
                                    </p>

                                    @if ($item->offer_price)
                                    <div class="mt-3 rounded-lg border border-white/10 bg-white/6 p-3 sm:rounded-xl">
                                        <p class="text-xs text-amber-100/50">Your Counter Offer</p>
                                        <p class="mt-1 text-sm font-bold text-white">
                                            ৳{{ number_format((float) $item->offer_price, 2) }}
                                        </p>
                                    </div>
                                    @endif

                                    @if ($item->negotiation_note)
                                    <div class="mt-3 rounded-lg border border-white/10 bg-white/6 p-3 sm:rounded-xl">
                                        <p class="text-xs text-amber-100/50">Negotiation History</p>

                                        <div class="mt-2 space-y-1.5">
                                            @foreach (collect(explode("\n", $item->negotiation_note))->reverse() as $line)
                                            @if (trim($line))
                                            <p class="whitespace-pre-line text-xs leading-5 text-amber-50/85">
                                                {{ $line }}
                                            </p>
                                            @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                                        <input type="number" min="0" step="0.01"
                                            wire:model="offerPrice.{{ $item->id }}"
                                            placeholder="Your offer (e.g., 7000)"
                                            class="w-full rounded-lg border border-white/10 bg-white/8 px-4 py-2.5 text-sm font-semibold text-white outline-none transition placeholder:text-white/30 focus:ring-2 focus:ring-amber-300/40 sm:rounded-xl">

                                        <textarea wire:model="negotiationNote.{{ $item->id }}" rows="1"
                                            placeholder="Comment for the team..."
                                            class="w-full resize-none rounded-lg border border-white/10 bg-white/8 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-white/30 focus:ring-2 focus:ring-amber-300/40 sm:rounded-xl"></textarea>
                                    </div>

                                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                        <button type="button"
                                            wire:click="sendNegotiation({{ $item->id }})"
                                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-amber-300/30 bg-amber-400/15 px-3 py-1.5 text-xs font-bold text-amber-100 transition hover:bg-amber-400/25">
                                            <span class="material-symbols-outlined text-base">send</span>
                                            Send Counter Offer
                                        </button>

                                        <button type="button"
                                            wire:click="acceptQuotation({{ $item->id }})"
                                            wire:confirm="Accept this quotation to confirm the booking?"
                                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-emerald-300/30 bg-emerald-400/15 px-3 py-1.5 text-xs font-bold text-emerald-100 transition hover:bg-emerald-400/25">
                                            <span class="material-symbols-outlined text-base">check_circle</span>
                                            Accept
                                        </button>

                                        <button type="button"
                                            wire:click="declineQuotation({{ $item->id }})"
                                            wire:confirm="Decline this quotation?"
                                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-rose-300/30 bg-rose-400/15 px-3 py-1.5 text-xs font-bold text-rose-100 transition hover:bg-rose-400/25">
                                            <span class="material-symbols-outlined text-base">close</span>
                                            Decline
                                        </button>
                                    </div>
                                </div>
                                @endif

                                @if (! $isOrder && $item->status !== 'quoted' && $item->email)
                                <a href="mailto:{{ $item->email }}?subject=Question about {{ $item->booking_no }}"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/8 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/12 sm:rounded-xl">
                                    <span class="material-symbols-outlined text-lg">support_agent</span>
                                    Ask a Question
                                </a>
                                @endif

                                @if ($isOrder && $item->email)
                                <a href="mailto:{{ $item->email }}"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/8 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/12 sm:rounded-xl">
                                    <span class="material-symbols-outlined text-lg">support_agent</span>
                                    Contact Support
                                </a>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div
                            class="flex flex-col items-center justify-center rounded-lg border border-dashed border-white/15 bg-white/5 px-6 py-12 text-center sm:rounded-[24px]">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-lg border border-amber-300/20 bg-amber-400/10 text-amber-200 sm:rounded-2xl">
                                <span class="material-symbols-outlined text-3xl">published_with_changes</span>
                            </div>

                            <h3 class="mt-4 text-lg font-bold text-white">Nothing pending</h3>

                            <p class="mt-1 max-w-md text-sm leading-6 text-blue-100/55">
                                You have no pending bookings or orders. Everything is confirmed.
                            </p>
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>