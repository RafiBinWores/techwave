<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Delete Account')] class extends Component {
    public string $password = '';

    public bool $showPassword = false;

    public bool $confirmed = false;

    public function updatedConfirmed(): void
    {
        $this->resetValidation('confirmed');
    }

    public function scheduleDeletion(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
            'confirmed' => ['accepted'],
        ], [
            'password.required' => 'Please enter your password to confirm.',
            'confirmed.accepted' => 'You must confirm that you want to delete your account.',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'The password you entered is incorrect.');

            return;
        }

        $user->update(['scheduled_deletion_at' => now()->addDays(15)]);

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->dispatch('toast', type: 'success', message: 'Account deletion scheduled. Your account will be deleted in 15 days.');
        $this->redirect(route('home'), navigate: true);
    }

    public function cancelDeletion(): void
    {
        auth()->user()->update(['scheduled_deletion_at' => null]);

        $this->dispatch('toast', type: 'success', message: 'Account deletion has been cancelled.');
    }

    public function getScheduledDeletionAtProperty(): ?Carbon
    {
        return auth()->user()->scheduled_deletion_at;
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <livewire:shared.font-toast-notification />

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
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

                    {{-- Content --}}
                    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

                        {{-- Left Content --}}
                        <div class="space-y-6">

                            @if ($this->scheduledDeletionAt)
                            {{-- Scheduled Deletion Active --}}
                            <div class="overflow-hidden p-0">
                                <div class="border-b border-white/10 px-4 pb-4 lg:px-6 lg:pb-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h2 class="text-2xl font-bold text-white">
                                                Deletion scheduled
                                            </h2>
                                            <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-100/60">
                                                Your account is scheduled for permanent deletion. If you log in
                                                before the deadline, the request will be automatically cancelled.
                                            </p>
                                        </div>

                                        <div
                                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-amber-300/20 bg-amber-400/10 text-amber-200">
                                            <span class="material-symbols-outlined text-3xl">schedule</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 lg:p-6">
                                    {{-- Countdown --}}
                                    <div
                                        class="rounded-2xl border border-amber-300/20 bg-amber-400/10 p-5">
                                        <div class="flex gap-3">
                                            <span
                                                class="material-symbols-outlined shrink-0 text-amber-200">timer</span>
                                            <div>
                                                <p class="text-sm font-bold text-amber-200">
                                                    Account will be deleted on
                                                    {{ $this->scheduledDeletionAt->format('d M Y, h:i A') }}
                                                </p>
                                                <p class="mt-1 text-sm leading-6 text-amber-200/70">
                                                    All your data including profile, orders, subscriptions,
                                                    linked billing account, and backed-up files will be
                                                    permanently removed.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- What happens --}}
                                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/6 p-5">
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                            What happens next
                                        </p>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-start gap-3">
                                                <span
                                                    class="material-symbols-outlined mt-0.5 text-cyan-200">login</span>
                                                <p class="text-sm leading-6 text-blue-100/65">
                                                    <span class="font-semibold text-white">Log in anytime</span>
                                                    to cancel the deletion request. Your account will be
                                                    fully restored.
                                                </p>
                                            </div>
                                            <div class="flex items-start gap-3">
                                                <span
                                                    class="material-symbols-outlined mt-0.5 text-cyan-200">event_busy</span>
                                                <p class="text-sm leading-6 text-blue-100/65">
                                                    <span class="font-semibold text-white">If no action</span>
                                                    is taken within 15 days, your account and all data will
                                                    be permanently deleted.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Cancel Button --}}
                                    <div class="mt-6 flex justify-end">
                                        <button type="button" wire:click="cancelDeletion"
                                            wire:loading.attr="disabled" wire:target="cancelDeletion"
                                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70">

                                            <span wire:loading.remove wire:target="cancelDeletion"
                                                class="material-symbols-outlined text-lg">
                                                restart_alt
                                            </span>

                                            <span wire:loading wire:target="cancelDeletion"
                                                class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white">
                                            </span>

                                            <span wire:loading.remove wire:target="cancelDeletion">
                                                Cancel Deletion Request
                                            </span>

                                            <span wire:loading wire:target="cancelDeletion">
                                                Cancelling...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @else
                            {{-- Request Deletion Form --}}
                            <div class="overflow-hidden p-0">
                                <div class="border-b border-white/10 px-4 pb-4 lg:px-6 lg:pb-4">
                                    <div>
                                        <h2 class="text-2xl font-bold text-white">
                                            Delete your account
                                        </h2>
                                        <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-100/60">
                                            Schedule your account for deletion.
                                        </p>
                                    </div>
                                </div>

                                <form wire:submit.prevent="scheduleDeletion" class="p-4 lg:p-6">

                                    {{-- Warning --}}
                                    <!-- <div
                                        class="mb-6 rounded-2xl border border-rose-300/20 bg-rose-500/10 p-5">
                                        <div class="flex gap-3">
                                            <span
                                                class="material-symbols-outlined shrink-0 text-rose-200">warning</span>
                                            <div>
                                                <p class="text-sm font-bold text-rose-200">
                                                    This will schedule permanent deletion
                                                </p>
                                                <p class="mt-1 text-sm leading-6 text-rose-200/70">
                                                    Your account will be marked for deletion and permanently
                                                    removed after 15 days. All data including profile,
                                                    orders, subscriptions, linked billing account, and
                                                    backed-up files will be lost.
                                                </p>
                                            </div>
                                        </div>
                                    </div> -->

                                    <div class="grid gap-5">

                                        {{-- Password --}}
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-blue-50">
                                                Confirm your password
                                            </label>

                                            <div class="relative">
                                                <input
                                                    :type="$wire.showPassword ? 'text' : 'password'"
                                                    wire:model="password"
                                                    autocomplete="current-password"
                                                    placeholder="Enter your current password"
                                                    class="w-full rounded-2xl border border-white/10 bg-white/8 px-5 py-4 pr-14 text-sm text-white outline-none backdrop-blur-xl placeholder:text-blue-100/35 transition focus:border-cyan-300/40 focus:bg-white/10 focus:ring-4 focus:ring-cyan-400/10">

                                                <button type="button"
                                                    wire:click="$toggle('showPassword')"
                                                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-xl text-blue-100/55 transition hover:bg-white/10 hover:text-white">
                                                    <span class="material-symbols-outlined text-xl">
                                                        {{ $showPassword ? 'visibility_off' : 'visibility' }}
                                                    </span>
                                                </button>
                                            </div>

                                            @error('password')
                                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Confirmation Checkbox --}}
                                        <div>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 py-1 transition">
                                                <input type="checkbox" wire:model="confirmed"
                                                    class="peer sr-only">
                                                <div
                                                    class="relative mt-0.5 h-4 w-4 shrink-0 rounded border-2 border-white/20 bg-white/5 transition-all peer-checked:border-cyan-400 peer-checked:bg-cyan-500">
                                                    <svg class="absolute inset-0 h-full w-full text-white opacity-0 transition-opacity peer-checked:opacity-100"
                                                        viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="3"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12" />
                                                    </svg>
                                                </div>

                                                <span class="text-xs leading-5 text-blue-100/55">
                                                    I understand that my account will be scheduled for
                                                    permanent deletion in 15 days. All my data, including
                                                    orders, subscriptions, and backed-up files will be
                                                    permanently deleted.
                                                </span>
                                            </label>

                                            @error('confirmed')
                                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    <div
                                        class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">

                                        <button type="submit" wire:loading.attr="disabled"
                                            wire:target="scheduleDeletion"
                                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-300/20 bg-rose-500/15 px-6 py-3 text-sm font-bold text-rose-200 shadow-lg transition hover:-translate-y-0.5 hover:bg-rose-500/25 disabled:cursor-not-allowed disabled:opacity-70">

                                            <span wire:loading.remove wire:target="scheduleDeletion"
                                                class="material-symbols-outlined text-lg">
                                                delete_forever
                                            </span>

                                            <span wire:loading wire:target="scheduleDeletion"
                                                class="h-5 w-5 animate-spin rounded-full border-2 border-rose-300/40 border-t-rose-200">
                                            </span>

                                            <span wire:loading.remove wire:target="scheduleDeletion">
                                                Schedule Deletion
                                            </span>

                                            <span wire:loading wire:target="scheduleDeletion">
                                                Scheduling...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            @endif

                        </div>

                        {{-- Right Content --}}
                        <div class="space-y-6">

                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Before you go
                                </p>

                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Consider these alternatives
                                </h2>

                                <div class="mt-6 space-y-4">
                                    <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                        <div class="flex gap-3">
                                            <span
                                                class="material-symbols-outlined text-cyan-200">link_off</span>
                                            <div>
                                                <p class="text-sm font-bold text-white">
                                                    Unlink Billing Instead?
                                                </p>
                                                <p class="mt-1 text-sm leading-6 text-blue-100/65">
                                                    If you only want to disconnect your billing account, you can
                                                    unlink it from the
                                                    <a href="{{ route('account.link-whmcs') }}"
                                                        wire:navigate
                                                        class="font-semibold text-cyan-200 underline underline-offset-2 hover:text-cyan-100">
                                                        Link Billing Account
                                                    </a>
                                                    page without deleting your entire profile.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                        <div class="flex gap-3">
                                            <span
                                                class="material-symbols-outlined text-cyan-200">help</span>
                                            <div>
                                                <p class="text-sm font-bold text-white">
                                                    Need help?
                                                </p>
                                                <p class="mt-1 text-sm leading-6 text-blue-100/65">
                                                    If you are experiencing issues, feel free to reach out through
                                                    our support
                                                    <a href="{{ route('client.tickets.index') }}"
                                                        wire:navigate
                                                        class="font-semibold text-cyan-200 underline underline-offset-2 hover:text-cyan-100">
                                                        ticket system
                                                    </a>.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>