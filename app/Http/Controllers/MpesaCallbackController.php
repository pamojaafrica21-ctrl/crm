<?php

namespace App\Http\Controllers;

use App\Infrastructure\Billing\MpesaService;
use Illuminate\Http\Request;

class MpesaCallbackController extends Controller
{
    public function __invoke(Request $request, MpesaService $mpesaService)
    {
        $mpesaService->handleCallback($request->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
