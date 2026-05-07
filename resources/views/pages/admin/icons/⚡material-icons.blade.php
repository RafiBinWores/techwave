<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Material Icons')] class extends Component {
    public string $search = '';

    public array $icons = [];

    public int $perPage = 60;

    public int $loadAmount = 60;

    public function mount(): void
    {
        $this->icons = Cache::remember('material_symbols_icons', now()->addDays(7), function () {
            return $this->fetchIcons();
        });
    }

    public function updatedSearch(): void
    {
        $this->perPage = $this->loadAmount;
    }

    public function loadMore(): void
    {
        $this->perPage += $this->loadAmount;
    }

    public function refreshIcons(): void
    {
        Cache::forget('material_symbols_icons');

        $this->icons = Cache::remember('material_symbols_icons', now()->addDays(7), function () {
            return $this->fetchIcons();
        });

        $this->perPage = $this->loadAmount;
    }

    public function getFilteredIconsProperty(): array
    {
        $search = strtolower(trim($this->search));

        if ($search === '') {
            return $this->icons;
        }

        return collect($this->icons)
            ->filter(fn ($icon) => str_contains(strtolower($icon), $search))
            ->values()
            ->toArray();
    }

    public function getVisibleIconsProperty(): array
    {
        return collect($this->filteredIcons)
            ->take($this->perPage)
            ->values()
            ->toArray();
    }

    public function getHasMoreIconsProperty(): bool
    {
        return count($this->filteredIcons) > $this->perPage;
    }

    private function fetchIcons(): array
    {
        try {
            $url = 'https://raw.githubusercontent.com/google/material-design-icons/master/variablefont/MaterialSymbolsOutlined%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints';

            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                return $this->fallbackIcons();
            }

            $icons = collect(explode("\n", trim($response->body())))
                ->map(fn ($line) => trim(Str::before($line, ' ')))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            return ! empty($icons) ? $icons : $this->fallbackIcons();

        } catch (\Throwable $e) {
            return $this->fallbackIcons();
        }
    }

    private function fallbackIcons(): array
    {
        return [
            'dashboard',
            'space_dashboard',
            'home',
            'menu',
            'apps',
            'grid_view',
            'widgets',
            'view_list',
            'view_module',
            'view_sidebar',
            'tune',
            'settings',
            'manage_accounts',
            'admin_panel_settings',

            'person',
            'person_add',
            'group',
            'groups',
            'account_circle',
            'badge',
            'verified_user',
            'lock',
            'lock_open',
            'key',
            'login',
            'logout',
            'password',
            'shield',
            'security',

            'add',
            'add_circle',
            'remove',
            'delete',
            'delete_forever',
            'edit',
            'edit_square',
            'save',
            'check',
            'check_circle',
            'close',
            'cancel',
            'done',
            'refresh',
            'sync',
            'restart_alt',
            'download',
            'upload',
            'content_copy',
            'share',
            'open_in_new',

            'arrow_back',
            'arrow_forward',
            'arrow_upward',
            'arrow_downward',
            'chevron_left',
            'chevron_right',
            'expand_more',
            'expand_less',
            'keyboard_arrow_down',
            'keyboard_arrow_up',

            'search',
            'filter_alt',
            'sort',
            'visibility',
            'visibility_off',
            'preview',
            'find_in_page',

            'shopping_cart',
            'shopping_bag',
            'receipt',
            'receipt_long',
            'payments',
            'credit_card',
            'account_balance_wallet',
            'currency_exchange',
            'price_check',
            'sell',
            'local_shipping',
            'inventory_2',
            'package_2',
            'request_quote',
            'add_shopping_cart',

            'business',
            'domain',
            'language',
            'public',
            'dns',
            'cloud',
            'cloud_upload',
            'cloud_done',
            'database',
            'storage',
            'hub',
            'analytics',
            'monitoring',
            'insights',
            'query_stats',
            'trending_up',
            'campaign',
            'support_agent',
            'headset_mic',
            'mail',
            'alternate_email',

            'code',
            'terminal',
            'developer_mode',
            'integration_instructions',
            'api',
            'web',
            'html',
            'css',
            'javascript',
            'php',
            'data_object',
            'bug_report',
            'build',
            'construction',

            'vpn_key',
            'vpn_lock',
            'firewall',
            'policy',
            'encrypted',
            'gpp_good',
            'gpp_bad',
            'security_update_good',
            'lan',
            'router',
            'print',
            'computer',
            'desktop_windows',
            'laptop_mac',
            'devices',
            'memory',
            'settings_ethernet',

            'article',
            'description',
            'note',
            'draft',
            'folder',
            'folder_open',
            'attach_file',
            'image',
            'photo_camera',
            'movie',
            'slideshow',

            'info',
            'warning',
            'error',
            'error_outline',
            'help',
            'help_center',
            'notifications',
            'notifications_active',
            'schedule',
            'calendar_month',
            'event',
            'history',
            'pending',
            'hourglass_empty',
            'task_alt',
            'published_with_changes',
        ];
    }
};
?>

<div
    x-data="{
        copied: null,

        copyIcon(icon) {
            navigator.clipboard.writeText(icon);

            this.copied = icon;

            setTimeout(() => {
                this.copied = null;
            }, 1400);
        }
    }"
