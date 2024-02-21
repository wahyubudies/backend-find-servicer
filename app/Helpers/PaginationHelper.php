<?php

namespace App\Helpers;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginationHelper
{
    public static function formatPagination($paginator)
    {
        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total_page' => $paginator->lastPage(),
            'next_page' => $paginator->currentPage() < $paginator->lastPage() ? $paginator->currentPage() + 1 : null,
            'prev_page' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'total_data' => $paginator->total(),
        ];
    }

    public static function paginateCollection($collection, $perPage)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($currentPageItems, $collection->count(), $perPage, $currentPage);
        return $paginator->setPath(request()->url());
    }
}
