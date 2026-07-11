<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    public function search(Request $request): JsonResponse
    {
        $search = $request->query('q', '');
        $customers = Customer::where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        })->limit(20)->get();

        return response()->json($customers);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json($customer->load(['notes', 'reservations']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $customer = Customer::create(array_merge($validated, [
            'property_id' => app(PropertyContext::class)->id(),
            'source' => 'crm',
        ]));

        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }
}
