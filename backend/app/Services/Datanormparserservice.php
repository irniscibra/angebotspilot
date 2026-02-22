<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Material;
use App\Models\DatanormImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatanormParserService
{
    /**
     * Importiert eine Datanorm-Datei und erstellt/aktualisiert Materialien.
     */
    public function import(DatanormImport $import, string $filePath): DatanormImport
    {
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException('Datei konnte nicht gelesen werden.');
            }

            // Encoding erkennen und zu UTF-8 konvertieren
            $content = $this->convertEncoding($content);

            // Zeilen aufteilen
            $lines = preg_split('/\r\n|\r|\n/', $content);
            $lines = array_filter($lines, fn($line) => trim($line) !== '');

            // Datanorm Sätze parsen
            $articles = $this->parseLines($lines, $import);

            // Materialien erstellen/aktualisieren
            $result = $this->upsertMaterials($articles, $import);

            $import->update([
                'status' => 'completed',
                'total_records' => count($articles),
                'imported_count' => $result['imported'],
                'updated_count' => $result['updated'],
                'skipped_count' => $result['skipped'],
                'error_count' => $result['errors_count'],
                'errors' => $result['errors'] ?: null,
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Datanorm import failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            $import->update([
                'status' => 'failed',
                'errors' => [['line' => 0, 'message' => $e->getMessage()]],
                'completed_at' => now(),
            ]);
        }

        return $import->fresh();
    }

    /**
     * Konvertiert den Dateiinhalt zu UTF-8.
     * Datanorm-Dateien sind oft in ISO-8859-1 oder CP850 (DOS) kodiert.
     */
    private function convertEncoding(string $content): string
    {
        // Versuche Encoding zu erkennen
        $detected = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252', 'CP850'], true);

        if ($detected && $detected !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $detected);
        }

        // Fallback: Wenn immer noch nicht UTF-8, force ISO-8859-1
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        return $content;
    }

    /**
     * Parst alle Zeilen und extrahiert Artikeldaten.
     *
     * Datanorm 4 Satzarten:
     * - A-Satz (Typ 'A'): Stammdaten (Artikelnummer, Kurztext, Einheit, Preise)
     * - B-Satz (Typ 'B'): Langtext (zusätzliche Beschreibung)
     * - P-Satz (Typ 'P'): Preisänderungen
     * - V-Satz (Typ 'V'): Vertriebsinformationen (Lieferant etc.)
     *
     * Datanorm 5 hat ähnliche Struktur mit erweiterten Feldern.
     */
    private function parseLines(array $lines, DatanormImport $import): array
    {
        $articles = [];
        $errors = [];

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Satzart ist das erste Zeichen (Datanorm 4) oder gekennzeichnet durch Trennzeichen
            $recordType = strtoupper(substr($line, 0, 1));

            try {
                switch ($recordType) {
                    case 'A':
                        $article = $this->parseARecord($line);
                        if ($article && !empty($article['article_number'])) {
                            $key = $article['article_number'];
                            $articles[$key] = array_merge($articles[$key] ?? [], $article);
                        }
                        break;

                    case 'B':
                        $bData = $this->parseBRecord($line);
                        if ($bData && !empty($bData['article_number']) && isset($articles[$bData['article_number']])) {
                            $articles[$bData['article_number']]['long_text'] = $bData['long_text'] ?? '';
                        }
                        break;

                    case 'P':
                        $pData = $this->parsePRecord($line);
                        if ($pData && !empty($pData['article_number']) && isset($articles[$pData['article_number']])) {
                            $articles[$pData['article_number']] = array_merge($articles[$pData['article_number']], $pData);
                        }
                        break;

                    case 'V':
                        // V-Satz: Lieferantendaten – für den Header nutzen
                        $vData = $this->parseVRecord($line);
                        if ($vData && !empty($vData['supplier_name']) && !$import->supplier_name) {
                            $import->update(['supplier_name' => $vData['supplier_name']]);
                        }
                        break;

                    default:
                        // Unbekannte Satzart – könnte auch ; getrennt sein (Datanorm 5)
                        if (str_contains($line, ';')) {
                            $article = $this->parseDelimitedLine($line);
                            if ($article && !empty($article['article_number'])) {
                                $key = $article['article_number'];
                                $articles[$key] = array_merge($articles[$key] ?? [], $article);
                            }
                        }
                        break;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'line' => $lineNum + 1,
                    'message' => $e->getMessage(),
                    'content' => Str::limit($line, 100),
                ];

                if (count($errors) > 50) {
                    break; // Zu viele Fehler – abbrechen
                }
            }
        }

        return $articles;
    }

    /**
     * Parst einen A-Satz (Stammdaten) – Datanorm 4.
     *
     * Typisches Format (semikolon-getrennt oder feste Feldlängen):
     * A;Artikelnummer;Kurztext1;Kurztext2;Mengeneinheit;Preiskennz;Preis;Rabattgruppe;Warengruppe;Matchcode;EAN
     *
     * Bei festen Feldlängen:
     * Pos 0: Satzart (1)
     * Pos 1-15: Artikelnummer (15)
     * Pos 16-55: Kurztext 1 (40)
     * Pos 56-95: Kurztext 2 (40)
     * Pos 96-98: Mengeneinheit (3)
     * Pos 99: Preiskennzeichen (1)
     * Pos 100-110: Preis (11, implizit 2 Dezimalen)
     * Pos 111-114: Rabattgruppe (4)
     * Pos 115-121: Hauptwarengruppe (7)
     * Pos 122-136: Matchcode (15)
     */
    private function parseARecord(string $line): ?array
    {
        // Versuche semikolon-getrennt (Datanorm 5 Format)
        if (substr_count($line, ';') >= 5) {
            return $this->parseDelimitedLine($line);
        }

        // Feste Feldlängen (Datanorm 4)
        if (strlen($line) < 50) return null;

        $articleNumber = trim(substr($line, 1, 15));
        $shortText1 = trim(substr($line, 16, 40));
        $shortText2 = trim(substr($line, 56, 40));
        $unit = trim(substr($line, 96, 3));
        $priceFlag = trim(substr($line, 99, 1));
        $priceRaw = trim(substr($line, 100, 11));
        $discountGroup = trim(substr($line, 111, 4));
        $productGroup = trim(substr($line, 115, 7));
        $matchCode = trim(substr($line, 122, 15));

        // Preis konvertieren (implizit 2 Dezimalstellen)
        $price = $this->parsePrice($priceRaw, $priceFlag);

        // Einheit konvertieren
        $unit = $this->convertUnit($unit);

        // Name zusammenbauen
        $name = $shortText1;
        if (!empty($shortText2)) {
            $name .= ' ' . $shortText2;
        }

        if (empty($articleNumber) || empty($name)) return null;

        return [
            'article_number' => $articleNumber,
            'name' => $name,
            'short_text_1' => $shortText1,
            'short_text_2' => $shortText2,
            'unit' => $unit,
            'list_price' => $price,
            'discount_group' => $discountGroup,
            'product_group' => $productGroup,
            'match_code' => $matchCode,
        ];
    }

    /**
     * Parst einen B-Satz (Langtext).
     */
    private function parseBRecord(string $line): ?array
    {
        if (substr_count($line, ';') >= 2) {
            $parts = explode(';', $line);
            return [
                'article_number' => trim($parts[1] ?? ''),
                'long_text' => trim($parts[2] ?? ''),
            ];
        }

        if (strlen($line) < 17) return null;

        return [
            'article_number' => trim(substr($line, 1, 15)),
            'long_text' => trim(substr($line, 16)),
        ];
    }

    /**
     * Parst einen P-Satz (Preisdaten).
     */
    private function parsePRecord(string $line): ?array
    {
        if (substr_count($line, ';') >= 3) {
            $parts = explode(';', $line);
            return [
                'article_number' => trim($parts[1] ?? ''),
                'list_price' => $this->parsePrice(trim($parts[2] ?? '0'), ''),
                'gross_price' => $this->parsePrice(trim($parts[3] ?? '0'), ''),
            ];
        }

        if (strlen($line) < 28) return null;

        return [
            'article_number' => trim(substr($line, 1, 15)),
            'list_price' => $this->parsePrice(trim(substr($line, 16, 11)), ''),
            'gross_price' => $this->parsePrice(trim(substr($line, 27, 11)), ''),
        ];
    }

    /**
     * Parst einen V-Satz (Vertriebsinformation/Header).
     */
    private function parseVRecord(string $line): ?array
    {
        if (substr_count($line, ';') >= 2) {
            $parts = explode(';', $line);
            return [
                'supplier_name' => trim($parts[1] ?? ''),
                'supplier_id' => trim($parts[2] ?? ''),
            ];
        }

        if (strlen($line) < 20) return null;

        return [
            'supplier_name' => trim(substr($line, 1, 40)),
        ];
    }

    /**
     * Parst semikolon-getrennte Zeilen (Datanorm 5 Format).
     */
    private function parseDelimitedLine(string $line): ?array
    {
        $parts = explode(';', $line);
        if (count($parts) < 5) return null;

        $recordType = strtoupper(trim($parts[0]));

        if ($recordType === 'A' || is_numeric(trim($parts[1] ?? ''))) {
            $offset = ($recordType === 'A') ? 1 : 0;

            $articleNumber = trim($parts[$offset] ?? '');
            $shortText1 = trim($parts[$offset + 1] ?? '');
            $shortText2 = trim($parts[$offset + 2] ?? '');
            $unit = trim($parts[$offset + 3] ?? 'ST');
            $priceRaw = trim($parts[$offset + 4] ?? '0');
            $discountGroup = trim($parts[$offset + 5] ?? '');
            $productGroup = trim($parts[$offset + 6] ?? '');
            $matchCode = trim($parts[$offset + 7] ?? '');
            $ean = trim($parts[$offset + 8] ?? '');

            $name = $shortText1;
            if (!empty($shortText2)) {
                $name .= ' ' . $shortText2;
            }

            if (empty($articleNumber) || empty($name)) return null;

            return [
                'article_number' => $articleNumber,
                'name' => $name,
                'short_text_1' => $shortText1,
                'short_text_2' => $shortText2,
                'unit' => $this->convertUnit($unit),
                'list_price' => $this->parseDecimalPrice($priceRaw),
                'discount_group' => $discountGroup,
                'product_group' => $productGroup,
                'match_code' => $matchCode,
                'ean' => !empty($ean) ? $ean : null,
            ];
        }

        return null;
    }

    /**
     * Konvertiert Preisstring (implizit 2 Dezimalen) in Dezimalwert.
     */
    private function parsePrice(string $raw, string $flag): float
    {
        // Leere Werte
        $raw = preg_replace('/[^0-9\-]/', '', $raw);
        if (empty($raw)) return 0.0;

        $value = (float)$raw;

        // Preiskennzeichen: Multiplikator
        // 0 oder leer = Stückpreis (implizit 2 Dezimalen)
        // 1 = Preis pro 10
        // 2 = Preis pro 100
        // 3 = Preis pro 1000
        $value = $value / 100; // Implizit 2 Dezimalstellen

        switch ($flag) {
            case '1': $value = $value / 10; break;
            case '2': $value = $value / 100; break;
            case '3': $value = $value / 1000; break;
        }

        return round($value, 2);
    }

    /**
     * Parst einen Preis der bereits als Dezimalzahl vorliegt.
     */
    private function parseDecimalPrice(string $raw): float
    {
        // Dezimalkomma -> Punkt
        $raw = str_replace(',', '.', $raw);
        $raw = preg_replace('/[^0-9.\-]/', '', $raw);

        return round((float)$raw, 2);
    }

    /**
     * Konvertiert Datanorm-Einheiten in deutsche Einheiten.
     */
    private function convertUnit(string $unit): string
    {
        $unit = strtoupper(trim($unit));

        $mapping = [
            'ST'  => 'Stück',
            'STK' => 'Stück',
            'STU' => 'Stück',
            'M'   => 'Meter',
            'MTR' => 'Meter',
            'LFM' => 'Meter',
            'QM'  => 'm²',
            'M2'  => 'm²',
            'M3'  => 'm³',
            'KBM' => 'm³',
            'L'   => 'Liter',
            'LTR' => 'Liter',
            'KG'  => 'kg',
            'KGM' => 'kg',
            'SET' => 'Set',
            'PAK' => 'pauschal',
            'PAU' => 'pauschal',
            'ROL' => 'Rolle',
            'BND' => 'Bund',
            'KAR' => 'Karton',
            'PCK' => 'Packung',
        ];

        return $mapping[$unit] ?? $unit ?: 'Stück';
    }

    /**
     * Erstellt oder aktualisiert Materialien aus den gepars­ten Daten.
     */
    private function upsertMaterials(array $articles, DatanormImport $import): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errorsCount = 0;
        $errors = [];

        $companyId = $import->company_id;
        $markupPercent = (float)$import->default_markup_percent;
        $supplierName = $import->supplier_name;

        DB::beginTransaction();

        try {
            foreach ($articles as $articleNum => $article) {
                try {
                    // Prüfe ob Material schon existiert (per Artikelnummer + Firma)
                    $existing = Material::where('company_id', $companyId)
                        ->where(function ($q) use ($articleNum) {
                            $q->where('datanorm_article_number', $articleNum)
                              ->orWhere('sku', $articleNum);
                        })
                        ->first();

                    $listPrice = (float)($article['list_price'] ?? 0);
                    $grossPrice = (float)($article['gross_price'] ?? $listPrice);

                    // Verkaufspreis = Listenpreis mit Aufschlag
                    $sellingPrice = $listPrice > 0
                        ? round($listPrice * (1 + $markupPercent / 100), 2)
                        : $grossPrice;

                    // Kategorie aus Warengruppe ableiten
                    $category = $this->mapProductGroupToCategory($article['product_group'] ?? '');

                    $materialData = [
                        'company_id' => $companyId,
                        'name' => $article['name'],
                        'description' => $article['short_text_2'] ?? null,
                        'long_description' => $article['long_text'] ?? null,
                        'sku' => $articleNum,
                        'unit' => $article['unit'] ?? 'Stück',
                        'category' => $category,
                        'purchase_price' => $listPrice > 0 ? $listPrice : null,
                        'selling_price' => $sellingPrice > 0 ? $sellingPrice : 0,
                        'markup_percent' => $markupPercent,
                        'list_price' => $listPrice > 0 ? $listPrice : null,
                        'gross_price' => $grossPrice > 0 ? $grossPrice : null,
                        'supplier' => $supplierName,
                        'supplier_sku' => $articleNum,
                        'datanorm_article_number' => $articleNum,
                        'ean' => $article['ean'] ?? null,
                        'match_code' => $article['match_code'] ?? null,
                        'product_group' => $article['product_group'] ?? null,
                        'discount_group' => $article['discount_group'] ?? null,
                        'source' => 'datanorm',
                        'datanorm_import_id' => $import->id,
                        'is_active' => true,
                    ];

                    if ($existing) {
                        if ($import->update_existing) {
                            // Nur Preise und Name aktualisieren, manuelle Kategorien beibehalten
                            $updateData = [
                                'name' => $materialData['name'],
                                'description' => $materialData['description'],
                                'long_description' => $materialData['long_description'],
                                'datanorm_article_number' => $articleNum,
                                'datanorm_import_id' => $import->id,
                                'match_code' => $materialData['match_code'],
                                'product_group' => $materialData['product_group'],
                                'discount_group' => $materialData['discount_group'],
                                'ean' => $materialData['ean'],
                                'source' => 'datanorm',
                            ];

                            if ($import->overwrite_prices) {
                                $updateData['purchase_price'] = $materialData['purchase_price'];
                                $updateData['selling_price'] = $materialData['selling_price'];
                                $updateData['list_price'] = $materialData['list_price'];
                                $updateData['gross_price'] = $materialData['gross_price'];
                            }

                            $existing->update($updateData);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        Material::create($materialData);
                        $imported++;
                    }

                } catch (\Throwable $e) {
                    $errorsCount++;
                    $errors[] = [
                        'article' => $articleNum,
                        'message' => $e->getMessage(),
                    ];

                    if ($errorsCount > 100) break;
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors_count' => $errorsCount,
            'errors' => $errors ?: null,
        ];
    }

    /**
     * Mappt Datanorm-Warengruppen auf lesbare Kategorien.
     */
    private function mapProductGroupToCategory(string $productGroup): string
    {
        if (empty($productGroup)) return 'Allgemein';

        $productGroup = trim($productGroup);

        // Erste 2 Ziffern = Hauptgruppe (SHK-typische Warengruppen)
        $mainGroup = substr($productGroup, 0, 2);

        $categoryMap = [
            '01' => 'Sanitär',
            '02' => 'Sanitär',
            '03' => 'Armaturen',
            '04' => 'Armaturen',
            '05' => 'Rohre & Fittings',
            '06' => 'Rohre & Fittings',
            '07' => 'Heizung',
            '08' => 'Heizung',
            '09' => 'Heizung',
            '10' => 'Klima & Lüftung',
            '11' => 'Klima & Lüftung',
            '12' => 'Werkzeuge',
            '13' => 'Werkzeuge',
            '14' => 'Dichtungen & Kleinmaterial',
            '15' => 'Elektro',
            '16' => 'Regelungstechnik',
            '17' => 'Gas',
            '18' => 'Gas',
            '19' => 'Abwasser & Entwässerung',
            '20' => 'Befestigungstechnik',
            '21' => 'Isolierung',
            '22' => 'Fliesen & Bad',
            '23' => 'Solar & Erneuerbare',
            '24' => 'Wärmepumpen',
        ];

        return $categoryMap[$mainGroup] ?? 'Sonstiges';
    }

    /**
     * Analysiert eine Datanorm-Datei ohne zu importieren (Vorschau).
     */
    public function preview(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException('Datei konnte nicht gelesen werden.');
        }

        $content = $this->convertEncoding($content);
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_filter($lines, fn($line) => trim($line) !== '');

        $stats = [
            'total_lines' => count($lines),
            'a_records' => 0,
            'b_records' => 0,
            'p_records' => 0,
            'v_records' => 0,
            'other' => 0,
            'supplier_name' => null,
            'sample_articles' => [],
        ];

        $import = new DatanormImport(); // Dummy für parseLines

        foreach ($lines as $line) {
            $type = strtoupper(substr(trim($line), 0, 1));
            match ($type) {
                'A' => $stats['a_records']++,
                'B' => $stats['b_records']++,
                'P' => $stats['p_records']++,
                'V' => $stats['v_records']++,
                default => $stats['other']++,
            };

            // Lieferant aus V-Satz
            if ($type === 'V') {
                $vData = $this->parseVRecord(trim($line));
                if ($vData && !empty($vData['supplier_name'])) {
                    $stats['supplier_name'] = $vData['supplier_name'];
                }
            }
        }

        // Beispiel-Artikel parsen (erste 5)
        $tempImport = new DatanormImport(['company_id' => 0]);
        $articles = $this->parseLines(array_slice($lines, 0, 100), $tempImport);
        $stats['sample_articles'] = array_slice(array_values($articles), 0, 5);
        $stats['estimated_articles'] = $stats['a_records'];

        return $stats;
    }
}