<?php

use App\Models\Service;
use App\Models\User;
use App\Models\UserService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Edit Assigned Service')] class extends Component {
    public UserService $userService;

    public string $userSearch = '';
    public string $serviceSearch = '';

    public bool $showUserResults = false;
    public bool $showServiceResults = false;

    public string $user_id = '';
    public string $service_id = '';

    public string $status = 'active';
    public string $price = '';
    public string $billing_cycle = 'monthly';
    public string $start_date = '';
    public string $end_date = '';
    public string $notes = '';

    public function mount(UserService $userService): void
    {
        $this->userService = $userService->load(['user', 'service', 'booking']);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $this->user_id = (string) $this->userService->user_id;
        $this->service_id = (string) $this->userService->service_id;

        $this->userSearch = $this->selectedUserLabel();
        $this->serviceSearch = $this->selectedServiceLabel();

        $this->status = $this->userService->status ?: 'active';
        $this->price = $this->userService->price !== null ? (string) $this->userService->price : '';
        $this->billing_cycle = $this->userService->billing_cycle ?: 'monthly';
        $this->start_date = $this->userService->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $this->userService->end_date?->format('Y-m-d') ?? '';
        $this->notes = $this->userService->notes ?: '';
    }

    public function selectedUserLabel(): string
    {
        $user = $this->userService->user;

        if (! $user) {
            return '';
        }

        return trim(($user->name ?? 'User') . ' — ' . ($user->email ?? 'No email'));
    }

    public function selectedServiceLabel(): string
    {
        $service = $this->userService->service;

        if (! $service) {
            return '';
        }

        return $service->card_title ?: ($service->detail_title ?: 'Selected Service');
    }

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'service_id' => ['required', 'exists:services,id'],
            'status' => ['required', 'in:pending,active,suspended,expired,cancelled'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['nullable', 'in:one_time,monthly,yearly,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'user_id',
            'service_id',
            'status',
            'price',
            'billing_cycle',
            'start_date',
            'end_date',
            'notes',
        ])) {
            $this->validateOnly($property);
        }
    }

    public function updatedUserSearch(): void
    {
        $this->showUserResults = true;

        $selectedUser = $this->selectedUser();

        if ($selectedUser) {
            $selectedLabel = trim(($selectedUser->name ?? 'User') . ' — ' . ($selectedUser->email ?? 'No email'));

            if ($this->userSearch !== $selectedLabel) {
                $this->user_id = '';
            }
        }
    }

    public function updatedServiceSearch(): void
    {
        $this->showServiceResults = true;

        $selectedService = $this->selectedService();

        if ($selectedService) {
            $selectedLabel = $selectedService->card_title ?: ($selectedService->detail_title ?: 'Selected Service');

            if ($this->serviceSearch !== $selectedLabel) {
                $this->service_id = '';
            }
        }
    }

    public function showUserDropdown(): void
    {
        $this->showUserResults = true;
        $this->showServiceResults = false;
    }

    public function hideUserDropdown(): void
    {
        $this->showUserResults = false;
    }

    public function showServiceDropdown(): void
    {
        $this->showServiceResults = true;
        $this->showUserResults = false;
    }

    public function hideServiceDropdown(): void
    {
        $this->showServiceResults = false;
    }

    public function selectUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->user_id = (string) $user->id;
        $this->userSearch = trim(($user->name ?? 'User') . ' — ' . ($user->email ?? 'No email'));
        $this->showUserResults = false;

        $this->resetValidation('user_id');
    }

    public function clearUser(): void
    {
        $this->user_id = '';
        $this->userSearch = '';
        $this->showUserResults = false;

        $this->resetValidation('user_id');
    }

    public function selectService(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);

        $this->service_id = (string) $service->id;
        $this->serviceSearch = $service->card_title ?: ($service->detail_title ?: 'Selected Service');
        $this->showServiceResults = false;

        $this->resetValidation('service_id');
    }

    public function clearService(): void
    {
        $this->service_id = '';
        $this->serviceSearch = '';
        $this->showServiceResults = false;

        $this->resetValidation('service_id');
    }

    public function users()
    {
        $search = trim($this->userSearch);

        if (str_contains($search, '—')) {
            $search = trim(explode('—', $search)[0]);
        }

        return User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function services()
    {
        $search = trim($this->serviceSearch);

        return Service::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('card_title', 'like', '%' . $search . '%')
                        ->orWhere('detail_title', 'like', '%' . $search . '%')
                        ->orWhere('short_description', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('card_title')
            ->limit(8)
            ->get();
    }

    public function selectedUser()
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }

    public function selectedService()
    {
        return $this->service_id ? Service::find($this->service_id) : null;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->userService->update([
            'user_id' => $validated['user_id'],
            'service_id' => $validated['service_id'],
            'status' => $validated['status'],
            'price' => $validated['price'] !== '' ? $validated['price'] : null,
            'billing_cycle' => $validated['billing_cycle'],
            'start_date' => $validated['start_date'] ?: null,
            'end_date' => $validated['end_date'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        $this->dispatch('toast', message: 'Assigned service updated successfully.', type: 'success');

        $this->redirectRoute('admin.assigned-services.index', navigate: true);
    }

    public function discard(): void
    {
        $this->userService = $this->userService->fresh(['user', 'service', 'booking']);

        $this->fillForm();

        $this->showUserResults = false;
        $this->showServiceResults = false;

        $this->resetValidation();

        $this->dispatch('toast', message: 'Changes restored.', type: 'info');
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Edit Assigned Service</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Update assigned service, billing, duration, status, and internal notes.
            </p>
        </div>

        <a href="{{ route('admin.assigned-services.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Assigned Services
        </a>
    </div>

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6 lg:col-span-8">
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">edit_square</span>
                        Edit Information
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {{-- User Search --}}
                        <div class="space-y-2 md:col-span-2" wire:key="edit-user-search-wrapper">
                            <label class="block font-label-md text-on-surface">Search & Select User</label>

                            <div x-data class="relative" @click.outside="$wire.hideUserDropdown()">
                                <div class="flex gap-3">
                                    <div class="relative flex-1">
                                        <span
                                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                            person_search
                                        </span>

                                        <input type="text"
                                            wire:model.live.debounce.500ms="userSearch"
                                            wire:focus="showUserDropdown"
                                            class="w-full rounded border border-outline-variant px-10 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                                            placeholder="Search by user name, email, or phone..."
                                            autocomplete="off" />

                                        @if ($user_id)
                                            <span
                                                class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-lg text-emerald-500">
                                                check_circle
                                            </span>
                                        @endif
                                    </div>

                                    @if ($user_id)
                                        <button type="button" wire:click="clearUser"
                                            class="rounded border border-red-100 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                            Clear
                                        </button>
                                    @endif
                                </div>

                                @if ($showUserResults)
                                    <div wire:key="edit-user-search-dropdown"
                                        class="absolute left-0 right-0 z-50 mt-2 max-h-80 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                                        <div wire:loading wire:target="userSearch"
                                            class="px-4 py-4 text-sm text-secondary">
                                            Searching users...
                                        </div>

                                        <div wire:loading.remove wire:target="userSearch">
                                            @forelse ($this->users() as $user)
                                                <button type="button"
                                                    wire:key="edit-user-option-{{ $user->id }}"
                                                    wire:click="selectUser({{ $user->id }})"
                                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-50">
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                                    </div>

                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-slate-900">
                                                            {{ $user->name }}
                                                        </p>

                                                        <p class="truncate text-xs text-secondary">
                                                            {{ $user->email }}
                                                        </p>

                                                        @if ($user->phone ?? false)
                                                            <p class="truncate text-[11px] text-slate-400">
                                                                {{ $user->phone }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </button>
                                            @empty
                                                <div class="px-4 py-5 text-center text-sm text-secondary">
                                                    No users found.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @error('user_id')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Service Search --}}
                        <div class="space-y-2 md:col-span-2" wire:key="edit-service-search-wrapper">
                            <label class="block font-label-md text-on-surface">Search & Select Service</label>

                            <div x-data class="relative" @click.outside="$wire.hideServiceDropdown()">
                                <div class="flex gap-3">
                                    <div class="relative flex-1">
                                        <span
                                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                            manage_search
                                        </span>

                                        <input type="text"
                                            wire:model.live.debounce.500ms="serviceSearch"
                                            wire:focus="showServiceDropdown"
                                            class="w-full rounded border border-outline-variant px-10 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                                            placeholder="Search by service title or description..."
                                            autocomplete="off" />

                                        @if ($service_id)
                                            <span
                                                class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-lg text-emerald-500">
                                                check_circle
                                            </span>
                                        @endif
                                    </div>

                                    @if ($service_id)
                                        <button type="button" wire:click="clearService"
                                            class="rounded border border-red-100 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                            Clear
                                        </button>
                                    @endif
                                </div>

                                @if ($showServiceResults)
                                    <div wire:key="edit-service-search-dropdown"
                                        class="absolute left-0 right-0 z-50 mt-2 max-h-80 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                                        <div wire:loading wire:target="serviceSearch"
                                            class="px-4 py-4 text-sm text-secondary">
                                            Searching services...
                                        </div>

                                        <div wire:loading.remove wire:target="serviceSearch">
                                            @forelse ($this->services() as $service)
                                                <button type="button"
                                                    wire:key="edit-service-option-{{ $service->id }}"
                                                    wire:click="selectService({{ $service->id }})"
                                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-50">
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                        <span class="material-symbols-outlined text-[22px]">
                                                            {{ $service->icon ?: 'design_services' }}
                                                        </span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-slate-900">
                                                            {{ $service->card_title ?? $service->detail_title }}
                                                        </p>

                                                        <p class="truncate text-xs text-secondary">
                                                            {{ $service->short_description }}
                                                        </p>
                                                    </div>
                                                </button>
                                            @empty
                                                <div class="px-4 py-5 text-center text-sm text-secondary">
                                                    No services found.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @error('service_id')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Assignment Status</label>
                            <select wire:model.live="status"
                                class="w-full rounded border border-outline-variant bg-white px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>

                            @error('status')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Billing Cycle</label>
                            <select wire:model.live="billing_cycle"
                                class="w-full rounded border border-outline-variant bg-white px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10">
                                <option value="one_time">One Time</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom</option>
                            </select>

                            @error('billing_cycle')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Price</label>
                            <input wire:model.live="price"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                                placeholder="e.g., 5000" type="number" step="0.01" />

                            @error('price')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Start Date</label>
                            <input wire:model.live="start_date"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                                type="date" />

                            @error('start_date')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">End Date</label>
                            <input wire:model.live="end_date"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                                type="date" />

                            @error('end_date')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Internal Notes</label>
                            <textarea wire:model.live="notes"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                                placeholder="Quotation details, client requirement, delivery notes..." rows="5"></textarea>

                            @error('notes')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" wire:click="discard" wire:loading.attr="disabled"
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                            Discard Changes
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Update Assignment</span>

                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Updating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-label-sm font-label-sm uppercase tracking-widest text-secondary">
                        Assignment Settings
                    </h3>

                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center gap-3">
                            <div @class([
                                'h-2.5 w-2.5 rounded-full',
                                'bg-emerald-500' => $status === 'active',
                                'bg-amber-500' => $status === 'pending',
                                'bg-orange-500' => $status === 'suspended',
                                'bg-slate-500' => $status === 'expired',
                                'bg-red-500' => $status === 'cancelled',
                            ])></div>

                            <div>
                                <span class="block text-label-md font-label-md text-on-surface">
                                    {{ ucfirst($status) }}
                                </span>

                                <span class="text-xs text-secondary">
                                    Current assignment status.
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-body-sm font-body-sm leading-relaxed text-secondary">
                        Active services will be visible in the user dashboard. Suspended or cancelled services should be
                        hidden or marked inactive for the user.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Quick Preview</h3>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined">
                                {{ $this->selectedService()?->icon ?: 'assignment_ind' }}
                            </span>
                        </div>

                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-emerald-100 text-emerald-700' => $status === 'active',
                                'bg-amber-100 text-amber-700' => $status === 'pending',
                                'bg-orange-100 text-orange-700' => $status === 'suspended',
                                'bg-slate-100 text-slate-600' => $status === 'expired',
                                'bg-red-100 text-red-700' => $status === 'cancelled',
                            ])>
                                {{ ucfirst($status) }}
                            </span>

                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600 shadow-sm">
                                {{ str_replace('_', ' ', ucfirst($billing_cycle)) }}
                            </span>
                        </div>

                        <h4 class="text-lg font-semibold text-on-surface">
                            {{ $this->selectedService()?->card_title ?? ($this->selectedService()?->detail_title ?? 'Selected Service') }}
                        </h4>

                        <p class="mt-2 text-sm leading-relaxed text-secondary">
                            Assigned to:
                            <span class="font-semibold text-on-surface">
                                {{ $this->selectedUser()?->name ?? 'No user selected' }}
                            </span>
                        </p>

                        <div class="mt-4 rounded-xl bg-white p-4 text-sm text-secondary shadow-sm">
                            <p>
                                <strong class="text-on-surface">Price:</strong>
                                {{ $price !== '' ? '৳ ' . number_format((float) $price, 2) : 'Not set' }}
                            </p>

                            <p class="mt-1">
                                <strong class="text-on-surface">Duration:</strong>
                                {{ $start_date ?: 'No start date' }}
                                —
                                {{ $end_date ?: 'No end date' }}
                            </p>
                        </div>
                    </div>
                </div>

                @if ($userService->booking)
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary">info</span>

                            <div>
                                <h3 class="text-sm font-bold text-slate-900">
                                    Linked Booking
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-secondary">
                                    This assignment is linked with booking ID #{{ $userService->service_booking_id }}.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>