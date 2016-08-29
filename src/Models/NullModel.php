<?php

namespace SedpMis\Laralib\Models;

/**
 * A null model object
 */
class NullModel extends BaseModel
{
    public $incrementing = null;
    
    public $timestamps = null;

    public $exists = null;

    public function __get($key)
    {
        return new static;
    }

    public function toArray()
    {
        return null;
    }
}
