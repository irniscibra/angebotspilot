<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcceptanceProtocol;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AcceptanceProtocolController extends Controller
{
    /**
     * Alle Protokolle der Firma.
     */
    public function index(Request $request): JsonResponse
    {
        $protocols = $request->user()->company->acceptanceProtocols()
            ->with('quote:id,quote_number,project_title')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($protocols);
    }

    /**
     * Einzelnes Protokoll anzeigen.
     */
    public function show(Request $request, AcceptanceProtocol $protocol): JsonResponse
    {
        $this->authorizeProtocol($request, $protocol);

        $protocol->load(['quote.items', 'quote.customer', 'creator']);

        // Signaturen nur laden wenn explizit angefragt
        if ($request->has('include_signatures')) {
            $protocol->makeVisible(['signature_contractor', 'signature_client']);
        }

        return response()->json($protocol);
    }

    /**
     * Neues Protokoll aus Angebot erstellen (mit KI-Zusammenfassung).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'quote_id' => 'required|exists:quotes,id',
        ]);

        $quote = Quote::with(['items', 'customer', 'company'])->findOrFail($request->quote_id);

        if ($quote->company_id !== $request->user()->company_id) {
            abort(403);
        }

        // KI-Zusammenfassung der durchgeführten Arbeiten generieren
        $workSummary = $this->generateWorkSummary($quote);

        // Protokoll-Nummer generieren
        $lastNumber = AcceptanceProtocol::where('company_id', $request->user()->company_id)
            ->max('id') ?? 0;
        $protocolNumber = 'ABN-' . date('Y') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        // Kundendaten
        $clientName = '';
        if ($quote->customer) {
            $clientName = $quote->customer->type === 'business'
                ? $quote->customer->company_name
                : $quote->customer->first_name . ' ' . $quote->customer->last_name;
        }

        $protocol = AcceptanceProtocol::create([
            'company_id' => $request->user()->company_id,
            'quote_id' => $quote->id,
            'created_by' => $request->user()->id,
            'protocol_number' => $protocolNumber,
            'project_title' => $quote->project_title,
            'project_address' => $quote->project_address,
            'acceptance_date' => now()->toDateString(),
            'contractor_name' => $quote->company->name,
            'client_name' => $clientName,
            'work_summary' => $workSummary,
            'defects' => [],
            'status' => 'draft',
        ]);

        $protocol->load(['quote.items', 'quote.customer', 'creator']);

        return response()->json($protocol, 201);
    }

    /**
     * Protokoll aktualisieren.
     */
    public function update(Request $request, AcceptanceProtocol $protocol): JsonResponse
    {
        $this->authorizeProtocol($request, $protocol);

        $request->validate([
            'project_title' => 'sometimes|string|max:255',
            'project_address' => 'nullable|string|max:500',
            'execution_start' => 'nullable|date',
            'execution_end' => 'nullable|date',
            'acceptance_date' => 'nullable|date',
            'contractor_name' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_representative' => 'nullable|string|max:255',
            'result' => 'sometimes|in:accepted,accepted_with_defects,rejected',
            'work_summary' => 'nullable|string',
            'defects' => 'nullable|array',
            'defects.*.title' => 'required_with:defects|string|max:255',
            'defects.*.description' => 'nullable|string',
            'defects.*.severity' => 'nullable|in:minor,major,critical',
            'defects.*.deadline' => 'nullable|date',
            'notes' => 'nullable|string',
            'agreements' => 'nullable|string',
            'status' => 'sometimes|in:draft,completed',
        ]);

        $protocol->update($request->only([
            'project_title', 'project_address',
            'execution_start', 'execution_end', 'acceptance_date',
            'contractor_name', 'client_name', 'client_representative',
            'result', 'work_summary', 'defects',
            'notes', 'agreements', 'status',
        ]));

        return response()->json($protocol->fresh());
    }

    /**
     * Unterschrift speichern.
     */
    public function sign(Request $request, AcceptanceProtocol $protocol): JsonResponse
    {
        $this->authorizeProtocol($request, $protocol);

        $request->validate([
            'type' => 'required|in:contractor,client',
            'signature' => 'required|string', // base64 Bild
        ]);

        if ($request->type === 'contractor') {
            $protocol->update([
                'signature_contractor' => $request->signature,
                'signed_contractor_at' => now(),
            ]);
        } else {
            $protocol->update([
                'signature_client' => $request->signature,
                'signed_client_at' => now(),
            ]);
        }

        // Prüfen ob beide unterschrieben haben
        $protocol->refresh();
        $protocol->markAsSigned();

        return response()->json([
            'message' => 'Unterschrift gespeichert.',
            'protocol' => $protocol,
        ]);
    }

    /**
     * Protokoll löschen.
     */
    public function destroy(Request $request, AcceptanceProtocol $protocol): JsonResponse
    {
        $this->authorizeProtocol($request, $protocol);
        $protocol->delete();
        return response()->json(['message' => 'Protokoll gelöscht.']);
    }

    /**
     * KI-Zusammenfassung der durchgeführten Arbeiten generieren.
     */
    private function generateWorkSummary(Quote $quote): string
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            return $this->generateFallbackSummary($quote);
        }

        // Positionen als Text aufbereiten
        $positionsList = '';
        foreach ($quote->items as $item) {
            $type = $item->type === 'material' ? 'Material' : 'Arbeit';
            $positionsList .= "- [{$type}] {$item->title}: {$item->quantity} {$item->unit} à {$item->unit_price}€\n";
            if ($item->description) {
                $positionsList .= "  Beschreibung: {$item->description}\n";
            }
        }

        $prompt = "Du bist ein erfahrener Bauleiter und erstellst ein professionelles Abnahmeprotokoll.

