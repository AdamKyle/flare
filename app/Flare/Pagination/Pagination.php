<?php

namespace App\Flare\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as LeagueCollection;
use League\Fractal\TransformerAbstract;

readonly class Pagination {

    public function __construct(private Manager $manager) {}

    /**
     * Responsible for paginating a Support Collection.
     *
     * @param Collection $items
     * @param int $perPage
     * @param int $currentPage
     * @return array
     */
    public function paginateCollectionResponse(Collection $items, int $perPage = 15, int $currentPage = 1): array
    {
        $total = $items->count();
        $sliced = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator($sliced, $total, $perPage, $currentPage);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'can_load_more' => $paginator->hasMorePages(),
                'pagination' => [
                    'count' => $paginator->count(),
                    'current_page' => $paginator->currentPage(),
                    'links' => [
                        'next' => $paginator->nextPageUrl(),
                        'prev' => $paginator->previousPageUrl(),
                    ],
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Transforms for the data and paginates it.
     *
     * @param EloquentCollection $databaseCollection
     * @param TransformerAbstract $transformer
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function buildPaginatedDate(EloquentCollection $databaseCollection, TransformerAbstract $transformer, int $perPage, int $page): array {
        $paginator = $this->paginateCollection($databaseCollection, $perPage, $page);

        $databaseCollection = new LeagueCollection($paginator->items(), $transformer);

        $databaseCollection->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $data = $this->manager->createData($databaseCollection)->toArray();

        $data['meta']['can_load_more'] = $paginator->hasMorePages();

        return $data;
    }

    /**
     * paginate a collection of objects.
     *
     * @param Collection $collection
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    private function paginateCollection(Collection $collection, int $perPage = 10, int $page = 1): LengthAwarePaginator {
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