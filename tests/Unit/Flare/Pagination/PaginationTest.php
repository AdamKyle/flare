<?php

namespace Tests\Unit\Flare\Pagination;

use App\Flare\Pagination\Pagination;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\TransformerAbstract;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    public function test_paginate_collection_response_returns_the_requested_page(): void
    {
        $pagination = new Pagination(new Manager());
        $items = new Collection(range(1, 25));

        $result = $pagination->paginateCollectionResponse($items, perPage: 10, currentPage: 2);

        $this->assertSame(range(11, 20), $result['data']);
        $this->assertSame(25, $result['meta']['pagination']['total']);
        $this->assertSame(2, $result['meta']['pagination']['current_page']);
        $this->assertTrue($result['meta']['can_load_more']);
    }

    public function test_build_paginated_date_transforms_and_paginates_an_eloquent_collection(): void
    {
        $pagination = new Pagination(new Manager());
        $transformer = new class extends TransformerAbstract
        {
            public function transform($item): array
            {
                return ['value' => $item];
            }
        };

        $result = $pagination->buildPaginatedDate(new EloquentCollection([1, 2, 3]), $transformer, 10, 1);

        $this->assertSame([['value' => 1], ['value' => 2], ['value' => 3]], $result['data']);
        $this->assertFalse($result['meta']['can_load_more']);
    }

    public function test_transform_length_aware_paginator_transforms_the_given_paginator(): void
    {
        $pagination = new Pagination(new Manager());
        $transformer = new class extends TransformerAbstract
        {
            public function transform($item): array
            {
                return ['value' => $item];
            }
        };
        $paginator = new LengthAwarePaginator([1, 2], 2, 10, 1);

        $result = $pagination->transformLengthAwarePaginator($paginator, $transformer);

        $this->assertSame([['value' => 1], ['value' => 2]], $result['data']);
    }

    public function test_transform_collection_response_slices_and_transforms_the_collection(): void
    {
        $pagination = new Pagination(new Manager());
        $transformer = new class extends TransformerAbstract
        {
            public function transform($item): array
            {
                return ['value' => $item];
            }
        };

        $result = $pagination->transformCollectionResponse(new Collection([1, 2, 3, 4]), $transformer, perPage: 2, currentPage: 1);

        $this->assertSame([['value' => 1], ['value' => 2]], $result['data']);
    }
}
