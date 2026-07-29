<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('My Profile')] class extends Component {
    use WithFileUploads;

    public $avatarUpload;

    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public ?string $designation = '';
    public ?string $avatar = null;

    protected array $rules = [
        'name' => ['required', 'string', 'max:120'],
        'phone' => ['nullable', 'string', 'max:30'],
        'designation' => ['nullable', 'string', 'max:120'],
        'avatarUpload' => ['nullable', 'image', 'max:2048'],
    ];

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->designation = $user->designation ?? '';
        $this->avatar = $user->avatar;
    }

    public function updated($property): void
    {
        if ($property === 'avatarUpload') {
            return;
        }

        $this->resetValidation($property);
        $this->validateOnly($property);
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        if ($this->avatarUpload) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $this->avatar = $this->avatarUpload->store('users/avatars', 'public');
        }

        $user->name = $this->name;
        $user->phone = $this->phone;
        $user->designation = $this->designation;
        $user->avatar = $this->avatar;
        $user->save();

        $this->avatarUpload = null;

        $this->dispatch('toast', message: 'Profile updated successfully.', type: 'success');
    }

    public function avatarPreview(): string
    {
        if ($this->avatarUpload) {
            return $this->avatarUpload->temporaryUrl();
        }

        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        return '';
    }
};
?>

<div>
    <!-- Header Section -->
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">My Profile</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Manage your personal information and profile picture.
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-12 gap-6">
            <!-- Profile Photo Section -->
            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-h3 font-h2">Profile Photo</h3>

                    <div class="flex flex-col items-center text-center">
                        <div class="h-32 w-32 overflow-hidden rounded-full border-4 border-slate-50 bg-slate-100 shadow-sm">
                            @if ($previewUrl = $this->avatarPreview())
                                <img src="{{ $previewUrl }}" alt="Avatar" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-primary/10 text-4xl font-bold text-primary">
                                    {{ strtoupper(substr($this->name ?: 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <input id="avatar-upload" type="file" wire:model="avatarUpload" accept="image/jpeg,image/png,image/webp" class="hidden">

                        <div class="mt-6 space-y-2">
                            <p class="text-label-md font-label-md">Update profile picture</p>
                            <p class="text-body-sm font-body-sm text-secondary">JPG, PNG or WEBP. Max size 2MB.</p>
                        </div>

                        <label for="avatar-upload"
                            class="mt-6 w-full cursor-pointer rounded-lg border border-dashed border-slate-300 py-2 text-label-sm font-label-md text-secondary transition-all hover:border-primary hover:text-primary">
                            Browse Files
                        </label>

                        <div wire:loading wire:target="avatarUpload" class="mt-3 text-sm text-primary">
                            Uploading image...
                        </div>

                        @error('avatarUpload')
                            <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Main Form Section -->
            <div class="col-span-12 space-y-6 lg:col-span-8">
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">person</span>
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Full Name</label>
                            <input wire:model.live.debounce.300ms="name"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text"
                                placeholder="Enter your name">
                            @error('name')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Email Address</label>
                            <input wire:model="email" disabled
                                class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-body-md font-body-md text-slate-500"
                                type="email">
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Phone</label>
                            <input wire:model.live.debounce.300ms="phone"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text"
                                placeholder="Enter your phone number">
                            @error('phone')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Designation</label>
                            <input wire:model.live.debounce.300ms="designation"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text"
                                placeholder="e.g. Software Engineer">
                            @error('designation')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-4 border-t border-slate-100 pt-6">
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Save Changes</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
