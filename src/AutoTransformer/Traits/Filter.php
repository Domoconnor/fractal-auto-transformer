<?php

namespace AutoTransformer\Traits;


use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

trait Filter
{
    public function resolveQuery($query)
    {
        if (request()->has('filter')) {
            $query = $this->applyApiFilters($query);
        }

        if (request()->has('sort')) {
            $query = $this->applyApiSort($query);
        }

        return $query->get();
    }

    private function applyApiFilters($query)
    {
        $filters = request()->get('filter');

        if (is_array($filters)) {
            foreach ($filters as $column => $value) {
                // Support gt/lt comparisons
                if (Str::startsWith($column, 'gt:')) {
                    $column = substr($column, 3); // remove gt: from name & set exclude mode to on
                    $this->applyComparisonFilter($query, $column, $value, 'gt');
                    continue;
                }
                if (Str::startsWith($column, 'lt:')) {
                    $column = substr($column, 3); // remove lt: from name & set exclude mode to on
                    $this->applyComparisonFilter($query, $column, $value, 'lt');
                    continue;
                }

                // gte / lte
                if (Str::startsWith($column, 'gte:')) {
                    $column = substr($column, 4); // remove gte: from name & set exclude mode to on
                    $this->applyComparisonFilter($query, $column, $value, 'gte');
                    continue;
                }
                if (Str::startsWith($column, 'lte:')) {
                    $column = substr($column, 4); // remove lte: from name & set exclude mode to on
                    $this->applyComparisonFilter($query, $column, $value, 'lte');
                    continue;
                }

                $query = $this->applyApiFilter($query, $column, $value);
            }
        }

        // Allow filtering using ?filter=column:value,column:value as api gateway doesn't support array keys in
        // query params
        if (is_string($filters)) {
            $filters = explode(',', $filters);
            foreach ($filters as $filter) {

                list ($column, $value) = explode(':', $filter);
                $query = $this->applyApiFilter($query, $column, $value);
            }
        }
        return $query;
    }

    /**
     * Apply comparsion filter (greater than / less than)
     *
     * e.g.
     *  ?filter[lt:created_at]=2018-01-01
     *
     * @param  Builder $query  Query Builder
     * @param  string $column Column to filter on
     * @param  mixed $value  value to compare with
     * @param  string $type   gt, lt, gte, lte
     * @return Builder $query
     */
    protected function applyComparisonFilter($query, string $column, $value, $type = 'gt')
    {
        switch ($type) {
            case 'gt':
                $direction = '>';
                break;
            case 'gte':
                $direction = '>=';
                break;
            case 'lt':
                $direction = '<';
                break;
            case 'lte':
                $direction = '<=';
                break;
        }

        // Support direct or nested filters
        if (strpos($column, '.') === false) {
            $query->where($column, $direction, $value);
        } else {
            $relation =  explode('.', $column);
            $attribute = array_pop($relation);

            $query->whereHas(
                implode('.', $relation),
                function ($query) use ($attribute, $direction, $value) {
                    $query->where($attribute, $direction, $value);
                }
            );
        }

        return $query;
    }


    private function applyApiFilter($query, string $attribute, string $value)
    {
        $namespacedAttribute = $query->getModel()->getTable().'.'.$attribute;
        return $query->whereIn($namespacedAttribute, str_getcsv($value, ','));

    }

    protected function applyApiSort($query)
    {
        // TODO: Split out logic to make cleaner
        if (request()->has('sort')) {
            $sort = request()->get('sort');
            // Support sort[aroundDate]=2000-01-01 sort syntax
            if (is_array($sort)) {
                foreach ($sort as $column => $value) {
                    if (method_exists($query->getModel(), $scope = 'scope'.str_replace('-', '', ucfirst($column)))) {
                        call_user_func_array([$query, str_replace('-', '', $column)], [$value]);
                    }
                }
            }

            if (is_string($sort)) {
                $sort = explode(',', request()->get('sort'));
                foreach ($sort as $col) {
                    if (method_exists($query->getModel(), $scope = 'scope' . str_replace('-', '', ucfirst($col)))) {
                        call_user_func_array([$query, str_replace('-', '', $col),], []);
                    }
                    if (substr($col, 0, 1) === '-') {
                        $query->orderBy(substr($col, 1), 'DESC');
                    } else {
                        $query->orderBy($col, 'ASC');
                    }
                }
            }
        } else {
            $query->orderBy('id', 'ASC');
        }

        return $query;
    }

}