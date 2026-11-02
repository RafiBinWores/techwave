@props(['report', 'sections', 'premium'])

@php
    $score = $report['score'] ?? null;
    $scoreColor = $score === null ? '#64748b' : ($score >= 80 ? '#34d399' : ($score >= 50 ? '#fbbf24' : '#fb7185'));
@endphp
<section class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-[0_24px_80px_rgba(0,0,0,0.2)] backdrop-blur-2xl" aria-labelledby="seo-on-page-score">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4 sm:px-6">
        <h2 id="seo-on-page-score" class="text-base font-extrabold text-white">On-page score</h2>
        <div class="flex items-center gap-2 text-xs text-blue-100/60">
            <span>Issues: <strong class="text-white">{{ $report['summary']['failed'] }}</strong></span>
            <span class="rounded-md px-2 py-1 text-[10px] font-bold {{ $report['summary']['failed'] > 0 ? 'bg-red-400/10 text-red-300' : 'bg-emerald-400/10 text-emerald-300' }}">{{ $report['summary']['failed'] > 0 ? 'Needs attention' : 'No failed checks' }}</span>
        </div>
    </header>
    <div class="grid items-center gap-8 p-5 sm:p-7 md:grid-cols-[200px_minmax(0,1fr)]">
        <div class="mx-auto flex size-44 flex-col items-center justify-center rounded-full bg-[radial-gradient(closest-side,#0f172a_83%,transparent_85%),conic-gradient(var(--score-color)_calc(var(--score)*1%),#ffffff12_0)]" style="--score:{{ $score ?? 0 }};--score-color:{{ $scoreColor }}" aria-label="SEO score {{ $score ?? 'unavailable' }} out of 100">
            <strong class="text-5xl font-extrabold tracking-tight text-white">{{ $score ?? '—' }}<span class="text-2xl">%</span></strong>
            <span class="mt-2 text-xs text-blue-100/60">{{ in_array($report['scoring_version'] ?? null, ['weighted-v1', 'weighted-v2', 'weighted-v3', \App\Support\Seo\ReportScores::VERSION], true) ? 'Weighted score' : 'Original score' }}</span>
        </div>
        <div>
            <div x-cloak x-show="selectedCategory" class="mb-2 flex flex-wrap items-center justify-end gap-2">
                <button type="button" x-cloak x-show="selectedCategory" @click="selectedCategory = null"
                    class="cursor-pointer text-[11px] font-bold text-cyan-300 transition hover:text-cyan-100 focus-visible:outline-2 focus-visible:outline-cyan-300">
                    Show all categories
                </button>
            </div>
            <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2">
                @foreach($sections as $category => $name)
                    @php($categoryScore = $report['categories'][$category] ?? null)
                    @php($categorySlug = \Illuminate\Support\Str::slug($category))
                    <a href="#seo-section-{{ $categorySlug }}"
                        @click.prevent="reportTab = 'details'; selectedCategory = '{{ $category }}'; $nextTick(() => document.getElementById('seo-section-{{ $categorySlug }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                        :aria-current="selectedCategory === '{{ $category }}' ? 'true' : 'false'"
                        :class="selectedCategory === '{{ $category }}' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-transparent hover:border-white/10 hover:bg-white/5'"
                        class="group block cursor-pointer rounded-xl border px-2 py-2 transition focus-visible:outline-2 focus-visible:outline-cyan-300"
                        title="Select {{ $name }}">
                        <div class="mb-2 flex items-start justify-between gap-2 text-xs">
                            <span class="text-blue-100/75 group-hover:text-white">{{ $name }}</span>
                            <strong class="tabular-nums text-white">{{ $categoryScore === null ? 'Not scored' : $categoryScore.'%' }}</strong>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full bg-linear-to-r from-cyan-400 to-blue-500" style="width:{{ $categoryScore ?? 0 }}%"></span></div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    <footer class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-white/10 px-5 py-3 text-[11px] text-blue-100/50 sm:px-6">
        <span>{{ $report['check_count'] ?? count($report['findings']) }} checks</span>
        <span class="text-emerald-300">{{ $report['summary']['passed'] }} passed</span>
        <span class="text-amber-300">{{ $report['summary']['warnings'] }} warnings</span>
        <span>{{ $report['summary']['unknown'] }} unverified</span>
        @if(!empty($report['selected_categories']))
            <span>{{ count($report['selected_categories']) }} selected categories</span>
        @endif
        <span class="sm:ml-auto">{{ $premium ? 'Premium report' : 'Free report' }}</span>
    </footer>
</section>
