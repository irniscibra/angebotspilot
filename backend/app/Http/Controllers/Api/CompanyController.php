<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Firmendaten abrufen.
     */
    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json($company);
    }

    /**
     * Firmendaten aktualisieren.
     */
    public function update(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address_street' => 'nullable|string|max:255',
            'address_zip' => 'nullable|string|max:10',
            'address_city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'trade_register' => 'nullable|string|max:100',
            'primary_color' => 'nullable|string|max:7',
            'default_vat_rate' => 'nullable|numeric|min:0|max:100',
            'default_hourly_rate' => 'nullable|numeric|min:0',
            'quote_validity_days' => 'nullable|integer|min:1|max:365',
            'quote_prefix' => 'nullable|string|max:10',
        ]);

        $company->update($request->only([
            'name', 'address_street', 'address_zip', 'address_city',
            'phone', 'email', 'website', 'tax_id', 'trade_register',
            'primary_color', 'default_vat_rate', 'default_hourly_rate',
            'quote_validity_days', 'quote_prefix',
        ]));

        return response()->json([
            'message' => 'Firmendaten aktualisiert.',
            'company' => $company->fresh(),
        ]);
    }

    /**
     * Logo hochladen.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $company = $request->user()->company;

        // Altes Logo löschen
        if ($company->logo_path) {
            $oldPath = storage_path('app/public/' . $company->logo_path);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $path = $request->file('logo')->store('logos/' . $company->id, 'public');

        $company->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo hochgeladen.',
            'logo_url' => asset('storage/' . $path),
            'company' => $company->fresh(),
        ]);
    }

    /**
     * Logo entfernen.
     */
    public function removeLogo(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if ($company->logo_path) {
            $oldPath = storage_path('app/public/' . $company->logo_path);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $company->update(['logo_path' => null]);
        }

        return response()->json([
            'message' => 'Logo entfernt.',
            'company' => $company->fresh(),
        ]);
    }
}