<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    public function generate(Request $request, Quote $quote)
    {
      if ($quote->company_id !== $request->user()->company_id) {
        abort(403);
    }

    $quote->load(['company', 'customer', 'items']);

    $groupedItems = $quote->items->groupBy('group_name');

$data = [
    'quote' => $quote,
    'company' => $quote->company,
    'customer' => $quote->customer,
    'groupedItems' => $groupedItems,
    'creator' => $quote->creator ?? $request->user(),
];

        $pdf = Pdf::loadView('pdf.quote', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);

        // PDF speichern
        $filename = 'angebote/' . $quote->company_id . '/' . $quote->quote_number . '.pdf';
        Storage::disk('local')->put($filename, $pdf->output());

        $quote->update([
            'pdf_path' => $filename,
            'pdf_generated_at' => now(),
        ]);

        return $pdf->download($quote->quote_number . '.pdf');
    }

    public function preview(Request $request, Quote $quote)
    {
        if ($quote->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $quote->load(['company', 'customer', 'items', 'creator']);
        $groupedItems = $quote->items->groupBy('group_name');

        $data = [
            'quote' => $quote,
            'company' => $quote->company,
            'customer' => $quote->customer,
            'groupedItems' => $groupedItems,
            'creator' => $quote->creator,
        ];

        $pdf = Pdf::loadView('pdf.quote', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream($quote->quote_number . '.pdf');
    }
}