<?php

namespace Aftab\LaravelCrud\Services\Dto;

class CrudTable
{
    public string $name;
    public ?string $displayName;
    /** @var array<int, CrudColumn> */
    public array $columns;
    /** @var array<int, CrudRelationship> */
    public array $relationships;
    public bool $timestamps;
    public bool $softDeletes;

    // routing/controller options for this table
    public ?string $routePrefix;
    /** @var array<int, string>|null */
    public ?array $middleware;
    public ?string $controller;

    /**
     * @param array<int, CrudColumn> $columns
     * @param array<int, CrudRelationship> $relationships
     * @param array<int, string>|null $middleware
     */
    public function __construct(
        string $name,
        ?string $displayName,
        array $columns,
        array $relationships = [],
        bool $timestamps = true,
        bool $softDeletes = false,
        ?string $routePrefix = null,
        ?array $middleware = null,
        ?string $controller = null,
    ) {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->columns = $columns;
        $this->relationships = $relationships;
        $this->timestamps = $timestamps;
        $this->softDeletes = $softDeletes;
        $this->routePrefix = $routePrefix;
        $this->middleware = $middleware;
        $this->controller = $controller;
    }
}


