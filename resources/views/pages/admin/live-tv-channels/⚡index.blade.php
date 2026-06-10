<?php

use App\Models\LiveTvChannel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Manage Live TV Channels')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $category = 'all';
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedCategory(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $channel = LiveTvChannel::findOrFail($id);
        $channel->update(['is_active' => !$channel->is_active]);
        $this->dispatch('toast', message: 'Channel status updated.', type: 'success');
    }

    public function delete(int $id): void
    {
        LiveTvChannel::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Channel deleted.', type: 'success');
    }

    public function channels()
    {
        return LiveTvChannel::query()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('url', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->category !== 'all', fn($q) => $q->where('category', $this->category))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);
    }
};
?>

<div class="mx-auto w-full space-y-stack-lg">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">Live TV Channels</h2>
            <p class="text-xs font-body-md text-secondary md:text-body-md">Manage channels displayed on the Live TV page.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">search</span>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search channels..."
                    class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-64" />
            </div>
            <div class="relative">
                <select wire:model.live="category"
                    class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-44">
                    <option value="all">All Categories</option>
                    <option value="Bangladeshi">Bangladeshi</option>
                    <option value="Sports">Sports</option>
                    <option value="News">News</option>
                    <option value="Entertainment">Entertainment</option>
                </select>
                <span
                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">expand_more</span>
            </div>
            <a href="{{ route('admin.live-tv-channels.create') }}" wire:navigate
                class="flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98]">
                <span class="material-symbols-outlined text-lg">add</span>
                Add Channel
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Channel</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Category</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">URL
                        </th>
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Order
                        </th>
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Status
                        </th>
                        <th
                            class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->channels() as $channel)
                        <tr wire:key="channel-{{ $channel->id }}" class="transition-colors hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <span class="material-symbols-outlined text-lg">
                                            {{ $channel->category === 'Sports' ? 'sports_esports' : 'tv' }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-label-md font-label-md text-on-surface">{{ $channel->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-label-sm text-slate-600">{{ $channel->category }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="max-w-[200px] truncate block font-mono text-body-sm text-secondary">{{ $channel->url }}</span>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-secondary">{{ $channel->sort_order }}</td>
                            <td class="px-6 py-4">
                                <button type="button" wire:click="toggleStatus({{ $channel->id }})"
                                    class="flex items-center gap-2">
                                    @if ($channel->is_active)
                                        <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                                        <span class="text-body-md font-body-md text-on-surface">Active</span>
                                    @else
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        <span class="text-body-md font-body-md text-on-surface">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button type="button" @click="open = !open"
                                        class="text-slate-400 transition-colors hover:text-primary">
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>
                                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                        class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                        <a href="{{ route('admin.live-tv-channels.edit', $channel->id) }}" wire:navigate
                                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">edit</span> Edit
                                        </a>
                                        <button type="button" wire:click="toggleStatus({{ $channel->id }})"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span
                                                class="material-symbols-outlined text-[18px]">{{ $channel->is_active ? 'block' : 'check_circle' }}</span>
                                            {{ $channel->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $channel->id }})"
                                            wire:confirm="Delete this channel?"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                            <span class="material-symbols-outlined text-[18px]">delete</span> Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div
                                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <span class="material-symbols-outlined">live_tv</span>
                                    </div>
                                    <h3 class="text-base font-semibold text-on-surface">No channels found</h3>
                                    <p class="mt-1 text-sm text-secondary">Add your first Live TV channel.</p>
                                    <a href="{{ route('admin.live-tv-channels.create') }}" wire:navigate
                                        class="mt-5 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90">Add
                                        Channel</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div
            class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="text-body-sm font-body-sm text-secondary">Per page</span>
                <select wire:model.live="perPage"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div>{{ $this->channels()->links() }}</div>
        </div>
    </div>
</div>
