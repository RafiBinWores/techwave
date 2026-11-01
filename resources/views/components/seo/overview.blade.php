@props(['report', 'sections', 'premium'])

@php
    $score = $report['score'] ?? null;
    $scoreColor = $score === null ? '#64748b' : ($score >= 80 ? '#34d399' : ($score >= 50 ? '#fbbf24' : '#fb7185'));
@endphp
<section class="overflow-hidden rounded-2xl border border-white/10 bg-white/6 shadow-[0_24px_80px_rgba(0,0,0,0.2)] backdrop-blur-2xl" aria-labelledby="seo-on-page-score">
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
            <span class="mt-2 text-xs text-blue-100/60">{{ ($report['scoring_version'] ?? null) === \App\Support\Seo\ReportScores::VERSION ? 'Weighted score' : 'Original score' }}</span>
        </div>
        <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
            @foreach($sections as $category => $name)
                @php($categoryScore = $report['categories'][$category] ?? null)
                <a href="#seo-section-{{ \Illuminate\Support\Str::slug($category) }}" class="group rounded-lg focus-visible:outline-2 focus-visible:outline-cyan-300" @click="reportTab = 'details'">
                    <div class="mb-2 flex justify-between gap-2 text-xs"><span class="text-blue-100/75 group-hover:text-white">{{ $name }}</span><strong class="tabular-nums text-white">{{ $categoryScore === null ? 'Not scored' : $categoryScore.'%' }}</strong></div>
                    <div class="h-2 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full bg-linear-to-r from-cyan-400 to-blue-500" style="width:{{ $categoryScore ?? 0 }}%"></span></div>
                </a>
            @endforeach
        </div>
    </div>
    <footer class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-white/10 px-5 py-3 text-[11px] text-blue-100/50 sm:px-6">
        <span>{{ $report['check_count'] ?? count($report['findings']) }} checks</span>
        <span class="text-emerald-300">{{ $report['summary']['passed'] }} passed</span>
        <span class="text-amber-300">{{ $report['summary']['warnings'] }} warnings</span>
        <span>{{ $report['summary']['unknown'] }} unverified</span>
        <span class="sm:ml-auto">{{ $premium ? 'Premium report' : 'Free report' }}</span>
    </footer>
</section>
