const measured = (value, divisor = 1) => Number.isFinite(value) && value >= 0 ? value / divisor : null;

export function readResults(results) {
    const summary = results.getSummary();
    const downloadSamples = results.getDownloadBandwidthPoints();
    const uploadSamples = results.getUploadBandwidthPoints();
    return {
        download: measured(summary.download, 1e6),
        upload: measured(summary.upload, 1e6),
        latency: measured(summary.latency),
        jitter: measured(summary.jitter),
        downloadLatency: measured(summary.downLoadedLatency),
        uploadLatency: measured(summary.upLoadedLatency),
        downloadJitter: measured(summary.downLoadedJitter),
        uploadJitter: measured(summary.upLoadedJitter),
        downloadSamples,
        uploadSamples,
        downloadPoints: downloadSamples.map(point => measured(point.bps, 1e6)).filter(value => value !== null),
        uploadPoints: uploadSamples.map(point => measured(point.bps, 1e6)).filter(value => value !== null),
        latencyPoints: results.getUnloadedLatencyPoints(),
        downloadLatencyPoints: results.getDownLoadedLatencyPoints(),
        uploadLatencyPoints: results.getUpLoadedLatencyPoints(),
        scores: results.getScores(),
    };
}

function coordinates(lat, lon) {
    if (lat === undefined || lat === null || lon === undefined || lon === null || lat === '' || lon === '') return null;
    lat = Number(lat);
    lon = Number(lon);
    return Number.isFinite(lat) && Number.isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180 ? { lat, lon } : null;
}

export function readConnection(data) {
    const edge = data.colo || {};
    return {
        ip: data.clientIp || data.ip || '',
        isp: data.asOrganization || data.connection?.isp || data.org || '',
        asn: data.asn || data.connection?.asn || '',
        city: data.city || '',
        country: data.country || data.country_code || '',
        serverCity: edge.city || '',
        serverCountry: edge.cca2 || '',
        colo: edge.iata || '',
        serverCoordinates: coordinates(edge.lat, edge.lon),
    };
}

export function chartPath(points) {
    const values = points.filter(value => Number.isFinite(value) && value >= 0);
    if (!values.length) return '';
    const peak = Math.max(1, ...values) * 1.15;
    return values.map((value, index) => `${index ? 'L' : 'M'}${(index / Math.max(1, values.length - 1) * 320).toFixed(2)} ${(108 - value / peak * 100).toFixed(2)}`).join(' ') + (values.length === 1 ? ' l 1 0' : '');
}
