@props(['report', 'premium', 'upgradeUrl'])

@php
    $sections = ['Metadata' => 'Meta data', 'Content' => 'Page quality', 'Others' => 'Others', 'Structure' => 'Link structure', 'Technical' => 'Server configuration'];
    $selectedCategories = $report['selected_categories'] ?? null;
    if (is_array($selectedCategories) && $selectedCategories !== []) {
        $sections = array_intersect_key($sections, array_flip($selectedCategories));
    }
    if (filled(config('seo.integrations.backlinks.endpoint'))) {
        $sections['External factors'] = 'External factors';
    }
    $groups = collect($report['groups'] ?? $report['findings']);
    $findings = collect($report['findings'])->keyBy('id');
    $visibleIds = array_fill_keys(array_column($report['findings'], 'id'), true);
    $orders = array_flip(['title', 'description', 'robots', 'canonical', 'language', 'hreflang', 'other-meta', 'domain', 'url', 'charset', 'doctype', 'favicon', 'content-length', 'frames', 'viewport', 'strong-bold', 'image-alt', 'social', 'structured-data', 'https', 'h1', 'headings', 'links', 'external-links', 'http-redirects', 'http-headers', 'performance']);
    $inventories = ['headings' => 'Heading structure', 'strong-bold' => 'Bold and strong tags', 'image-alt' => 'Media files', 'other-meta' => 'Meta tags', 'links' => 'Internal links', 'external-links' => 'External links', 'http-headers' => 'HTTP response header', 'resource-counts' => 'Page resources'];
@endphp

