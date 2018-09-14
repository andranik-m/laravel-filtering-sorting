<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait FilterHelpers
{
    //@TODO db raw date
    protected function dateFilter(Builder $query, string $requestKey, array $options): void
    {
        if (!key_exists('operator', $options)){
            throwException(new \Exception("Operator is required on '$requestKey'"));
        } elseif (!key_exists('column', $options)){
            throwException(new \Exception("Column is required on '$requestKey'"));
        }

        try {
            $date = Carbon::parse($this->dataManager->get($requestKey));
        } catch (\Exception $e) {
            throwException(new \Exception('Invalid parameter for date filter'));
        }

        if ($options['endOfDay'] ?? false) $date->endOfDay();

        $query->where($options['column'], $options['operator'], $this->modifyDate($date));
    }

    protected function modifyDate(Carbon $date): Carbon
    {
        return $date;
    }

    protected function dateParams(string $column, string $operator = '>=', bool $endOfDay = false) : array
    {
        return [
            'action'    => 'date',
            'operator'  => $operator,
            'column'    => $column,
            'endOfDay'  => $endOfDay
        ];
    }

    protected function from(string $column = 'created_at') : array
    {
        return $this->dateParams($column);
    }

    protected function to(string $column = 'created_at') : array
    {
        return $this->dateParams($column, '<=', true);
    }

    protected function relationFilter(string $relation, $column, $queryMethod = 'whereIn') : array // column doesn't have type because it can be array or string
    {
        $relationHierarchy = explode('.', $relation);

        $output = [
            'action'        => 'relation',
            'relationName'  => array_shift($relationHierarchy),
            'rule'          => $this->getRule($relationHierarchy, $column, $queryMethod)
        ];

        return $output;
    }

    protected function getRule(array &$remainingRelations, $column, $queryMethod = 'whereIn') : array
    {
        if (empty($remainingRelations)) {
            return $this->getRelationParams($column, $queryMethod);
        }

        return $this->relationFilter(implode('.', $remainingRelations), $column, $queryMethod);
    }

    private function getRelationParams($column, string $queryMethod = 'whereIn') : array
    {
        return is_array($column) ? $column : [
            'params' => [
                'column'        => $column,
                'queryMethod'   => $queryMethod
            ]
        ];
    }

    //@TODO simple
    protected function params(string $column, string $queryMethod = 'whereIn'): array
    {
        return [
            'params' => [
                'column' => $column,
                'queryMethod' => $queryMethod
            ]
        ];
    }

    private function applySearch(Builder $query, string $requestKey, array $options)
    {
        $query->where(function (Builder $query) use ($requestKey, $options) {
            foreach ((array) $this->dataManager->get($requestKey) as $search) {
                foreach ($options['searchIn'] as $searchableColumn) {
                    $query->orWhere($searchableColumn, 'like', $search . '%'); // @TODO percents
                }
            }
        });
    }

    // #TODO search
    protected function searchParams($searchIn): array
    {
        return [
            'action'    => 'search',
            'searchIn'  => is_array($searchIn) ? $searchIn : func_get_args()
        ];
    }
}