<?php

namespace SedpMis\Laralib\Models;


use Illuminate\Database\Eloquent\Model as EloquentModel;
use SedpMis\Lib\Transformer\Transformer;
use Carbon\Carbon;

class BaseModel extends EloquentModel
{
    /**
     * Attributes to be hidden.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Attributes data typecasting or transformation.
     *
     * @var array
     */
    protected $typecasts = [];

    /**
     * Trashed attributes when clean() method is called.
     *
     * @var array
     */
    protected $trashedAttributes = [];

    /**
     * Array of relations which can be converted to nullModel.
     *
     * @var array
     */
    protected $toNullModelRelations = [];

    /**
     * Delimiter use to separate the getter and setter typecasts.
     *
     * @var  string
     */
    const TYPECASTS_SET_SEPARATOR = ',';

    /**
     * Delimiter use to separate typecasts rules.
     *
     * @string
     */
    const TYPECASTS_SEPARATOR = '|';

    /**
     * Override. Get a relationship value from a method.
     *
     * @param  string  $key
     * @param  string  $camelKey
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($key, $camelKey)
    {
        $relation = parent::getRelationshipFromMethod($key, $camelKey);
        
        if (is_null($relation) && in_array($camelKey, $this->toNullModelRelations)) {
            return $this->relations[$key] = new NullModel;
        }

        return $relation;
    }

    /**
     * Instantiate or make new instance via static method.
     *
     * @param array $attributes Model attributes
     * @return $instance
     */
    public static function make(array $attributes = array())
    {
        return (new ReflectionClass(get_called_class()))->newInstanceArgs(func_get_args());
    }

    /**
     * Returns primary key of the model.
     *
     * @return int|mixed Primary key
     */
    public function key()
    {
        return $this->getKey();
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @override
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        if ($this->isInTypecasts($key)) {
            $value = (new Transformer)->transform($this->setterTypecasts($key), $value);
        }

        $this->setAttribute($key, $value);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @override
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = $this->getAttribute($key);

        // Typecast attribute when exist in $typecasts property
        if ($this->isInTypecasts($key)) {
            return (new Transformer)->transform($this->getterTypecasts($key), $value);
        }

        return $value;
    }

    /**
     * Check if an attribute is included in typecasts.
     *
     * @param string $attribute Attribute to be checked
     * @return bool
     */
    public function isInTypecasts($attribute)
    {
        return array_key_exists($attribute, $this->typecasts);
    }

    /**
     * Return array of typecast for getter or setter typecasts.
     *
     * @param  string $set
     * @return array
     */
    public function arrayOfTypecasts($set)
    {
        $set .= 'Typecasts';
        $typecasts = [];
        foreach (array_keys($this->typecasts) as $attribute) {
            $typecasts[$attribute] = $this->{$set}($attribute);
        }

        return $typecasts;
    }

    /**
     * Get getter typecasts.
     *
     * @param  string|null $attribute
     * @return string|array
     */
    public function getterTypecasts($attribute = null)
    {
        // Polymorphic handling for array of getterTypecasts
        if (is_null($attribute)) {
            return $this->arrayOfTypecasts('getter');
        }

        // Logic code
        return head(explode(static::TYPECASTS_SET_SEPARATOR, $this->typecasts[$attribute]));
    }

    /**
     * Get setter typecasts.
     *
     * @param  string $attribute
     * @return string
     */
    public function setterTypecasts($attribute = null)
    {
        // Polymorphic handling for array of getterTypecasts
        if (is_null($attribute)) {
            return $this->arrayOfTypecasts('setter');
        }

        // Logic code
        $typecast = last(explode(static::TYPECASTS_SET_SEPARATOR, $this->typecasts[$attribute]));
        return trim($typecast) == '*' ? $this->getterTypecasts($attribute) : $typecast;
    }

    /**
     * Typecast attributes by the given typecasts.
     *
     * @param  array $typecasts
     * @param  array $attributes
     * @return array
     */
    public function typecastAttributes(array $typecasts, array $attributes = array())
    {
        $attributes = $attributes ?: $this->attributes;

        foreach (array_keys($attributes) as $attribute) {
            if (!array_key_exists($attribute, $typecasts)) {
                continue;
            }

            $typecast = $typecasts[$attribute];
            $rules    = explode(static::TYPECASTS_SEPARATOR, $typecast);

            foreach ($rules as $rule) {
                $attributes[$attribute] = (new Transformer)->transform($rule, $this->attributes[$attribute]);
            }
        }

        return $attributes;
    }

