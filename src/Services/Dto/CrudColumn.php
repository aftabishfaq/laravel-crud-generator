<?php

namespace Aftab\LaravelCrud\Services\Dto;

class CrudColumn
{
    public string $name;
    public string $type;
    public ?string $label;
    public ?string $inputType;
    public bool $isNullable;
    public bool $isUnique;
    public mixed $defaultValue;
    public ?array $validationRules; // string or array normalized to array of strings
    public ?string $foreignTable;
    public ?string $foreignColumn;
    public ?string $onDelete;
    public ?array $options; // for enums/static options
    public ?string $optionsSource; // e.g., 'table:groups'

    public function __construct(
        string $name,
        string $type,
        ?string $label = null,
        ?string $inputType = null,
        bool $isNullable = false,
        bool $isUnique = false,
        mixed $defaultValue = null,
        ?array $validationRules = null,
        ?string $foreignTable = null,
        ?string $foreignColumn = null,
        ?string $onDelete = null,
        ?array $options = null,
        ?string $optionsSource = null,
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->label = $label;
        $this->inputType = $inputType;
        $this->isNullable = $isNullable;
        $this->isUnique = $isUnique;
        $this->defaultValue = $defaultValue;
        $this->validationRules = $validationRules;
        $this->foreignTable = $foreignTable;
        $this->foreignColumn = $foreignColumn;
        $this->onDelete = $onDelete;
        $this->options = $options;
        $this->optionsSource = $optionsSource;
    }
}


