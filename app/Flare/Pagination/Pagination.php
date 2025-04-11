<?php

namespace App\Flare\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Pagination {

    /**
     * paginate a collection of objects.
     *
     * @param Collection $collection
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function paginateCollection(Collection $collection, int $perPage = 10, int $page = 1): LengthAwarePaginator {
        $items = $collection->forPage($page, $perPage);

        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}