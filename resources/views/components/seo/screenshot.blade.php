@props(['report', 'screenshotState'])

<div x-data="{ state: 'loading' }" class="relative flex h-36 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-slate-950 text-center">
    @if(in_array($screenshotState, ['connected', 'ready'], true) && !empty($report['integrations']['screenshot']['image_url']))
        <img src="{{ $report['integrations']['screenshot']['image_url'] }}"
            alt="Screenshot of the inspected page" loading="lazy" decoding="async" referrerpolicy="no-referrer"
            x-on:load="state = 'loaded'" x-on:error="state = 'error'"
            x-show="state !== 'error'" class="h-full w-full object-cover object-top">
        <p x-show="state === 'loading'" role="status" class="pointer-events-none absolute inset-x-0 bottom-0 bg-slate-950/80 px-3 py-2 text-xs text-blue-100/70">Loading screenshot…</p>
        <div x-show="state === 'error'" x-cloak role="status" class="p-3 text-xs leading-6 text-blue-100/60">
            <span class="material-symbols-outlined block text-3xl text-cyan-300/50" aria-hidden="true">image_not_supported</span>
            Screenshot unavailable. Your SEO results are still available.
        </div>
    @else
        <div class="p-3 text-xs text-blue-100/60">
            <span class="material-symbols-outlined block text-3xl text-cyan-300/50" aria-hidden="true">web_asset</span>
            Screenshot unavailable
        </div>
    @endif
</div>
