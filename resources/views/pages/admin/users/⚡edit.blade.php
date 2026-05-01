<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Edit User')] class extends Component {
    use WithFileUploads;

    public User $user;

    public string $name = '';
    public string $email = '';
    public string $role = 'staff';
    public ?int $department_id = null;
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

    public function mount(User $user): void
    {
        $this->user = $user;

        $this->name = $user->name;
        $this->email = $user->email;
         $this->role = $user->role->value;
        $this->department_id = $user->department_id;
        $this->is_active = (bool) $user->is_active;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],

            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],

            'role' => ['required', Rule::in(array_keys($this->roles))],

            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],

            'is_active' => ['boolean'],

            // Password optional on edit
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            // Image optional / nullable
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function departments()
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function update(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'is_active' => $validated['is_active'],
        ];

        if (! blank($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->photo) {
            if ($this->user->avatar && Storage::disk('public')->exists($this->user->avatar)) {
                Storage::disk('public')->delete($this->user->avatar);
            }

            $data['avatar'] = $this->photo->store('users/photos', 'public');
        }

        $this->user->update($data);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'User profile updated successfully.',
        ]);

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function discard(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->role = $this->user->role;
        $this->department_id = $this->user->department_id;
        $this->is_active = (bool) $this->user->is_active;

        $this->password = '';
        $this->password_confirmation = '';
        $this->photo = null;

        $this->resetValidation();

        $this->dispatch(
            'toast',
            message: 'Changes discarded.',
            type: 'info'
        );
    }

    public function generatePassword(): void
    {
        $password = Str::password(14);

        $this->password = $password;
        $this->password_confirmation = $password;

        $this->resetValidation(['password', 'password_confirmation']);

        $this->dispatch(
            'toast',
            message: 'Secure password generated.',
            type: 'success'
        );
    }
};
?>

<div>
    <!-- Header Section -->
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Edit User</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Update user access level, department, account status and security credentials.
            </p>
        </div>

        <a
            href="{{ route('admin.users.index') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50"
        >
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Users
        </a>
    </div>

    <form wire:submit.prevent="update">
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
                                @elseif ($user->avatar)
                                    <img
                                        src="{{ Storage::url($user->avatar) }}"
                                        alt="{{ $user->name }}"
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
                            <p class="text-label-md font-label-md">Update profile picture</p>
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
                            <div
                                @class([
                                    'h-2.5 w-2.5 rounded-full',
                                    'bg-emerald-500' => $is_active,
                                    'bg-red-500' => ! $is_active,
                                ])
                            ></div>

                            <span class="text-label-md font-label-md text-on-surface">
                                {{ $is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </div>

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                wire:model="is_active"
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
                                wire:model="name"
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
                                wire:model="email"
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
                                    wire:model="role"
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

                            <div class="relative">
                                <select
                                    wire:model="department_id"
                                    class="relative z-10 w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                >
                                    <option value="">No Department</option>

                                    @foreach ($this->departments() as $department)
                                        <option value="{{ $department->id }}">
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @error('department_id')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            <div class="pt-1">
                                <a
                                    href="{{ route('admin.departments.create') }}"
                                    wire:navigate
                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                >
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    Create new department
                                </a>
                            </div>
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

                    <p class="mb-6 text-sm text-secondary">
                        Leave password fields empty if you do not want to change this user's password.
                    </p>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2 space-y-2 md:col-span-1">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    New Password
                                </label>

                                <div class="relative">
                                    <input
                                        wire:model="password"
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        placeholder="Enter new password"
                                        :type="showPassword ? 'text' : 'password'"
                                    />

                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[20px]"
                                            x-text="showPassword ? 'visibility_off' : 'visibility'"
                                        ></span>
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
                                        wire:model="password_confirmation"
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        placeholder="Confirm new password"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                    />

                                    <button
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[20px]"
                                            x-text="showConfirmPassword ? 'visibility_off' : 'visibility'"
                                        ></span>
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
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer"
                        >
                            Discard Changes
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="update">Update User Profile</span>

                            <span wire:loading wire:target="update" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Updating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>