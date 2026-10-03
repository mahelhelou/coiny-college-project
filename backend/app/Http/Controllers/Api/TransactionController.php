<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    /**
     * TRX-08/09: filtered, paginated list (BR-16).
     */
    public function index(TransactionIndexRequest $request): AnonymousResourceCollection
    {
        $transactions = $request->user()->transactions()
            ->with('category')
            ->filter($request->validated())
            ->latestFirst()
            ->paginate($request->perPage())
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    public function store(TransactionRequest $request): JsonResponse
    {
        $transaction = $request->user()->transactions()->create($request->validated());

        return TransactionResource::make($transaction->load('category'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $transaction): TransactionResource
    {
        $transaction = $request->user()->transactions()->with('category')->findOrFail($transaction);

        return TransactionResource::make($transaction);
    }

    public function update(TransactionRequest $request, string $transaction): TransactionResource
    {
        $transaction = $request->user()->transactions()->findOrFail($transaction);
        $transaction->update($request->validated());

        return TransactionResource::make($transaction->load('category'));
    }

    /**
     * BR-08: hard delete; the client confirms first.
     */
    public function destroy(Request $request, string $transaction): Response
    {
        $request->user()->transactions()->findOrFail($transaction)->delete();

        return response()->noContent();
    }
}
