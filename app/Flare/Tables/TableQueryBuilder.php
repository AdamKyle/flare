<?php

namespace App\Flare\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TableQueryBuilder
{
    /**
     * @param  TableColumn[]  $columns
     */
    public static function paginate(Builder $query, array $columns, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $rawSearch = $request->query('search', '');
        $search = trim(is_string($rawSearch) ? $rawSearch : '');

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($columns, $search) {
                foreach ($columns as $column) {
                    if (! $column->searchable || is_null($column->field)) {
                        continue;
                    }

                    if (str_contains($column->field, '.')) {
                        $segments = explode('.', $column->field);
                        $fieldName = array_pop($segments);
                        $relationPath = implode('.', $segments);

                        $searchQuery->orWhereHas($relationPath, function (Builder $relationQuery) use ($fieldName, $search) {
                            $relationQuery->where($fieldName, 'like', '%'.$search.'%');
                        });

                        continue;
                    }

                    $searchQuery->orWhere($column->field, 'like', '%'.$search.'%');
                }
            });
        }

        $sortField = $request->query('sort');
        $sortDirection = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $sortColumn = collect($columns)->first(
            fn (TableColumn $column) => $column->sortable && $column->field === $sortField
        );

        if (! is_null($sortColumn)) {
            if (! is_null($sortColumn->sortUsing)) {
                ($sortColumn->sortUsing)($query, $sortDirection);
            } else {
                $query->orderBy($sortColumn->field, $sortDirection);
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
