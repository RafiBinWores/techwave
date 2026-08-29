<?php

use App\Mail\WhmcsAccountLinkedMail;
use App\Mail\WhmcsAccountUnlinkedMail;
use App\Models\User;
use App\Models\WhmcsAccount;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('WHMCS Accounts')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 10;

    // Link modal state
    public bool $showLinkModal = false;
    public string $linkEmail = '';
    public string $userSearch = '';
    public bool $showUserDropdown = false;
    public bool $isLinking = false;
    public ?User $selectedUser = null;

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

    public function updatedUserSearch(): void
    {
        $this->showUserDropdown = strlen($this->userSearch) > 0;
        $this->selectedUser = null;
    }

    public function openLinkModal(): void
    {
        $this->showLinkModal = true;
        $this->linkEmail = '';
        $this->userSearch = '';
        $this->selectedUser = null;
        $this->isLinking = false;
        $this->resetValidation();
    }

    public function closeLinkModal(): void
    {
        $this->showLinkModal = false;
        $this->linkEmail = '';
        $this->userSearch = '';
        $this->selectedUser = null;
        $this->isLinking = false;
        $this->resetValidation();
    }

    public function selectUser(User $user): void
    {
        $this->selectedUser = $user;
        $this->userSearch = $user->name . ' (' . $user->email . ')';
        $this->showUserDropdown = false;
    }

    public function unlink(int $whmcsAccountId): void
    {
        $account = WhmcsAccount::with('user')->findOrFail($whmcsAccountId);

        $user = $account->user;
        $whmcsEmail = $account->email;

        $account->delete();

        if ($user) {
            Mail::to($user->email)->queue(new WhmcsAccountUnlinkedMail($user, $whmcsEmail));
        }

        $this->dispatch('toast', message: 'WHMCS account has been unlinked successfully.', type: 'success');
    }

    public function linkAccount(): void
    {
        $this->validate([
            'selectedUser' => ['required'],
            'linkEmail' => ['required', 'email', 'max:190'],
        ]);

        $this->isLinking = true;

        /** @var User $user */
        $user = $this->selectedUser;

        if ($user->whmcsAccount) {
            $this->addError('linkEmail', 'This user already has a linked WHMCS account. Please unlink it first.');
            $this->isLinking = false;

            return;
        }

        $email = strtolower(trim($this->linkEmail));

        $api = app(WhmcsApi::class);

        try {
            $whmcsUser = $api->findUserByEmail($email);
        } catch (WhmcsApiException $exception) {
            $this->addError('linkEmail', $exception->getMessage());
            $this->isLinking = false;

            return;
        }

        if (! $whmcsUser) {
            $this->addError('linkEmail', 'No billing account was found for this email address.');
            $this->isLinking = false;

            return;
        }

        $whmcsUserId = (string) ($whmcsUser['id'] ?? '');

        if (WhmcsAccount::query()->where('whmcs_user_id', $whmcsUserId)->where('user_id', '!=', $user->id)->exists()) {
            $this->addError('linkEmail', 'This billing account is already linked to another portal account.');
            $this->isLinking = false;

            return;
        }

        $clientId = null;

        try {
            $clientDetails = $api->getClientDetails(null, $email);
            $clientId = $clientDetails !== null ? (string) data_get($clientDetails, 'id', '') : '';
            $clientId = $clientId !== '' ? $clientId : null;
        } catch (WhmcsApiException) {
            $clientId = null;
        }

        WhmcsAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'whmcs_user_id' => $whmcsUserId,
                'whmcs_client_id' => $clientId,
                'email' => $email,
                'verified_at' => now(),
            ],
        );

        Mail::to($user->email)->queue(new WhmcsAccountLinkedMail($user, $email));

        $this->closeLinkModal();
        $this->dispatch('toast', message: 'WHMCS account linked successfully to ' . $user->name . '.', type: 'success');
    }

    public function accounts()
    {
        return WhmcsAccount::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('email', 'like', '%' . $this->search . '%')
                        ->orWhere('whmcs_user_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status === 'verified', fn ($query) => $query->whereNotNull('verified_at'))
            ->when($this->status === 'unverified', fn ($query) => $query->whereNull('verified_at'))
            ->latest()
            ->paginate($this->perPage);
    }
};
?>

