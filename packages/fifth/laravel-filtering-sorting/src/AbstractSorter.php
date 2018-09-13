<?php

namespace App\Providers;


use Illuminate\Database\Eloquent\Builder as ElBuilder;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractSorter
{
    use SorterHelpers;

    private $dataManager;
    private $query;

    public function __construct(DataManager $dataManager)
    {
        $this->dataManager = $dataManager;
    }

    abstract protected function orders(): array;

    /**
     * @param Builder|ElBuilder $query
     * @return Builder|ElBuilder
     */
    public function handle($query)
    {
        $this->setQuery($query)->applyOrder();

        return $this->query;
    }

    protected function applyOrder(): self
    {
        foreach ((array) $this->dataManager->get('order_by', []) as $column => $direction) {

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

//    private function optimiseRules(): array
//    {
//        $rules = $this->getRules();
//
//        foreach ($this->joined as $table) {
//            $rules = array_merge($rules, $this->getOptimisedRulesByTable($table));
//        }
//
//        return $rules;
//    }

//    protected function getOptimisedRulesByTable(string $table): array
//    {
//        return $this->joinRulesMap()[$table] ?? [];
//    }

    protected function getOrders(): array
    {
        return $this->orders();
    }

    protected function joinRulesMap(): array
    {
        return [
            //
        ];
    }

    /**
     * @param Builder|ElBuilder $query
     * @return self
     */
    private function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }
}