<?php

use App\Application\Dashboard\KpiService;
use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Models\Property;
use App\Domain\Properties\Services\PropertyContext;
use App\Http\Controllers\Api\V1\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user());

        Route::get('/properties', function () {
            $user = auth()->user();
            $properties = app(PropertyContext::class)->availableForUser($user);

            return response()->json($properties);
        });

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/search', [CustomerController::class, 'search']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);

        Route::get('/dashboard/kpis', function (KpiService $kpiService, Request $request) {
            $from = $request->date('from') ?? now()->startOfMonth();
            $to = $request->date('to') ?? now();

            return response()->json($kpiService->getDashboardMetrics($from, $to));
        });
    });
});
