<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Internet Speed Test')] class extends Component {};
?>

@push('styles')
    <style>
    .st-page { --st-muted: rgba(219,234,254,.52); --st-border: rgba(255,255,255,.1); }
    .st-page [hidden] { display: none !important; }
    :is(.st-server-picker,.st-server-choice,.st-server-close,.st-server-auto):focus-visible { outline: 2px solid #67e8f9; outline-offset: 3px; }
    @media(max-width:640px) { .st-server-overlay { padding: .75rem; } .st-server-modal { padding: 1.1rem; max-height: 92dvh; } .st-server-change { font-size: 0; } .st-server-change .material-symbols-outlined { font-size: 1.25rem; } .st-server-picker { padding: .8rem; } }
    .st-panel { min-width: 0; padding: 1.4rem; border: 1px solid var(--st-border); border-radius: 1rem; background: rgba(255,255,255,.055); box-shadow: 0 18px 50px #0003; backdrop-filter: blur(24px); }
    .st-overview { display: grid; grid-template-columns: minmax(0,2fr) minmax(0,1fr); gap: 1.25rem; align-items: stretch; }
    @media(min-width:1024px) { .st-server-panel { display: flex; flex-direction: column; } .st-server-panel .st-map { flex: 1; min-height: 270px; } }
    .st-heading { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: 1.2rem; }
    .st-heading h2 { font-size: 1rem; font-weight: 750; letter-spacing: -.025em; }
    .st-heading > .material-symbols-outlined { color: #67e8f9; font-size: 1.2rem; }
    .st-heading a { color: #67e8f9; font-size: .7rem; }
    .st-heading a:hover { text-decoration: underline; }
    .st-phase { padding: .3rem .7rem; border: 1px solid var(--st-border); border-radius: 999px; color: var(--st-muted); font-size: .65rem; font-weight: 700; }
    .st-phase[data-state="active"] { border-color: #22d3ee55; color: #67e8f9; background: #22d3ee15; }
    .st-phase[data-state="done"] { color: #6ee7b7; background: #10b98115; border-color: #10b98144; }
    .st-phase[data-state="error"] { color: #fca5a5; background: #ef444415; }
    .st-metrics { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr) minmax(0,.72fr); gap: 1.4rem; }
    .st-metrics h3 { display: flex; align-items: center; gap: .3rem; font-size: .75rem; color: #dbeafe; font-weight: 650; }
    .st-metrics h3 .material-symbols-outlined { color: #67e8f9; font-size: 1rem; }
    .st-big-value { margin-top: .9rem; font-size: clamp(1.55rem,2.65vw,2.4rem); line-height: 1.15; letter-spacing: -.045em; font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .st-chart-caption { display: flex; justify-content: space-between; gap: .3rem; margin-top: 1.25rem; padding-top: .4rem; border-top: 1px solid var(--st-border); font-size: .55rem; color: var(--st-muted); }
    .st-bandwidth-chart { width: 100%; height: 160px; margin-top: .3rem; overflow: visible; }
    .st-chart-line { stroke: #22d3ee; stroke-width: 2; fill: none; stroke-linejoin: round; stroke-linecap: round; vector-effect: non-scaling-stroke; }
    #st-chart-upload, #st-chart-latency-upload { stroke: #60a5fa; }
    #st-chart-latency { stroke: #c4b5fd; }
    .st-response-metrics { display: flex; flex-direction: column; gap: 1.3rem; }
    .st-small-value { margin-top: .35rem; font-size: 1.3rem; font-weight: 750; letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
    .st-loaded { display: flex; flex-wrap: wrap; gap: .45rem; color: var(--st-muted); font-size: .6rem; margin-top: .25rem; }
    .st-controls { display: flex; align-items: center; flex-wrap: wrap; gap: .6rem; margin-top: 1.3rem; }
    .st-controls button { cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; border-radius: .7rem; font-size: .7rem; font-weight: 700; padding: .65rem .8rem; transition: background .2s; flex-shrink: 0; }
    .st-controls button:focus-visible { outline: 2px solid #67e8f9; outline-offset: 3px; }
    .st-controls button:disabled { opacity: .45; cursor: default; }
    .st-controls .material-symbols-outlined { font-size: 1rem; }
    .st-start { color: #fff; background: linear-gradient(110deg,#06b6d4,#1565f9); box-shadow: 0 5px 20px #06b6d425; }
    .st-icon-button { color: #dbeafe; background: #ffffff08; border: 1px solid var(--st-border); }
    .st-icon-button:hover { background: #ffffff14; }
    .st-controls p { flex: 1; font-size: .7rem; color: #a5f3fc; min-width: 140px; }
    .st-progress { margin-top: .65rem; height: 5px; border-radius: 5px; background: #ffffff0c; overflow: hidden; }
    .st-progress::before { content: ''; display: block; width: var(--st-progress,0%); height: 100%; background: linear-gradient(90deg,#22d3ee,#3b82f6); transition: width .4s; }
    .st-quality { margin-top: 1.6rem; padding-top: 1.25rem; border-top: 1px solid var(--st-border); }
    .st-experiences { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: .6rem; }
    .st-experience { display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .85rem .6rem; background: #ffffff04; border: 1px solid var(--st-border); border-radius: .7rem; }
    .st-experience-icon { color: #a5f3fc; font-size: 1.65rem; }
    .st-experience h3 { color: #dbeafe; font-size: .7rem; }
    .st-experience p { font-size: .6rem; color: var(--st-muted); margin-top: .2rem; }
    .st-experience p[data-level="great"], .st-experience p[data-level="good"] { color: #6ee7b7; }
    .st-experience p[data-level="average"] { color: #fde68a; }
    .st-experience p[data-level="bad"], .st-experience p[data-level="poor"] { color: #fca5a5; }
    .st-stars { display: flex; margin-top: .25rem; }
    .st-stars span { color: #ffffff25; font-size: .95rem; font-variation-settings: 'FILL' 0; }
    .st-stars span[data-filled="true"] { color: #67e8f9; font-variation-settings: 'FILL' 1; }
    .st-map { position: relative; height: 270px; overflow: hidden; border: 1px solid var(--st-border); border-radius: .75rem; background: #02061766; }
    .st-map iframe { width: 100%; height: 100%; border: 0; filter: invert(.93) hue-rotate(180deg) saturate(.7); }
    #st-map-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--st-muted); font-size: .7rem; }
    #st-map-placeholder .material-symbols-outlined { color: #22d3ee55; font-size: 4rem; margin-bottom: .5rem; }
    .st-map-caption { position: absolute; left: .6rem; right: .6rem; bottom: .6rem; padding: .55rem .75rem; background: #020617d9; border: 1px solid #ffffff12; border-radius: .5rem; backdrop-filter: blur(12px); pointer-events: none; }
    .st-map-caption span { display: block; font-size: .8rem; font-weight: 700; color: #e0f2fe; }
    .st-map-caption small { font-size: .6rem; color: #67e8f9; }
    .st-connection { margin-top: 1rem; display: grid; gap: .75rem; }
    .st-connection > div { display: grid; grid-template-columns: 105px minmax(0,1fr); gap: .65rem; font-size: .7rem; line-height: 1.5; }
    .st-connection dt { display: flex; align-items: start; gap: .35rem; color: var(--st-muted); }
    .st-connection dt .material-symbols-outlined { font-size: .95rem; }
    .st-connection dd { color: #dbeafe; overflow-wrap: anywhere; }
    .st-asn, #st-d-ip { color: #67e8f9; }
    .st-auto-note { display: flex; align-items: center; gap: .35rem; color: var(--st-muted); font-size: .6rem; margin-top: 1rem; padding-top: .75rem; border-top: 1px solid var(--st-border); }
    .st-auto-note .material-symbols-outlined { color: #67e8f9; font-size: 1rem; }
    .st-measurements { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 1.25rem; margin-top: 1.25rem; align-items: start; }
    .st-count { font-size: .6rem; color: var(--st-muted); }
    .st-empty { font-size: .7rem; color: var(--st-muted); line-height: 1.6; }
    .st-samples details { margin-top: .6rem; border: 1px solid var(--st-border); border-radius: .6rem; overflow: hidden; }
    .st-samples summary { cursor: pointer; padding: .8rem; color: #dbeafe; font-size: .7rem; background: #ffffff04; }
    .st-samples table { width: 100%; font-size: .65rem; font-variant-numeric: tabular-nums; }
    .st-samples :is(td,th) { padding: .45rem .6rem; text-align: right; border-top: 1px solid #ffffff08; }
    .st-samples th { color: var(--st-muted); font-weight: 600; }
    .st-samples :is(td,th):first-child { text-align: left; }
    .st-latency-row { padding: .65rem 0; border-top: 1px solid #ffffff08; }
    .st-latency-row > div { display: flex; justify-content: space-between; font-size: .65rem; color: var(--st-muted); }
    .st-latency-row strong { color: #dbeafe; font-weight: 600; }
    .st-latency-row svg { width: 100%; height: 55px; margin-top: .4rem; }
    .st-packet-detail { margin-top: 1.25rem; }
    .st-packet-detail .st-heading { margin-bottom: .6rem; }
    .st-method { display: flex; align-items: start; gap: .6rem; margin-top: 1.25rem; color: var(--st-muted); font-size: .65rem; line-height: 1.7; }
    .st-method > .material-symbols-outlined { color: #67e8f9; font-size: 1.1rem; }
    .st-method p { flex: 1; }
    @media(max-width:1023px) { .st-overview { grid-template-columns: minmax(0,1fr); } .st-server-panel { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 1rem; } .st-server-panel .st-heading { grid-column: 1/-1; margin-bottom: 0; } .st-map { height: 220px; grid-row: span 2; } .st-server-panel .st-connection { margin-top: 0; } .st-measurements { grid-template-columns: minmax(0,1fr) minmax(0,1fr); } .st-measurements > section:last-child { grid-column: 1/-1; } }
    @media(max-width:639px) { .st-panel { padding: 1rem; } .st-metrics { grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: .85rem; } .st-big-value { font-size: 1.6rem; } .st-bandwidth-chart { height: 100px; } .st-chart-caption { flex-direction: column; font-size: .55rem; } .st-response-metrics { grid-column: 1/-1; flex-direction: row; justify-content: space-between; gap: .4rem; margin-top: .5rem; } .st-response-metrics article { flex: 1; min-width: 0; } .st-small-value { font-size: 1.1rem; } .st-loaded { flex-direction: column; gap: .1rem; } .st-experiences { grid-template-columns: minmax(0,1fr); } .st-experience { padding: .75rem; } .st-experience > div { display: grid; grid-template-columns: 1fr auto; flex: 1; align-items: center; gap: .2rem .75rem; } .st-experience p { grid-column: 1/-1; margin: 0; } .st-server-panel { display: block; } .st-server-panel .st-heading { margin-bottom: 1rem; } .st-server-panel .st-connection { margin-top: 1rem; } .st-measurements { grid-template-columns: minmax(0,1fr); } .st-measurements > section:last-child { grid-column: auto; } }
</style>
@endpush

<section class="min-h-screen text-white">
    <div class="mx-auto w-full max-w-7xl px-4 pb-24 pt-10 sm:px-6 lg:px-8">
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">Internet <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text pr-1.5 italic text-transparent">Speed Test</span></h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100/60">Test your download, upload and latency.</p>
        </div>

        <div id="speed-test-app" class="st-page"
            data-st-geo-providers='@json(config("speed-test.geo.providers"))'
            data-st-geo-timeout='@json((int) config("speed-test.geo.timeout_ms", 4000))'>
            <div class="st-overview">
                <section class="st-panel st-speed-panel" aria-labelledby="st-speed-heading">
                    <div class="st-heading"><h2 id="st-speed-heading">Your Internet Speed</h2><span id="st-phase" class="st-phase" data-state="ready">Ready</span></div>
                    <div class="st-metrics">
                        <article class="st-bandwidth">
                            <h3>Download <span class="material-symbols-outlined" aria-hidden="true">south</span></h3>
                            <p id="st-m-download" class="st-big-value">-- Mbps</p>
                            <div class="st-chart-caption"><span>90th percentile</span><span id="st-download-count">0 samples</span></div>
                            <svg class="st-bandwidth-chart" viewBox="0 0 320 116" preserveAspectRatio="none" role="img" aria-label="download speed measurements"><defs><linearGradient id="st-download-gradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#22d3ee" stop-opacity=".3"/><stop offset="100%" stop-color="#22d3ee" stop-opacity="0"/></linearGradient></defs><path id="st-chart-download-area" fill="url(#st-download-gradient)"/><path id="st-chart-download" class="st-chart-line"/></svg>
                        </article>
                        <article class="st-bandwidth st-upload">
                            <h3>Upload <span class="material-symbols-outlined" aria-hidden="true">north</span></h3>
                            <p id="st-m-upload" class="st-big-value">-- Mbps</p>
                            <div class="st-chart-caption"><span>90th percentile</span><span id="st-upload-count">0 samples</span></div>
                            <svg class="st-bandwidth-chart" viewBox="0 0 320 116" preserveAspectRatio="none" role="img" aria-label="upload speed measurements"><defs><linearGradient id="st-upload-gradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#3b82f6" stop-opacity=".3"/><stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/></linearGradient></defs><path id="st-chart-upload-area" fill="url(#st-upload-gradient)"/><path id="st-chart-upload" class="st-chart-line"/></svg>
                        </article>
                        <div class="st-response-metrics">
                            <article><h3>Latency</h3><p id="st-m-ping" class="st-small-value">-- ms</p><div class="st-loaded"><span title="Latency during download">↓ <span id="st-ping-download">-- ms</span></span><span title="Latency during upload">↑ <span id="st-ping-upload">-- ms</span></span></div></article>
                            <article><h3>Jitter</h3><p id="st-m-jitter" class="st-small-value">-- ms</p><div class="st-loaded"><span title="Jitter during download">↓ <span id="st-jitter-download">-- ms</span></span><span title="Jitter during upload">↑ <span id="st-jitter-upload">-- ms</span></span></div></article>
                        </div>
                    </div>
                    <div class="st-controls">
                        <button id="st-start" type="button" class="st-start"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>Start Test</button>
                        <button id="st-pause" type="button" class="st-icon-button" hidden><span id="st-pause-icon" class="material-symbols-outlined" aria-hidden="true">pause</span><span id="st-pause-label">Pause</span></button>
                        <button id="st-retest" type="button" class="st-icon-button" hidden><span class="material-symbols-outlined" aria-hidden="true">refresh</span>Retest</button>
                        <p id="st-status" role="status" aria-live="polite">Ready to measure your connection</p>
                    </div>
                    <div id="st-progress" class="st-progress" role="progressbar" aria-label="Speed test progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-state="ready"></div>
                    <div class="st-quality">
                        <div class="st-heading"><h2>Network Quality Score</h2><a href="https://developers.cloudflare.com/fundamentals/speed/aim/" target="_blank" rel="noopener noreferrer">Learn more <span aria-hidden="true">↗</span></a></div>
                        <div class="st-experiences"><article class="st-experience"><span class="material-symbols-outlined st-experience-icon" aria-hidden="true">live_tv</span><div><h3>Streaming</h3><div id="st-stars-streaming" class="st-stars" aria-label="Score pending"><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span></div></div></article>
<article class="st-experience"><span class="material-symbols-outlined st-experience-icon" aria-hidden="true">sports_esports</span><div><h3>Gaming</h3><div id="st-stars-gaming" class="st-stars" aria-label="Score pending"><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span></div></div></article>
<article class="st-experience"><span class="material-symbols-outlined st-experience-icon" aria-hidden="true">videocam</span><div><h3>Video Calls</h3><div id="st-stars-rtc" class="st-stars" aria-label="Score pending"><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span><span class="material-symbols-outlined" data-filled="false" aria-hidden="true">star</span></div></div></article></div>
                    </div>
                </section>

                <aside class="st-panel st-server-panel" aria-labelledby="st-server-heading">
                    <div class="st-heading"><h2 id="st-server-heading">Server Location</h2><span class="material-symbols-outlined" aria-hidden="true">public</span></div>
                    <div class="st-map">
                        <iframe id="st-server-map" title="Detected speed test server location" loading="lazy" referrerpolicy="no-referrer" hidden></iframe>
                        <div id="st-map-placeholder"><span class="material-symbols-outlined" aria-hidden="true">travel_explore</span><p>Detecting server location…</p></div>
                        <div class="st-map-caption"><span id="st-m-server">Detecting…</span><small id="st-d-colo">Automatically routed edge</small></div>
                    </div>
                    <dl class="st-connection">
                        <div><dt><span class="material-symbols-outlined" aria-hidden="true">lan</span>Connected via</dt><dd id="st-d-protocol">--</dd></div>
                        <div><dt><span class="material-symbols-outlined" aria-hidden="true">hub</span>Your network</dt><dd><span id="st-d-isp">Detecting…</span> <span id="st-d-asn" class="st-asn"></span></dd></div>
                        <div><dt><span class="material-symbols-outlined" aria-hidden="true">language</span>Your IP address</dt><dd id="st-d-ip">--</dd></div>
                        <div><dt><span class="material-symbols-outlined" aria-hidden="true">location_on</span>Your location</dt><dd id="st-d-location">--</dd></div>
                    </dl>
                </aside>
            </div>

            <div class="st-measurements">
                <section class="st-panel"><div class="st-heading"><h2>Download Measurements</h2><span class="material-symbols-outlined" aria-hidden="true">south</span></div><div id="st-download-measurements" class="st-samples"><p class="st-empty">Waiting for measurements…</p></div></section>
                <section class="st-panel"><div class="st-heading"><h2>Upload Measurements</h2><span class="material-symbols-outlined" aria-hidden="true">north</span></div><div id="st-upload-measurements" class="st-samples"><p class="st-empty">Waiting for measurements…</p></div></section>
                <section class="st-panel"><div class="st-heading"><h2>Latency Measurements</h2><span id="st-latency-count" class="st-count">0 samples</span></div><div class="st-latency-row"><div><span>Unloaded latency</span><strong id="st-detail-latency-idle">-- ms</strong></div><svg viewBox="0 0 320 116" preserveAspectRatio="none" role="img" aria-label="Unloaded latency measurements"><path id="st-chart-latency" class="st-chart-line"/></svg></div>
<div class="st-latency-row"><div><span>During download</span><strong id="st-detail-latency-download">-- ms</strong></div><svg viewBox="0 0 320 116" preserveAspectRatio="none" role="img" aria-label="During download measurements"><path id="st-chart-latency-download" class="st-chart-line"/></svg></div>
<div class="st-latency-row"><div><span>During upload</span><strong id="st-detail-latency-upload">-- ms</strong></div><svg viewBox="0 0 320 116" preserveAspectRatio="none" role="img" aria-label="During upload measurements"><path id="st-chart-latency-upload" class="st-chart-line"/></svg></div></section>
            </div>
            <div class="st-method"><span class="material-symbols-outlined" aria-hidden="true">verified</span><p>Results vary by server and connection.</p><span id="st-finished-at"></span></div>
        </div>
    </div>

    @push('scripts')
            <script>
    (() => {
        function initSpeedTest() {
            const root = document.getElementById('speed-test-app');
            if (!root || root.dataset.stMounted) return;
            if (!window.loadSpeedTest) {
                root.querySelector('#st-status').textContent = 'Could not load the test engine. Refresh the page.';
                root.querySelector('#st-start').disabled = true;
                return;
            }
            window.loadSpeedTest().then(({ mountSpeedTest }) => mountSpeedTest(root)).catch(() => {
                root.querySelector('#st-status').textContent = 'Could not load the test engine. Refresh the page.';
                root.querySelector('#st-start').disabled = true;
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSpeedTest);
        else initSpeedTest();
        document.addEventListener('livewire:navigated', initSpeedTest);
    })();
</script>
    @endpush
</section>

