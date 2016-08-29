<?php

namespace SedpMis\Laralib\Models;

use SedpMis\Lib\Breakdown\Breakdown;

/**
 * Duration model with base unit in years. This is a value object that represents duration of time 
 * which is useful for example like measuring membership length, length of stay, etc.
 */
class Duration extends BaseModel
{
    protected $fillable = ['in_years'];

    protected $attributes = [
        'in_years' => 0
    ];

    protected $formatLabels = [
        'year'  => '%y',
        'month' => '%m',
        'day'   => '%d',
    ];

    public function inYears()
    {
        return $this->in_years;
    }

    public function inMonths()
    {
        return $this->inYears() * 12;
    }

    public function inDays()
    {
        return $this->inMonths() * 30;
    }

    public function breakdown()
    {
        $breakdown = Breakdown::make([
            'year'  => 360,
            'month' => 30,
            'day'   => 1
        ])
        ->breakdown($this->inDays());

        $this->attributes = array_merge($this->attributes, $breakdown);

        return $this;
    }

    public function format($format = '%y year(s), %m month(s), %d day(s)')
    {
        if (!array_key_exists('year', $this->attributes)) {
            $this->breakdown();
        }

        foreach ($this->formatLabels as $attribute => $label) {
            $format = str_replace($label, $this->{$attribute} ?: 0, $format);
        }

        return $format;
    }

    public function __toString()
    {
        return $this->format();
    }
}