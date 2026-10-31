<?php

use App\Models\Product;
use App\Services\ImageService;
use App\Services\Products\ProductContent;
use App\Services\Products\ProductPresentation;
use App\Services\Products\ProductPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Product editor')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Product $product = null;

    public string $name = '';

    public string $summary = '';

    public string $description = '';

    public string $demo_url = '';

    public bool $is_published = false;

    public bool $pricing_enabled = false;

    public bool $show_in_navbar = true;

    public $sort_order = 0;

    public array $installation_steps = [];

    public array $limit_definitions = [];

    public $cover = null;

    public $software = null;

    public function boot(): void
    {
        ProductContent::authorizeAdmin();
    }

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            foreach (['name', 'summary', 'description', 'demo_url'] as $field) {
                $this->$field = $product->$field ?? '';
            }
            $this->is_published = $product->is_published;
            $this->pricing_enabled = $product->pricing_enabled;
            $this->show_in_navbar = $product->show_in_navbar;
            $this->sort_order = $product->sort_order;
            $this->installation_steps = $product->installation_steps ?? [];
            $this->limit_definitions = $product->limit_definitions ?? [];
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => [
                'bail',
                'required',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // Quill can submit HTML that contains only blank paragraphs or non-breaking spaces.
                    $visibleText = html_entity_decode(
                        strip_tags(app(ProductPresentation::class)->overview($value)),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    );
                    if (! preg_match('/[^\s\p{Z}\p{C}]/u', $visibleText)) {
                        $fail('Please enter an overview for your software.');
                    }
                },
            ],
            'demo_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ProductPricing::safeUrl($value)) {
                        $fail('Use an HTTP or HTTPS demo URL without embedded credentials.');
                    }
                },
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_published' => ['boolean'],
            'pricing_enabled' => ['boolean'],
            'show_in_navbar' => ['boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'software' => ['nullable', 'file', 'extensions:exe,msi,zip', 'max:256000'],
            'limit_definitions' => ['array', 'max:20'],
            'limit_definitions.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
            'limit_definitions.*.label' => ['required', 'string', 'max:100'],
            'limit_definitions.*.unit' => ['nullable', 'string', 'max:40'],
            'installation_steps' => ['array', 'max:40'],
            'installation_steps.*.title' => ['required', 'string', 'max:160'],
            'installation_steps.*.text' => ['nullable', 'string', 'max:10000'],
            'installation_steps.*.command' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Please enter a product name.',
            'summary.required' => 'Please enter a short description.',
            'description.required' => 'Please enter an overview for your software.',
            'installation_steps.*.title.required' => 'Please enter a title for this installation step.',
            'sort_order.required' => 'Please enter a display order.',
            'software.max' => 'The software file must not exceed 250 MB.',
            'software.extensions' => 'Upload a ZIP, EXE, or MSI software file.',
            'cover.max' => 'The product image must not exceed 4 MB.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'summary' => 'short description',
            'description' => 'overview',
            'demo_url' => 'demo link',
            'sort_order' => 'display order',
            'cover' => 'product image',
            'software' => 'software file',
            'installation_steps.*.title' => 'step title',
            'installation_steps.*.text' => 'step instructions',
            'installation_steps.*.command' => 'setup command',
        ];
    }

    public function updated(string $property): void
    {
        if (! in_array($property, ['cover', 'software'], true)) {
            $this->validateOnly($property);
        }
    }

    public function discard(): void
    {
        $product = $this->product;
        $this->reset([
            'name',
            'summary',
            'description',
            'demo_url',
            'sort_order',
            'is_published',
            'pricing_enabled',
            'show_in_navbar',
            'installation_steps',
            'limit_definitions',
            'cover',
            'software',
        ]);
        if ($product) {
            $this->mount($product->fresh());
        }
        $this->resetValidation();
    }

    public function updatedCover(): void
    {
        $this->validateOnly('cover', ['cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
    }

    public function updatedSoftware(): void
    {
        $this->validateOnly('software', [
            'software' => 'nullable|file|extensions:exe,msi,zip|max:256000',
        ]);
    }

    public function clearSoftware(): void
    {
        $this->reset('software');
        $this->resetValidation('software');
    }

    public function addLimit(): void
    {
        if (count($this->limit_definitions) < 20) {
            $this->limit_definitions[] = ['key' => '', 'label' => '', 'unit' => ''];
        }
    }

    public function removeLimit(int $index): void
    {
        unset($this->limit_definitions[$index]);
        $this->limit_definitions = array_values($this->limit_definitions);
    }

    public function addInstallationStep(): void
    {
        if (count($this->installation_steps) < 40) {
            $this->installation_steps[] = ['title' => '', 'text' => '', 'command' => ''];
        }
    }

    public function removeInstallationStep(int $index): void
    {
        unset($this->installation_steps[$index]);
        $this->installation_steps = array_values($this->installation_steps);
    }

    /**
     * Save optional media with the product, retaining its previous release when no file is selected.
     */
    public function save(): void
    {
        ProductContent::authorizeAdmin();
        $this->validate();
        $existing = $this->product ? Product::findOrFail($this->product->id) : null;
        $input = [
            'name' => $this->name,
            'summary' => $this->summary,
            'description' => $this->description,
            'demo_url' => $this->demo_url ?: null,
            'is_published' => $this->is_published,
            'pricing_enabled' => $this->pricing_enabled,
            'show_in_navbar' => $this->show_in_navbar,
            'sort_order' => $this->sort_order,
            // Limit definitions belong to this product; individual plan amounts are edited in pricing.
            'limit_definitions' => $this->limit_definitions,
            'gallery' => $existing?->gallery ?? [],
            'tutorials' => $existing?->tutorials ?? [],
            'installation_steps' => $this->installation_steps,
        ];
        $product = DB::transaction(function () use ($input, $existing): Product {
            $product = app(ProductContent::class)->save($input, $existing);
            if ($this->cover) {
                try {
                    $path = app(ImageService::class)->optimizeAndStore(
                        $this->cover,
                        'products/'.$product->id.'/media',
                        1600,
                        85,
                        (string) Str::uuid(),
                    );
                } catch (\Throwable $exception) {
                    report($exception);
                    throw ValidationException::withMessages(['cover' => 'The image could not be saved. Please try again. Your existing product has been kept.']);
                }
                $product->update(['cover_path' => $path]);
            }

            // Use the same admin-configured cloud providers as images, with a private document prefix.
            if ($this->software) {
                $releasePath = null;

                try {
                    $extension = strtolower($this->software->getClientOriginalExtension());
                    $releasePath = $this->software->storeAs(
                        'products/'.$product->id.'/releases',
                        Str::uuid().'.'.$extension,
                        'documents',
                    );

                    if (! $releasePath) {
                        throw new \RuntimeException('The software file could not be stored.');
                    }

                    $product->update([
                        'release_path' => $releasePath,
                        'release_name' => Str::limit(basename($this->software->getClientOriginalName()), 255, ''),
                        'release_size' => $this->software->getSize(),
                    ]);
                } catch (\Throwable $exception) {
                    if ($releasePath) {
                        Storage::disk('documents')->delete($releasePath);
                    }

                    report($exception);
                    throw ValidationException::withMessages([
                        'software' => 'The software file could not be saved. Please try again. Your existing product has been kept.',
                    ]);
                }
            }

            return $product;
        });
        session()->flash('toast', ['type' => 'success', 'message' => 'Product saved successfully.']);
        $this->redirectRoute('admin.products.edit', ['product' => $product], navigate: true);
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">
                {{ $product ? 'Edit product' : 'Add product' }}
            </h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                {{ $product ? $product->name : 'Introduce your software and configure its product details.' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($product && $pricing_enabled)
            <a
                href="{{ route('admin.products.plans', $product) }}"
                wire:navigate
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-sm font-semibold text-primary">
                <span class="material-symbols-outlined text-lg">
                    payments
                </span>
                Manage pricing plans
            </a>
            @endif
            <a href="{{ route('admin.products.index') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white
                    px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>
                Back to Products
            </a>
        </div>
    </div>
    {{-- Product content, optional installation instructions, and publication settings. --}}
    <form wire:submit="save" novalidate class="space-y-6">
        @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">
                Please check the following:
            </p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6 lg:col-span-8">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">inventory_2</span>
                        Product Information
                    </h2>
                    <p class="mt-1 text-sm text-secondary">
                        The essentials about your software.
                    </p>
                    <div class="mt-6 space-y-5">
                        <div>
                            <label for="product-name" class="mb-2 block font-label-md text-on-surface">
                                Product name
                                <span class="text-red-600">
                                    *
                                </span>
                            </label>
                            <input
                                id="product-name"
                                wire:model.blur="name"
                                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                aria-describedby="product-name-error"
                                maxlength="160"
                                required
                                placeholder="e.g. Techwave NetWatch"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('name') border-red-500! focus:ring-red-500/10! @enderror">
                            @error('name')
                            <p id="product-name-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="product-summary" class="mb-2 block font-label-md text-on-surface">
                                Short description
                                <span class="text-red-600">
                                    *
                                </span>
                            </label>
                            <textarea
                                id="product-summary"
                                wire:model.blur="summary"
                                aria-invalid="{{ $errors->has('summary') ? 'true' : 'false' }}"
                                aria-describedby="product-summary-error"
                                maxlength="500"
                                required
                                rows="3"
                                placeholder="What does this software help people do?"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('summary') border-red-500! focus:ring-red-500/10! @enderror"></textarea>
                            @error('summary')
                            <p id="product-summary-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div
                            role="group"
                            aria-labelledby="product-overview-label"
                            aria-describedby="product-description-error"
                            aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}"
                            class="space-y-2 @error('description') [&_.quill-fixed-editor]:border-red-500! @enderror">
                            <label id="product-overview-label" class="block font-label-md text-on-surface">
                                Overview
                                <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            {{-- Quill manages this DOM; Livewire receives its HTML through the entangled value. --}}
                            <div
                                wire:ignore
                                x-data="{
                                    quill: null,
                                    value: @entangle('description'),

                                    init() {
                                        this.quill = new Quill(this.$refs.editor, {
                                            theme: 'snow',
                                            placeholder: 'Describe your software, its features and who it is for…',
                                            modules: {
                                                toolbar: [
                                                    [{ header: [2, 3, false] }],
                                                    [{ font: [] }],
                                                    ['bold', 'italic', 'underline', 'strike'],
                                                    [{ color: [] }, { background: [] }],
                                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                                    [{ align: [] }],
                                                    ['blockquote', 'code-block'],
                                                    ['link'],
                                                    ['clean']
                                                ]
                                            }
                                        });
                                        this.quill.root.setAttribute('aria-labelledby', 'product-overview-label');
                                        this.quill.root.setAttribute('aria-required', 'true');
                                        this.quill.root.setAttribute('aria-describedby', 'product-description-error');

                                        if (this.value && this.value !== '<p><br></p>') {
                                            this.quill.clipboard.dangerouslyPasteHTML(this.value, 'silent');
                                        }

                                        this.quill.on('text-change', () => {
                                            this.value = this.quill.getText().trim().length ? this.quill.root.innerHTML : '';
                                        });

                                        this.$watch('value', (newValue) => {
                                            const cleanValue = newValue === '<p><br></p>' ? '' : (newValue || '');
                                            if (this.quill.root.innerHTML !== cleanValue) {
                                                this.quill.clipboard.dangerouslyPasteHTML(cleanValue, 'silent');
                                            }
                                        });
                                    }
                                }"
                                @focusout="if (!$el.contains($event.relatedTarget)) { $wire.$set('description', value, true) }"
                                class="quill-fixed-editor h-90 overflow-hidden rounded-lg border border-outline-variant bg-white">
                                <div x-ref="editor">
                                </div>
                            </div>
                            @error('description')
                            <p id="product-description-error" role="alert" class="text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror
                        </div>
                        <div>
                            <label for="product-demo" class="mb-2 block font-label-md text-on-surface">
                                Demo link (optional)
                            </label>
                            <input
                                id="product-demo"
                                wire:model.blur="demo_url"
                                aria-invalid="{{ $errors->has('demo_url') ? 'true' : 'false' }}"
                                aria-describedby="product-demo_url-error"
                                type="url"
                                placeholder="https://"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('demo_url') border-red-500! focus:ring-red-500/10! @enderror">
                            @error('demo_url')
                            <p id="product-demo_url-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="flex items-center gap-2 text-h3 font-h2">
                                <span class="material-symbols-outlined text-primary" aria-hidden="true">terminal</span>
                                Installation process (optional)
                            </h2>
                            <p class="mt-1 text-sm text-secondary">
                                Explain how to install and start using your software.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="addInstallationStep"
                            class="shrink-0 rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-primary cursor-pointer hover:bg-blue-100 transition-all duration-300"
                            @disabled(count($installation_steps)>= 40)
                            >
                            Add step
                        </button>
                    </div>
                    @error('installation_steps')
                    <p role="alert" class="mt-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <div class="mt-5 space-y-4">
                        @forelse ($installation_steps as $index => $step)
                        <div
                            wire:key="installation-step-{{ $index }}"
                            class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold">
                                    Step {{ $index + 1 }}
                                </h3>
                                <button
                                    type="button"
                                    wire:click="removeInstallationStep({{ $index }})"
                                    class="text-xs font-medium text-red-600 cursor-pointer hover:underline">
                                    Remove step
                                </button>
                            </div>
                            <label class="block font-label-md text-on-surface">
                                Step title
                                <input
                                    wire:model.blur="installation_steps.{{ $index }}.title"
                                    aria-invalid="{{ $errors->has('installation_steps.'.$index.'.title') ? 'true' : 'false' }}"
                                    aria-describedby="product-installation_steps-{{ $index }}-title-error"
                                    maxlength="160"
                                    placeholder="e.g. Download and run the installer"
                                    class="mt-2 w-full rounded-lg border border-outline-variant bg-white px-3 py-2.5 text-sm
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('installation_steps.'.$index.'.title') border-red-500! focus:ring-red-500/10! @enderror">
                                @error('installation_steps.'.$index.'.title')
                                <p id="product-installation_steps-{{ $index }}-title-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </label>
                            <label class="block font-label-md text-on-surface">
                                Instructions
                                <textarea
                                    wire:model.blur="installation_steps.{{ $index }}.text"
                                    aria-invalid="{{ $errors->has('installation_steps.'.$index.'.text') ? 'true' : 'false' }}"
                                    aria-describedby="product-installation_steps-{{ $index }}-text-error"
                                    rows="3"
                                    maxlength="10000"
                                    placeholder="Describe what the user needs to do."
                                    class="mt-2 w-full rounded-lg border border-outline-variant bg-white px-3 py-2.5 text-sm
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('installation_steps.'.$index.'.text') border-red-500! focus:ring-red-500/10! @enderror"></textarea>
                                @error('installation_steps.'.$index.'.text')
                                <p id="product-installation_steps-{{ $index }}-text-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </label>
                            <label class="block font-label-md text-on-surface">
                                Command (optional)
                                <textarea
                                    wire:model.blur="installation_steps.{{ $index }}.command"
                                    aria-invalid="{{ $errors->has('installation_steps.'.$index.'.command') ? 'true' : 'false' }}"
                                    aria-describedby="product-installation_steps-{{ $index }}-command-error"
                                    rows="2"
                                    maxlength="10000"
                                    placeholder="Paste a setup command if needed"
                                    class="mt-2 w-full rounded-lg border border-outline-variant bg-white px-3 py-2.5 font-mono text-sm
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('installation_steps.'.$index.'.command') border-red-500! focus:ring-red-500/10! @enderror"></textarea>
                                @error('installation_steps.'.$index.'.command')
                                <p id="product-installation_steps-{{ $index }}-command-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </label>
                        </div>
                        @empty
                        <p
                            class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-secondary">
                            Add installation steps if your software needs setup instructions.
                        </p>
                        @endforelse
                    </div>
                </section>
                @if ($pricing_enabled)
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="flex items-center gap-2 text-h3 font-h2">
                                <span class="material-symbols-outlined text-primary" aria-hidden="true">tune</span>
                                Plan limits
                            </h2>
                            <p class="mt-1 text-sm text-secondary">
                                Define what varies between plans. Set the amounts on each pricing plan.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="addLimit"
                            class="shrink-0 rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-primary cursor-pointer hover:bg-blue-100 transition-all duration-300"
                            @disabled(count($limit_definitions)>= 20)
                            >
                            Add limit
                        </button>
                    </div>
                    @error('limit_definitions')
                    <p role="alert" class="mt-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <div class="mt-5 space-y-3">
                        @forelse ($limit_definitions as $index => $definition)
                        <div
                            wire:key="limit-definition-{{ $index }}"
                            class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <label class="text-xs font-medium text-secondary">
                                    Label
                                    <input
                                        aria-label="Limit {{ $index + 1 }} label"
                                        wire:model.blur="limit_definitions.{{ $index }}.label"
                                        aria-invalid="{{ $errors->has('limit_definitions.'.$index.'.label') ? 'true' : 'false' }}"
                                        aria-describedby="product-limit_definitions-{{ $index }}-label-error"
                                        placeholder="e.g. Users"
                                        maxlength="100"
                                        class="mt-1.5 w-full rounded-lg border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('limit_definitions.'.$index.'.label') border-red-500! focus:ring-red-500/10! @enderror">
                                    @error('limit_definitions.'.$index.'.label')
                                    <p id="product-limit_definitions-{{ $index }}-label-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </label>
                                <label class="text-xs font-medium text-secondary">
                                    Key
                                    <input
                                        aria-label="Limit {{ $index + 1 }} key"
                                        wire:model.blur="limit_definitions.{{ $index }}.key"
                                        aria-invalid="{{ $errors->has('limit_definitions.'.$index.'.key') ? 'true' : 'false' }}"
                                        aria-describedby="product-limit_definitions-{{ $index }}-key-error"
                                        placeholder="e.g. users"
                                        maxlength="64"
                                        class="mt-1.5 w-full rounded-lg border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface read-only:bg-slate-100
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('limit_definitions.'.$index.'.key') border-red-500! focus:ring-red-500/10! @enderror">
                                    @error('limit_definitions.'.$index.'.key')
                                    <p id="product-limit_definitions-{{ $index }}-key-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </label>
                                <label class="text-xs font-medium text-secondary">
                                    Unit (optional)
                                    <input
                                        aria-label="Limit {{ $index + 1 }} unit"
                                        wire:model.blur="limit_definitions.{{ $index }}.unit"
                                        aria-invalid="{{ $errors->has('limit_definitions.'.$index.'.unit') ? 'true' : 'false' }}"
                                        aria-describedby="product-limit_definitions-{{ $index }}-unit-error"
                                        placeholder="e.g. GB"
                                        maxlength="40"
                                        class="mt-1.5 w-full rounded-lg border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('limit_definitions.'.$index.'.unit') border-red-500! focus:ring-red-500/10! @enderror">
                                    @error('limit_definitions.'.$index.'.unit')
                                    <p id="product-limit_definitions-{{ $index }}-unit-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </label>
                            </div>
                            <button
                                type="button"
                                wire:click="removeLimit({{ $index }})"
                                class="mt-3 text-xs font-medium text-red-600 cursor-pointer">
                                Remove limit
                            </button>
                        </div>
                        @empty
                        <div
                            class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-secondary">
                            No limits yet. Add users, devices, projects, storage or another allowance.
                        </div>
                        @endforelse
                    </div>
                    <p class="mt-4 text-xs text-secondary">
                        Keys stay consistent across plans. Limits used by active plans cannot be removed.
                    </p>
                </section>
                            @endif

            </div>
            <div class="col-span-12 space-y-6 lg:col-span-4">
                {{-- Click selection and drag-and-drop both use the same validated upload input. --}}
                <section
                    x-data="{ dragging: false }"
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">image</span>
                        Product image
                    </h2>
                    <button
                        type="button"
                        aria-label="Upload or replace product image"
                        @click="$refs.coverInput.click()"
                        @dragover.prevent.stop="dragging = true"
                        @dragleave.prevent.stop="dragging = false"
                        @drop.prevent.stop="
                            dragging = false;
                            if ($event.dataTransfer.files.length) {
                                $refs.coverInput.files = $event.dataTransfer.files;
                                $refs.coverInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        "
                        :class="dragging ? 'border-primary bg-blue-50' : 'border-slate-300 bg-slate-50'"
                        class="relative mt-4 flex h-64 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed transition-colors hover:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        @if ($cover && !$errors->has('cover'))
                        <img
                            src="{{ $cover->temporaryUrl() }}"
                            alt="New product image"
                            class="pointer-events-none h-full w-full object-contain">
                        @elseif ($product?->cover_path)
                        <img
                            src="{{ \App\Services\UploadStorage::url($product->cover_path) }}"
                            alt="Current product image"
                            class="pointer-events-none h-full w-full object-contain">
                        @else
                        <span
                            class="pointer-events-none flex flex-col items-center gap-2 px-4 pb-6 text-secondary">
                            <span class="material-symbols-outlined text-4xl text-slate-400">
                                add_photo_alternate
                            </span>
                            <span class="text-sm font-medium">
                                Click to upload or drag an image here
                            </span>
                        </span>
                        @endif
                        @if (($cover && !$errors->has('cover')) || $product?->cover_path)
                        <span
                            class="pointer-events-none absolute inset-x-0 bottom-0 bg-white/90 px-3 py-2 text-xs font-medium text-primary">
                            Click or drop an image to replace
                        </span>
                        @endif
                        <span
                            x-show="dragging"
                            x-cloak
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-blue-50/95 text-sm font-semibold text-primary">
                            Drop image to upload
                        </span>
                    </button>
                    <input
                        x-ref="coverInput"
                        id="product-cover"
                        type="file"
                        wire:model="cover"
                        aria-invalid="{{ $errors->has('cover') ? 'true' : 'false' }}"
                        aria-describedby="product-cover-error"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('cover') border-red-500! focus:ring-red-500/10! @enderror"
                        tabindex="-1">
                    <p class="mt-2 text-xs text-secondary">
                        JPG, PNG or WebP, up to 4 MB.
                    </p>
                    <p
                        wire:loading
                        wire:target="cover"
                        role="status"
                        class="mt-2 text-xs text-primary">
                        Uploading image…
                    </p>
                    @error('cover')
                    <p id="product-cover-error" class="mt-2 text-xs text-red-600" role="alert">
                        {{ $message }}
                    </p>
                    @enderror
                </section>
                {{-- A software release is optional and independent of images and pricing plans. --}}
                <section
                    x-data="{ dragging: false, progress: 0, uploading: false }"
                    x-on:livewire-upload-start="uploading = true; progress = 0"
                    x-on:livewire-upload-finish="uploading = false"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-cancel="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">upload_file</span>
                        Software file (optional)
                    </h2>
                    <p class="mt-1 text-sm text-secondary">Upload your installer or software archive.</p>

                    <button
                        type="button"
                        @click="$refs.softwareInput.click()"
                        @dragover.prevent.stop="dragging = true"
                        @dragleave.prevent.stop="dragging = false"
                        @drop.prevent.stop="
                            dragging = false;
                            if ($event.dataTransfer.files.length === 1) {
                                $refs.softwareInput.files = $event.dataTransfer.files;
                                $refs.softwareInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        "
                        :class="dragging ? 'border-primary bg-blue-50' : 'border-slate-300 bg-slate-50'"
                        class="mt-4 flex w-full flex-col items-center gap-2 rounded-lg border-2 border-dashed px-4 py-7
                            text-center transition-colors hover:border-primary focus-visible:outline-2
                            focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <span class="material-symbols-outlined text-3xl text-primary" aria-hidden="true">upload_file</span>
                        <span class="text-sm font-medium">Click to upload or drop a software file</span>
                        <span class="text-xs text-secondary">ZIP, EXE or MSI · up to 250 MB</span>
                    </button>
                    <input
                        x-ref="softwareInput"
                        id="product-software"
                        type="file"
                        wire:model="software"
                        aria-invalid="{{ $errors->has('software') ? 'true' : 'false' }}"
                        aria-describedby="product-software-error"
                        accept=".zip,.exe,.msi"
                        class="hidden
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('software') border-red-500! focus:ring-red-500/10! @enderror"
                        tabindex="-1">

                    <p x-show="uploading" x-cloak role="status" class="mt-3 text-xs text-primary">
                        Uploading software… <span x-text="progress + '%'"></span>
                    </p>

                    @if ($software && !$errors->has('software'))
                    <div class="mt-4 flex items-start justify-between gap-3 rounded-lg bg-blue-50 p-3">
                        <div class="min-w-0">
                            <p class="break-all text-sm font-medium text-primary">{{ $software->getClientOriginalName() }}</p>
                            <p class="mt-1 text-xs text-secondary">{{ number_format($software->getSize() / 1048576, 2) }} MB · Ready to save</p>
                        </div>
                        <button type="button" wire:click="clearSoftware" class="shrink-0 text-xs font-medium text-red-600">
                            Cancel
                        </button>
                    </div>
                    @endif

                    @if ($product?->release_path)
                    <div class="mt-4 rounded-lg border border-slate-200 p-3">
                        <p class="text-xs font-medium text-secondary">Current software file</p>
                        <p class="mt-1 break-all text-sm font-medium">{{ $product->release_name }}</p>
                        <p class="mt-1 text-xs text-secondary">{{ number_format($product->release_size / 1048576, 2) }} MB</p>
                    </div>
                    <p class="mt-2 text-xs text-secondary">Select a new file to replace the current release when you save.</p>
                    @endif

                    @error('software')
                    <p id="product-software-error" class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </section>
                <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">settings</span>
                        Settings
                    </h2>
                    {{-- Use the same icon rows and switches as service publication settings. --}}
                    @foreach ([
                    [
                    'field' => 'is_published', 'label' => 'Published', 'icon' => 'public',
                    'help' => 'Make this product visible on the client site.',
                    'row' => 'border-slate-100 bg-slate-50', 'iconClass' => 'bg-blue-100 text-primary',
                    'switch' => 'peer-checked:bg-primary peer-focus-visible:ring-blue-100',
                    ],
                    [
                    'field' => 'pricing_enabled', 'label' => 'Uses pricing plans (optional)', 'icon' => 'payments',
                    'help' => 'Enable for plan-based software. Existing plans remain saved when disabled.',
                    'row' => 'border-amber-100 bg-amber-50/50', 'iconClass' => 'bg-amber-100 text-amber-600',
                    'switch' => 'peer-checked:bg-amber-500 peer-focus-visible:ring-amber-100',
                    ],
                    [
                    'field' => 'show_in_navbar', 'label' => 'Show in Navbar', 'icon' => 'menu',
                    'help' => 'Include this product in the Products menu when published.',
                    'row' => 'border-sky-100 bg-sky-50/50', 'iconClass' => 'bg-sky-100 text-sky-600',
                    'switch' => 'peer-checked:bg-sky-500 peer-focus-visible:ring-sky-100',
                    ],
                    ] as $setting)
                    <div class="flex items-center justify-between gap-4 rounded-lg border p-3 {{ $setting['row'] }}">
                        <div class="flex items-start gap-3">
                            <span @class([ 'flex size-8 shrink-0 items-center justify-center rounded-full' ,
                                $setting['iconClass']=> $this->{$setting['field']},
                                'bg-slate-100 text-slate-400' => !$this->{$setting['field']},
                                ])>
                                <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $setting['icon'] }}</span>
                            </span>
                            <label for="product-{{ $setting['field'] }}">
                                <span class="block text-label-md font-label-md text-on-surface">{{ $setting['label'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-secondary">{{ $setting['help'] }}</span>
                            </label>
                        </div>
                        <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input
                                id="product-{{ $setting['field'] }}"
                                type="checkbox"
                                wire:model.live="{{ $setting['field'] }}"
                                class="peer sr-only">
                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors
                                    after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full
                                    after:border after:border-gray-300 after:bg-white after:transition-transform after:content-['']
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    peer-focus-visible:ring-4 {{ $setting['switch'] }}"></span>
                        </label>
                    </div>
                    @error($setting['field'])
                    <p role="alert" class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @endforeach
                    <div>
                        <label for="product-order" class="mb-2 block font-label-md text-on-surface">
                            Display order
                        </label>
                        <input
                            id="product-order"
                            type="number"
                            wire:model.blur="sort_order"
                            aria-invalid="{{ $errors->has('sort_order') ? 'true' : 'false' }}"
                            aria-describedby="product-sort_order-error"
                            min="0"
                            max="100000"
                            class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md
                                    outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10
                                    @error('sort_order') border-red-500! focus:ring-red-500/10! @enderror">
                        @error('sort_order')
                        <p id="product-sort_order-error" role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-secondary">
                            Lower numbers appear first.
                        </p>
                    </div>
                </section>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-3 py-5 sm:flex-row sm:items-center">
            <button
                type="button"
                wire:click="discard"
                wire:loading.attr="disabled"
                class="cursor-pointer rounded-lg border border-outline-variant px-5 py-3 text-label-md font-label-md
                    text-on-surface transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                Discard Changes
            </button>
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3
                    text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90
                    disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">
                    Save product
                </span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <span class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                    Saving…
                </span>
            </button>
        </div>
    </form>
</div>