<div class="mt-8 text-blue-50 [&_[x-cloak]]:hidden" x-data="{ reportTab: 'details', previewDevice: 'desktop', selectedCategory: null }" wire:key="seo-report-{{ $report['checked_at'] }}">
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-300">SEO analysis</p>
            <p class="mt-1 max-w-2xl text-xs leading-6 wrap-anywhere text-blue-100/60">{{ $report['url'] }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-[11px] text-blue-100/45">
            <span>Checked {{ \Carbon\Carbon::parse($report['checked_at'])->timezone(config('app.timezone'))->format('M j, Y · H:i T') }}</span>
            @if($premium && isset($report['report_id']))
                <a href="{{ route('client.tools.seo-report.pdf', $report['report_id']) }}" class="inline-flex items-center gap-1 rounded-lg border border-white/15 px-3 py-2 font-bold text-cyan-200 hover:bg-slate-900"><span class="material-symbols-outlined text-base" aria-hidden="true">download</span>Download PDF</a>
            @endif
        </div>
    </header>

    <nav role="tablist" aria-label="Report views" class="mb-5 flex gap-5 overflow-x-auto border-b border-white/10">
        @foreach(['details' => 'Checks', 'structure' => 'Elements', 'meta' => 'Miscellaneous'] as $tab => $label)
            <button type="button" id="seo-tab-{{ $tab }}" role="tab" aria-controls="seo-panel-{{ $tab }}"
                :aria-selected="reportTab === '{{ $tab }}'" :tabindex="reportTab === '{{ $tab }}' ? 0 : -1"
                @click="reportTab = '{{ $tab }}'"
                @keydown.arrow-right.prevent="reportTab = { details: 'structure', structure: 'meta', meta: 'details' }[reportTab]; $nextTick(() => $el.parentElement.querySelector('[aria-selected=true]').focus())"
                @keydown.arrow-left.prevent="reportTab = { details: 'meta', structure: 'details', meta: 'structure' }[reportTab]; $nextTick(() => $el.parentElement.querySelector('[aria-selected=true]').focus())"
                class="cursor-pointer border-b-2 px-1 py-3 text-sm font-bold whitespace-nowrap transition focus-visible:outline-2 focus-visible:outline-cyan-300"
                :class="reportTab === '{{ $tab }}' ? 'border-cyan-300 text-cyan-200' : 'border-transparent text-blue-100/50 hover:text-white'">{{ $label }}</button>
        @endforeach
    </nav>

    <div id="seo-panel-details" role="tabpanel" aria-labelledby="seo-tab-details" x-show="reportTab === 'details'" class="space-y-5">
        <x-seo.overview :report="$report" :sections="$sections" :premium="$premium" />
        <x-seo.page-information :report="$report" />

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900 backdrop-blur-2xl" aria-labelledby="seo-tasks-heading">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4 sm:px-6">
                <div><p class="mb-1 text-[10px] font-bold tracking-widest text-cyan-300 uppercase">To-do list</p><h2 id="seo-tasks-heading" class="text-base font-extrabold text-white">Tasks sorted by priority</h2></div>
                <span class="rounded-full bg-slate-900 px-3 py-1 text-[11px] text-blue-100/60">{{ $report['recommendation_count'] }} recommendations</span>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] border-collapse text-xs">
                    <thead class="bg-slate-950 text-left text-[10px] uppercase tracking-wider text-blue-100/45"><tr><th scope="col" class="px-5 py-3">To do</th><th scope="col" class="px-4 py-3">Importance</th><th scope="col" class="px-5 py-3">Action</th></tr></thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($report['recommendations'] as $fix)
                            <tr>
                                <td class="border-l-2 px-5 py-3.5 leading-6 text-blue-100/85 {{ $fix['status'] === 'fail' ? 'border-red-400 bg-red-400/5' : 'border-amber-300 bg-amber-400/5' }}">{{ $fix['recommendation'] }}</td>
                                <td class="px-4 py-3.5"><span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10px] font-bold whitespace-nowrap {{ $fix['status'] === 'fail' ? 'bg-red-400/10 text-red-300' : 'bg-amber-400/10 text-amber-300' }}">{{ $fix['status'] === 'fail' ? 'Error' : 'Warning' }}</span><p class="mt-1 text-[10px] whitespace-nowrap text-blue-100/45">{{ [1 => 'Very important', 2 => 'Important', 3 => 'Low importance', 4 => 'Nice to have'][$fix['priority'] ?? 2] }}</p></td>
                                <td class="px-5 py-3.5">
                                    @if(isset($visibleIds[$fix['id']]))
                                        <a href="#seo-finding-{{ $fix['id'] }}" @click.prevent="reportTab = {{ in_array($fix['id'], ['robots-file', 'sitemap-file'], true) ? "'meta'" : "'details'" }}; selectedCategory = null; $nextTick(() => document.getElementById('seo-finding-{{ $fix['id'] }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))" class="inline-flex items-center gap-1 rounded-lg border border-white/15 px-3 py-2 text-[11px] font-bold whitespace-nowrap text-cyan-200 hover:bg-slate-900">Show details <span aria-hidden="true">↓</span></a>
                                    @else
                                        <a href="{{ $upgradeUrl }}" wire:navigate class="text-[11px] font-bold whitespace-nowrap text-cyan-300">Unlock details ↗</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-5 text-blue-100/60">No actionable issues found by the available checks.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(!$premium && $report['recommendation_count'] > 5)<p class="border-t border-white/10 px-5 py-3 text-xs text-blue-100/50">Showing your top five fixes. Premium includes {{ $report['recommendation_count'] - 5 }} more recommendations.</p>@endif
        </section>

        <nav aria-label="Jump to report section" class="sticky top-3 z-10 flex gap-1.5 overflow-x-auto rounded-xl border border-white/10 bg-slate-950/90 p-2 shadow-xl backdrop-blur-2xl">
            <button type="button" @click="selectedCategory = null"
                :class="!selectedCategory ? 'bg-cyan-400/15 text-cyan-200' : 'text-blue-100/70 hover:bg-cyan-400/10 hover:text-cyan-200'"
                class="cursor-pointer rounded-lg px-3 py-2 text-[11px] font-bold whitespace-nowrap transition focus-visible:outline-2 focus-visible:outline-cyan-300">All</button>
            @foreach($sections as $category => $name)
                @php $categorySlug = \Illuminate\Support\Str::slug($category); @endphp
                <a href="#seo-section-{{ $categorySlug }}"
                    @click.prevent="reportTab = 'details'; selectedCategory = '{{ $category }}'; $nextTick(() => document.getElementById('seo-section-{{ $categorySlug }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                    :aria-current="selectedCategory === '{{ $category }}' ? 'true' : 'false'"
                    :class="selectedCategory === '{{ $category }}' ? 'bg-cyan-400/15 text-cyan-200' : 'text-blue-100/70 hover:bg-cyan-400/10 hover:text-cyan-200'"
                    class="rounded-lg px-3 py-2 text-[11px] font-bold whitespace-nowrap transition focus-visible:outline-2 focus-visible:outline-cyan-300">{{ $name }}</a>
            @endforeach
        </nav>

        @foreach($sections as $category => $name)
            @php
                $categorySlug = \Illuminate\Support\Str::slug($category);
                $categoryGroups = $groups->where('category', $category)->reject(fn ($group) => in_array($group['id'], ['robots-file', 'sitemap-file'], true))->sortBy(fn ($group) => $orders[$group['id']] ?? 100);
            @endphp
            <section id="seo-section-{{ $categorySlug }}"
                x-show="!selectedCategory || selectedCategory === '{{ $category }}'"
                class="scroll-mt-28 pt-3">
                <header class="mb-4 flex items-center justify-between gap-4 border-b border-cyan-300/25 pb-3">
                    <h2 class="text-xl font-extrabold tracking-tight text-white">{{ $name }}</h2>
                    <strong class="text-xl font-extrabold tabular-nums text-cyan-300">{{ isset($report['categories'][$category]) ? $report['categories'][$category].'%' : 'Not scored' }}</strong>
                </header>
                <div class="space-y-3">
                    @forelse($categoryGroups as $group)
                        <x-seo.finding :finding="$group" />
                    @empty
                        <div class="flex flex-col items-start gap-4 rounded-xl border border-dashed border-white/20 bg-slate-900 p-6 sm:flex-row sm:items-center sm:justify-between">
                            @if(!$premium && $category === 'Structure')
                                <div><h3 class="flex items-center gap-2 text-sm font-bold text-white"><span class="material-symbols-outlined text-cyan-300" aria-hidden="true">lock</span>Detailed link analysis</h3><p class="mt-2 text-xs leading-6 text-blue-100/55">Your Link structure score is included. Premium unlocks internal and external link findings, anchors, and evidence tables.</p></div>
                                <a href="{{ $upgradeUrl }}" wire:navigate class="shrink-0 rounded-xl border border-cyan-300/25 bg-cyan-400/10 px-4 py-2.5 text-xs font-bold text-cyan-200">Unlock link details ↗</a>
                            @else
                                <p class="text-xs leading-6 text-blue-100/55">No checks are available here for this report version. Run a new check to inspect the current groups.</p>
                            @endif
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
        <details class="text-[11px] leading-6 text-blue-100/40">
            <summary class="cursor-pointer font-semibold text-blue-100/60">How this score is calculated</summary>
            <x-seo.score-methodology :report="$report" class="mt-2" />
        </details>
    </div>

    <div id="seo-panel-structure" role="tabpanel" aria-labelledby="seo-tab-structure" x-show="reportTab === 'structure'" x-cloak class="space-y-4">
        <header class="mb-5"><h2 class="text-lg font-extrabold text-white">Page elements</h2><p class="mt-2 text-xs leading-6 text-blue-100/55">Inspect the HTML evidence behind your report. Tables show up to 50 rows.</p></header>
        @if(!empty($report['elements']['paragraphs']))
            <details class="overflow-hidden rounded-xl border border-white/10 bg-slate-900">
                <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 text-sm font-bold text-white">Recognized text paragraphs <span class="rounded-md bg-slate-900 px-2 py-1 text-xs tabular-nums text-cyan-200">{{ $report['elements']['paragraph_count'] }}</span></summary>
                <div class="border-t border-white/10 p-4"><x-seo.evidence-table :rows="$report['elements']['paragraphs']" /></div>
            </details>
        @endif
        @foreach($inventories as $id => $label)
            @php
                $inventory = $findings->get($id);
                $locked = !$premium && in_array($id, ['links', 'external-links'], true);
                $count = $inventory['detail_count'] ?? count($inventory['details'] ?? []);
            @endphp
            <details class="overflow-hidden rounded-xl border border-white/10 bg-slate-900" @if($id === 'headings') open @endif>
                <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 text-sm font-bold text-white"><span>{{ $label }}</span><span class="rounded-md bg-slate-900 px-2 py-1 text-xs tabular-nums text-cyan-200">{{ $locked ? 'Premium' : $count }}</span></summary>
                <div class="border-t border-white/10 p-4">
                    @if($locked)
                        <p class="text-xs leading-6 text-blue-100/55">Detailed link tables are a Premium feature.</p><a href="{{ $upgradeUrl }}" wire:navigate class="mt-2 inline-block text-xs font-bold text-cyan-300">Unlock tables ↗</a>
                    @elseif(!empty($inventory['details']))
                        <x-seo.evidence-table :rows="$inventory['details']" />
                    @else
                        <p class="text-xs leading-6 text-blue-100/55">No entries found in the inspected HTML.</p>
                    @endif
                </div>
            </details>
        @endforeach
    </div>

    <div id="seo-panel-meta" role="tabpanel" aria-labelledby="seo-tab-meta" x-show="reportTab === 'meta'" x-cloak class="space-y-5">
        <section class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                <h2 class="text-base font-extrabold text-white">Search preview</h2>
                <div class="flex rounded-lg border border-white/10 p-1" aria-label="Preview size">
                    @foreach(['desktop' => 'desktop_windows', 'mobile' => 'smartphone'] as $device => $icon)
                        <button type="button" @click="previewDevice = '{{ $device }}'" :aria-pressed="previewDevice === '{{ $device }}'" aria-label="{{ ucfirst($device) }} preview" class="cursor-pointer rounded-md px-3 py-1.5 text-cyan-200" :class="previewDevice === '{{ $device }}' ? 'bg-cyan-400/15' : 'opacity-50'"><span class="material-symbols-outlined text-base" aria-hidden="true">{{ $icon }}</span></button>
                    @endforeach
                </div>
            </header>
            <div class="p-5 sm:p-7">
                <div class="mx-auto rounded-xl border border-white/10 bg-slate-950 p-5 transition-all" :class="previewDevice === 'mobile' ? 'max-w-sm' : 'max-w-2xl'">
                    <p class="text-xs wrap-anywhere text-blue-100/55">{{ $report['url'] }}</p>
                    <h3 class="mt-2 text-xl leading-7 wrap-anywhere text-blue-300">{{ $report['snippet']['title'] ?: 'No page title found' }}</h3>
                    <p class="mt-2 text-sm leading-7 wrap-anywhere text-blue-100/70">{{ $report['snippet']['description'] ?: 'No meta description found.' }}</p>
                </div>
                <p class="mt-4 text-center text-[11px] leading-6 text-blue-100/40">Illustration based on the observed title and description. Search engines can choose different text.</p>
            </div>
        </section>
        @foreach(['robots-file', 'sitemap-file'] as $id)
            @if($crawlFinding = $findings->get($id))<x-seo.finding :finding="$crawlFinding" />@endif
        @endforeach
        <section class="rounded-xl border border-white/10 bg-slate-900 p-5">
            <h2 class="text-sm font-extrabold text-white">About this check</h2>
            <p class="mt-2 text-xs leading-6 text-blue-100/55">Analyzer {{ $report['version'] }} inspects server-rendered HTML for one page. Resource files and linked pages are not crawled. The screenshot is a separate preview and does not affect your score.</p>
        </section>
    </div>

    @if(!$premium)
        <aside class="mt-7 flex flex-col items-start justify-between gap-4 rounded-2xl border border-cyan-300/20 bg-linear-to-r from-cyan-950 to-blue-950 p-6 sm:flex-row sm:items-center">
            <div><h3 class="text-base font-extrabold text-white">Get the complete report with Premium</h3><p class="mt-2 text-xs leading-6 text-blue-100/55">Daily checks based on your plan, detailed link analysis, every recommendation, saved history, and PDF downloads.</p></div>
            <a href="{{ $upgradeUrl }}" wire:navigate class="shrink-0 rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-3 text-xs font-extrabold text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5">Explore Premium ↗</a>
        </aside>
    @endif
</div>