    /**
     * Override. Typecast and fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        if (!empty($attributes) && !empty($this->typecasts)) {
            $attributes = $this->typecastAttributes($this->setterTypecasts(), $attributes);
        }

        parent::fill($attributes);

        return $this;
    }

    /**
     * Overrides. Typecast attributes and convert the model instance to an array.
     *
     * @param array $attributes Selected model attributes to be returned. Optional
     * @return array
     */
    public function toArray(array $attributes = null, array $except = null)
    {
        if (method_exists($this, 'removeAppends')) {
            $this->removeAppends();
        }

        $array = parent::toArray();

        if (!empty($except)) {
            foreach ($except as $exceptAttribute) {
                unset($array[$exceptAttribute]);
            }
        }

        if (!empty($attributes)) {
            $array = array_only($array, $attributes);
        }

        if (!empty($this->typecasts)) {
            $array = $this->typecastAttributes($this->getterTypecasts(), $array);
        }
        
        return $array;
    }

    /**
     * Set typecasts property.
     *
     * @param  array $typecasts
     * @param  bool  $overwrite = false
     * @return $this
     */
    public function setTypecasts(array $typecasts, $overwrite = false)
    {
        if ($overwrite) {
            $this->typecasts = $typecasts;
        } else {
            $this->typecasts = array_merge($this->typecasts, $typecasts);
        }

        return $this;
    }

    /**
     * Override Eloquent::getAttributes(), enabling to pass a selected attributes.
     *
     * @param  array $attributes Array of attributes to be returned
     * @return array
     */
    public function getAttributes(array $attributes = null)
    {
        if ($attributes === null) {
            return parent::getAttributes();
        }

        $modelAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->$attribute !== null) {
                $modelAttributes[$attribute] = $this->$attribute;
            }
        }

        return $modelAttributes;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Services\IlluminateExtensions\Collection
     */
    public function newCollection(array $models = array())
    {
        return new \SedpMis\Lib\IlluminateExtensions\Collection($models);
    }

    /**
     * Makes a copy of the model.
     *
     * @param  bool $fullCopy If true, this will copy also the relations
     * @return static
     */
    public function copy($fullCopy = false)
    {
        $copy = clone $this;

        if (!$fullCopy) {
            $copy->setRelations([]);
        }

        return $copy;
    }

    /**
     * Return random model.
     *
     * @return static
     */
    public static function randomOne()
    {
        $ids   = static::lists('id');
        $index = array_rand($ids);

        return static::find($ids[$index]);
    }

    /**
     * Return the full classpath of the model.
     *
     * @return string
     */
    public static function getClass()
    {
        return static::class;
    }

    /**
     * Get the carbon instance of a datetime attribute.
     *
     * @param  string $attribute
     * @return \Carbon\Carbon
     */
    public function carbon($attribute)
    {
        return new Carbon($this->getAttribute($attribute));
    }

    /**
     * Return the carbon instance of a datetime attribute if not null, else return null.
     *
     * @param  string $attribute
     * @return null|\Carbon\Carbon
     */
    public function carbonOrNull($attribute)
    {
        return is_null($this->getAttribute($attribute)) ? null : $this->carbon($attribute);
    }

    /**
     * Add attributes to appends.
     *
     * @param  array $appends
     * @return $this
     */
    public function addAppends(array $appends)
    {
        $this->appends = array_merge($this->appends, $appends);

        return $this;
    }

    /**
     * Return the attribute from $attributes property, not being mutated or transformed.
     *
     * @param  string $attribute
     * @return mixed
     */
    public function rawAttribute($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
    }

    /**
     * Clean attributes, useful before saving. Remove attributes which are not in $fillable.
     *
     * @return $this
     */
    public function clean()
    {
        $fillable            = $this->getFillable();
        if (!in_array($pk = $this->getKeyName(), $fillable)) {
            $fillable[] = $pk;
        }
        $attributesWithValue = array_keys($this->attributes);
        $unFillable          = array_diff($attributesWithValue, $fillable);

        $this->trashedAttributes = array_only($this->attributes, $unFillable);
        $this->attributes        = array_only($this->attributes, $fillable);

        return $this;
    }

    /**
     * Return the trashedAttributes.
     *
     * @return array
     */
    public function getTrashedAttributes()
    {
        return $this->trashedAttributes;
    }

    /**
     * Remove a relation from the model.
     *
     * @param  string $relation
     * @return $this
     */
    public function removeRelation($relation)
    {
        unset($this->relations[$relation]);

        return $this;
    }

    /**
     * Identify if relation exist in the model.
     *
     * @param  string $relation
     * @return bool
     */
    public function relationExists($relation)
    {
        return array_key_exists($relation, $this->relations) ? true : false;
    }

    /**
     * Override. Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|static
     *
     * @throws \ModelNotFoundException
     */
    public static function findOrFail($id, $columns = array('*'))
    {
        if (!is_null($model = static::find($id, $columns))) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(static::class, $id);
    }

    /**
     * Override. Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }
}
