<?php

namespace App\Http\Controllers;

use App\Models\SeoReport;
use App\Support\Seo\Entitlements;
use App\Support\Seo\ReportPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoReportPdfController extends Controller
{
    public function __invoke(Request $request, SeoReport $seoReport): Response
    {
        abort_unless($request->user() && $seoReport->user_id === $request->user()->id && app(Entitlements::class)->premium($request->user()), 403);
        abort_if($seoReport->created_at->lt(now()->subDays(config('seo.retention_days', 90))), 404);
        $report = app(ReportPresenter::class)->present($seoReport->payload, true);

        return Pdf::loadView('pages.client.tools.seo.report-pdf', compact('report'))
            ->setOption('isRemoteEnabled', false)->setOption('isPhpEnabled', false)->setOption('isJavascriptEnabled', false)
            ->download('seo-report-' . $seoReport->id . '.pdf');
    }
}
