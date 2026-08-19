<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProposalPdfController extends Controller
{
        public function download(Proposal $proposal)
    {
        $proposal->loadMissing('items');

        $template = ProposalTemplate::activeTemplate();
        $settings = SiteSetting::current();

        $pdf = Pdf::loadView('pdf.proposal-invoice', [
            'proposal' => $proposal,
            'template' => $template,
            'settings' => $settings,
        ])
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'chroot' => base_path(),
            ]);

        return $pdf->download(($proposal->proposal_no ?? 'proposal').'.pdf');
    }
}
