<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\TransactionResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request, DashboardService $dashboard): JsonResponse
    {
        $summary = $dashboard->summary($request->user(), $request->period());
        $summary['recent_transactions'] = TransactionResource::collection($summary['recent_transactions']);

        return response()->json(['data' => $summary]);
    }
}
