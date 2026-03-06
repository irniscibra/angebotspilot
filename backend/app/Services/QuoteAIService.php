<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Material;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class QuoteAIService
{
    /**
     * Generiert ein Angebot aus einer Projektbeschreibung.
     * Nutzt den Materialkatalog des Unternehmens für echte Preise.
     */
    public function generateQuote(Quote $quote, string $description): array
    {
        $company = $quote->company;

        // Materialkatalog laden für echte Preise
        $catalogContext = $this->buildCatalogContext($company);

        $systemPrompt = $this->buildSystemPrompt($company, $catalogContext);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $description],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
            'max_tokens' => 4000,
        ]);

        $content = $response->choices[0]->message->content;
        $usage = $response->usage;

        // KI-Nutzung loggen
        AiUsageLog::create([
            'company_id' => $company->id,
            'user_id' => $quote->created_by,
            'quote_id' => $quote->id,
            'action' => 'generate_quote',
            'model' => 'gpt-4o',
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'total_tokens' => $usage->totalTokens,
            'cost_cents' => $this->calculateCost($usage->promptTokens, $usage->completionTokens),
        ]);

        $aiResult = json_decode($content, true);

        if (!$aiResult || !isset($aiResult['groups'])) {
            Log::error('AI returned invalid response', ['content' => $content]);
            throw new \RuntimeException('KI-Antwort konnte nicht verarbeitet werden. Bitte versuchen Sie es erneut.');
        }

        // Angebot mit KI-Daten aktualisieren
        $quote->update([
            'project_title' => $aiResult['project_title'] ?? $quote->project_title,
            'ai_prompt' => $description,
            'ai_response' => $aiResult,
            'ai_model' => 'gpt-4o',
            'ai_tokens_used' => $usage->totalTokens,
        ]);

        // Positionen erstellen – mit Katalog-Matching
        $this->createQuoteItems($quote, $aiResult['groups'], $company);

        // Angebot neu kalkulieren
        $quote->recalculate();

        return $aiResult;
    }

    /**
     * Baut den Materialkatalog-Kontext für den Prompt.
     * Gibt die relevanten Materialien als formatierten String zurück.
     */
    private function buildCatalogContext(Company $company): string
    {
        $materials = Material::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'sku', 'unit', 'selling_price', 'supplier', 'datanorm_article_number']);

        if ($materials->isEmpty()) {
            return '';
        }

        $lines = [];
        $currentCategory = '';

        foreach ($materials as $mat) {
            if ($mat->category !== $currentCategory) {
                $currentCategory = $mat->category;
                $lines[] = "\n[{$currentCategory}]";
            }

            $sku = $mat->datanorm_article_number ?: $mat->sku;
            $price = number_format((float)$mat->selling_price, 2, '.', '');
            $lines[] = "- Art.{$sku}: {$mat->name} | {$mat->unit} | {$price} EUR" .
                       ($mat->supplier ? " | {$mat->supplier}" : '');
        }

        // Auf max. ~3000 Zeichen begrenzen (damit Prompt nicht zu lang wird)
        $catalog = implode("\n", $lines);
        if (strlen($catalog) > 3000) {
            $catalog = substr($catalog, 0, 3000) . "\n... (weitere Artikel verfügbar)";
        }

        return $catalog;
    }

    /**
     * Baut den System-Prompt mit Firmendaten und Materialkatalog.
     */
    private function buildSystemPrompt(Company $company, string $catalogContext): string
    {
        $hourlyRate = number_format($company->default_hourly_rate, 2, '.', '');
        $vatRate = number_format($company->default_vat_rate, 2, '.', '');

        // Katalog-Abschnitt nur wenn Materialien vorhanden
        $catalogSection = '';
        if (!empty($catalogContext)) {
            $catalogSection = <<<CATALOG

MATERIALKATALOG DES BETRIEBS (echte Einkaufspreise – BEVORZUGT verwenden!):
{$catalogContext}

WICHTIG ZUM KATALOG:
- Verwende IMMER Materialien aus dem Katalog wenn passende vorhanden sind!
- Nutze die exakten Preise aus dem Katalog – das sind die echten Verkaufspreise des Betriebs.
- Gib bei Katalog-Materialien die Artikelnummer im "sku"-Feld zurück.
- Nur wenn kein passendes Material im Katalog ist, schätze den Marktpreis.
- Kennzeichne Katalog-Materialien mit "from_catalog": true
CATALOG;
        }

        return <<<PROMPT
Du bist ein erfahrener SHK-Meister (Sanitär, Heizung, Klima) und Kalkulator in Deutschland.
Erstelle aus der Projektbeschreibung ein detailliertes, professionelles Angebot.

FIRMENDATEN:
- Standard-Stundensatz Monteur: {$hourlyRate} EUR/Std (netto)
- MwSt-Satz: {$vatRate}%
- Standort: Deutschland
{$catalogSection}

REGELN FÜR DIE KALKULATION:
1. Gliedere das Angebot in logische Gewerke-Gruppen (z.B. "Demontage & Entsorgung", "Sanitärinstallation", "Rohrleitungen", "Heizungsarbeiten", etc.)
2. Trenne IMMER Material und Arbeitsleistung als separate Positionen
3. Kalkuliere realistische Mengen und Preise für den deutschen Markt (Stand 2026)
4. Verwende marktübliche Markenmaterialien (Grohe, Hansgrohe, Viega, Geberit, Buderus, Vaillant etc.)
5. Plane eine Kleinmaterial-Pauschale ein (5-8% der Materialkosten) für Dichtungen, Schrauben, Silikon etc.
6. Berücksichtige Anfahrt, Baustelleneinrichtung und -reinigung wenn sinnvoll
7. Arbeitszeiten realistisch kalkulieren – lieber etwas großzügiger als zu knapp
8. Bei Heizungsarbeiten: EnEV/GEG Normen berücksichtigen
9. Bei Sanitärarbeiten: DIN und DVGW Normen berücksichtigen

MATERIALPREISE (Richtwerte netto – NUR verwenden wenn KEIN Katalog-Artikel passt):
- Kupferrohr 15mm: 10-15 EUR/m
- Kupferrohr 22mm: 15-20 EUR/m
- Verbundrohr 16mm: 5-8 EUR/m
- HT-Rohr DN50: 7-10 EUR/m
- HT-Rohr DN100: 12-18 EUR/m
- Standard WC (Villeroy & Boch / Duravit): 300-600 EUR
- Unterputzspülkasten Geberit: 150-250 EUR
- Waschtisch Keramik: 200-500 EUR
- Waschtischarmatur (Hansgrohe/Grohe): 150-350 EUR
- Duscharmatur Unterputz: 350-600 EUR
- Duschwanne flach: 250-450 EUR
- Badewanne Standard: 400-800 EUR
- Gas-Brennwertgerät (Buderus/Vaillant): 3.000-6.000 EUR
- Heizkörper Typ 22 (60x100): 200-350 EUR
- Fußbodenheizung: 30-50 EUR/m²

STUNDENSÄTZE:
- Monteur/Geselle: {$hourlyRate} EUR/Std
- Helfer: 45.00 EUR/Std

ANTWORTE AUSSCHLIESSLICH als valides JSON in exakt diesem Format:
{
    "project_title": "Kurzer, professioneller Projekttitel",
    "groups": [
        {
            "name": "1. Gruppenname",
            "items": [
                {
                    "type": "material",
                    "title": "Materialbezeichnung mit Hersteller/Spezifikation",
                    "description": "Kurze Beschreibung oder Spezifikation",
                    "quantity": 1.0,
                    "unit": "Stück",
                    "unit_price": 0.00,
                    "sku": "Artikelnummer falls aus Katalog, sonst leer",
                    "from_catalog": true
                },
                {
                    "type": "labor",
                    "title": "Beschreibung der Arbeitsleistung",
                    "description": "Was wird gemacht",
                    "quantity": 2.0,
                    "unit": "Std",
                    "unit_price": {$hourlyRate},
                    "sku": "",
                    "from_catalog": false
                }
            ]
        }
    ],
    "notes": "Wichtige Hinweise zur Ausführung, Normen, Voraussetzungen",
    "estimated_days": 3
}

WICHTIG:
- Einheiten nur: "Stück", "Meter", "m²", "m³", "Std", "pauschal", "Liter", "kg"
- Preise sind NETTO (ohne MwSt)
- Jede Position muss "type" haben: "material" oder "labor"
- Gruppen nummerieren: "1. ...", "2. ...", etc.
- Mindestens 2 Gruppen, realistisch detailliert
- Bei jedem Material "sku" und "from_catalog" angeben
PROMPT;
    }

    /**
     * Erstellt QuoteItems aus der KI-Antwort.
     * Matcht KI-Vorschläge mit echten Katalog-Materialien.
     */
    private function createQuoteItems(Quote $quote, array $groups, Company $company): void
    {
        // Bestehende Positionen löschen (bei Regenerierung)
        $quote->items()->delete();

        // Katalog-Materialien für Matching laden
        $catalogMaterials = Material::where('company_id', $company->id)
            ->where('is_active', true)
            ->get()
            ->keyBy(function ($m) {
                return $m->datanorm_article_number ?: $m->sku;
            });

        $position = 1;
        $sortOrder = 0;

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $unitPrice = $item['unit_price'] ?? 0;
                $materialId = null;

                // Versuche Katalog-Match über SKU
                $sku = $item['sku'] ?? '';
                if (!empty($sku) && $catalogMaterials->has($sku)) {
                    $catalogMat = $catalogMaterials->get($sku);
                    // Echten Preis aus Katalog verwenden
                    $unitPrice = (float) $catalogMat->selling_price;
                    $materialId = $catalogMat->id;
                }

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'position_number' => $position++,
                    'group_name' => $group['name'],
                    'type' => $item['type'] ?? 'material',
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit' => $item['unit'] ?? 'Stück',
                    'unit_price' => $unitPrice,
                    'total_price' => ($item['quantity'] ?? 1) * $unitPrice,
                    'is_ai_generated' => true,
                    'sort_order' => $sortOrder++,
                    'material_id' => $materialId,
                ]);
            }
        }
    }

    /**
     * Berechnet die KI-Kosten in Cent (GPT-4o Preise).
     */
    private function calculateCost(int $promptTokens, int $completionTokens): int
    {
        // GPT-4o: $2.50/1M input, $10/1M output (Stand 2026)
        $inputCost = ($promptTokens / 1_000_000) * 2.50;
        $outputCost = ($completionTokens / 1_000_000) * 10.00;

        return (int) round(($inputCost + $outputCost) * 100);
    }
}