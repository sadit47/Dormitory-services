<?php

if (!function_exists('apiResponse')) {
    function apiResponse(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): \Illuminate\Http\JsonResponse
    {
        $ok = $status >= 200 && $status < 300;

        $payload = [
            'ok' => $ok,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}

if (!function_exists('apiPaginate')) {
    function apiPaginate(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, string $message = 'OK'): \Illuminate\Http\JsonResponse
    {
        // ✅ ส่ง “paginate object” แบบตรงๆ อยู่ใน data
        return apiResponse([
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ], $message, 200);
    }
}
