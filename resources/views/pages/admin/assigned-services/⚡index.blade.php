<?php

use App\Models\UserService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Assigned Services')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function assignedServices()
    {
        $search = trim($this->search);

        return UserService::query()
            ->with(['user', 'service', 'booking'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
                    })
                        ->orWhereHas('service', function ($serviceQuery) use ($search) {
                            $serviceQuery->where('card_title', 'like', '%' . $search . '%')->orWhere('detail_title', 'like', '%' . $search . '%');
                        })
                        ->orWhere('billing_cycle', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function activate(int $id): void
    {
        UserService::findOrFail($id)->update(['status' => 'active']);

        $this->dispatch('toast', message: 'Service activated successfully.', type: 'success');
    }

    public function suspend(int $id): void
    {
        UserService::findOrFail($id)->update(['status' => 'suspended']);

        $this->dispatch('toast', message: 'Service suspended successfully.', type: 'success');
    }

    public function cancel(int $id): void
    {
        UserService::findOrFail($id)->update(['status' => 'cancelled']);

        $this->dispatch('toast', message: 'Service cancelled successfully.', type: 'success');
    }

    public function delete(int $id): void
    {
        UserService::findOrFail($id)->delete();

        $this->dispatch('toast', message: 'Assigned service deleted successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">

        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Assigned Services
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage confirmed services assigned to users after quotation or booking approval.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        search
                    </span>

                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Search user, service, billing, or notes..."
                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                </div>

                <div class="relative">
                    <select wire:model.live="status"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <span
                        class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>

                <a href="{{ route('admin.assigned-services.create') }}" wire:navigate
                    class="flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Assign Service
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                User
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Service
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Billing
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Duration
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status
                            </th>

                            <th
                                class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->assignedServices() as $assigned)
                            <tr wire:key="assigned-service-{{ $assigned->id }}"
                                class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <span class="block text-label-md font-label-md text-on-surface">
                                        {{ $assigned->user?->name ?? 'N/A' }}
                                    </span>

                                    <span class="block text-xs text-secondary">
                                        {{ $assigned->user?->email ?? 'No email' }}
                                    </span>

                                    @if ($assigned->booking)
                                        <span
                                            class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase text-blue-700">
                                            From Booking
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm font-medium text-on-surface">
                                        {{ $assigned->service?->card_title ?? ($assigned->service?->detail_title ?? 'N/A') }}
                                    </span>

                                    <span class="block text-xs text-secondary">
                                        Assigned {{ $assigned->created_at?->diffForHumans() }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm font-semibold text-on-surface">
                                        {{ $assigned->price ? '৳ ' . number_format($assigned->price, 2) : 'N/A' }}
                                    </span>

                                    <span class="block text-xs capitalize text-secondary">
                                        {{ str_replace('_', ' ', $assigned->billing_cycle ?: 'custom') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $assigned->start_date?->format('M d, Y') ?? 'No start date' }}
                                    </span>

                                    <span class="block text-xs text-secondary">
                                        Ends: {{ $assigned->end_date?->format('M d, Y') ?? 'No end date' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-amber-100 text-amber-700' => $assigned->status === 'pending',
                                        'bg-emerald-100 text-emerald-700' => $assigned->status === 'active',
                                        'bg-orange-100 text-orange-700' => $assigned->status === 'suspended',
                                        'bg-slate-100 text-slate-600' => $assigned->status === 'expired',
                                        'bg-red-100 text-red-700' => $assigned->status === 'cancelled',
                                    ])>
                                        {{ ucfirst($assigned->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

                                            <a href="{{ route('admin.assigned-services.edit', $assigned) }}"
                                                wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                                Edit Assignment
                                            </a>

                                            @if ($assigned->status !== 'active')
                                                <button type="button" wire:click="activate({{ $assigned->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span
                                                        class="material-symbols-outlined text-[18px]">check_circle</span>
                                                    Activate
                                                </button>
                                            @endif

                                            @if ($assigned->status !== 'suspended')
                                                <button type="button" wire:click="suspend({{ $assigned->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span
                                                        class="material-symbols-outlined text-[18px]">pause_circle</span>
                                                    Suspend
                                                </button>
                                            @endif

                                            @if ($assigned->status !== 'cancelled')
                                                <button type="button" wire:click="cancel({{ $assigned->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                                                    Cancel
                                                </button>
                                            @endif

                                            <button type="button" wire:click="delete({{ $assigned->id }})"
                                                wire:confirm="Are you sure you want to delete this assigned service?"
                                                @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">assignment_ind</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No assigned services found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Assigned user services will appear here after confirmation.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-body-sm font-body-sm text-secondary">Per page</span>

                    <select wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div>
                    {{ $this->assignedServices()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
