<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DatanormImport;
use App\Services\DatanormParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatanormController extends Controller
{
    public function __construct(
        private DatanormParserService $parserService
    ) {}

    /**
     * Alle Datanorm-Imports des Unternehmens auflisten.
     */
    public function index(Request $request): JsonResponse
    {
        $imports = DatanormImport::where('company_id', $request->user()->company_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($import) {
                $import->summary = $import->summary;
                return $import;
            });

        return response()->json([
            'imports' => $imports,
        ]);
    }

    /**
     * Vorschau einer Datanorm-Datei ohne Import.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        // Akzeptierte Dateitypen
        if (!in_array($extension, ['dat', 'csv', 'txt', '001', '002', '003', '004', '005'])) {
            return response()->json([
                'message' => 'Ungültiges Dateiformat. Akzeptiert werden: .dat, .csv, .txt, .001-.005'
            ], 422);
        }

        try {
            // Ordner sicherstellen
            $tempDir = storage_path('app/temp/datanorm');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $file->store('temp/datanorm', 'local');
            $fullPath = storage_path('app/private/' . $tempPath);

            // Fallback: Prüfe ob Datei unter 'app/' liegt (Laravel 10) oder 'app/private/' (Laravel 11)
            if (!file_exists($fullPath)) {
                $fullPath = storage_path('app/' . $tempPath);
            }

            $preview = $this->parserService->preview($fullPath);

            // Temp-Datei behalten für späteren Import (in Session speichern)
            $preview['temp_path'] = $tempPath;
            $preview['original_filename'] = $file->getClientOriginalName();
            $preview['file_size'] = $file->getSize();

            return response()->json($preview);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Fehler beim Lesen der Datei: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Datanorm-Datei importieren.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required_without:temp_path|file|max:51200',
            'temp_path' => 'required_without:file|string',
            'original_filename' => 'nullable|string',
            'supplier_name' => 'nullable|string|max:255',
            'default_markup_percent' => 'nullable|numeric|min:0|max:500',
            'update_existing' => 'nullable|boolean',
            'overwrite_prices' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Datei ermitteln
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $storedPath = $file->store('datanorm/' . $user->company_id, 'local');
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        } elseif ($request->temp_path) {
            $storedPath = $request->temp_path;
            $originalFilename = $request->original_filename ?? 'datanorm.dat';

            // Pfad ermitteln (Laravel 11 = private/, Laravel 10 = direkt)
            $privatePath = storage_path('app/private/' . $storedPath);
            $directPath = storage_path('app/' . $storedPath);
            $actualPath = file_exists($privatePath) ? $privatePath : $directPath;
            $fileSize = file_exists($actualPath) ? filesize($actualPath) : 0;

            // Temp-Datei an permanenten Ort verschieben
            $newPath = 'datanorm/' . $user->company_id . '/' . basename($storedPath);
            Storage::disk('local')->move($storedPath, $newPath);
            $storedPath = $newPath;
        } else {
            return response()->json(['message' => 'Keine Datei angegeben.'], 422);
        }

        // Import-Eintrag erstellen
        $import = DatanormImport::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'filename' => $storedPath,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'supplier_name' => $request->supplier_name,
            'default_markup_percent' => $request->default_markup_percent ?? 30.00,
            'update_existing' => $request->update_existing ?? true,
            'overwrite_prices' => $request->overwrite_prices ?? true,
            'status' => 'pending',
        ]);

        // Import ausführen – Pfad ermitteln (Laravel 11 vs 10)
        $fullPath = storage_path('app/private/' . $storedPath);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $storedPath);
        }
        $result = $this->parserService->import($import, $fullPath);

        return response()->json([
            'import' => $result,
            'message' => $result->status === 'completed'
                ? "Import erfolgreich! {$result->summary}"
                : "Import fehlgeschlagen: " . ($result->errors[0]['message'] ?? 'Unbekannter Fehler'),
        ], $result->status === 'completed' ? 200 : 422);
    }

    /**
     * Details eines Imports anzeigen.
     */
    public function show(Request $request, DatanormImport $datanormImport): JsonResponse
    {
        if ($datanormImport->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $datanormImport->load('materials');
        $datanormImport->summary = $datanormImport->summary;

        return response()->json($datanormImport);
    }

    /**
     * Import und zugehörige Materialien löschen.
     */
    public function destroy(Request $request, DatanormImport $datanormImport): JsonResponse
    {
        if ($datanormImport->company_id !== $request->user()->company_id) {
            abort(403);
        }

        // Optional: Materialien dieses Imports löschen
        if ($request->boolean('delete_materials', false)) {
            $datanormImport->materials()->delete();
        } else {
            // Referenz auf Import entfernen
            $datanormImport->materials()->update(['datanorm_import_id' => null]);
        }

        // Datei löschen
        if ($datanormImport->filename && Storage::disk('local')->exists($datanormImport->filename)) {
            Storage::disk('local')->delete($datanormImport->filename);
        }

        $datanormImport->delete();

        return response()->json(['message' => 'Import gelöscht.']);
    }
}