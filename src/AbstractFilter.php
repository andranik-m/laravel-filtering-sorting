<?php

namespace ASD;

use Illuminate\Database\Eloquent\Builder as ElBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

//@TODO function accessibility
abstract class AbstractFilter
{
    use FilterHelpers;

    public $dataManager;

    protected $query;

    private $joined = [];
    protected $nullableKeys = [];

    abstract protected function rules(): array;

    /**
     * @param DataManager|Request $manager
     */
    public function __construct($manager)
    {
        $this->dataManager = $manager;
    }

    /**
     * @param Builder|ElBuilder $query
     * @return Builder|ElBuilder
     */
    public function handle($query)
    {
        $this->setQuery($query)->filterUsingRules();

        return $this->query;
    }

    /**
     * @param Builder|ElBuilder $query
     * @return AbstractFilter
     */
    protected function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    protected function optimiseRules(): array
    {
        $rules = $this->rules();

        foreach ($this->joined as $table) {
            $rules = array_merge($rules, $this->getOptimisedRulesByTable($table));
        }

        return $rules;
    }

    protected function getOptimisedRulesByTable(string $table): array
    {
        return $this->joinRulesMap()[$table] ?? [];
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
        if ($this->shouldFilter($requestKey)) {
            return;
        }

        $magicMethods = ['relation', 'date', 'search'];

        $method = $options['action'] ?? 'simple';

        if (!(in_array($method, $magicMethods) || method_exists($this, $method))) {
            throwException(new \Exception("Method '$method' not found"));
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
            throwException(new \Exception("RelationName is required on '$requestKey'"));
        } elseif (!key_exists('rule', $options)) {
            throwException(new \Exception("Rule is required on '$requestKey'"));
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
            throwException(new \Exception("Column is required on '$requestKey'"));
        }

        call_user_func_array(
            [$query, $this->getQueryMethod($params)],
            $this->getArguments($requestKey, $params)
        );
    }

    private function shouldFilter(string $requestKey): bool
    {
        return array_search($requestKey, $this->nullableKeys) === false ?
            !(is_null($this->dataManager->get($requestKey))) :
            !$this->dataManager->has($requestKey);
    }

    protected function getArguments(string $requestKey, array $params)
    {
        $operator = $params['operator'] ?? '=';

        switch ($this->getQueryMethod($params)) {

            case 'whereIn':
                return (array) $this->dataManager->get($requestKey, []);

            case 'whereRaw':
                return "{$params['column']} {$operator} '{$this->dataManager->get($requestKey)}'";

            default :
                return [$params['column'], $operator, $this->dataManager->get($requestKey)];
        }
    }

    protected function getQueryMethod(array $params): string
    {
        return $params['queryMethod'] ?? 'where';
    }

    protected function joinRulesMap(): array
    {
        return [
            //
        ];
    }
}