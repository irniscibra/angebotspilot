<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->company->materials();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('datanorm_article_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $materials = $query->orderBy('category')->orderBy('name')->get();

        // Kategorien mit Anzahl
        $categories = $request->user()->company->materials()
            ->select('category')
            ->selectRaw('count(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'materials' => $materials,
            'categories' => $categories,
        ]);
    }

    /**
     * Schnelle Materialsuche für Autocomplete in der Positionserfassung.
     * Gibt max 20 Ergebnisse zurück mit den wichtigsten Feldern.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $search = $request->q;

        $materials = $request->user()->company->materials()
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('datanorm_article_number', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('ean', 'like', "%{$search}%");
            })
            ->select([
                'id', 'name', 'description', 'category', 'sku', 'unit',
                'purchase_price', 'selling_price', 'supplier',
                'datanorm_article_number', 'source',
            ])
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", ["{$search}%"])
            ->limit(20)
            ->get();

        return response()->json($materials);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'markup_percent' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_sku' => 'nullable|string|max:100',
        ]);

        $material = Material::create([
            'company_id' => $request->user()->company_id,
            ...$request->only([
                'category', 'subcategory', 'name', 'description', 'sku', 'unit',
                'purchase_price', 'selling_price', 'markup_percent',
                'supplier', 'supplier_sku',
            ]),
        ]);

        return response()->json($material, 201);
    }

    public function show(Request $request, Material $material): JsonResponse
    {
        if ($material->company_id !== $request->user()->company_id) {
            abort(403);
        }

        return response()->json($material);
    }

    public function update(Request $request, Material $material): JsonResponse
    {
        if ($material->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $material->update($request->only([
            'category', 'subcategory', 'name', 'description', 'sku', 'unit',
            'purchase_price', 'selling_price', 'markup_percent',
            'supplier', 'supplier_sku', 'is_active',
        ]));

        return response()->json($material);
    }

    public function destroy(Request $request, Material $material): JsonResponse
    {
        if ($material->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $material->delete();

        return response()->json(['message' => 'Material gelöscht.']);
    }
}