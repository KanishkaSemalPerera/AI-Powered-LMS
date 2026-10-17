<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Builds every API response in one consistent envelope:
 *
 *   { "success": bool, "message": string, "data": mixed, "errors": object|null, "meta": object|null }
 *
 * Controllers and the exception handler both go through this class so the
 * React client can rely on a single response shape.
 */
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200, ?array $meta = null): JsonResponse
    {
        // Paginated resource collections carry their own pagination meta.
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $meta = array_merge(self::paginationMeta($data->resource), $meta ?? []);
            $data = $data->collection;
        } elseif ($data instanceof LengthAwarePaginator) {
            $meta = array_merge(self::paginationMeta($data), $meta ?? []);
            $data = $data->items();
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => $meta,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created successfully.'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => null,
        ], $status);
    }

    /**
     * @return array<string, int|null>
     */
    public static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
