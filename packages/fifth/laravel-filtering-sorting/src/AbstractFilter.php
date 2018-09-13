<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder as ElBuilder;
use Illuminate\Database\Query\Builder;

abstract class AbstractFilter
{
    use FilterHelpers;

    public $dataManager;

    protected $query;

    protected $withOrders = true;

    protected $orderColumnMap = [];

    public function __construct(FilterDataManager $manager)
    {
        $this->dataManager = $manager;
    }

    /**
     * @param Builder|ElBuilder $query
     * @return Builder|ElBuilder
     */

    public function handle($query)
    {
        $this->setQuery($query)
            ->applyOrder()
            ->filterUsingRules();

        return $this->query;
    }

    protected function applyOrder(): self
    {
        if (!$this->withOrders) {
            return $this;
        }

        foreach ((array) $this->dataManager->order_by ?? [] as $column => $direction) {
            if (!array_key_exists($column, $this->orderColumnMap)) continue;

            $this->query->orderBy($this->orderColumnMap[$column], $direction);
        }

        return $this;
    }

    protected function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    protected function filterUsingRules(): self
    {
        $relations = [];

        foreach ($this->rules() as $requestKey => $options) {
            $this->filterApplier($this->query, $requestKey, $options, $relations);
        }

        $this->handleRelations($relations, $this->query);

        return $this;
    }

    protected function filterApplier($query, string $requestKey, array $options, &$relations): void
    {
        if (
            array_search($requestKey, $this->nullableKeys ?? []) === false ?
                !$this->dataManager->has($requestKey) :
                !$this->dataManager->exists($requestKey)
        ) {
            return;
        }

        $magicMethods = ['relation', 'date', 'search'];

        $method = $options['action'] ?? 'simple';

        if (!(in_array($method, $magicMethods) || method_exists($this, $method))) {
            throw new \Exception("Method '$method' not found");
        }

        $this->applyMethod($method, $requestKey, $options, $query, $relations);
    }

    private function applyMethod(string $method, string $requestKey, array $options, $query, array &$relations): void
    {
        switch ($method) {

            case 'relation':
                $this->collectRelations($requestKey, $options, $relations);
                break;

            case 'date':
                $this->dateFilter($query, $requestKey, $options);
                break;

            case 'search':
                $this->applySearch($query, $requestKey, $options);
                break;

            default :
                $this->standardFilter(
                    $query,
                    $requestKey,
                    $method,
                    $options['params'] ?? []
                );
        }
    }

    private function standardFilter($query, string $requestKey, string $method, array $params): void
    {
        call_user_func_array(
            [$this, $method],
            [$query, $requestKey, $params]
        );
    }

    private function collectRelations(string $requestKey, array $options, array &$relations): void
    {
        if (!key_exists('relationName', $options)) {
            throw new \Exception("RelationName is required on '$requestKey'");
        } elseif (!key_exists('rule', $options)) {
            throw new \Exception("Rule is required on '$requestKey'");
        }

        $relations[$options['relationName']][$requestKey] = $options['rule'];
    }

    protected function handleRelations(array $relations, $query): void
    {
        foreach ($relations as $relationName => $rules) {
            $query->whereHas($relationName, function ($relationQuery) use ($rules, $relationName, $relations) {
                $nestedRelations = [];

                foreach ($rules as $requestKey => $rule) {
                    $this->filterApplier($relationQuery, $requestKey, $rule, $nestedRelations);
                }

                $this->handleRelations($nestedRelations, $relationQuery);
            });
        }
    }

    private function simple($query, $requestKey, $params): void
    {
        if (!key_exists('column', $params)) {
            throw new \Exception("Column is required on '$requestKey'");
        }

        $arguments = key_exists('operator', $params)
            ? [$params['column'], $params['operator'], $this->dataManager->get($requestKey)]
            : [$params['column'], $this->dataManager->get($requestKey)];

        //@TODO clean code
        if (isset($params['queryMethod'])) {
            if (strcasecmp($params['queryMethod'], 'whereRaw') == 0) {
                $operator = $params['operator'] ?? '=';
                $arguments = [$params['column'] . $operator . "'" . $this->dataManager->get($requestKey) . "'"];
            } elseif (strcasecmp($params['queryMethod'], 'whereIn') == 0) {
                $arguments[1] = (array)$arguments[1]; //second item is values, so for whereIn we cast it to array (just in case)
            }
        }
        //END

        call_user_func_array(
            [$query, $params['queryMethod'] ?? 'where'],
            $arguments
        );
    }
}