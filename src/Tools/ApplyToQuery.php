<?php

namespace Mateffy\LaravelCodebaseMcp\Tools;

use Closure;
use Mateffy\Introspect\Query\Query;

trait ApplyToQuery
{
    protected function applyOr(Query $query, array $values, Closure $apply): Query
    {
        if (count($values) === 0) {
            return $query;
        }

        return $query->or(function (Query $query) use ($values, $apply) {
            foreach ($values as $value) {
                $apply($query, $value);
            }

            return $query;
        });
    }

    protected function applyAnd(Query $query, array $values, Closure $apply): Query
    {
        if (count($values) === 0) {
            return $query;
        }

        foreach ($values as $value) {
            $apply($query, $value);
        }

        return $query;
    }
}
