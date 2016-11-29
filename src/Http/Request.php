<?php

namespace SedpMis\Laralib\Http;

use Illuminate\Support\Facades\App;

/**
 * Decorator for request object, since extending to the request class is not working.
 */
class Request
{
    /**
     * Retrieve an input item from the request, or else fail when empty.
     *
     * @param  string  $key
     * @return mixed
     */
    public function inputOrFail($key = null)
    {
        return App::make('request')->input($key) ?: App::abort(400, "Missing request key `{$key}`.");
    }

    /**
     * Call original request methods.
     *
     * @param  mixed $method
     * @param  mixed $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([App::make('request'), $method], $parameters);
    }
}
