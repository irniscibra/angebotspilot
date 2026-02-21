<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customers = $request->user()->company->customers()
            ->withCount('quotes')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:private,business',
            'first_name' => 'required_if:type,private|nullable|string|max:100',
            'last_name' => 'required_if:type,private|nullable|string|max:100',
            'company_name' => 'required_if:type,business|nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'address_street' => 'nullable|string|max:255',
            'address_zip' => 'nullable|string|max:10',
            'address_city' => 'nullable|string|max:100',
        ]);

        $customer = Customer::create([
            'company_id' => $request->user()->company_id,
            ...$request->only([
                'type', 'first_name', 'last_name', 'company_name',
                'contact_person', 'email', 'phone', 'mobile',
                'address_street', 'address_zip', 'address_city', 'notes',
            ]),
        ]);

        return response()->json($customer, 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            abort(403);
        }

        return response()->json($customer->load('quotes'));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $customer->update($request->only([
            'type', 'first_name', 'last_name', 'company_name',
            'contact_person', 'email', 'phone', 'mobile',
            'address_street', 'address_zip', 'address_city', 'notes',
        ]));

        return response()->json($customer);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $customer->delete();

        return response()->json(['message' => 'Kunde gelöscht.']);
    }
}