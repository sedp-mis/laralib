<?php

namespace SedpMis\Laralib\Models;

class BaseBranchModel extends BaseModel
{
    /**
     * Incrementing property, should be false for branch models.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * For branch-inserts, tail zero digits to be concatenated to the prefix.
     *
     * @var string
     */
    public $tail;

    /**
     * Return the next insert id.
     *
     * @throws \Exception
     * @return int|mixed
     */
    public function nextInsertId()
    {
        $this->validateBranchInsert();

        $primaryPrefix = $this->primaryKeyPrefix();

        $maxId = static::where($keyName = $this->getKeyName(), 'like', "{$primaryPrefix}%")
            ->where(DB::raw("char_length({$this->getKeyName()})"), '=', $this->pkCharLength())
            ->max($keyName);
        $maxId = $maxId ?: $primaryPrefix.$this->tail;

        return ++$maxId;
    }

    /**
     * The char_length to be check when retrieving the max value of primary key.
     *
     * @return int|string
     */
    public function pkCharLength()
    {
        return strlen($this->tail) + strlen($this->primaryKeyPrefix());
    }

    /**
     * Validate branch insert on model.
     *
     * @throws \Exception
     * @return void
     */
    public function validateBranchInsert()
    {
        if (is_null($this->tail)) {
            throw new \Exception('Property $tail is not set in branch model '.get_class($this));
        }

        if ($this->incrementing) {
            throw new \Exception("{$this->getClass()} incrementing property should be false.");
        }

        if (empty($this->primaryKeyPrefix())) {
            throw new \Exception("Performing branch insert on model {$this->getClass()} must have a primaryKeyPrefix e.g. branch_id.");
        }
    }

    /**
     * Set the id of model.
     *
     * @param int $id
     * @return $this
     */
    public function setIdAttribute($id)
    {
        $this->attributes = array_merge(['id' => $id], $this->attributes);

        return $this;
    }

    /**
     * Override. Save the model to the database. Use branch-insert for inserts.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        if ($this->id === null) {
            $this->id = $this->nextInsertId();
        }

        return parent::save($options);
    }
}
