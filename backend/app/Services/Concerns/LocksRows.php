<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait LocksRows
{
    protected function lock(Builder $query): Builder
    {
        $driver = $query->getModel()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $query;
        }

        return $query->lockForUpdate();
    }
}
