<?php

namespace App\Helpers;

class PaginationHelper
{
    public static function formatPagination($paginator)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'data' => $paginator->items(),
            'total_data' => $paginator->total(),
            'total_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'next_page' => $paginator->currentPage() < $paginator->lastPage() ? $paginator->currentPage() + 1 : null,
            'prev_page' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null            
        ];
    }
}
