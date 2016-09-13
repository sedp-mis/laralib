<?php

namespace SedpMis\Laralib\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;

class ModelNotFoundException extends EloquentModelNotFoundException
{
    /**
     * Set the affected Eloquent model.
     *
     * @param  string $model
     * @param  mixed $id
     * @return $this
     */
    public function setModel($model, $id = null)
    {
        if (is_null($id)) {
            return parent::setModel();
        }

        $this->model = $model;

        $this->message = "{$model} model not found, id = {$id}.";

        return $this;
    }
}
