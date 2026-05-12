<?php

use App\Models\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Services')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

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
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function statusClass(?string $status): string
    {
        return match ($status) {
            'active' => 'border-emerald-300/20 bg-emerald-400/10 text-emerald-200',
            'ongoing' => 'border-cyan-300/20 bg-cyan-400/10 text-cyan-200',
            'pending' => 'border-amber-300/20 bg-amber-400/10 text-amber-200',
            'inactive' => 'border-slate-300/20 bg-slate-400/10 text-slate-200',
            'cancelled' => 'border-rose-300/20 bg-rose-400/10 text-rose-200',
            'expired' => 'border-red-300/20 bg-red-400/10 text-red-200',
            default => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
        };
    }

    public function with(): array
    {
        $userId = Auth::id();

        $purchasedServices = UserService::query()
            ->with(['service'])
            ->where('user_id', $userId)
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('billing_cycle', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhere('notes', 'like', '%' . $this->search . '%')
                        ->orWhereHas('service', function ($serviceQuery) {
                            $serviceQuery
                                ->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('title', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate(8);

        $totalServices = UserService::where('user_id', $userId)->count();
        $activeServices = UserService::where('user_id', $userId)->where('status', 'active')->count();
        $pendingServices = UserService::where('user_id', $userId)->where('status', 'pending')->count();

        return [
            'purchasedServices' => $purchasedServices,
            'totalServices' => $totalServices,
            'activeServices' => $activeServices,
            'pendingServices' => $pendingServices,
        ];
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button
                                @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <span class="material-symbols-outlined">menu</span>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Client Dashboard</p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">My Services</h1>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 backdrop-blur-xl">
                            <span class="material-symbols-outlined text-cyan-200">workspace_premium</span>
                            <div>
                                <p class="text-xs text-blue-100/45">Purchased Services</p>
                                <p class="text-sm font-semibold text-white">{{ $totalServices }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="mb-6 grid gap-5 md:grid-cols-3">
                        <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Total Services</p>
                            <h3 class="mt-5 text-4xl font-bold text-white">{{ str_pad($totalServices, 2, '0', STR_PAD_LEFT) }}</h3>
                            <p class="mt-2 text-sm text-blue-100/60">All assigned services</p>
                        </div>

                        <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Active Services</p>
                            <h3 class="mt-5 text-4xl font-bold text-emerald-300">{{ str_pad($activeServices, 2, '0', STR_PAD_LEFT) }}</h3>
                            <p class="mt-2 text-sm text-blue-100/60">Currently running</p>
                        </div>

                        <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Pending Services</p>
                            <h3 class="mt-5 text-4xl font-bold text-amber-300">{{ str_pad($pendingServices, 2, '0', STR_PAD_LEFT) }}</h3>
                            <p class="mt-2 text-sm text-blue-100/60">Waiting for process</p>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="mb-6 rounded-[28px] border border-white/10 bg-white/8 p-5 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="grid gap-4 lg:grid-cols-[1fr_220px_auto]">
                            <div class="relative">
                                <input
                                    type="text"
                                    wire:model.live.debounce.400ms="search"
                                    placeholder="Search service, status, billing cycle..."
                                    class="h-12 w-full rounded-2xl border border-white/10 bg-white/8 pl-12 pr-4 text-sm text-white placeholder:text-blue-100/35 outline-none backdrop-blur-xl focus:border-cyan-300/40">

                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-blue-100/45">
                                    search
                                </span>
                            </div>

                            <select
                                wire:model.live="status"
                                class="h-12 w-full rounded-2xl border border-white/10 bg-white/8 px-4 text-sm text-white outline-none backdrop-blur-xl focus:border-cyan-300/40">
                                <option value="" class="bg-slate-900">All Status</option>
                                <option value="active" class="bg-slate-900">Active</option>
                                <option value="ongoing" class="bg-slate-900">Ongoing</option>
                                <option value="pending" class="bg-slate-900">Pending</option>
                                <option value="inactive" class="bg-slate-900">Inactive</option>
                                <option value="expired" class="bg-slate-900">Expired</option>
                                <option value="cancelled" class="bg-slate-900">Cancelled</option>
                            </select>

                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="inline-flex h-12 items-center justify-center rounded-2xl border border-white/10 bg-white/8 px-5 text-sm font-semibold text-white transition hover:bg-white/12">
                                Clear
                            </button>
                        </div>
                    </div>

                    {{-- Services --}}
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Purchased Services</p>
                                <h2 class="mt-2 text-2xl font-bold text-white">Your service list</h2>
                            </div>
                        </div>

                        @if ($purchasedServices->count())
                            <div class="grid gap-5 lg:grid-cols-2">
                                @foreach ($purchasedServices as $userService)
                                    @php
                                        $serviceName = $userService->service?->name
                                            ?? $userService->service?->title
                                            ?? 'Service';

                                        $serviceDescription = $userService->service?->short_description
                                            ?? $userService->service?->description
                                            ?? null;
                                    @endphp

                                    <div class="group rounded-[26px] border border-white/10 bg-white/7 p-5 shadow-[0_14px_40px_rgba(0,0,0,0.16)] transition hover:-translate-y-1 hover:bg-white/10">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-4">
                                                <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                                    <span class="material-symbols-outlined">design_services</span>
                                                </div>

                                                <div>
                                                    <h3 class="text-lg font-bold text-white">
                                                        {{ $serviceName }}
                                                    </h3>

                                                    @if ($serviceDescription)
                                                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-blue-100/55">
                                                            {{ $serviceDescription }}
                                                        </p>
                                                    @else
                                                        <p class="mt-2 text-sm leading-6 text-blue-100/55">
                                                            Service details are available in your account.
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold {{ $this->statusClass($userService->status) }}">
                                                {{ ucfirst($userService->status ?? 'active') }}
                                            </span>
                                        </div>

                                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Price</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    ৳{{ number_format((float) ($userService->price ?? 0), 2) }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Billing Cycle</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    {{ ucfirst($userService->billing_cycle ?? 'N/A') }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Start Date</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    {{ $this->formatDate($userService->start_date) }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">End Date</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    {{ $this->formatDate($userService->end_date) }}
                                                </p>
                                            </div>
                                        </div>

                                        @if ($userService->notes)
                                            <div class="mt-4 rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Notes</p>
                                                <p class="mt-2 text-sm leading-6 text-blue-50/80">
                                                    {{ $userService->notes }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $purchasedServices->links() }}
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center rounded-[26px] border border-dashed border-white/15 bg-white/5 px-6 py-14 text-center">
                                <div class="flex h-16 w-16 items-center justify-center rounded-3xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                    <span class="material-symbols-outlined text-4xl">inventory_2</span>
                                </div>

                                <h3 class="mt-5 text-xl font-bold text-white">No services found</h3>

                                <p class="mt-2 max-w-md text-sm leading-7 text-blue-100/55">
                                    You do not have any purchased or assigned services yet. Once admin assigns a service to your account, it will appear here.
                                </p>

                                @if ($search || $status)
                                    <button
                                        type="button"
                                        wire:click="clearFilters"
                                        class="mt-5 inline-flex items-center justify-center rounded-full border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                                        Clear Filters
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>