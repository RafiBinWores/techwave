@props(['report'])

@php
    $page = $report['page'] ?? [];
    $screenshotState = $report['integrations']['screenshot']['status'] ?? 'not_connected';
@endphp
<section class="overflow-hidden rounded-2xl border border-white/10 bg-white/6 backdrop-blur-2xl" aria-labelledby="seo-page-information">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4 sm:px-6">
        <h2 id="seo-page-information" class="flex items-center gap-2 text-base font-extrabold text-white"><span class="material-symbols-outlined text-xl text-cyan-300" aria-hidden="true">html</span>Page information</h2>
        <a href="{{ $report['url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-bold text-blue-100 transition hover:border-cyan-300/30 hover:text-cyan-200">Show page ↗</a>
    </header>
    <div class="grid gap-6 p-5 sm:p-6 md:grid-cols-[240px_minmax(0,1fr)]">
        <div>
            <x-seo.screenshot :report="$report" :screenshot-state="$screenshotState" />
            <p class="mt-2 text-[10px] text-blue-100/40">Page screenshot: {{ in_array($screenshotState, ['ready', 'connected'], true) ? 'Preview' : 'Unavailable' }}</p>
        </div>
        <div class="min-w-0">
            <dl class="space-y-3 text-xs">
                <div><dt class="mb-1 text-blue-100/45">Meta title</dt><dd class="font-semibold leading-6 wrap-anywhere text-white">{{ $report['snippet']['title'] ?: 'No page title found' }}</dd></div>
                <div><dt class="mb-1 text-blue-100/45">Meta description</dt><dd class="leading-6 wrap-anywhere text-blue-100/75">{{ $report['snippet']['description'] ?: 'No meta description found.' }}</dd></div>
                <div class="flex gap-3"><dt class="text-blue-100/45">URL</dt><dd class="min-w-0 wrap-anywhere text-cyan-300">{{ $report['url'] }}</dd></div>
            </dl>
            @if($page)
                <dl class="mt-4 grid gap-x-8 gap-y-3 border-t border-white/10 pt-4 text-xs sm:grid-cols-2">
                    @foreach(['status' => 'Status code', 'response_ms' => 'Response time', 'language' => 'Language', 'html_bytes' => 'File size', 'word_count' => 'Word count', 'image_count' => 'Images'] as $key => $label)
                        <div class="flex items-center justify-between gap-3"><dt class="text-blue-100/50">{{ $label }}</dt><dd class="font-semibold tabular-nums text-blue-100">{{ match($key) { 'response_ms' => number_format(($page[$key] ?? 0) / 1000, 2).' sec', 'html_bytes' => number_format(($page[$key] ?? 0) / 1000, 2).' kB', 'language' => strtoupper(($page[$key] ?? '') ?: 'Not declared'), default => number_format($page[$key] ?? 0) } }}</dd></div>
                    @endforeach
                    <div class="flex items-center justify-between gap-3"><dt class="text-blue-100/50">Page directives</dt><dd class="flex flex-wrap gap-1">
                        @foreach(['indexable' => ['No noindex', 'Noindex'], 'followable' => ['Follow', 'Nofollow']] as $key => $labels)
                            @if(isset($page[$key]))<span class="rounded px-2 py-1 text-[10px] font-bold {{ $page[$key] ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300' }}">{{ $labels[$page[$key] ? 0 : 1] }}</span>@endif
                        @endforeach
                    </dd></div>
                </dl>
            @endif
        </div>
    </div>
</section>
