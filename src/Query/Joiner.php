<?php

namespace SedpMis\Laralib\Query;

use Illuminate\Support\Facades\DB;

class Joiner
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public static function make($query)
    {
        return new static($query);
    }

    public function join($selectSql, $onSql, $joinType = 'join')
    {
        return $this->query->{$joinType}(
            DB::raw($selectSql),
            DB::raw(''),
            DB::raw($onSql),
            DB::raw('')
        );
    }

    public function leftJoin($selectSql, $onSql)
    {
        return $this->query->leftJoin(
            DB::raw($selectSql),
            DB::raw(''),
            DB::raw($onSql),
            DB::raw('')
        );
    }
}
