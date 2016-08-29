<?php

namespace SedpMis\Laralib\Models;

trait DefaultBranchModelTrait
{
    /**
     * The branchId prefix for branch insert.
     *
     * @var int
     */
    public $branchId;

    /**
     * Primary key prefix for retrieving next_insert_id.
     *
     * @return int|mixed
     */
    public function primaryKeyPrefix()
    {
        return $this->branchId;
    }

    /**
     * Setter for branchId.
     *
     * @param int|mixed $branchId
     * @return $this
     */
    public function setBranchId($branchId)
    {
        $this->branchId = (int) $branchId;

        return $this;
    }

    /**
     * Getter for branchId.
     *
     * @return int|mixed
     */
    public function getBranchId()
    {
        return $this->branchId;
    }
}