Erstelle eine sachliche Zusammenfassung der durchgeführten Arbeiten basierend auf folgenden Angebotspositionen.
Die Zusammenfassung soll im Stil eines offiziellen Bauprotokolls geschrieben sein – präzise, fachlich korrekt, in der Vergangenheitsform.

Projekt: {$quote->project_title}
Adresse: {$quote->project_address}

Positionen:
{$positionsList}

Schreibe die Zusammenfassung auf Deutsch in 3-5 Absätzen. Keine Aufzählungszeichen, nur Fließtext.
Erwähne die wesentlichen Gewerke, eingesetzte Materialien und den Umfang der Arbeiten.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Du bist ein deutscher Bauleiter der professionelle Abnahmeprotokolle erstellt.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? $this->generateFallbackSummary($quote);
            }
        } catch (\Exception $e) {
            \Log::warning('Acceptance protocol AI summary failed: ' . $e->getMessage());
        }

        return $this->generateFallbackSummary($quote);
    }

    /**
     * Fallback-Zusammenfassung ohne KI.
     */
    private function generateFallbackSummary(Quote $quote): string
    {
        $materialItems = $quote->items->where('type', 'material');
        $laborItems = $quote->items->whereIn('type', ['labor', 'flat']);

        $summary = "Im Rahmen des Projekts \"{$quote->project_title}\" wurden folgende Arbeiten ausgeführt:\n\n";

        if ($materialItems->count() > 0) {
            $summary .= "Eingesetzte Materialien: ";
            $summary .= $materialItems->pluck('title')->implode(', ') . ".\n\n";
        }

        if ($laborItems->count() > 0) {
            $summary .= "Durchgeführte Arbeiten: ";
            $summary .= $laborItems->pluck('title')->implode(', ') . ".\n\n";
        }

        $summary .= "Die Arbeiten wurden fachgerecht und gemäß den geltenden DIN-Normen sowie den anerkannten Regeln der Technik ausgeführt.";

        return $summary;
    }

    /**
     * Prüft ob Protokoll zur Firma des Users gehört.
     */
    private function authorizeProtocol(Request $request, AcceptanceProtocol $protocol): void
    {
        if ($protocol->company_id !== $request->user()->company_id) {
            abort(403, 'Zugriff verweigert.');
        }
    }
}