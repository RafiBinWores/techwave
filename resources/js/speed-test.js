import SpeedTest from '@cloudflare/speedtest';
import { readResults, readConnection, chartPath } from './speed-test-results';

const names = { latency: 'Latency', download: 'Download', upload: 'Upload' };
const format = (value, unit = '') => value === null || !Number.isFinite(value) ? '--' : `${value.toLocaleString('en', { maximumFractionDigits: unit === 'ms' ? 2 : 1 })}${unit ? ' ' + unit : ''}`;
const bytesLabel = bytes => bytes >= 1e6 ? `${bytes / 1e6} MB` : `${bytes / 1e3} kB`;

export function mountSpeedTest(root) {
    if (!root || root.dataset.stMounted) return;
    root.dataset.stMounted = '1';
    const node = id => root.querySelector('#' + id);
    const text = (id, value) => { const element = node(id); if (element) element.textContent = value; };
    const start = node('st-start');
    const pause = node('st-pause');
    const retest = node('st-retest');
    let engine = null;
    let complete = false;
    let failed = false;
    let phase = null;
    let measurementIndex = 0;
    let metadataRequest = null;
    let disposed = false;

    function state(label, status) {
        text('st-phase', label);
        node('st-phase').dataset.state = status;
        node('st-progress').dataset.state = status;
    }

    function graph(id, points) {
        const path = node(id);
        const d = chartPath(points);
        path.setAttribute('d', d);
        const area = node(id + '-area');
        if (area) area.setAttribute('d', d ? d + ` L${points.length === 1 ? 1 : 320} 116 L0 116 Z` : '');
    }

    function measurements(id, samples) {
        const host = node(id);
        const groups = new Map();
        samples.forEach(sample => {
            if (!groups.has(sample.bytes)) groups.set(sample.bytes, []);
            groups.get(sample.bytes).push(sample);
        });
        const open = new Set([...host.querySelectorAll('details[open]')].map(detail => detail.dataset.bytes));
        host.replaceChildren();
        if (!groups.size) {
            const placeholder = document.createElement('p');
            placeholder.className = 'st-empty';
            placeholder.textContent = 'Waiting for measurements…';
            host.appendChild(placeholder);
        }
        groups.forEach((points, bytes) => {
            const details = document.createElement('details');
            details.dataset.bytes = String(bytes);
            details.open = open.has(String(bytes));
            const summary = document.createElement('summary');
            summary.textContent = `${bytesLabel(bytes)} test · ${points.length} samples`;
            const table = document.createElement('table');
            const head = table.createTHead().insertRow();
            ['Sample', 'Speed', 'Duration'].forEach(label => {
                const cell = document.createElement('th');
                cell.textContent = label;
                head.appendChild(cell);
            });
            const body = table.createTBody();
            points.forEach((point, index) => {
                const row = body.insertRow();
                [index + 1, format(point.bps / 1e6, 'Mbps'), format(point.duration, 'ms')].forEach(value => {
                    row.insertCell().textContent = value;
                });
            });
            details.append(summary, table);
            host.appendChild(details);
        });
    }

    function render() {
        if (!engine || disposed) return;
        const view = readResults(engine.results);
        [['download', view.download, 'Mbps'], ['upload', view.upload, 'Mbps'], ['ping', view.latency, 'ms'], ['jitter', view.jitter, 'ms']].forEach(([key, value, unit]) => text('st-m-' + key, format(value, unit)));
        [['ping-download', view.downloadLatency], ['ping-upload', view.uploadLatency], ['jitter-download', view.downloadJitter], ['jitter-upload', view.uploadJitter]].forEach(([key, value]) => text('st-' + key, format(value, 'ms')));
        graph('st-chart-download', view.downloadPoints);
        graph('st-chart-upload', view.uploadPoints);
        graph('st-chart-latency', view.latencyPoints);
        graph('st-chart-latency-download', view.downloadLatencyPoints);
        graph('st-chart-latency-upload', view.uploadLatencyPoints);
        text('st-download-count', view.downloadSamples.length + ' samples');
        text('st-upload-count', view.uploadSamples.length + ' samples');
        text('st-latency-count', view.latencyPoints.length + ' samples');
        measurements('st-download-measurements', view.downloadSamples);
        measurements('st-upload-measurements', view.uploadSamples);
        [['idle', view.latency], ['download', view.downloadLatency], ['upload', view.uploadLatency]].forEach(([key, value]) => text('st-detail-latency-' + key, format(value, 'ms')));
        if (engine.isRunning && phase) {
            const count = phase.type === 'latency' ? view.latencyPoints.length + ' latency samples' : bytesLabel(phase.bytes) + ' payload';
            text('st-status', `Measuring ${names[phase.type] || phase.type} · ${count}`);
        }
        const progress = complete ? 100 : Math.min(97, (measurementIndex + 1) / engine.config.measurements.length * 100);
        node('st-progress').style.setProperty('--st-progress', progress + '%');
        node('st-progress').setAttribute('aria-valuenow', String(Math.round(progress)));
        for (const key of ['streaming', 'gaming', 'rtc']) {
            const score = complete ? view.scores[key] : null;
            const stars = node('st-stars-' + key);
            stars.setAttribute('aria-label', score ? `${score.classificationIdx + 1} out of 5` : 'Score pending');
            [...stars.children].forEach((star, index) => star.dataset.filled = String(Boolean(score && index <= score.classificationIdx)));
        }
    }

    function renderConnection(data) {
        const info = readConnection(data);
        if (info.ip) text('st-d-ip', info.ip);
        if (info.isp) text('st-d-isp', info.isp);
        if (info.asn) text('st-d-asn', 'AS' + String(info.asn).replace(/^AS/i, ''));
        if (info.city) text('st-d-location', [info.city, info.country].filter(Boolean).join(', '));
        if (info.ip) text('st-d-protocol', info.ip.includes(':') ? 'IPv6' : 'IPv4');
        if (info.serverCity) {
            text('st-m-server', [info.serverCity, info.serverCountry].filter(Boolean).join(', '));
            text('st-d-colo', info.colo ? 'Cloudflare Edge · ' + info.colo : 'Cloudflare Edge');
        }
        if (info.serverCoordinates) {
            const { lat, lon } = info.serverCoordinates;
            const map = node('st-server-map');
            const url = new URL('https://www.openstreetmap.org/export/embed.html');
            url.searchParams.set('bbox', [Math.max(-180, lon - .22), Math.max(-90, lat - .16), Math.min(180, lon + .22), Math.min(90, lat + .16)].join(','));
            url.searchParams.set('layer', 'mapnik');
            url.searchParams.set('marker', lat + ',' + lon);
            if (map.src !== url.href) map.src = url.href;
            map.hidden = false;
            node('st-map-placeholder').hidden = true;
        }
    }

    async function lookupConnection(refresh = false) {
        if (metadataRequest && !refresh) return metadataRequest;
        metadataRequest = (async () => {
            const endpoints = ['https://speed.cloudflare.com/meta'];
            let fallback = [];
            try { fallback = JSON.parse(root.dataset.stGeoProviders || '[]'); } catch {}
            endpoints.push(...fallback);
            for (const endpoint of endpoints) {
                try {
                    const response = await fetch(endpoint, { cache: 'no-store', signal: AbortSignal.timeout(Number(root.dataset.stGeoTimeout) || 4000) });
                    if (!response.ok) continue;
                    const data = await response.json();
                    if (disposed) return;
                    if (data.clientIp || data.ip) {
                        renderConnection(data);
                        return;
                    }
                } catch { /* Try the next IP information provider. */ }
            }
            if (!disposed) text('st-d-isp', 'Unavailable');
        })();
        return metadataRequest;
    }

    function newTest() {
        if (disposed || (engine?.isRunning && !complete && !failed)) return;
        engine?.pause();
        complete = false;
        failed = false;
        phase = null;
        measurementIndex = 0;
        // Preserve the official engine's timing, staged payloads and percentiles.
        // Only disable result logging; measurements still go directly to Cloudflare.
        engine = new SpeedTest({ autoStart: false, logAimApiUrl: null, logMeasurementApiUrl: null });
        engine.config.measurements = engine.config.measurements.filter(measurement => !measurement.type.startsWith('packetLoss'));
        const current = engine;
        engine.onPhaseChange = ({ measurement, measurementId }) => {
            if (disposed || engine !== current) return;
            phase = measurement;
            measurementIndex = measurementId;
            state(names[measurement.type] || measurement.type, 'active');
            render();
        };
        engine.onResultsChange = () => { if (engine === current) render(); };
        engine.onRunningChange = running => {
            if (disposed || engine !== current) return;
            pause.disabled = complete || failed;
            pause.hidden = false;
            text('st-pause-label', running ? 'Pause' : 'Resume');
            text('st-pause-icon', running ? 'pause' : 'play_arrow');
            if (!running && !complete && !failed) state('Paused', 'paused');
        };
        engine.onFinish = () => {
            if (disposed || engine !== current) return;
            complete = true;
            state('Complete', 'done');
            text('st-status', 'Test complete');
            pause.disabled = true;
            pause.hidden = true;
            render();
            text('st-finished-at', new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
        };
        engine.onError = () => {
            if (disposed || engine !== current) return;
            failed = true;
            state('Error', 'error');
            text('st-status', 'The measurement could not finish. Check your connection and retest.');
            pause.disabled = true;
            pause.hidden = true;
        };
        start.hidden = true;
        retest.hidden = false;
        pause.hidden = false;
        render();
        engine.play();
        lookupConnection(true);
    }

    start.addEventListener('click', newTest);
    retest.addEventListener('click', newTest);
    pause.addEventListener('click', () => {
        if (!engine || complete || failed) return;
        if (engine.isRunning) engine.pause();
        else { state(names[phase?.type] || 'Testing', 'active'); engine.play(); }
    });
    document.addEventListener('livewire:navigating', () => { disposed = true; engine?.pause(); }, { once: true });
    lookupConnection();
}