>
    {{-- Page Header --}}
    <div class="mb-10 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1 text-xs font-semibold text-primary">
                <span class="material-symbols-outlined text-base">auto_awesome</span>
                Material Symbols Library
            </div>

            <h1 class="mt-4 text-h1 font-h1 text-on-surface">
                Material Icons
            </h1>

            <p class="mt-1 max-w-2xl text-body-md font-body-md text-secondary">
                Search, preview, and copy Material Symbols icon names for your admin panel UI.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                wire:click="refreshIcons"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:bg-primary/5 hover:shadow-md disabled:opacity-60"
            >
                <span
                    wire:loading.remove
                    wire:target="refreshIcons"
                    class="material-symbols-outlined text-lg text-primary"
                >
                    sync
                </span>

                <span
                    wire:loading
                    wire:target="refreshIcons"
                    class="material-symbols-outlined text-lg text-primary animate-spin"
                >
                    progress_activity
                </span>

                <span>Refresh</span>
            </button>

            <div class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface shadow-sm">
                <span class="material-symbols-outlined text-lg text-primary">widgets</span>
                <span>
                    {{ count($this->visibleIcons) }} shown / {{ count($this->filteredIcons) }} found
                </span>
            </div>
        </div>
    </div>

    {{-- Search Card --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-gradient-to-r from-primary/5 via-white to-slate-50 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="flex items-center gap-2 text-h3 font-h2 text-on-surface">
                        <span class="material-symbols-outlined text-primary">search</span>
                        Search Icons
                    </h3>

                    <p class="mt-1 text-sm text-secondary">
                        Click any icon card to copy the icon name instantly.
                    </p>
                </div>

                <div class="w-full lg:max-w-md">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            search
                        </span>

                        <input
                            type="search"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Search dashboard, payment, user..."
                            class="w-full rounded-xl border border-outline-variant bg-white py-3 pl-10 pr-4 text-label-md font-label-md text-on-surface outline-none transition placeholder:text-secondary focus:border-primary focus:ring-4 focus:ring-primary/10"
                        />
                    </div>
                </div>
            </div>
        </div>

        {{-- Icon Grid --}}
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                @forelse ($this->visibleIcons as $icon)
                    <button
                        type="button"
                        wire:key="material-icon-{{ $icon }}"
                        @click="copyIcon('{{ $icon }}')"
                        class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10 cursor-pointer"
                    >
                        {{-- Glow --}}
                        <div class="pointer-events-none absolute -right-10 -top-10 h-24 w-24 rounded-full bg-primary/10 blur-2xl transition duration-300 group-hover:bg-primary/20"></div>

                        <div class="relative flex items-start justify-between gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-primary/10 bg-linear-to-br from-primary/10 to-primary/5 text-primary shadow-sm transition duration-300 group-hover:scale-105 group-hover:border-primary/30 group-hover:bg-primary group-hover:text-white">
                                <span class="material-symbols-outlined text-[28px]">
                                    {{ $icon }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1">
                                <span
                                    x-show="copied === '{{ $icon }}'"
                                    x-transition
                                    class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-600 ring-1 ring-emerald-100"
                                    style="display: none;"
                                >
                                    Copied
                                </span>

                                <span
                                    x-show="copied !== '{{ $icon }}'"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition group-hover:bg-primary/10 group-hover:text-primary"
                                >
                                    <span class="material-symbols-outlined text-base">content_copy</span>
                                </span>
                            </div>
                        </div>

                        <div class="relative mt-5">
                            <p class="break-all text-sm font-bold text-on-surface">
                                {{ $icon }}
                            </p>

                            <p class="mt-1 text-xs text-secondary">
                                Click to copy icon name
                            </p>
                        </div>

                        {{-- <div class="relative mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs text-secondary transition group-hover:bg-primary/5 group-hover:text-primary">
                            <span class="font-medium">
                                material-symbols
                            </span>

                            <span class="material-symbols-outlined text-base">
                                arrow_forward
                            </span>
                        </div> --}}
                    </button>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                            <span class="material-symbols-outlined text-4xl">search_off</span>
                        </div>

                        <h3 class="mt-4 text-h3 font-h2 text-on-surface">
                            No icons found
                        </h3>

                        <p class="mt-1 text-sm text-secondary">
                            Try another keyword.
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Load More --}}
            @if ($this->hasMoreIcons)
                <div class="mt-8 flex justify-center">
                    <button
                        type="button"
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        wire:target="loadMore"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:bg-primary/90 hover:shadow-xl hover:shadow-primary/25 disabled:opacity-60"
                    >
                        <span
                            wire:loading.remove
                            wire:target="loadMore"
                            class="material-symbols-outlined text-xl"
                        >
                            expand_more
                        </span>

                        <span
                            wire:loading
                            wire:target="loadMore"
                            class="material-symbols-outlined text-xl animate-spin"
                        >
                            progress_activity
                        </span>

                        <span>
                            Load More Icons
                        </span>

                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-xs">
                            {{ count($this->filteredIcons) - count($this->visibleIcons) }} left
                        </span>
                    </button>
                </div>
            @else
                @if (count($this->filteredIcons) > 0)
                    <div class="mt-8 flex justify-center">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-600">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                            All icons loaded
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>