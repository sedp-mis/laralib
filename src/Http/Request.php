<?php

namespace SedpMis\Laralib\Http;

use Illuminate\Http\Request as IlluminateRequest;

class Request extends IlluminateRequest
{
    /**
     * Retrieve an input item from the request, or else fail when empty.
     *
     * @param  string  $key
     * @return mixed
     */
    public function inputOrFail($key = null)
    {
        return $this->input($input) ?: App::abort(404);
    }
}
