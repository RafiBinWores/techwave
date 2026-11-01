@props(['finding'])

@php
    $status = $finding['status'];
    $colors = ['pass' => 'border-l-emerald-400', 'warning' => 'border-l-amber-300', 'fail' => 'border-l-red-400', 'unknown' => 'border-l-slate-400'];
    $icons = ['pass' => 'check_circle', 'warning' => 'error', 'fail' => 'cancel', 'unknown' => 'help'];
    $iconColors = ['pass' => 'text-emerald-300', 'warning' => 'text-amber-300', 'fail' => 'text-red-300', 'unknown' => 'text-slate-400'];
    $help = [
        'title' => 'Use a descriptive title that identifies this page. Length checks are review guidelines; search engines may rewrite or truncate titles.',
        'description' => 'Summarize the page in its meta description. Search engines can use different text for different searches.',
        'robots' => 'These checks inspect indexing directives and the fetched response. Search engines make their own indexing decisions.',
        'canonical' => 'A canonical link identifies the preferred URL for similar content. It is a hint to search engines, not a guaranteed instruction.',
        'language' => 'Declare the language of the page so browsers and assistive technologies can interpret the content.',
        'hreflang' => 'Hreflang links identify language and regional alternatives. They should point to the corresponding translated pages.',
        'content-length' => 'These checks inspect the visible text, repetition and HTML resource references on this page. Word counts and ratios are guidelines, not ranking requirements.',
        'viewport' => 'A viewport declaration helps mobile browsers display the page. This check does not replace testing the rendered layout on mobile devices.',
        'image-alt' => 'Alternative text should describe meaningful images. Dimensions help browsers reserve space and reduce layout shifts.',
        'social' => 'Sharing metadata describes the page when its URL is shared. Social profile links and sharing metadata are inspected separately.',
        'structured-data' => 'Structured data describes entities to machines. Valid syntax does not guarantee rich results or higher rankings.',
        'https' => 'HTTPS protects the page transfer. Resource checks inspect URLs declared in the HTML; scripts and stylesheets are not executed.',
        'h1' => 'Use a clear main heading that describes the page. Heading checks inspect the HTML outline and visible text.',
        'headings' => 'Headings should provide a meaningful hierarchy. Empty headings and skipped levels can make the page harder to navigate.',
        'links' => 'Internal links should have descriptive anchors and useful destinations. This inventory does not fetch every linked page to test for broken links.',
        'external-links' => 'External destinations and their declared link relationships are listed from the inspected HTML.',
        'http-redirects' => 'HTTP and alternate www/non-www URLs should redirect to the same preferred HTTPS page, preserving its path and query. Unreachable alternate URLs remain unverified.',
        'http-headers' => 'X-Powered headers can disclose server technology. Compression reduces transferred bytes. These checks use the headers returned for this request.',
        'performance' => 'The timing guideline is 0.4 seconds; the HTML size guideline is 512 kB. Timing includes the server-side transfer and redirects, not isolated TTFB or browser load time. HTML size is measured after decompression.',
    ];
@endphp
<article id="seo-finding-{{ $finding['id'] }}" class="scroll-mt-36 overflow-hidden rounded-xl border border-white/10 border-l-[3px] bg-white/4 {{ $colors[$status] }}">
    @foreach($finding['source_ids'] ?? [] as $sourceId)
        @if($sourceId !== $finding['id'])<span id="seo-finding-{{ $sourceId }}" class="block scroll-mt-36"></span>@endif
    @endforeach
    <div class="grid sm:grid-cols-[200px_minmax(0,1fr)] lg:grid-cols-[230px_minmax(0,1fr)]">
        <header class="border-b border-white/10 bg-white/3 p-4 sm:border-r sm:border-b-0 sm:p-5">
            <h3 class="text-sm font-extrabold text-white">{{ $finding['label'] }}</h3>
            <details class="mt-2 text-xs leading-6 text-blue-100/60">
                <summary class="cursor-pointer font-semibold text-cyan-300">Learn more</summary>
                <p class="mt-2">{{ $help[$finding['id']] ?? 'These checks use the observed HTML and response for one public page. Review the evidence in the context of the page’s purpose.' }}</p>
            </details>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if($status !== 'pass')
                    <span class="rounded-md bg-white/6 px-2 py-1 text-[10px] font-bold {{ $iconColors[$status] }}">{{ ['warning' => 'Warning', 'fail' => 'Errors', 'unknown' => 'Unverified'][$status] }}</span>
                @endif
                <span class="text-sm font-bold tabular-nums text-blue-100">{{ $finding['passed'] }}<span class="text-blue-100/40"> / {{ $finding['total'] }}</span></span>
            </div>
            <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-blue-100/45">{{ [1 => 'Very important', 2 => 'Important', 3 => 'Low importance', 4 => 'Nice to have'][$finding['priority'] ?? 2] }}</p>
        </header>
        <div class="min-w-0 p-4 sm:p-5">
            <ul class="space-y-3">
                @foreach($finding['checks'] as $check)
                    <li class="flex items-start gap-2.5 text-xs leading-6 text-blue-100/80">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-base {{ $iconColors[$check['status']] }}" aria-hidden="true">{{ $icons[$check['status']] }}</span>
                        <span class="min-w-0 wrap-anywhere">@if($check['status'] === 'unknown')<span class="mr-1 font-semibold text-slate-400">Unverified:</span>@endif{{ $check['message'] }}</span>
                    </li>
                @endforeach
            </ul>
            @foreach($finding['sources'] ?? [$finding] as $source)
                @if(!empty($source['details']))
                    <details class="mt-4 border-t border-white/10 pt-3 text-xs">
                        <summary class="cursor-pointer font-semibold text-cyan-300">{{ $source['label'] }} · {{ min(50, count($source['details'])) }} evidence rows</summary>
                        <div class="mt-3"><x-seo.evidence-table :rows="$source['details']" /></div>
                        @if(($source['detail_count'] ?? 0) > 50)<p class="mt-2 text-blue-100/45">First 50 rows shown.</p>@endif
                    </details>
                @endif
            @endforeach
        </div>
    </div>
</article>
