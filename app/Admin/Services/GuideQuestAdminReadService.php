<?php

namespace App\Admin\Services;

use App\Admin\Requests\GuideQuestIndexRequest;
use App\Flare\Models\GuideQuest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GuideQuestAdminReadService
{
    /**
     * Paginate Guide Quests for the validated Admin list request.
     *
     * @param GuideQuestIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(GuideQuestIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        return GuideQuest::query()
            ->when(! empty($searchText), function (Builder $query) use ($searchText): void {
                $query->where(function (Builder $searchQuery) use ($searchText): void {
                    $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                        ->orWhere('intro_text', 'LIKE', '%'.$searchText.'%');
                });
            })
            ->orderBy($request->validated('sort_key'), $request->validated('sort_direction'))
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }
}
