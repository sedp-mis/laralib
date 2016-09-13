<?php 

namespace SedpMis\Laralib\Query;

use Illuminate\Support\Facades\DB;
use SedpMis\Lib\Makeable\MakeableTrait;

class PossibleMatch 
{
    use MakeableTrait;

    protected $model;
    protected $field;

    public function __construct($model, $field)
    {
        $this->model = $model;
        $this->field = $field;
    }

    public function matches($search)
    {
        $search = $this->parseSearch($search);
        return $this->model->where($this->field, 'like', $search)->orderBy(DB::raw("length({$this->field})"));
    }

    protected function parseSearch($search)
    {
        return '%'.implode('%', str_split($search, 1)).'%';
    }
}
