<?php

/*
 * Query Helpers
 */
if (!function_exists('db_raw')) {
    /**
     * Return a db raw expression.
     *
     * @param  mixed $expression
     * @return \Illuminate\Database\Query\Expression
     */
    function db_raw($expression)
    {
        return \Illuminate\Support\Facades\DB::raw($expression);
    }
}

if (!function_exists('joiner')) {
    /**
     * Return a joiner instance.
     *
     * @param  mixed $query
     * @return mixed
     */
    function joiner($query)
    {
        return new \SedpMis\Laralib\Query\Joiner($query);
    }
}

if (!function_exists('drop_foreign_keys')) {
    /**
     * Drop foreign keys.
     *
     * @param  mixed $table
     * @param  array $foreignKeys
     * @return void
     */
    function drop_foreign_keys($table, array $foreignKeys)
    {
        $tableName = $table->getTable();
        foreach ($foreignKeys as $i => $key)
        {
            $table->dropForeign($tableName.'_'.$key.'_foreign');
        }
    }
}
