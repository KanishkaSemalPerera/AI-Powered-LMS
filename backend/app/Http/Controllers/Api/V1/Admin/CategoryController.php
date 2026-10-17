<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\SaveCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    /** GET /admin/categories */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);

        return ApiResponse::success(CategoryResource::collection($this->categories->list($perPage)));
    }

    /** POST /admin/categories */
    public function store(SaveCategoryRequest $request): JsonResponse
    {
        return ApiResponse::created(new CategoryResource($this->categories->create($request->validated())), 'Category created.');
    }

    /** PUT /admin/categories/{category} */
    public function update(SaveCategoryRequest $request, Category $category): JsonResponse
    {
        return ApiResponse::success(new CategoryResource($this->categories->update($category, $request->validated())), 'Category updated.');
    }

    /** DELETE /admin/categories/{category} */
    public function destroy(Category $category): JsonResponse
    {
        $this->categories->delete($category);

        return ApiResponse::success(null, 'Category deleted. Its courses are now uncategorised.');
    }
}
