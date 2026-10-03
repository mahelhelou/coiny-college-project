<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['income', 'expense'])],
        ]);

        $categories = $request->user()->categories()
            ->withCount('transactions')
            ->when($validated['type'] ?? null, fn ($q, string $type) => $q->where('type', $type))
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $request->user()->categories()->create($request->validated());

        return CategoryResource::make($category->loadCount('transactions'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * BR-04: rename only; the request never exposes a type on update.
     */
    public function update(CategoryRequest $request, string $category): CategoryResource
    {
        $category = $request->user()->categories()->findOrFail($category);
        $category->update($request->validated());

        return CategoryResource::make($category->loadCount('transactions'));
    }

    /**
     * BR-07: a category with transactions cannot be deleted (409).
     */
    public function destroy(Request $request, string $category): Response
    {
        $category = $request->user()->categories()->findOrFail($category);

        abort_if(
            $category->transactions()->exists(),
            Response::HTTP_CONFLICT,
            'This category has transactions and cannot be deleted.',
        );

        $category->delete();

        return response()->noContent();
    }
}
