<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\DatanormController;
use App\Http\Controllers\Api\ServiceTemplateController;
/*
|--------------------------------------------------------------------------
| API Routes – AngebotsPilot
|--------------------------------------------------------------------------
*/

// ── Auth (öffentlich) ──
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// ── Geschützte Routen (Sanctum) ──
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Angebote (CRUD)
    Route::apiResource('quotes', QuoteController::class);

    // Angebote (Aktionen)
    Route::post('quotes/{quote}/regenerate', [QuoteController::class, 'regenerate']);
    Route::post('quotes/{quote}/send', [QuoteController::class, 'send']);
    Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate']);

    // Angebots-Positionen
    Route::post('quotes/{quote}/items', [QuoteController::class, 'addItem']);
    Route::put('quotes/{quote}/items/{item}', [QuoteController::class, 'updateItem']);
    Route::delete('quotes/{quote}/items/{item}', [QuoteController::class, 'deleteItem']);

    // Kunden
    Route::apiResource('customers', CustomerController::class);

    // Firma
Route::get('company', [CompanyController::class, 'show']);
Route::put('company', [CompanyController::class, 'update']);
Route::post('company/logo', [CompanyController::class, 'uploadLogo']);
Route::delete('company/logo', [CompanyController::class, 'removeLogo']);

// Materialsuche (Autocomplete)
Route::get('/materials/search', [MaterialController::class, 'search']);

// Materialien
Route::apiResource('materials', MaterialController::class);

// Datanorm Import
Route::prefix('datanorm')->group(function () {
    Route::get('/', [DatanormController::class, 'index']);
    Route::post('/preview', [DatanormController::class, 'preview']);
    Route::post('/import', [DatanormController::class, 'import']);
    Route::get('/{datanormImport}', [DatanormController::class, 'show']);
    Route::delete('/{datanormImport}', [DatanormController::class, 'destroy']);
});

//template ServiceTemplate
Route::prefix('service-templates')->group(function () {
    Route::get('/', [ServiceTemplateController::class, 'index']);
    Route::post('/', [ServiceTemplateController::class, 'store']);
    Route::get('/{serviceTemplate}', [ServiceTemplateController::class, 'show']);
    Route::put('/{serviceTemplate}', [ServiceTemplateController::class, 'update']);
    Route::delete('/{serviceTemplate}', [ServiceTemplateController::class, 'destroy']);
    Route::post('/from-quote/{quote}', [ServiceTemplateController::class, 'createFromQuote']);
    Route::post('/{serviceTemplate}/apply/{quote}', [ServiceTemplateController::class, 'applyToQuote']);
});

// PDF-Generierung^
Route::get('quotes/{quote}/pdf', [PdfController::class, 'generate']);
Route::get('quotes/{quote}/pdf/preview', [PdfController::class, 'preview']);

    // Dashboard Stats
    Route::get('dashboard/stats', function (\Illuminate\Http\Request $request) {
        $company = $request->user()->company;

        return response()->json([
            'quotes_total' => $company->quotes()->count(),
            'quotes_draft' => $company->quotes()->where('status', 'draft')->count(),
            'quotes_sent' => $company->quotes()->where('status', 'sent')->count(),
            'quotes_accepted' => $company->quotes()->where('status', 'accepted')->count(),
            'quotes_this_month' => $company->quotes()->whereMonth('created_at', now()->month)->count(),
            'revenue_accepted' => $company->quotes()->where('status', 'accepted')->sum('total_gross'),
            'conversion_rate' => $company->quotes()->whereIn('status', ['sent', 'viewed', 'accepted', 'rejected'])->count() > 0
                ? round(
                    $company->quotes()->where('status', 'accepted')->count() /
                    $company->quotes()->whereIn('status', ['sent', 'viewed', 'accepted', 'rejected'])->count() * 100,
                    1
                )
                : 0,
        ]);
    });
});