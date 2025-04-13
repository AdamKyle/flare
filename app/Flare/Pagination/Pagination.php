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
     * @param EloquentCollection $slots
     * @param TransformerAbstract $transformer
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function buildPaginatedDate(EloquentCollection $slots, TransformerAbstract $transformer, int $perPage, int $page): array {
        $paginator = $this->paginateCollection($slots, $perPage, $page);

        $slots = new LeagueCollection($paginator->items(), $transformer);

        $slots->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $data = $this->manager->createData($slots)->toArray();

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