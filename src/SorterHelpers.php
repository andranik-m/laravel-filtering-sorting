<?php

namespace FifthLLC\LaravelFilteringSorting;


use Illuminate\Database\Eloquent\Builder;

trait SorterHelpers
{
    protected $joined = [];
    private $initialTable;

    protected function applyJoin(array $params): Builder
    {
        if (! in_array($params['table'], $this->joined)) {
            $this->joined[] = $params['table'];

            return $this->query->{$params['method']}($params['table'], $this->getFirst($params), '=', $this->getSecond($params));
        }

        return $this->query;
    }

    protected function applyTranslationsJoin(array $params): Builder
    {
        if (! in_array($params['table'], $this->joined)) {
            $this->joined[] = $params['table'];

            return  $this->query->joinTranslations($params['model'], $params['first']);
        }

        return $this->query;
    }

    private function getFirst(array $params) : string
    {
        return $params['first'] ?? $this->getInitialTable() . '.' . $this->getForeignKey($params['table']);
    }

    private function getSecond(array $params) : string
    {
        return $params['second'] ?? $params['table'] . '.' . 'id';
    }

    /**
     * Returns given column for ordering (it can be callback, or just a string) or request key
     *
     * @param  array  $params
     * @param  string $requestKey
     *
     * @return string | callable
     */

    private function getColumn(array $params, string $requestKey)
    {
        return $params['column'] ?? $params['table'] . '.' . $requestKey;
    }

    /**
     * Form join params for ordering
     *  orders a single column, or by callback
     *
     * @param  string  $table
     * @param  string | callable  $column
     * @param  null | string  $first = null
     * @param  null | string  $second = null
     * @param  null | string  $method = 'join'
     * @param  null | string  $action = 'applyJoin'
     *
     * @return array
     */
    protected function join(
        string  $table,
        $column = null,
        ?string $first = null,
        ?string $second = null,
        ?string $method = 'join',
        string  $action = 'applyJoin'
    ): array
    {
        return [
            'column' => $column,
            'table'  => $table,
            'first'  => $first,
            'second' => $second,
            'method' => $method,
            'action' => $action
        ];
    }

    /**
     * Form join params for ordering
     *  orders a single column, or by callback
     *
     * @param  string  $table
     * @param  string | callable  $column
     * @param  null | string  $first = null
     * @param  null | string  $second = null
     *
     * @return array
     */
    protected function leftJoin(string $table, $column = null, ?string $first = null, ?string $second = null): array
    {
        return $this->join($table, $column, $first, $second, 'leftJoin');
    }

    /**
     * Form join params for ordering
     *  orders a single column, or by callback
     *
     * @param  string  $table
     * @param  string | callable  $column
     * @param  null | string  $first = null
     * @param  null | string  $second = null
     *
     * @return array
     */
    protected function rightJoin(string $table, $column = null, ?string $first = null, ?string $second = null): array
    {
        return $this->join($table, $column, $first, $second, 'rightJoin');
    }

    protected function translationsJoin(string $column = 'text', string $model = null, string $first = null): array
    {
        return [
            'model'  => $model ?? ($model = $this->getModelByTable($this->getInitialTable())),
            'table'  => $model . '_translations',
            'column' => $model . '_translations.' . $column,
            'first'  => $first,
            'action' => 'applyTranslationsJoin'
        ];
    }

    private function getForeignKey(string $table): string
    {
        return $this->getModelByTable($table) . '_id';
    }

    private function getModelByTable(string $table): string
    {
        return substr($table, 0, -1);
    }

    private function getInitialTable(): string
    {
        return $this->initialTable ?? $this->initialTable = $this->query->getModel()->getTable();
    }
}