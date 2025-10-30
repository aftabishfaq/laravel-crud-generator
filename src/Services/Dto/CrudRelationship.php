<?php

namespace Aftab\LaravelCrud\Services\Dto;

class CrudRelationship
{
    public string $type; // belongsTo, hasOne, hasMany, belongsToMany
    public ?string $model;
    public ?string $foreignKey;

    public function __construct(string $type, ?string $model = null, ?string $foreignKey = null)
    {
        $this->type = $type;
        $this->model = $model;
        $this->foreignKey = $foreignKey;
    }
}


