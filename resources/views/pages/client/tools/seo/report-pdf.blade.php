<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SEO report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #18243b;
            font-size: 9px;
            line-height: 1.6
        }

        h1 {
            font-size: 25px;
            margin: 5px 0
        }

        h2 {
            font-size: 15px;
            color: #087f9c;
            margin-top: 24px;
            border-bottom: 1px solid #b9d9df;
            padding-bottom: 5px
        }

        h3 {
            font-size: 11px;
            margin: 0 0 7px
        }

        .muted {
            color: #64748b
        }

        .score {
            font-size: 32px;
            font-weight: bold;
            color: #087f9c;
            margin: 10px 0
        }

        .finding {
            border: 1px solid #dce4ed;
            border-left: 3px solid #64748b;
            padding: 10px;
            margin: 10px 0;
            page-break-inside: avoid
        }

        .finding.pass {
            border-left-color: #10b981
        }

        .finding.warning {
            border-left-color: #f59e0b
        }

        .finding.fail {
            border-left-color: #f43f5e
        }

        .check {
            padding: 4px 0
        }

        .check.pass {
            color: #047857
        }

        .check.warning {
            color: #a16207
        }

        .check.fail {
            color: #be123c
        }

        .check.unknown {
            color: #64748b
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8px;
            margin-top: 8px
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #dce4ed;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word
        }

        th {
            background: #edf5f7
        }

        p {
            word-wrap: break-word
        }

        .finding table {
            page-break-inside: auto
        }

        tr {
            page-break-inside: avoid
        }
    </style>
</head>

<body>
    @php
    $sections = ['Metadata'=>'Meta data','Content'=>'Page quality','Others'=>'Others','Structure'=>'Link structure','Technical'=>'Server configuration'];
    $selectedCategories = $report['selected_categories'] ?? null;
    if (is_array($selectedCategories) && $selectedCategories !== []) {
    $sections = array_intersect_key($sections, array_flip($selectedCategories));
    }
    if (filled(config('seo.integrations.backlinks.endpoint'))) {
    $sections['External factors'] = 'External factors';
    }
    @endphp
    <p class="muted">{{ $siteName }}</p>
    <h1>SEO Checker report</h1>
    <p>{{ $report['url'] }}</p>
    <p class="muted">Checked {{ $report['checked_at'] }} · Analyzer {{ $report['version'] }}</p>
    <p class="score">{{ $report['score'] ?? '—' }} / 100</p>
    <table>
        <tbody>@foreach($sections as $category => $label)<tr>
                <th>{{ $label }}</th>
                <td>{{ isset($report['categories'][$category]) ? $report['categories'][$category].'%' : 'Not scored' }}</td>
            </tr>@endforeach</tbody>
    </table>
    @if(!empty($report['page']))<h2>Page information</h2>
    <p><strong>{{ $report['page']['title'] }}</strong></p>
    <p>{{ $report['page']['description'] }}</p>
    <p>{{ $report['page']['word_count'] }} words · {{ $report['page']['image_count'] }} images · {{ number_format($report['page']['html_bytes'] / 1024, 1) }} KB HTML · {{ $report['page']['response_ms'] }} ms fetch · HTTP {{ $report['page']['status'] }}</p>@endif
    <p class="muted">Only the categories selected for this check are scored. Subchecks earn full credit for a pass, 25% for a warning and 0% for an issue; groups are weighted by importance, and category weights are normalized across the available selected categories. Unknown checks, optional network probes and external metrics are unscored. This is a server-rendered single-page assessment, not a ranking prediction or Core Web Vitals test.</p>
    <h2>Tasks sorted by priority</h2>
    <table>
        <thead>
            <tr>
                <th style="width:18%">Importance</th>
                <th>Recommendation</th>
            </tr>
        </thead>
        <tbody>@forelse($report['recommendations'] as $fix)<tr>
                <td>{{ [1=>'Very important',2=>'Important',3=>'Review',4=>'Nice to have'][$fix['priority'] ?? 2] }}</td>
                <td>{{ $fix['recommendation'] }}</td>
            </tr>@empty<tr>
                <td colspan="2">No actionable recommendations from the available checks.</td>
            </tr>@endforelse</tbody>
    </table>
    @foreach($sections as $category => $label)<h2>{{ $label }}</h2>@forelse(collect($report['findings'])->where('category',$category) as $finding)<div class="finding {{ $finding['status'] }}">
        <h3>{{ $finding['label'] }} · {{ ucfirst($finding['status']) }}</h3>@foreach($finding['checks'] ?? [['status'=>$finding['status'],'message'=>$finding['evidence']]] as $check)<div class="check {{ $check['status'] }}">[{{ $check['status'] }}] {{ $check['message'] }}</div>@endforeach
        @if(!empty($finding['details']))@php($columns = array_keys($finding['details'][0]))<table>
            <thead>
                <tr>@foreach($columns as $column)<th>{{ str_replace('_',' ',$column) }}</th>@endforeach</tr>
            </thead>
            <tbody>@foreach(array_slice($finding['details'],0,20) as $row)<tr>@foreach($columns as $column)<td>{{ is_bool($row[$column] ?? null) ? ($row[$column] ? 'Yes' : 'No') : (is_scalar($row[$column] ?? '') ? ($row[$column] ?? '') : json_encode($row[$column])) }}</td>@endforeach</tr>@endforeach</tbody>
        </table>@if(count($finding['details']) > 20)<p class="muted">First 20 evidence rows shown in PDF; open the web report for up to 50 rows.</p>@endif @endif
    </div>@empty<p class="muted">No checks in this section for this saved report version.</p>@endforelse @endforeach
    <p class="muted">Screenshot provider: {{ str_replace('_',' ',$report['integrations']['screenshot']['status'] ?? 'not connected') }}. Remote screenshot images are not loaded in PDF exports. Backlink metrics require a configured provider; missing data does not lower your score. Length and volume thresholds are review guidelines, not search-engine requirements. JavaScript rendering and full-site crawling are not performed.</p>
</body>

</html>