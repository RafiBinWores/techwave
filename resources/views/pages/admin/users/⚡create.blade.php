<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Create User')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $role = 'staff';
    public string $department = '';
    public bool $is_active = true;

    public string $password = '';
    public string $password_confirmation = '';

    public $photo = null;

    public array $roles = [
        'admin' => 'Admin',
        'manager' => 'Manager',
        'staff' => 'Staff',
        'client' => 'Client',
    ];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys($this->roles))],
            'department' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],

            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $photoPath = null;

        if ($this->photo) {
            $photoPath = $this->photo->store('users/photos', 'public');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department' => $validated['department'] ?? null,
            'is_active' => $validated['is_active'],
            'password' => Hash::make($validated['password']),
            'avatar' => $photoPath,
        ]);

        session()->flash('success', 'User profile created successfully.');

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function discard(): void
    {
        $this->reset([
            'name',
            'email',
            'department',
            'password',
            'password_confirmation',
            'photo',
        ]);

        $this->role = 'staff';
        $this->is_active = true;

        $this->resetValidation();
    }

    public function generatePassword(): void
    {
        $password = Str::password(14);

        $this->password = $password;
        $this->password_confirmation = $password;

        $this->resetValidation(['password', 'password_confirmation']);
    }
};
?>

<div>
    <!-- Header Section -->
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Create User</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Create infrastructure access levels and security credentials for system operators.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <!-- Bento Grid Form Layout -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Profile Photo Section -->
            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-h3 font-h2">Identity</h3>

                    <div class="flex flex-col items-center text-center">
                        <div class="relative group">
                            <div class="h-32 w-32 overflow-hidden rounded-full border-4 border-slate-50 bg-slate-100 shadow-sm">
                                @if ($photo)
                                    <img
                                        src="{{ $photo->temporaryUrl() }}"
                                        alt="Profile preview"
                                        class="h-full w-full object-cover"
                                    />
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-primary/10 text-4xl font-bold text-primary">
                                        {{ $name ? strtoupper(Str::substr($name, 0, 1)) : 'U' }}
                                    </div>
                                @endif
                            </div>

                            <label
                                for="photo"
                                class="absolute bottom-1 right-1 cursor-pointer rounded-full border border-slate-200 bg-white p-1.5 text-primary shadow-sm transition-colors hover:bg-slate-50"
                            >
                                <span class="material-symbols-outlined text-[20px]">photo_camera</span>
                            </label>

                            <input
                                id="photo"
                                type="file"
                                wire:model="photo"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                class="hidden"
                            />
                        </div>

                        <div class="mt-6 space-y-2">
                            <p class="text-label-md font-label-md">Upload profile picture</p>
                            <p class="text-body-sm font-body-sm text-secondary">JPG, PNG or WEBP. Max size 2MB.</p>
                        </div>

                        <label
                            for="photo"
                            class="mt-6 w-full cursor-pointer rounded-lg border border-dashed border-slate-300 py-2 text-label-sm font-label-md text-secondary transition-all hover:border-primary hover:text-primary"
                        >
                            Browse Files
                        </label>

                        <div wire:loading wire:target="photo" class="mt-3 text-sm text-primary">
                            Uploading image...
                        </div>

                        @error('photo')
                            <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Account Status Section -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-label-sm font-label-sm uppercase tracking-widest text-secondary">
                        Account Status
                    </h3>

                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3">
                        <div class="flex items-center gap-3">
                            <div @class([
                                'h-2.5 w-2.5 rounded-full',
                                'bg-emerald-500' => $is_active,
                                'bg-red-500' => ! $is_active,
                            ])></div>

                            <span class="text-label-md font-label-md text-on-surface">
                                {{ $is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </div>

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                wire:model.live="is_active"
                                class="peer sr-only"
                            />

                            <div
                                class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100">
                            </div>
                        </label>
                    </div>

                    <p class="mt-3 text-body-sm font-body-sm leading-relaxed text-secondary">
                        Suspended users cannot access the dashboard or receive system alerts.
                    </p>
                </div>
            </div>

            <!-- Main Form Section -->
            <div class="col-span-12 space-y-6 lg:col-span-8">
                <!-- General Info -->
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">person</span>
                        General Information
                    </h3>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Full Name
                            </label>

                            <input
                                wire:model.live="name"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text"
                                placeholder="Enter full name"
                            />

                            @error('name')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Email Address
                            </label>

                            <input
                                wire:model.live="email"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="email"
                                placeholder="user@example.com"
                            />

                            @error('email')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                System Role
                            </label>

                            <div class="relative">
                                <select
                                    wire:model.live="role"
                                    class="relative z-10 w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                >
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @error('role')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Assigned Department
                            </label>

                            <input
                                wire:model.live="department"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text"
                                placeholder="Cloud Infrastructure"
                            />

                            @error('department')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Password Management -->
                <div
                    x-data="{
                        showPassword: false,
                        showConfirmPassword: false,
                    }"
                    class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm"
                >
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">lock</span>
                        Security Credentials
                    </h3>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2 space-y-2 md:col-span-1">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    New Password
                                </label>

                                <div class="relative">
                                    <input
                                        wire:model.live="password"
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        placeholder="Enter password"
                                        :type="showPassword ? 'text' : 'password'"
                                    />

                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary"
                                    >
                                        <span class="material-symbols-outlined text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                </div>

                                @error('password')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2 space-y-2 md:col-span-1">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Confirm Password
                                </label>

                                <div class="relative">
                                    <input
                                        wire:model.live="password_confirmation"
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        placeholder="Confirm password"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                    />

                                    <button
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary"
                                    >
                                        <span class="material-symbols-outlined text-[20px]" x-text="showConfirmPassword ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <button
                                type="button"
                                wire:click="generatePassword"
                                class="flex items-center gap-2 text-label-md font-label-md text-primary hover:underline"
                            >
                                <span class="material-symbols-outlined text-[18px]">key</span>
                                Generate Secure Password
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Buttons -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <button
                            type="button"
                            wire:click="discard"
                            wire:loading.attr="disabled"
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Discard Changes
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="save">Save User Profile</span>

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



{{-- <!-- Danger Zone -->
                <div class="bg-red-50/30 p-8 rounded-xl border border-red-100 shadow-sm">
                    <h3 class="text-label-sm font-label-sm uppercase tracking-widest text-red-700 mb-4">Critical
                        Actions</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-label-md font-label-md text-on-surface">Delete Account</p>
                            <p class="text-body-sm font-body-sm text-secondary mt-1">Permanently remove this user and
                                all associated audit logs and permissions.</p>
                        </div>
                        <button
                            class="px-5 py-2 bg-white text-red-600 border border-red-200 rounded-lg text-label-sm font-label-md hover:bg-red-600 hover:text-white transition-all">Delete
                            Permanently</button>
                    </div>
                </div> --}}
