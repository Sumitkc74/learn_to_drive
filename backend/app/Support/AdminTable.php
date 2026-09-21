<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminTable
{
    public static function paginate(
        Builder $query,
        Request $request,
        array $searchable,
        array $sortable,
        array $filters = []
    ) {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search, $searchable) {
                foreach ($searchable as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, 'like', '%' . $search . '%');
                }
            });
        }

        foreach ($filters as $parameter => $settings) {
            $value = $request->query($parameter);
            $allowed = $settings['allowed'] ?? [];

            if ($value !== null && in_array($value, $allowed, true)) {
                if (isset($settings['apply']) && is_callable($settings['apply'])) {
                    $settings['apply']($query, $value);
                } else {
                    $query->where($settings['column'] ?? $parameter, $value);
                }
            }
        }

        $sort = (string) $request->query('sort', 'created_at');
        $sort = in_array($sort, $sortable, true) ? $sort : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }
}
