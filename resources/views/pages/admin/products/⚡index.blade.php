<?php

use App\Models\Product;
use App\Services\Products\ProductContent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Products')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';

    public function boot(): void
    {
        ProductContent::authorizeAdmin();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Archive catalog visibility while preserving existing saved product data.
     */
    public function delete(int $id): void
    {
        ProductContent::authorizeAdmin();
        Product::findOrFail($id)->delete();
        $this->resetPage();
        $this->dispatch('toast', message: 'Product deleted. Product details have been archived.', type: 'success');
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->withCount('plans')
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                }))
                ->when(
                    in_array($this->status, ['published', 'draft'], true),
                    fn ($query) => $query->where('is_published', $this->status === 'published'),
                )
                ->orderBy('sort_order')
                ->latest('id')
                ->paginate(10),
            'total' => Product::count(),
            'published' => Product::published()->count(),
            'drafts' => Product::where('is_published', false)->count(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Page heading and the new-product action. --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-h1 font-manrope font-bold text-on-surface">
                Products
            </h1>
            <p class="mt-1 text-sm text-secondary">
                Your software, pricing plans and product limits in one place.
            </p>
        </div>
        <a
            href="{{ route('admin.products.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-container"
        >
            <span class="material-symbols-outlined text-lg">
                add
            </span>
            Add product
        </a>
    </div>
    {{-- Catalog status totals. --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach (['All products' => $total, 'Published' => $published, 'Drafts' => $drafts] as $label => $count)
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-secondary">
                    {{ $label }}
                </p>
                <p class="mt-2 text-3xl font-bold font-manrope text-on-surface">
                    {{ $count }}
                </p>
            </div>
        @endforeach
    </div>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div
            class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <label class="relative">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400">
                    search
                </span>
                <input
                    aria-label="Search products"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search products…"
                    class="w-full rounded-lg border border-outline-variant py-2.5 pl-10 pr-4 text-sm sm:w-80"
                >
            </label>
            <select
                aria-label="Product status"
                wire:model.live="status"
                class="rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-sm"
            >
                <option value="all">
                    All statuses
                </option>
                <option value="published">
                    Published
                </option>
                <option value="draft">
                    Drafts
                </option>
            </select>
        </div>
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-secondary"
                >
                    <tr>
                        <th class="px-6 py-4">
                            Product
                        </th>
                        <th class="px-6 py-4">
                            Status
                        </th>
                        <th class="px-6 py-4">
                            Pricing
                        </th>
                        <th class="px-6 py-4">
                            Plans
                        </th>
                        <th class="px-6 py-4 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($product->cover_path)
                                        <img
                                            src="{{ \App\Services\UploadStorage::url($product->cover_path) }}"
                                            alt=""
                                            class="h-12 w-12 rounded-lg border border-slate-200 object-cover"
                                        >
                                    @else
                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700"
                                        >
                                            <span class="material-symbols-outlined">
                                                deployed_code
                                            </span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('admin.products.edit', $product) }}"
                                            wire:navigate
                                            class="font-semibold text-on-surface hover:text-primary"
                                        >
                                            {{ $product->name }}
                                        </a>
                                        <p class="mt-1 max-w-sm truncate text-xs text-secondary">
                                            {{ $product->summary }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700' => $product->is_published, 'bg-slate-100 text-slate-600' => !$product->is_published])
                                >
                                    {{ $product->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-secondary">
                                {{ $product->pricing_enabled ? 'Enabled' : 'Hidden' }}
                            </td>
                            <td class="px-6 py-4">
                                <a
                                    class="font-medium text-primary hover:underline"
                                    href="{{ route('admin.products.plans', $product) }}"
                                    wire:navigate
                                >
                                    {{ $product->plans_count }} plans
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        wire:navigate
                                        class="rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-100"
                                    >
                                        Edit
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $product->id }})"
                                        wire:confirm="Delete this product? This archives the product."
                                        class="rounded-lg p-2 text-red-600 hover:bg-red-50"
                                        aria-label="Delete {{ $product->name }}"
                                    >
                                        <span class="material-symbols-outlined text-xl">
                                            delete
                                        </span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined mb-3 text-4xl text-slate-400">
                                    inventory_2
                                </span>
                                <h2 class="font-semibold text-on-surface">
                                    {{ $search || $status !== 'all' ? 'No matching products' : 'Your software starts here' }}
                                </h2>
                                <p class="mt-2 text-secondary">
                                    {{ $search || $status !== 'all' ? 'Try another search or status.' : 'Add your first product, then create the plans that fit it.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $products->links() }}
        </div>
    </div>
</div>
