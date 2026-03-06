<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTemplate;
use App\Models\ServiceTemplateItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTemplateController extends Controller
{
    /**
     * Alle Vorlagen der Firma auflisten.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->company->serviceTemplates()
            ->withCount('items');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->where('is_active', true)
            ->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get();

        // Kategorien
        $categories = $request->user()->company->serviceTemplates()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('count(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'templates' => $templates,
            'categories' => $categories,
        ]);
    }

    /**
     * Einzelne Vorlage mit Positionen anzeigen.
     */
    public function show(Request $request, ServiceTemplate $serviceTemplate): JsonResponse
    {
        $this->authorizeTemplate($request, $serviceTemplate);

        $serviceTemplate->load('items.material');

        return response()->json($serviceTemplate);
    }

    /**
     * Neue Vorlage erstellen.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.group_name' => 'required|string|max:100',
            'items.*.type' => 'required|in:material,labor,flat,text',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.material_id' => 'nullable|exists:materials,id',
        ]);

        $template = ServiceTemplate::create([
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
        ]);

        foreach ($request->items as $index => $item) {
            ServiceTemplateItem::create([
                'service_template_id' => $template->id,
                'group_name' => $item['group_name'],
                'type' => $item['type'],
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'material_id' => $item['material_id'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $template->load('items');

        return response()->json($template, 201);
    }

    /**
     * Vorlage aktualisieren.
     */
    public function update(Request $request, ServiceTemplate $serviceTemplate): JsonResponse
    {
        $this->authorizeTemplate($request, $serviceTemplate);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $serviceTemplate->update($request->only([
            'name', 'category', 'description', 'is_active',
        ]));

        return response()->json($serviceTemplate);
    }

    /**
     * Vorlage löschen.
     */
    public function destroy(Request $request, ServiceTemplate $serviceTemplate): JsonResponse
    {
        $this->authorizeTemplate($request, $serviceTemplate);

        $serviceTemplate->delete();

        return response()->json(['message' => 'Vorlage gelöscht.']);
    }

    /**
     * Vorlage aus bestehendem Angebot erstellen.
     */
    public function createFromQuote(Request $request, Quote $quote): JsonResponse
    {
        if ($quote->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        $template = ServiceTemplate::create([
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description ?? $quote->project_description,
        ]);

        foreach ($quote->items as $index => $item) {
            ServiceTemplateItem::create([
                'service_template_id' => $template->id,
                'group_name' => $item->group_name,
                'type' => $item->type,
                'title' => $item->title,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'material_id' => $item->material_id,
                'sort_order' => $index,
            ]);
        }

        $template->load('items');

        return response()->json($template, 201);
    }

    /**
     * Vorlage in ein Angebot einfügen.
     * Fügt alle Positionen der Vorlage zum Angebot hinzu.
     */
    public function applyToQuote(Request $request, ServiceTemplate $serviceTemplate, Quote $quote): JsonResponse
    {
        $this->authorizeTemplate($request, $serviceTemplate);

        if ($quote->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $lastPosition = $quote->items()->max('position_number') ?? 0;
        $lastSort = $quote->items()->max('sort_order') ?? 0;
        $addedItems = [];

        foreach ($serviceTemplate->items as $item) {
            $lastPosition++;
            $lastSort++;

            $quoteItem = QuoteItem::create([
                'quote_id' => $quote->id,
                'position_number' => $lastPosition,
                'group_name' => $item->group_name,
                'type' => $item->type,
                'title' => $item->title,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'material_id' => $item->material_id,
                'is_ai_generated' => false,
                'sort_order' => $lastSort,
            ]);

            $addedItems[] = $quoteItem;
        }

        // Nutzungszähler erhöhen
        $serviceTemplate->incrementUsage();

        // Angebot neu kalkulieren
        $quote->recalculate();

        return response()->json([
            'message' => count($addedItems) . ' Positionen hinzugefügt.',
            'added_count' => count($addedItems),
            'quote' => $quote->fresh()->load('items'),
        ]);
    }

    /**
     * Prüft ob Vorlage zur Firma des Users gehört.
     */
    private function authorizeTemplate(Request $request, ServiceTemplate $serviceTemplate): void
    {
        if ($serviceTemplate->company_id !== $request->user()->company_id) {
            abort(403, 'Zugriff verweigert.');
        }
    }
}