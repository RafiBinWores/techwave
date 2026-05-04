<?php

use App\Models\CompanyLogo;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Edit Company Logo')] class extends Component {
    use WithFileUploads;

    public CompanyLogo $companyLogo;

    public string $name = '';
    public string $website_url = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    public $logo = null;

    public function mount(CompanyLogo $companyLogo): void
    {
        $this->companyLogo = $companyLogo;

        $this->name = $companyLogo->name;
        $this->website_url = $companyLogo->website_url ?? '';
        $this->sort_order = (int) $companyLogo->sort_order;
        $this->is_active = (bool) $companyLogo->is_active;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function update(): void
    {
        $validated = $this->validate();

        $logoPath = $this->companyLogo->logo;

        if ($this->logo) {
            if ($this->companyLogo->logo && Storage::disk('public')->exists($this->companyLogo->logo)) {
                Storage::disk('public')->delete($this->companyLogo->logo);
            }

            $logoPath = $this->logo->store('company-logos', 'public');
        }

        $this->companyLogo->update([
            'name' => $validated['name'],
            'logo' => $logoPath,
            'website_url' => $validated['website_url'] ?: null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ]);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Company logo updated successfully.',
        ]);

        $this->redirectRoute('admin.company-logos.index', navigate: true);
    }

    public function discard(): void
    {
        $this->name = $this->companyLogo->name;
        $this->website_url = $this->companyLogo->website_url ?? '';
        $this->sort_order = (int) $this->companyLogo->sort_order;
        $this->is_active = (bool) $this->companyLogo->is_active;
        $this->logo = null;

        $this->resetValidation();

        $this->dispatch(
            'toast',
            message: 'Changes discarded.',
            type: 'info'
        );
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Edit Company Logo</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Update company logo, name, website URL and display status.
            </p>
        </div>

        <a
            href="{{ route('admin.company-logos.index') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50"
        >
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Companies
        </a>
    </div>

    <form wire:submit.prevent="update">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-h3 font-h2">Company Logo</h3>

                    <label
                        for="logo"
                        class="flex h-64 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-outline-variant bg-surface transition-colors hover:bg-surface-container"
                    >
                        @if ($logo)
                            <img
                                src="{{ $logo->temporaryUrl() }}"
                                alt="Logo preview"
                                class="h-full w-full object-contain p-8"
                            />
                        @elseif ($companyLogo->logo)
                            <img
                                src="{{ Storage::url($companyLogo->logo) }}"
                                alt="{{ $companyLogo->name }}"
                                class="h-full w-full object-contain p-8"
                            />
                        @else
                            <span class="material-symbols-outlined mb-2 text-5xl text-outline">
                                add_photo_alternate
                            </span>

                            <p class="text-sm font-body-sm text-outline">
                                Click to upload company logo
                            </p>
                        @endif
                    </label>

                    <input
                        id="logo"
                        type="file"
                        wire:model="logo"
                        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                        class="hidden"
                    />

                    <div wire:loading wire:target="logo" class="mt-3 text-sm text-primary">
                        Uploading logo...
                    </div>

                    @error('logo')
                        <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-label-sm font-label-sm uppercase tracking-widest text-secondary">
                        Display Status
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

                            <div>
                                <span class="block text-label-md font-label-md text-on-surface">
                                    {{ $is_active ? 'Active' : 'Inactive' }}
                                </span>

                                <span class="text-xs text-secondary">
                                    Show or hide this logo publicly.
                                </span>
                            </div>
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
                </div>
            </div>

            <div class="col-span-12 space-y-6 lg:col-span-8">
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">business</span>
                        Company Information
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Company Name
                            </label>

                            <input
                                type="text"
                                wire:model="name"
                                placeholder="Example: Cloudflare"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />

                            @error('name')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Sort Order
                            </label>

                            <input
                                type="number"
                                min="0"
                                wire:model="sort_order"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />

                            @error('sort_order')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Website URL
                            </label>

                            <input
                                type="url"
                                wire:model="website_url"
                                placeholder="https://example.com"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />

                            @error('website_url')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

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
                            <span wire:loading.remove wire:target="update">Update Company Logo</span>

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