<?php

namespace SedpMis\Laralib;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use SedpMis\Laralib\Models\NullModel;
use SedpMis\Laralib\Models\NullObject;
use Closure;

class Collection extends EloquentCollection
{
    protected $attributeFilters = [];

    /**
     * Add many items.
     *
     * @param array|static $items
     */
    public function addMany($items)
    {
        if ($items instanceof static) {
            $items = $items->all();
        }

        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /**
     * Pluck items from nested collection.
     *
     * @param  string $nestedKey
     * @return array|static
     */
    public function pluckNested($nestedKey)
    {
        $keys = explode('.', $nestedKey);

        $items = $this;

        foreach ($keys as $key) {
            if (is_array($items)) {
                $items = collection($items);
            }

            $items = $items->pluck($key);

            if (is_array(head($items)) || head($items) instanceof static) {
                $items = static::make($items)->collapse();
            }
        }

        return $items;
    }

    /**
     * Alias of pluckNested().
     *
     * @param  string $nestedKey
     * @return array|static
     */
    public function nestedPluck($nestedKey)
    {
        return $this->nestedPluck($nestedKey);
    }

    /**
     * Pluck items from collection.
     *
     * @param  string $field
     * @return array
     */
    public function pluck($field)
    {
        return array_pluck($this->items, $field);
    }

    /**
     * Pluck unique items from collection.
     *
     * @param  string $field
     * @return array
     */
    public function pluckUnique($field)
    {
        return array_unique(array_pluck($this->items, $field));
    }

    /**
     * Group the items using array of callbacks.
     *
     * @param array
     * @return array
     */
    public function group(array $callbacks)
    {
        $groups = [];
        foreach ($callbacks as $groupName => $callback) {
            $groups[$groupName] = array_filter($this->items, $callback);
        }

        return $groups;
    }

    /**
     * Separate the items into two groups using callback.
     *
     * @param  callable  $callback
     * @param  int $group1Key
     * @param  int $group2Key
     * @return array
     */
    public function separate($callback, $group1Key = 0, $group2Key = 1)
    {
        $groups = [
            $group1Key => [],
            $group2Key => [],
        ];

        foreach ($this->items as $index => $item) {
            if (call_user_func_array($callback, [$item, $index])) {
                $groups[$group1Key][$index] = $item;
            } else {
                $groups[$group2Key][$index] = $item;
            }
        }

        return $groups;
    }

    /**
     * Remove set filters.
     *
     * @return $this
     */
    public function resetFilter()
    {
        $this->attributeFilters = [];

        return $this;
    }

    /**
     * Set attribute filters
     * TODO filterOr|filterWhereOr, saved in $this->attributeOrFilters then check it first prior to $this->attributeFilters.
     *
     * @param  array|string  $attributeFilters Array of attribute filters, or String for single attribute filter
     * @param  mixed $valueFilter Value to filter, if first parameter is string attribute
     * @return $this
     */
    public function filterWhere($attributeFilters, $valueFilter = null)
    {
        if (is_array($attributeFilters)) {
            $this->attributeFilters = array_merge($this->attributeFilters, $attributeFilters);
        } elseif (is_string($attributeFilters)) {
            $this->attributeFilters[$attributeFilters] = $valueFilter;
        }

        return $this;
    }

    /**
     * Get filtered items based from attribute filters set in filterWhere() method.
     *
     * @param bool $useStrict If using strict equals
     * @return static
     */
    public function getFiltered($useStrict = false)
    {
        return $this->filter(function ($item) use ($useStrict) {
            $result = true;

            foreach ($this->attributeFilters as $attribute => $value) {
                $result = $result && (($useStrict) ? ($item[$attribute] === $value) : ($item[$attribute] == $value));
            }

            return $result;
        });
    }

    /**
     * Where condition for data.
     *
     * @param  array|string $field    The fields or field to test.
     * @param  mixed        $value    Value to test.
     * @param  string       $operator Operand, please refer to the compare method
     * @return array
     */
    public function where($field, $value, $operator = '==')
    {
        return $this->filter(function ($item) use ($field, $value, $operator) {
            // If type of field is array
            if (is_array($field)) {
                $fieldExists = true;
                $comparison = false;

                foreach ($field as $key => $f) {
                    if (!array_key_exists($f, $item)) {
                        $fieldExists = false;
                    }

                    $compareResult = $this->compare($item[$f], $value, $operator);

                    if ($compareResult) {
                        $comparison = true;
                    }
                }

                if (!$fieldExists || !$comparison) {
                    return false;
                }

                return true;
            }

            // If type of field is string
            if (is_array($item) && !array_key_exists($field, $item)) {
                return false;
            }

            return $this->compare($item[$field], $value, $operator);
        });
    }

    public function whereIn($field, array $values)
    {
        return $this->filter(function ($item) use ($field, $values) {
            return in_array($item[$field], $values);
        });
    }

    /**
     * @deprecated Use first() insted
     */
    public function firstItem()
    {
        $items = $this->items;

        if (empty($items)) {
            return;
        }

        return (object) array_shift($items);
    }

    /**
     * @deprecated Use first() insted
     */
    public function firstObject()
    {
        $items = $this->items;

        if (empty($items)) {
            return;
        }

        return (object) array_shift($items);
    }

    /**
     * Allows to sum field(s) or attribute(s) of items or models in a collection.
     *
     * @param  string|array $field
     * @return mixed
     */
    public function sumOf($field)
    {
        if (is_string($field)) {
            return array_sum(array_pluck($this->items, $field));
        }

        $fields = $field;
        $sum    = 0;

        foreach ($fields as $field) {
            $sum += array_sum(array_pluck($this->items, $field));
        }

        return $sum;
    }

    /**
     * Override. Allows to sum field(s) or attribute(s) of items or models in a collection.
     *
     * @param  string|array|callable $field
     * @return mixed
     */
    public function sum($field)
    {
        if (is_string($field) || is_array($field)) {
            return $this->sumOf($field);
        }

        return parent::sum($field); // as callback
    }

    /**
     * Get the first item and remove from the collection.
     *
     * @param  callable   $callback
     * @param  mixed      $default
     * @return mixed|null
     */
    public function takeFirst(callable $callback = null, $default = null)
    {
        return array_take_first($this->items, $callback, $default);
    }

    /**
     * Sort array the order clause.
     *
     * @param  string $orderByClause The clause or condition of ordering the collection.
     *                               i.e.  'center_name DESC, full_name ASC'
     *                                     'center_name, full_name'
     *                         Default order is ASC.
     * @return object Collection
     */
    public function orderBy($orderByClause)
    {
        if (!$orderByClause) {
            throw new \Exception('Order By parameter is required.');
        }

        if (empty($this->items)) {
            return $this;
        }

        $sortFields = $this->orderByFields($orderByClause);

        $sortedArray = [];

        $sortFieldsSize = count($sortFields);

        $fields = $this->getFieldsOnly($sortFields);

        foreach ($this->items as $k => $v) {
            foreach ($fields as $field) {
                $sortedArray[$field][$k] = $v[$field];
            }
        }

        call_user_func_array('array_multisort', $this->getParams($sortedArray, $sortFields));

        return $this;
    }

    /**
     * Get the formatted parameters of array_multisort function that will be used in call_user_func_array.
     *
     * @param  array  $sortedArray  The sorted array parameter of array_multisort.
     * @param  array  $sortFields   Field names and its corresponding sort order.
     *
     * @return array  $params       Pre-formatted array of array_multisort function
     */
    private function getParams($sortedArray, $sortFields)
    {
        $params = [];

        $index = 0;

        foreach ($sortFields as $sortField) {
            if ($index % 2 === 0) {
                $params[] = $sortedArray[$sortField];
            } else {
                $params[] = $sortField;
            }

            $index++;
        }

        $params[] = &$this->items;

        return $params;
    }

    /**
     * Get only the fields in the sortFields array.
     *
     * @param  array   $sortFields Field names and its corresponding sort order.
     * @param  int $fieldIndex Field index of the field.  Starting 1.
     *
     * @return array
     */
    private function getFieldsOnly($sortFields, $fieldIndex = 2)
    {
        $sortFieldsSize = count($sortFields);

        $fields = [];

        for ($i = 0; $i < $sortFieldsSize; $i += $fieldIndex) {
            $fieldName = $sortFields[$i];
            $fields[]  = $fieldName;
        }

        return $fields;
    }

    /**
     * Create and formats order by clause.
     *
     * @param  string $orderByClause The clause or condition of ordering the collection.
     * @return array
     */
    private function orderByFields($orderByClause)
    {
        if (!$orderByClause) {
            return [];
        }

        $orderByClause = explode(',', $orderByClause);

        return $this->createSortFields($orderByClause);
    }

    /**
     * Remove spaces on the the orderBy fields.
     *
     * @param  array $orderByFields Formatted array of orderByClause.
     * @return array
     */
    private function cleanOrderByFields($orderByFields)
    {
        $cleanFields = [];

        foreach ($orderByFields as $field) {
            $cleanFields[] = trim($field);
        }

        return $cleanFields;
    }

    /**
     * Create the pre-formatted array_multisort parameters.
     *
     * @param  array $orderByFields Formatted array of orderByClause.
     * @return array
     */
    private function createSortFields($orderByFields)
    {
        $cleanFields = $this->cleanOrderByFields($orderByFields);

        $sortFields = [];

        foreach ($cleanFields as $field) {
            $sortFields = array_merge($sortFields, $this->sortCondition($field));
        }

        return $sortFields;
    }

    /**
     * Formats sort condition of the field based on input to be used in array_multisort.
     *
     * @param  string $field The fieldname or sort order.
     * @return array
     */
    private function sortCondition($field)
    {
        $sortFields = [];

        $sortConditions = explode(' ', $field);

        $sortConditionSize = count($sortConditions);

        if ($sortConditionSize >= 3) {
            throw new \Exception('Excessive space between field and operand in order by clause');
        }

        foreach ($sortConditions as $sortCondition) {
            if ($sortConditionSize === 2 && empty($sortCondition)) {
                $sortCondition = SORT_ASC;
            }

            if (strtoupper($sortCondition) === 'ASC') {
                $sortCondition = SORT_ASC;
            }

            if (strtoupper($sortCondition) === 'DESC') {
                $sortCondition = SORT_DESC;
            }

            array_push($sortFields, $sortCondition);
        }

        if ($sortConditionSize === 1) {
            array_push($sortFields, SORT_ASC);
        }

        return $sortFields;
    }

    /**
     * Diff array or models bu their attributes.
     *
     * @param  array  $attributes   Attributes to compare
     * @param  array|static  $compareItems Items or models to compare
     * @param  callable $callback     Callback to make the returned diff items
     * @return static
     */
    public function diffByAttributes(array $attributes, $compareItems, callable $callback = null)
    {
        $diff = new static();

        $compareItemsAttributes = [];

        foreach ($compareItems as $item) {
            $compareItemsAttributes[] = $this->diffAttributesString($item, $attributes);
        }

        foreach ($this->items as $i => $item) {
            if (!in_array($this->diffAttributesString($item, $attributes), $compareItemsAttributes)) {
                $diff->add(is_null($callback) ? $item : $callback($item, $i));
            }
        }

        return $diff;
    }

    /**
     * Create a string concatenated of item's attributes used in diffByAttributes.
     *
     * @param  array|Eloquent $item       Array of data model
     * @param  array $attributes Array of attributes
     * @return string
     */
    private function diffAttributesString($item, array $attributes)
    {
        $attributesString = '';

        foreach ($attributes as $attribute) {
            $attributesString .= $item[$attribute].'_';
        }

        return $attributesString;
    }

    /**
     * Insert Record.
     *
     * @param  array  $arr     The array to be inserted
     * @param  string $keyName Required if the value of $arr is not array.
     */
    public function insert(array $arr, $keyName = '')
    {
        foreach ($this->items as $key => $value) {
            if (is_array($arr[$key])) {
                $this->items[$key] = array_merge($arr[$key], $value);
            } else {
                if ($keyName) {
                    $this->items[$key] = array_merge([$keyName => $arr[$key]], $value);
                } else {
                    throw new \Exception('Key name is required.');
                }
            }
        }

        return $this;
    }

    /**
     * Make a copy of the collection.
     *
     * @return static
     */
    public function copy()
    {
        return $this->make($this->items);
    }

    /**
     * Set Field Value with Condtion.
     *
     * @param string  $fieldToChange          Field name to be changed
     * @param mixed   $fieldNewValue          New Value of the field to be changed
     * @param string  $fieldConditionValue    The value of the field use in comparison
     * @param string  $fieldCondition         Field name of the condition
     * @param string  $fieldConditionOpertor  The value of the field use in conditional comparison
     * @param bool $replaceOnce            Set true to replace only the first occurence.
     */
    public function setWhere($fieldToChange, $fieldNewValue, $fieldConditionValue, $fieldCondition = 'id', $fieldConditionOperator = '===', $replaceOnce = true)
    {
        return $this->setWhereHead($fieldToChange, $fieldNewValue, $fieldConditionValue, $fieldCondition, $fieldConditionOperator, $replaceOnce, false);
    }

    /**
     * Set Field value with new one adding the its existing value, use this only for numeric field.
     *
     * @param string  $fieldToChange          Field name to be changed
     * @param mixed   $fieldNewValue          New Value of the field to be changed
     * @param string  $fieldConditionValue    The value of the field use in comparison
     * @param string  $fieldCondition         Field name of the condition
     * @param string  $fieldConditionOpertor  The value of the field use in conditional comparison
     * @param bool $replaceOnce            Set true to replace only the first occurence.
     * @param bool $add                    Set true to add the current and the new value
     */
    public function setWhereAdd($fieldToChange, $fieldNewValue, $fieldConditionValue, $fieldCondition = 'id', $fieldConditionOperator = '===', $replaceOnce = true, $add = false)
    {
        return $this->setWhereHead($fieldToChange, $fieldNewValue, $fieldConditionValue, $fieldCondition, $fieldConditionOperator, $replaceOnce, true);
    }

    /**
     * Head method of the setWhere and setWhereAdd.
     *
     * @param string  $fieldToChange          Field name to be changed
     * @param mixed   $fieldNewValue          New Value of the field to be changed
     * @param string  $fieldConditionValue    The value of the field use in comparison
     * @param string  $fieldCondition         Field name of the condition
     * @param string  $fieldConditionOpertor  The value of the field use in conditional comparison
     * @param bool $replaceOnce            Set true to replace only the first occurence.
     * @param bool $add                    Set true to add the current and the new value
     */
    private function setWhereHead($fieldToChange, $fieldNewValue, $fieldConditionValue, $fieldCondition = 'id', $fieldConditionOperator = '===', $replaceOnce = true, $add = false)
    {
        foreach ($this->items as $k => &$a) {
            if ($this->compare($a[$fieldCondition], $fieldConditionValue)) {
                if ($add) {
                    array_set($a, $fieldToChange, $a[$fieldToChange] + $fieldNewValue);
                } else {
                    array_set($a, $fieldToChange, $fieldNewValue);
                }

                if ($replaceOnce) {
                    return $this;
                }
            }
        }

        return $this;
    }

    /**
     * Set Field Value with Condtion.
     *
     * @param string  $fieldToChange       Field name to be changed
     * @param mixed  $fieldNewValue       New Value of the field to be changed
     * @param bool $replaceOnce         Set true to replace only the first occurence.
     */
    public function set($fieldToChange, $fieldNewValue, $replaceOnce = true)
    {
        foreach ($this->items as $k => &$a) {
            array_set($a, $fieldToChange, $fieldNewValue);

            if ($replaceOnce) {
                return $this;
            }
        }

        return $this;
    }

    /**
     * Compare 2 values based on operator passed.
     *
     * @param  mixed $value1   First value to be compared
     * @param  mixed $value2   Second value to be compared
     * @param  string $operator Comparison Operator
     * @return bool
     */
    private function compare($value1, $value2, $operator = '==')
    {
        switch ($operator) {
            case '==':
                return $value1 == $value2;
            case '===':
                return $value1 === $value2;
            case '!=':
                return $value1 != $value2;
            case '!==':
                return $value1 !== $value2;
            case '<':
                return $value1 < $value2;
            case '>':
                return $value1 > $value2;
            case '<=':
                return $value1 <= $value2;
            case '>=':
                return $value1 >= $value2;
            default:
                return $value1 == $value2;
        }
    }

    /**
     * Filter by where not in.
     *
     * @param  string $field
     * @param  array  $values
     * @return static
     */
    public function whereNotIn($field, array $values)
    {
        return $this->filter(function ($item) use ($field, $values) {
            return !in_array($item[$field], $values);
        });
    }

    /**
     * Return the first item that will pass the predicate, or a null object instead.
     *
     * @param  \Closure|null $callback
     * @return mixed|\NullObject
     */
    public function firstOrNullObject(Closure $callback = null)
    {
        return $this->first($callback) ?: new NullObject;
    }

    /**
     * Return the first item that will pass the predicate, or a null model instead.
     *
     * @param  \Closure|null $callback
     * @return mixed|\NullModel
     */
    public function firstOrNullModel(Closure $callback = null)
    {
        return $this->first($callback) ?: new NullModel;
    }
}
