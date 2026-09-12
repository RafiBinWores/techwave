<?php

use App\Models\Company;
use App\Models\Department;
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
    public ?string $phone = null;
    public ?string $designation = null;
    public ?string $country = null;
    public string $type = 'personal';
    public string $role = 'staff';
    public ?int $department_id = null;
    public bool $is_active = true;

    public ?string $company_name = '';
    public ?string $company_phone = '';
    public ?string $company_address = '';
    public ?string $company_website = '';
    public $company_logo = null;

    public string $password = '';
    public string $password_confirmation = '';

    public $photo = null;

    public array $roles = [
        'admin' => 'Admin',
        'admin_manager' => 'Admin Manager',
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

            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],

            'type' => ['required', Rule::in(['personal', 'company'])],

            'company_name' => ['nullable', 'string', 'max:160', Rule::requiredIf($this->type === 'company')],

            'company_phone' => ['nullable', 'string', 'max:30', Rule::requiredIf($this->type === 'company')],

            'company_address' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->type === 'company')],

            'company_website' => ['nullable', 'url', 'max:160'],

            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'phone' => ['nullable', 'string', 'max:30'],

            'designation' => ['nullable', 'string', 'max:120'],

            'country' => ['nullable', 'string', 'size:2'],

            'is_active' => ['boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Image is nullable
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function departments()
    {
        return Department::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function countries(): array
    {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'BN' => 'Brunei',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CR' => 'Costa Rica',
            'CI' => 'Côte d’Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'SZ' => 'Eswatini',
            'ET' => 'Ethiopia',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GR' => 'Greece',
            'GD' => 'Grenada',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'North Korea',
            'KR' => 'South Korea',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'MK' => 'North Macedonia',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'QA' => 'Qatar',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'São Tomé and Príncipe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'SS' => 'South Sudan',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatican City',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $photoPath = null;

        if ($this->photo) {
            $photoPath = $this->photo->store('users/photos', 'public');
        }

        $companyId = null;

        if ($this->type === 'company') {
            $companyLogoPath = null;

            if ($this->company_logo) {
                $companyLogoPath = $this->company_logo->store('companies/logos', 'public');
            }

            $company = Company::create([
                'company_name' => $validated['company_name'],
                'phone' => $validated['company_phone'],
                'address' => $validated['company_address'],
                'website' => $validated['company_website'] ?? null,
                'logo' => $companyLogoPath,
            ]);

            $companyId = $company->id;
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'country' => $validated['country'] ?? null,
            'type' => $validated['type'],
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'company_id' => $companyId,
            'is_active' => $validated['is_active'],
            'password' => Hash::make($validated['password']),
            'avatar' => $photoPath,
        ]);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'User profile created successfully.',
        ]);

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function discard(): void
    {
        $this->reset(['name', 'email', 'phone', 'designation', 'country', 'department_id', 'company_name', 'company_phone', 'company_address', 'company_website', 'company_logo', 'password', 'password_confirmation', 'photo']);

        $this->role = 'staff';
        $this->type = 'personal';
        $this->is_active = true;

        $this->resetValidation();

        $this->dispatch('toast', message: 'Changes discarded.', type: 'info');
    }

    public function generatePassword(): void
    {
        $password = Str::password(14);

        $this->password = $password;
        $this->password_confirmation = $password;

        $this->resetValidation(['password', 'password_confirmation']);

        $this->dispatch('toast', message: 'Secure password generated.', type: 'success');
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

        <a href="{{ route('admin.users.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Users
        </a>
    </div>

    <form wire:submit.prevent="save">
        <!-- Bento Grid Form Layout -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Profile Photo Section -->
            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-h3 font-h2">Identity</h3>

                    <div class="flex flex-col items-center text-center">
                        <div class="relative group">
                            <div
                                class="h-32 w-32 overflow-hidden rounded-full border-4 border-slate-50 bg-slate-100 shadow-sm">
                                @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" alt="Profile preview"
                                    class="h-full w-full object-cover" />
                                @else
                                <div
                                    class="flex h-full w-full items-center justify-center bg-primary/10 text-4xl font-bold text-primary">
                                    {{ $name ? strtoupper(Str::substr($name, 0, 1)) : 'U' }}
                                </div>
                                @endif
                            </div>

                            <label for="photo"
                                class="absolute bottom-1 right-1 cursor-pointer rounded-full border border-slate-200 bg-white p-1.5 text-primary shadow-sm transition-colors hover:bg-slate-50">
                                <span class="material-symbols-outlined text-[20px]">photo_camera</span>
                            </label>

                            <input id="photo" type="file" wire:model="photo"
                                accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" />
                        </div>

                        <div class="mt-6 space-y-2">
                            <p class="text-label-md font-label-md">Upload profile picture</p>
                            <p class="text-body-sm font-body-sm text-secondary">JPG, PNG or WEBP. Max size 2MB.</p>
                        </div>

                        <label for="photo"
                            class="mt-6 w-full cursor-pointer rounded-lg border border-dashed border-slate-300 py-2 text-label-sm font-label-md text-secondary transition-all hover:border-primary hover:text-primary">
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
                            <div @class([ 'h-2.5 w-2.5 rounded-full' , 'bg-emerald-500'=> $is_active,
                                'bg-red-500' => !$is_active,
                                ])></div>

                            <span class="text-label-md font-label-md text-on-surface">
                                {{ $is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </div>

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="is_active" class="peer sr-only" />

                            <div
                                class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100">
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

                            <input wire:model="name"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text" placeholder="Enter full name" />

                            @error('name')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Email Address
                            </label>

                            <input wire:model="email"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="email" placeholder="user@example.com" />

                            @error('email')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                System Role
                            </label>

                            <div class="relative">
                                <select wire:model="role"
                                    class="relative z-10 w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <span
                                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
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
                                <select wire:model="department_id"
                                    class="relative z-10 w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    <option value="">No Department</option>

                                    @foreach ($this->departments() as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->name }}
                                    </option>
                                    @endforeach
                                </select>

                                <span
                                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @error('department_id')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            <div class="pt-1">
                                <a href="{{ route('admin.departments.create') }}" wire:navigate
                                    class="text-body-sm inline-flex items-center gap-1 font-body-md text-primary transition-all font-semibold hover:text-blue-700">
                                    <span class="material-symbols-outlined text-[14px]">add</span>
                                    Create new department
                                </a>
                            </div>
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Account Type
                            </label>

                            <div class="relative">
                                <select wire:model.live="type"
                                    class="relative z-10 w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    <option value="personal">Personal</option>
                                    <option value="company">Company</option>
                                </select>

                                <span
                                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @error('type')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Phone Number
                            </label>

                            <input wire:model="phone"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text" placeholder="Enter phone number" />

                            @error('phone')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Country
                            </label>

                            <div class="relative">
                                <select wire:model="country"
                                    class="relative z-10 w-full appearance-none rounded-lg border border-slate-200 bg-transparent px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    <option value="">No Country</option>

                                    @foreach ($this->countries() as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>

                                <span
                                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @error('country')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-2 space-y-2 md:col-span-1">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Designation
                            </label>

                            <input wire:model="designation"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                type="text" placeholder="e.g. CEO, Manager, IT Officer" />

@error('designation')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Company Information -->
                @if ($type === 'company')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">business</span>
                            Company Information
                        </h3>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2 space-y-2 md:col-span-1">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Company Name
                                </label>

                                <input wire:model="company_name"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    type="text" placeholder="Enter company name" />

                                @error('company_name')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2 space-y-2 md:col-span-1">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Company Phone
                                </label>

                                <input wire:model="company_phone"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    type="text" placeholder="Enter company phone" />

                                @error('company_phone')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2 space-y-2">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Company Website
                                </label>

                                <input wire:model="company_website"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    type="url" placeholder="https://example.com" />

                                @error('company_website')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2 space-y-2">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Company Address
                                </label>

                                <textarea wire:model="company_address" rows="3"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    placeholder="Enter company address"></textarea>

                                @error('company_address')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2 space-y-2">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Company Logo
                                </label>

                                <div class="flex items-center gap-4">
                                    @if ($company_logo)
                                        <img src="{{ $company_logo->temporaryUrl() }}" alt="Company logo preview"
                                            class="h-14 w-14 rounded-lg border border-slate-200 object-contain bg-white" />
                                    @endif

                                    <label for="company_logo"
                                        class="cursor-pointer rounded-lg border border-dashed border-slate-300 px-4 py-2.5 text-label-sm font-label-md text-secondary transition-all hover:border-primary hover:text-primary">
                                        Browse Logo
                                    </label>

                                    <input id="company_logo" type="file" wire:model="company_logo"
                                        accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" />
                                </div>

                                <p class="text-body-sm font-body-sm text-secondary">JPG, PNG or WEBP. Max size 2MB.</p>

                                <div wire:loading wire:target="company_logo" class="text-sm text-primary">
                                    Uploading logo...
                                </div>

                                @error('company_logo')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Password Management -->
                <div x-data="{
                    showPassword: false,
                    showConfirmPassword: false,
                }" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
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
                                    <input wire:model="password"
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        placeholder="Enter password" :type="showPassword ? 'text' : 'password'" />

                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary">
                                        <span class="material-symbols-outlined text-[20px]"
                                            x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
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
                                    <input wire:model="password_confirmation"
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        placeholder="Confirm password"
                                        :type="showConfirmPassword ? 'text' : 'password'" />

                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary">
                                        <span class="material-symbols-outlined text-[20px]"
                                            x-text="showConfirmPassword ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <button type="button" wire:click="generatePassword"
                                class="flex items-center gap-2 text-label-md font-label-md text-primary hover:underline">
                                <span class="material-symbols-outlined text-[18px]">key</span>
                                Generate Secure Password
                            </button>
                        </div>
                    </div>
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
                            class="px-5 py-2 bg-white text-red-600 border border-red-200 rounded-lg text-label-sm font-label-md hover:bg-red-600 hover:text-white transition-all cursor-pointer">Delete
                            Permanently</button>
                    </div>
                </div> --}}

                <!-- Bottom Action Buttons -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" wire:click="discard" wire:loading.attr="disabled"
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                            Discard Changes
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                            <span wire:loading.remove wire:target="save">Save User Profile</span>

                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>