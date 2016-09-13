<?php

/*
 * Collection Helpers
 */
if (!function_exists('collection')) {
    /**
     * Create items into new collection.
     *
     * @param  array|mixed $items
     * @return \SedpMis\Laralib\Collection
     */
    function collection($items = null)
    {
        return \SedpMis\Laralib\Collection::make($items);
    }
}
if (!function_exists('is_collection')) {
    /**
     * Determine if is instance of collection.
     *
     * @param  mixed  $var
     * @return boolean
     */
    function is_collection($var)
    {
        return $var instanceof \Illuminate\Support\Collection;
    }
}
