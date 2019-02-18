<?php


namespace AutoTransformer\Traits;


trait Filter
{
    public function resolveQuery($query)
    {
        if (request()->has('filter')) {
            $query = $this->applyApiFilters($query);
        }

        return $query->get();
    }

    private function applyApiFilters($query)
    {
        $filters = request()->get('filter');

        if (is_array($filters)) {
            foreach ($filters as $column => $value) {
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

    private function applyApiFilter($query, string $attribute, string $value)
    {
        $namespacedAttribute = $query->getModel()->getTable().'.'.$attribute;
        return $query->whereIn($namespacedAttribute, str_getcsv($value, ','));

    }
}