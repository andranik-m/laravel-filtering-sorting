<?php

namespace App\Providers;


abstract class AbstractSorter
{
    use SmartOrders;

    public function __construct(Request $request)
    {
        $this->setRequest($request);
    }

    abstract protected function joinRulesMap(): array;
    abstract protected function orders(): array;

    protected function filterUsingRules(): parent
    {
        $relations = [];

        foreach ($this->optimiseRules() as  $requestKey => $options) {
            $this->filterApplier($this->query, $requestKey, $options, $relations);
        }

        $this->handleRelations($relations, $this->query);

        return $this;
    }

    protected function applyOrder(): parent
    {
        if (! $this->withOrders) {
            return $this;
        }

        foreach ((array) $this->request->order_by as $column => $direction) {

            if (! array_key_exists($column, ($orders = $this->getOrders()))) {
                continue;
            }

            if (! is_array($params = $orders[$column])) {
                $this->query->orderBy($params, $direction);

            } else {
                $this->{$params['action']}($params)
                    ->call(function ($builder) use ($params, $direction, $column) {

                        return is_callable($orderColumn = $this->getColumn($params, $column))
                            ? $orderColumn($builder, $direction) : $builder->orderBy($orderColumn, $direction);
                    });

            }

        }

        return $this;
    }

    private function optimiseRules(): array
    {
        $rules = $this->getRules();

        foreach ($this->joined as $table) {
            $rules = array_merge($rules, $this->getOptimisedRulesByTable($table));
        }

        return $rules;
    }

    protected function getOptimisedRulesByTable(string $table): array
    {
        return $this->joinRulesMap()[$table] ?? [];
    }

    protected function getOrders(): array
    {
        return $this->orders();
    }

    protected function getRules(): array
    {
        return $this->rules();
    }
}