<div class="mx-auto w-full space-y-stack-lg">
    <!-- Header Section -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                WHMCS Billing Accounts
            </h2>

            <p class="text-xs font-body-md text-secondary md:text-body-md">
                Manage linked WHMCS billing accounts for users.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    search
                </span>

                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search by email or name..."
                    class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-64" />
            </div>

            <div class="relative">
                <select wire:model.live="status"
                    class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-44">
                    <option value="all">All Status</option>
                    <option value="verified">Verified Only</option>
                    <option value="unverified">Unverified Only</option>
                </select>

                <span
                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    expand_more
                </span>
            </div>

            <button type="button" wire:click="openLinkModal"
                class="flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98]">
                <span class="material-symbols-outlined text-lg">link</span>
                Link Account
            </button>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Portal User
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            WHMCS Email
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            WHMCS User ID
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            WHMCS Client ID
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Verified Status
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Linked At
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary text-right">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->accounts() as $account)
                        <tr wire:key="account-{{ $account->id }}" class="transition-colors hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 overflow-hidden rounded-full bg-slate-100">
                                        @if ($account->user?->avatar)
                                            <img src="{{ Storage::url($account->user->avatar) }}" alt="{{ $account->user->name }}"
                                                class="h-full w-full object-cover" />
                                        @else
                                            <div
                                                class="flex h-full w-full items-center justify-center bg-primary/10 text-sm font-bold uppercase text-primary">
                                                {{ str($account->user?->name ?? 'U')->substr(0, 1) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col">
                                        <span class="text-label-md font-label-md text-on-surface">
                                            {{ $account->user?->name ?? 'Deleted User' }}
                                        </span>

                                        <span class="text-body-sm font-body-sm text-secondary">
                                            {{ $account->user?->email ?? 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-body-sm font-body-sm text-on-surface">
                                    {{ $account->email }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700">
                                    {{ $account->whmcs_user_id }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700">
                                    {{ $account->whmcs_client_id ?? 'N/A' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold @if ($account->verified_at) bg-emerald-50 text-emerald-700 @else bg-amber-50 text-amber-700 @endif">
                                    @if ($account->verified_at)
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Verified
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Unverified
                                    @endif
                                </span>
                            </td>

                            <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                                {{ $account->created_at?->format('M d, Y') }}
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button type="button" @click="open = !open"
                                        class="text-slate-400 transition-colors hover:text-primary">
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>

                                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                        class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                        <button type="button" wire:click="unlink({{ $account->id }})"
                                            wire:confirm="Are you sure you want to unlink this WHMCS account? This action cannot be undone."
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                            <span class="material-symbols-outlined text-[18px]">link_off</span>
                                            Unlink Account
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div
                                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <span class="material-symbols-outlined">link_off</span>
                                    </div>

                                    <h3 class="text-base font-semibold text-on-surface">
                                        No WHMCS accounts linked
                                    </h3>

                                    <p class="mt-1 text-sm text-secondary">
                                        Users haven't linked any WHMCS billing accounts yet.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="text-body-sm font-body-sm text-secondary">
                    Per page
                </span>

                <select wire:model.live="perPage"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div>
                {{ $this->accounts()->links() }}
            </div>
        </div>
    </div>

    <!-- Link Account Modal -->
    @if ($showLinkModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-cloak>
            <div class="absolute inset-0 bg-black/50" wire:click="closeLinkModal"></div>

            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">

                <!-- Modal Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-on-surface">Link WHMCS Account</h3>
                        <p class="mt-1 text-sm text-secondary">
                            Select a user and enter their WHMCS billing email.
                        </p>
                    </div>

                    <button type="button" wire:click="closeLinkModal"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="linkAccount" class="space-y-5">
                    <!-- User Search -->
                    <div>
                        <label class="mb-2 block text-label-md font-label-md text-on-surface">
                            Select Portal User <span class="text-red-500">*</span>
                        </label>

                        <div class="relative" x-data="{ open: @entangle('showUserDropdown') }" x-on:click.outside="$wire.set('showUserDropdown', false)">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                search
                            </span>

                            <input type="text" wire:model.live.debounce.300ms="userSearch"
                                placeholder="Search by name or email..."
                                autocomplete="off"
                                class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />

                            <!-- Dropdown -->
                            <div x-show="open && !$wire.selectedUser" x-transition
                                class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">

                                @php
                                    $users = \App\Models\User::query()
                                        ->when($this->userSearch, fn ($q) => $q->where(function ($wq) use ($userSearch) {
                                            $wq->where('name', 'like', '%' . $userSearch . '%')
                                                ->orWhere('email', 'like', '%' . $userSearch . '%');
                                        }))
                                        ->where('is_active', true)
                                        ->latest()
                                        ->limit(20)
                                        ->get();
                                @endphp

                                @forelse ($users as $user)
                                    <button type="button" wire:click="selectUser({{ $user->id }})"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-50">
                                        <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-slate-100">
                                            @if ($user->avatar)
                                                <img src="{{ Storage::url($user->avatar) }}" class="h-full w-full object-cover" />
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-primary/10 text-xs font-bold uppercase text-primary">
                                                    {{ str($user->name)->substr(0, 1) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-on-surface">{{ $user->name }}</p>
                                            <p class="truncate text-xs text-secondary">{{ $user->email }}</p>
                                        </div>

                                        @if ($user->whmcsAccount)
                                            <span class="ml-auto shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                                Linked
                                            </span>
                                        @endif
                                    </button>
                                @empty
                                    <div class="px-4 py-6 text-center text-sm text-secondary">
                                        No users found.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Selected User Card -->
                        @if ($selectedUser)
                            <div class="mt-3 flex items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 p-3">
                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-slate-100">
                                    @if ($selectedUser->avatar)
                                        <img src="{{ Storage::url($selectedUser->avatar) }}" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-primary/10 text-sm font-bold uppercase text-primary">
                                            {{ str($selectedUser->name)->substr(0, 1) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-on-surface">{{ $selectedUser->name }}</p>
                                    <p class="truncate text-xs text-secondary">{{ $selectedUser->email }}</p>
                                </div>

                                @if ($selectedUser->whmcsAccount)
                                    <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                        Already Linked
                                    </span>
                                @else
                                    <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                        Available
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- WHMCS Email -->
                    <div>
                        <label class="mb-2 block text-label-md font-label-md text-on-surface">
                            WHMCS Billing Email <span class="text-red-500">*</span>
                        </label>

                        <input type="email" wire:model="linkEmail"
                            placeholder="Enter the WHMCS billing email address"
                            class="w-full rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />

                        @error('linkEmail')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        <p class="mt-1.5 text-xs text-secondary">
                            The system will verify this email exists in WHMCS before linking.
                        </p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeLinkModal"
                            class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50">
                            Cancel
                        </button>

                        <button type="submit" wire:loading.attr="disabled" wire:target="linkAccount"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60">

                            <span wire:loading.remove wire:target="linkAccount" class="inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">link</span>
                                Link Account
                            </span>

                            <span wire:loading wire:target="linkAccount" class="inline-flex items-center gap-2">
                                <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
                                Linking...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
